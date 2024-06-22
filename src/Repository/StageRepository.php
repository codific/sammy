<?php

/**
 * This is automatically generated file using the Codific Prototizer
 * PHP version 8
 * @category PHP
 * @author   CODIFIC <info@codific.com>
 * @see     http://codific.com
 */

declare(strict_types=1);

namespace App\Repository;

// #BlockStart number=212 id=_19_0_3_40d01a2_1646749246273_943490_4876_#_0

use App\Entity\AssessmentAnswer;
use App\Entity\AssessmentStream;
use App\Entity\Assignment;
use App\Entity\Remark;
use App\Entity\Stage;
use App\Interface\EntityInterface;
use App\Repository\Abstraction\AbstractRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Stage|null find($id, $lockMode = null, $lockVersion = null)
 * @method Stage|null findOneBy(array $criteria, array $orderBy = null)
 * @method Stage[]    findAll()
 * @method Stage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StageRepository extends AbstractRepository
{
    /**
     * StageRepository constructor.
     *
     * @return void
     */
    public function __construct(ManagerRegistry $registry, string $entityClassName = Stage::class)
    {
        parent::__construct($registry, $entityClassName);
    }

    /**
     * Duplicate the object and save the duplicate.
     *
     * @param Stage $stage The object to be duplicated
     */
    public function duplicate(Stage $stage): Stage
    {
        $clone = $stage->getCopy();
        $this->getEntityManager()->persist($clone);
        $this->getEntityManager()->flush();

        return $clone;
    }

    /**
     * @return Stage[]
     */
    public function getStreamCompletedStages(AssessmentStream $assessmentStream, ?int $maxResults = null): array
    {
        if ($maxResults !== null && $maxResults < 0) {
            $maxResults = null;
        }
        $stream = $assessmentStream->getStream();
        $assessment = $assessmentStream->getAssessment();

        return $this->createQueryBuilder('stage')
            ->join('stage.assessmentStream', 'assessmentStream')
            ->where('assessmentStream.stream = :stream')->setParameter('stream', $stream)
            ->andWhere('assessmentStream.assessment = :assessment')->setParameter('assessment', $assessment)
            ->andWhere('stage.completedAt IS NOT NULL')
            ->orderBy('stage.completedAt', 'DESC')
            ->setMaxResults($maxResults)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param AssessmentStream[] $assessmentStreams
     *
     * @return Stage[]
     */
    public function findByAssessmentStreams(array $assessmentStreams, string $stageType): array
    {
        return $this->createQueryBuilder('stage')
            ->join('stage.assessmentStream', 'assessmentStream')
            ->where('stage.assessmentStream IN (:assessmentStreams)')
            ->andWhere('stage INSTANCE OF :type')
            ->setParameter('assessmentStreams', $assessmentStreams)
            ->setParameter('type', $this->getEntityManager()->getClassMetadata($stageType))
            ->orderBy('stage.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function trash(EntityInterface $stage): void
    {
        parent::trash($stage);
    }


    public function deepRestore(EntityInterface $stage): void
    {
        parent::restore($stage);

        $isDeletedFilterEnabled = false;
        if ($this->getEntityManager()->getFilters()->isEnabled('deleted_entity')) {
            $this->getEntityManager()->getFilters()->disable('deleted_entity');
            $isDeletedFilterEnabled = true;
        }

        $assessmentAnswerRepository = $this->getEntityManager()->getRepository(AssessmentAnswer::class);
        $remarkRepository = $this->getEntityManager()->getRepository(Remark::class);
        $assignmentRepository = $this->getEntityManager()->getRepository(Assignment::class);

        $assessmentAnswers = $assessmentAnswerRepository->findBy(['stage' => $stage]);
        $remarks = $remarkRepository->findBy(['stage' => $stage]);
        $assignments = $assignmentRepository->findBy(['stage' => $stage]);

        foreach ($assessmentAnswers as $assessmentAnswer) {
            $assessmentAnswerRepository->restore($assessmentAnswer);
        }
        foreach ($remarks as $remark) {
            $remarkRepository->restore($remark);
        }
        foreach ($assignments as $assignment) {
            $assignmentRepository->restore($assignment);
        }
        if ($isDeletedFilterEnabled) {
            $this->getEntityManager()->getFilters()->enable('deleted_entity');
        }
    }
// #BlockEnd number=212

}
