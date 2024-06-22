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

// #BlockStart number=138 id=_19_0_3_40d01a2_1635865758967_363555_6779_#_0

use App\Entity\AssessmentAnswer;
use App\Entity\Evaluation;
use App\Entity\Improvement;
use App\Entity\Stage;
use App\Entity\User;
use App\Enum\AssessmentAnswerType;
use App\Repository\Abstraction\AbstractRepository;
use App\Service\AssessmentAnswersService;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AssessmentAnswer|null find($id, $lockMode = null, $lockVersion = null)
 * @method AssessmentAnswer|null findOneBy(array $criteria, array $orderBy = null)
 * @method AssessmentAnswer[]    findAll()
 * @method AssessmentAnswer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AssessmentAnswerRepository extends AbstractRepository
{
    /**
     * AssessmentAnswerRepository constructor.
     *
     * @return void
     */
    public function __construct(ManagerRegistry $registry, string $entityClassName = AssessmentAnswer::class)
    {
        parent::__construct($registry, $entityClassName);
    }

    /**
     * Duplicate the object and save the duplicate.
     *
     * @param AssessmentAnswer $assessmentAnswer The object to be duplicated
     */
    public function duplicate(AssessmentAnswer $assessmentAnswer): AssessmentAnswer
    {
        $clone = $assessmentAnswer->getCopy();
        $this->getEntityManager()->persist($clone);
        $this->getEntityManager()->flush();

        return $clone;
    }

    /**
     * @return AssessmentAnswer[]
     */
    public function findByStageOptimized(Evaluation|Improvement $stage, ?AssessmentAnswerType $type = null): array
    {
        $type ??= AssessmentAnswersService::getAnswerTypeByStageClass($stage::class);

        return $this->getOptimizedAnswerQueryBuilder()
            ->where('stage = :stage')->setParameter('stage', $stage)
            ->andWhere('assessmentAnswer.type = :type')->setParameter('type', $type)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param Stage[] $stages
     *
     * @return AssessmentAnswer[]
     */
    public function findByStages(array $stages): array
    {
        return $this->getOptimizedAnswerQueryBuilder()
            ->where('stage in (:stages)')->setParameter('stages', $stages)
            ->orderBy('businessFunction.order', 'ASC')
            ->addOrderBy('practice.order', 'ASC')
            ->addOrderBy('stream.order', 'ASC')
            ->getQuery()
            ->getResult();
    }

//    /**
//     * @return AssessmentAnswer[]
//     */
//    public function findByAssessment(Assessment $assessment, AssessmentAnswerType $type): array
//    {
//        return $this->getOptimizedAnswerQueryBuilder()
//            ->join("assessmentStream.assessment", "assessment")
//            ->addSelect("assessment")
//            ->where("assessmentStream.assessment = :assessment")->setParameter("assessment", $assessment)
//            ->andWhere("assessmentAnswer.type = :type")->setParameter("type", $type)
//            ->andWhere("assessmentStream.status != :archived")->setParameter('archived', \App\Enum\AssessmentStatus::ARCHIVED)
//            ->orderBy('businessFunction.order', "ASC")
//            ->addOrderBy('practice.order', "ASC")
//            ->addOrderBy('stream.order', "ASC")
//            ->getQuery()
//            ->getResult();
//    }

    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('assessment_answer')
            ->select('assessment_answer')
            ->where('assessment_answer.user = :user')->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    private function getOptimizedAnswerQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('assessmentAnswer')
            ->join('assessmentAnswer.stage', 'stage')
            ->addSelect('stage')
            ->join('stage.assessmentStream', 'assessmentStream')
            ->addSelect('assessmentStream')
            ->join('assessmentStream.stream', 'stream')
            ->addSelect('stream')
            ->join('stream.practice', 'practice')
            ->addSelect('practice')
            ->join('practice.businessFunction', 'businessFunction')
            ->addSelect('businessFunction');
    }

    public function findLatestAnswersOfUsers(array $users)
    {
        return $this->createQueryBuilder('assessmentAnswer', "assessmentAnswer.user")
            ->addSelect("MAX(assessmentAnswer.updatedAt)")
            ->where("assessmentAnswer.user IN (:users)")
            ->groupBy("assessmentAnswer.user")
            ->setParameter("users", $users)
            ->getQuery()
            ->getResult();
    }


    public function findAnswersByStageIndexedByStages(array $stages): array
    {
        return $this->createQueryBuilder('assessmentAnswer', "assessmentAnswer.stage")
            ->where("assessmentAnswer.stage IN (:stages)")
            ->setParameter("stages", $stages)
            ->getQuery()
            ->getResult();
    }

// #BlockEnd number=138

}
