<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\DocumentationDTO;
use App\Entity\AssessmentStream;
use App\Entity\Assignment;
use App\Entity\Evaluation;
use App\Entity\Improvement;
use App\Entity\MaturityLevel;
use App\Entity\MaturityLevelRemark;
use App\Entity\Remark;
use App\Entity\User;
use App\Entity\Validation;
use App\Enum\Custom\RemarkType;
use App\Enum\ImprovementStatus;
use App\Enum\ValidationStatus;
use App\Event\Application\Post\PostRemarkSaveEvent;
use App\Event\Application\Pre\PreRemarkSaveEvent;
use App\Exception\InsufficientAttachmentRemarkParameters;
use App\Exception\InsufficientPermissionsToSaveRemarkException;
use App\Exception\QueueNotOnlineException;
use App\Repository\MaturityLevelRemarkRepository;
use App\Repository\RemarkRepository;
use App\Repository\StageRepository;
use App\ViewStructures\Remark\RemarkObject;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class RemarkService
{
    private const MAX_STAGES_TO_CHECK_FOR_REMARK = 100;

    public function __construct(
        private readonly Filesystem $fileSystem,
        private readonly EntityManagerInterface $entityManager,
        private readonly ParameterBagInterface $parameterBag,
        private readonly MetamodelService $metamodelService,
        private readonly StageService $stageService,
        private readonly AssignmentService $assignmentService,
        private readonly UploadService $uploadService,
        private readonly RemarkRepository $remarkRepository,
        private readonly StageRepository $stageRepository,
        private readonly MaturityLevelRemarkRepository $maturityLevelRemarkRepository,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function deleteRemark(Remark $remark): void
    {
        if ($remark->getFile() !== null) {
            $filePath = $this->getAttachmentFolder($remark->getStage()->getAssessmentStream()).'/'.$remark->getFile();
            $this->fileSystem->remove($filePath);
        }
        $this->remarkRepository->trash($remark);
    }

    /**
     * @throws InsufficientAttachmentRemarkParameters
     * @throws InsufficientPermissionsToSaveRemarkException
     * @throws QueueNotOnlineException
     */
    public function saveDocumentationRemark(DocumentationDTO $documentationDTO, AssessmentStream $assessmentStream, User $user): void
    {
        if (!$this->canSaveDocumentationRemark($documentationDTO)) {
            throw new InsufficientAttachmentRemarkParameters();
        }
        $remark = $documentationDTO->getRemarkId() !== null ? $this->remarkRepository->find($documentationDTO->getRemarkId()) : null;
        if ($remark !== null && $remark->getUser() !== $user) {
            throw new InsufficientPermissionsToSaveRemarkException();
        }

        if ($remark?->getStage()->getAssessmentStream()->getStatus() === \App\Enum\AssessmentStatus::ARCHIVED) {
            throw new InsufficientPermissionsToSaveRemarkException();
        }

        $this->eventDispatcher->dispatch(new PreRemarkSaveEvent($assessmentStream));

        if ($assessmentStream->getCurrentStage() === null) {
            $evaluation = new Evaluation();
            $this->assignmentService->addAssignment(new Assignment(), $evaluation, $user, $user);
            $this->stageService->addNewStage($assessmentStream, $evaluation, $user);
        }

        $existingMaturityLevelRemarks = [];
        if ($remark === null) {
            $remark = new Remark();
            $remark->setStage($assessmentStream->getCurrentStage());
            $remark->setUser($user);
        } else {
            $existingMaturityLevelRemarks = $this->maturityLevelRemarkRepository->findByRemarkIndexedByMaturityLevel($remark);
        }
        $this->deletedUnusedMaturityLevelRemarks($existingMaturityLevelRemarks, $documentationDTO->getMaturityLevel());
        $this->addNewMaturityLevelRemarks($remark, $existingMaturityLevelRemarks, $documentationDTO->getMaturityLevel());

        $remark->setText($documentationDTO->getText());

        $attachmentTitle = $documentationDTO->getAttachmentTitle();
        if ($attachmentTitle === null && $documentationDTO->getAttachmentFile() !== null) {
            $attachmentTitle = $documentationDTO->getAttachmentFile()->getClientOriginalName();
        }

        if ($documentationDTO->getAttachmentFile() !== null) {
            $fileName = $this->saveAttachment($assessmentStream, $documentationDTO->getAttachmentFile());
            $remark->setFile($fileName);
        }

        if ($attachmentTitle !== null && $attachmentTitle !== '') {
            $remark->setTitle($attachmentTitle);
        }

        $this->entityManager->persist($remark);
        $this->entityManager->flush();

        $this->eventDispatcher->dispatch(new PostRemarkSaveEvent($assessmentStream, $remark));
    }

    /**
     * @return RemarkObject[]
     */
    public function findAllCurrentAndOldRemarks(AssessmentStream $assessmentStream): array
    {
        $stages = $this->getStagesToFetchRemarksFrom($assessmentStream);

        $result = [];
        foreach ($this->getStagesToFetchRemarksFrom($assessmentStream) as $stage) {
            if ($stage instanceof Validation
                && !in_array($stage->getStatus(), [ValidationStatus::NEW, ValidationStatus::RETRACTED], true)
                && $stage->getComment() !== null && $stage->getComment() !== '') {
                $newRemark = new RemarkObject(
                    RemarkType::VALIDATION,
                    $stage->getId(),
                    $stage->getComment(),
                    $stage->getCompletedAt() ?? $stage->getUpdatedAt(),
                    $stage->getSubmittedBy(),
                    null,
                    null,
                    $stage->getAssessmentStream()->getStatus()
                );

                $result[] = $newRemark;
            } elseif ($stage instanceof Improvement
                && !in_array($stage->getStatus(), [ImprovementStatus::NEW, ImprovementStatus::DRAFT, ImprovementStatus::WONT_IMPROVE], true)
                && $stage->getPlan() !== null && $stage->getPlan() !== '') {
                $newRemark = new RemarkObject(
                    RemarkType::IMPROVEMENT,
                    $stage->getId(),
                    $stage->getPlan(),
                    $stage->getCompletedAt() ?? $stage->getUpdatedAt(),
                    $stage->getSubmittedBy(),
                    null,
                    null,
                    $stage->getAssessmentStream()->getStatus()
                );

                $result[] = $newRemark;
            }
        }

        foreach ($this->remarkRepository->findAllForMultipleStages($stages) as $remark) {
            $newRemark = new RemarkObject(
                RemarkType::DOCUMENTATION,
                $remark->getId(),
                $remark->getText(),
                $remark->getCreatedAt(),
                $remark->getUser(),
                $remark->getFile(),
                $remark->getTitle(),
                $remark->getStage()->getAssessmentStream()->getStatus()
            );

            $result[] = $newRemark;
        }

        uasort($result, fn(RemarkObject $item1, RemarkObject $item2) => $item2->date <=> $item1->date);

        return $result;
    }

    public function getAttachmentFilePath(Remark $remark): ?string
    {
        $path = $this->getAttachmentFolder($remark->getStage()->getAssessmentStream());

        return $this->fileSystem->readlink("{$path}/{$remark->getFile()}", true);
    }

    public function deleteValidationRemark(Validation $validation): void
    {
        $validation->setComment(null);
        $this->entityManager->flush();
    }

    /**
     * @throws InsufficientPermissionsToSaveRemarkException
     */
    public function saveValidationRemark(DocumentationDTO $documentationDTO, AssessmentStream $assessmentStream, User $user): void
    {
        $validationStage = $this->stageRepository->find($documentationDTO->getRemarkId());
        if (!$validationStage instanceof Validation || $assessmentStream->getStatus() !== \App\Enum\AssessmentStatus::VALIDATED
            || $validationStage->getSubmittedBy() !== $user) {
            throw new InsufficientPermissionsToSaveRemarkException();
        }

        $validationStage->setComment($documentationDTO->getText());
        $this->entityManager->flush();
    }

    public function findAllCurrentAndOldRemarkLevels(AssessmentStream $assessmentStream): array
    {
        $maturityLevelRemarks = $this->maturityLevelRemarkRepository->findAllForMultipleStages($this->getStagesToFetchRemarksFrom($assessmentStream));
        $result = [];
        foreach ($maturityLevelRemarks as $maturityLevelRemark) {
            $result[$maturityLevelRemark->getRemark()->getId()][] = $maturityLevelRemark->getMaturityLevel()->getLevel();
        }

        return $result;
    }

    private function addNewMaturityLevelRemarks(Remark $remark, array $existingMaturityLevelRemarks, array $chosenMaturityLevels): void
    {
        $allMaturityLevels = $this->metamodelService->getMaturityLevels($remark->getStage()?->getAssessmentStream()?->getAssessment()?->getProject()?->getMetamodel());
        foreach ($chosenMaturityLevels as $chosenMaturityLevel) {
            $maturityLevelEntity = $allMaturityLevels[$chosenMaturityLevel];
            $remarkIsConnectedToThisLevel = array_key_exists($maturityLevelEntity->getId(), $existingMaturityLevelRemarks);

            if (!$remarkIsConnectedToThisLevel) {
                $remarkMaturityLevel = new MaturityLevelRemark();
                $remarkMaturityLevel->setRemark($remark);
                $maturityLevel = $this->entityManager->getReference(MaturityLevel::class, $allMaturityLevels[$chosenMaturityLevel]->getId());
                $remarkMaturityLevel->setMaturityLevel($maturityLevel);
                $this->entityManager->persist($remarkMaturityLevel);
            }
        }
    }

    private function getStagesToFetchRemarksFrom(AssessmentStream $assessmentStream): array
    {
        $stages = $this->stageRepository->getStreamCompletedStages($assessmentStream, self::MAX_STAGES_TO_CHECK_FOR_REMARK);
        $stages[] = $assessmentStream->getCurrentStage();

        return $stages;
    }

    private function canSaveDocumentationRemark(DocumentationDTO $documentationDTO): bool
    {
        $haveTitleWithoutFile = $documentationDTO->getAttachmentFile() === null && $documentationDTO->getAttachmentTitle() !== null;
        $emptyFormSubmitted = $documentationDTO->getText() === null && $documentationDTO->getAttachmentFile() === null
            && $documentationDTO->getRemarkId() === null;
        $newRemarkSubmitted = $documentationDTO->getRemarkId() === null;
        if ($newRemarkSubmitted && ($emptyFormSubmitted || $haveTitleWithoutFile)) {
            return false;
        }

        return true;
    }

    private function saveAttachment(AssessmentStream $assessmentStream, UploadedFile $file): string
    {
        $path = $this->getAttachmentFolder($assessmentStream);
        if (!$this->fileSystem->exists($path)) {
            $this->fileSystem->mkdir($path);
        }

        return $this->uploadService->upload($file, (string)$assessmentStream->getId());
    }

    /**
     * @param MaturityLevelRemark[] $existingMaturityLevelRemarks
     */
    private function deletedUnusedMaturityLevelRemarks(array $existingMaturityLevelRemarks, array $chosenMaturityLevels): void
    {
        foreach ($existingMaturityLevelRemarks as $existingMaturityLevelRemark) {
            $keepThisMaturityLevelRemark = false;
            foreach ($chosenMaturityLevels as $chosenMaturityLevel) {
                if ($existingMaturityLevelRemark->getMaturityLevel()->getLevel() === $chosenMaturityLevel) {
                    $keepThisMaturityLevelRemark = true;
                }
            }
            if (!$keepThisMaturityLevelRemark) {
                $this->maturityLevelRemarkRepository->trash($existingMaturityLevelRemark);
            }
        }
    }

    private function getAttachmentFolder(AssessmentStream $assessmentStream): string
    {
        return $this->parameterBag->get('kernel.project_dir').'/private/'.$assessmentStream->getId();
    }
}
