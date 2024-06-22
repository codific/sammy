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

// #BlockStart number=221 id=_19_0_3_40d01a2_1638283953829_657991_4947_#_0

use App\Entity\Assessment;
use App\Entity\AssessmentStream;
use App\Entity\Assignment;
use App\Entity\Evaluation;
use App\Entity\Stage;
use App\Entity\Stream;
use App\Entity\User;
use App\Entity\Validation;
use App\Enum\AssessmentStatus;
use App\Enum\ValidationStatus;
use App\Interface\EntityInterface;
use App\Repository\Abstraction\AbstractRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AssessmentStream|null find($id, $lockMode = null, $lockVersion = null)
 * @method AssessmentStream|null findOneBy(array $criteria, array $orderBy = null)
 * @method AssessmentStream[]    findAll()
 * @method AssessmentStream[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AssessmentStreamRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry, string $entityClassName = AssessmentStream::class)
    {
        parent::__construct($registry, $entityClassName);
    }

    public function duplicate(AssessmentStream $assessmentStream): AssessmentStream
    {
        $clone = $assessmentStream->getCopy();
        $this->getEntityManager()->persist($clone);
        $this->getEntityManager()->flush();

        return $clone;
    }

    /**
     * @return AssessmentStream[]
     */
    public function findAllStreamsForAssessment(Assessment $assessment, ?string $indexBy = null): array
    {
        return $this->createQueryBuilder('assessmentStream', $indexBy)
            ->join('assessmentStream.stream', 'stream')
            ->leftJoin('assessmentStream.assessmentStreamStages', 'stage')
            ->addSelect('stage')
            ->leftJoin('stage.stageAssessmentAnswers', 'assessmentAnswer')
            ->addSelect('assessmentAnswer')
            ->leftJoin('assessmentAnswer.answer', 'answer')
            ->addSelect('answer')
            ->where('assessmentStream.assessment = :targetedAssessment')
            ->setParameter('targetedAssessment', $assessment)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return AssessmentStream[]
     */
    public function findValidatedByAssessment(Assessment $assessment): array
    {
        return $this->createQueryBuilder('assessmentStream')
            ->join('assessmentStream.assessmentStreamStages', 'assessment_stream_stages')
            ->addSelect('assessment_stream_stages')
            ->where('assessmentStream.assessment = :assessment')->setParameter('assessment', $assessment)
            ->andWhere('assessmentStream.status >= :accepted')->setParameter('accepted', \App\Enum\AssessmentStatus::VALIDATED)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return AssessmentStream[]
     */
    public function findLatestValidatedByAssessmentAndDate(Assessment $assessment, \DateTime $dateTime): array
    {
        $dateTimeClone = clone $dateTime;

        $mainQuery = $this->getEntityManager()->createQueryBuilder()
            ->select('max(assessment_stream.id)')
            ->from(AssessmentStream::class, 'assessment_stream')
            ->innerJoin(Stage::class, 'stage', Query\Expr\Join::WITH, 'stage.assessmentStream = assessment_stream')
            ->innerJoin(Validation::class, 'validation', Query\Expr\Join::WITH, 'validation.id = stage.id')
            ->where('assessment_stream.assessment = :myAssessment')
            ->andWhere('assessment_stream.status >= :validated')
            ->andWhere('validation.status = :accepted OR validation.status = :autoAccepted')
            ->andWhere('validation.completedAt < :date')
            ->groupBy('assessment_stream.stream');

        $expr = $this->getEntityManager()->getExpressionBuilder();
        $wrapperQuery = $this->createQueryBuilder('assessmentStream')
            ->where(
                $expr->in(
                    'assessmentStream.id',
                    $mainQuery->getDQL()
                )
            )
            ->setParameter('myAssessment', $assessment)
            ->setParameter('validated', \App\Enum\AssessmentStatus::VALIDATED)
            ->setParameter('accepted', \App\Enum\ValidationStatus::ACCEPTED)
            ->setParameter('autoAccepted', \App\Enum\ValidationStatus::AUTO_ACCEPTED)
            ->setParameter('date', $dateTimeClone->modify('+1 day'));

        return $wrapperQuery->getQuery()->getResult();
    }

    /**
     * @return AssessmentStream[]
     */
    public function findLatestByAssessmentAndDate(Assessment $assessment, \DateTime $dateTime): array
    {
        $dateTimeClone = clone $dateTime;

        $mainQuery = $this->getEntityManager()->createQueryBuilder()
            ->select('max(assessment_stream.id)')
            ->from(AssessmentStream::class, 'assessment_stream')
            ->innerJoin(Stage::class, 'stage', Query\Expr\Join::WITH, 'stage.assessmentStream = assessment_stream')
            ->innerJoin(Evaluation::class, 'evaluation', Query\Expr\Join::WITH, 'evaluation.id = stage.id')
            ->where('assessment_stream.assessment = :myAssessment')
            ->andWhere('evaluation.createdAt < :date')
            ->groupBy('assessment_stream.stream');

        $expr = $this->getEntityManager()->getExpressionBuilder();
        $wrapperQuery = $this->createQueryBuilder('assessmentStream')
            ->where(
                $expr->in(
                    'assessmentStream.id',
                    $mainQuery->getDQL()
                )
            )
            ->setParameter('myAssessment', $assessment)
            ->setParameter('date', $dateTimeClone->modify('+1 day'));

        return $wrapperQuery->getQuery()->getResult();
    }

    /**
     * @return AssessmentStream[]
     */
    public function findLatestValidatedByDateAndAssessments(\DateTime $dateTime, Assessment ...$assessments): array
    {
        $dateTimeClone = clone $dateTime;

        $mainQuery = $this->getEntityManager()->createQueryBuilder()
            ->select('max(assessment_stream.id)')
            ->from(AssessmentStream::class, 'assessment_stream')
            ->innerJoin(Stage::class, 'stage', Query\Expr\Join::WITH, 'stage.assessmentStream = assessment_stream')
            ->innerJoin(Validation::class, 'validation', Query\Expr\Join::WITH, 'validation.id = stage.id')
            ->where('assessment_stream.assessment IN (:myAssessment)')
            ->andWhere('assessment_stream.status >= :validated')
            ->andWhere('validation.status = :accepted OR validation.status = :autoAccepted')
            ->andWhere('stage.completedAt < :date')
            ->groupBy('assessment_stream.assessment')
            ->addGroupBy('assessment_stream.stream');

        $expr = $this->getEntityManager()->getExpressionBuilder();
        $wrapperQuery = $this->createQueryBuilder('assessmentStream')
            ->where(
                $expr->in(
                    'assessmentStream.id',
                    $mainQuery->getDQL()
                )
            )
            ->setParameter('myAssessment', $assessments)
            ->setParameter('validated', AssessmentStatus::VALIDATED)
            ->setParameter('accepted', ValidationStatus::ACCEPTED)
            ->setParameter('autoAccepted', ValidationStatus::AUTO_ACCEPTED)
            ->setParameter('date', $dateTimeClone->modify('+1 day'))
            ->orderBy('assessmentStream.assessment');

        return $wrapperQuery->getQuery()->getResult();
    }

    /**
     * @return AssessmentStream[]
     */
    public function findLatestByAssessments(Assessment ...$assessments): array
    {
        $mainQuery = $this->getEntityManager()->createQueryBuilder()
            ->select('max(assessment_stream.id)')
            ->from(AssessmentStream::class, 'assessment_stream')
            ->where('assessment_stream.assessment IN (:assessments)')
            ->groupBy('assessment_stream.assessment')
            ->addGroupBy('assessment_stream.stream');

        $expr = $this->getEntityManager()->getExpressionBuilder();
        $wrapperQuery = $this->createQueryBuilder('assessmentStream')
            ->where(
                $expr->in(
                    'assessmentStream.id',
                    $mainQuery->getDQL()
                )
            )
            ->setParameter('assessments', $assessments)
            ->orderBy('assessmentStream.assessment');

        return $wrapperQuery->getQuery()->getResult();
    }

    /**
     * @return AssessmentStream[]
     */
    public function findActiveByAssessment(Assessment $assessment): array
    {
        return $this->createQueryBuilder('assessmentStream')
            ->leftJoin('assessmentStream.assessmentStreamStages', 'stage')
            ->addSelect('stage')
            ->where('assessmentStream.assessment = :assessment')->setParameter('assessment', $assessment)
            ->andWhere('assessmentStream.status != :archived')->setParameter('archived', \App\Enum\AssessmentStatus::ARCHIVED)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return AssessmentStream[]
     */
    public function findUserAssignedStreamsByAssessments(Assessment $assessment, User $user): array
    {
        return $this->createQueryBuilder('assessmentStream')
            ->innerJoin(Stage::class, 'stage', Query\Expr\Join::WITH, 'stage.assessmentStream = assessmentStream')
            ->innerJoin(Assignment::class, 'assignment', Query\Expr\Join::WITH, 'assignment.stage = stage')
            ->where('assessmentStream.assessment = :assessment')->setParameter('assessment', $assessment)
            ->andWhere('assessmentStream.status != :archived')->setParameter('archived', AssessmentStatus::ARCHIVED)
            ->andWhere('assignment.user = :user')->setParameter('user', $user)
            ->andWhere('stage.completedAt IS NULL')
            ->getQuery()
            ->getResult();
    }

    public function findOneActiveByAssessmentAndStream(Assessment $assessment, Stream $stream): ?AssessmentStream
    {
        return $this->createQueryBuilder('assessmentStream')
            ->where('assessmentStream.stream = :stream')->setParameter('stream', $stream)
            ->andWhere('assessmentStream.assessment = :assessment')->setParameter('assessment', $assessment)
            ->andWhere('assessmentStream.status <> :archived')->setParameter('archived', AssessmentStatus::ARCHIVED)
            ->setMaxResults(1)->getQuery()->getOneOrNullResult();
    }

    public function trash(EntityInterface $assessmentStream): void
    {
        parent::trash($assessmentStream);
        $stageRepository = $this->getEntityManager()->getRepository(Stage::class);
        $stages = $stageRepository->findBy(['assessmentStream' => $assessmentStream]);
        foreach ($stages as $stage) {
            /** @var StageRepository $stageRepository */
            $stageRepository->trash($stage);
        }
    }

//    public function deepRestore(EntityInterface $assessmentStream): void
//    {
//        parent::restore($assessmentStream);
//
//        $isDeletedFilterEnabled = false;
//        if ($this->getEntityManager()->getFilters()->isEnabled('deleted_entity')) {
//            $this->getEntityManager()->getFilters()->disable('deleted_entity');
//            $isDeletedFilterEnabled = true;
//        }
//
//        $stageRepository = $this->getEntityManager()->getRepository(Stage::class);
//        $stages = $stageRepository->findBy(['assessmentStream' => $assessmentStream]);
//        foreach ($stages as $stage) {
//            $stageRepository->deepRestore($stage);
//        }
//        if ($isDeletedFilterEnabled) {
//            $this->getEntityManager()->getFilters()->enable('deleted_entity');
//        }
//    }

// #BlockEnd number=221

}
