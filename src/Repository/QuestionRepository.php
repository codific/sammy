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

// #BlockStart number=102 id=_19_0_3_40d01a2_1635864957642_388856_6397_#_0

use App\Entity\Metamodel;
use App\Entity\Question;
use App\Repository\Abstraction\AbstractRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Question|null find($id, $lockMode = null, $lockVersion = null)
 * @method Question|null findOneBy(array $criteria, array $orderBy = null)
 * @method Question[]    findAll()
 * @method Question[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class QuestionRepository extends AbstractRepository
{
    /**
     * QuestionRepository constructor.
     *
     * @return void
     */
    public function __construct(ManagerRegistry $registry, string $entityClassName = Question::class)
    {
        parent::__construct($registry, $entityClassName);
    }

    /**
     * Duplicate the object and save the duplicate.
     *
     * @param Question $question The object to be duplicated
     */
    public function duplicate(Question $question): Question
    {
        $clone = $question->getCopy();
        $this->getEntityManager()->persist($clone);
        $this->getEntityManager()->flush();

        return $clone;
    }

    /**
     * @return Question[]
     */
    public function findByMetamodel(Metamodel $metamodel): array
    {
        return $this->createQueryBuilder('question')
            ->join('question.activity', '_activity')
            ->join('_activity.stream', '_stream')
            ->join('_stream.practice', '_practice')
            ->join('_practice.businessFunction', '_business_function')
            ->where('_business_function.metamodel = :metamodel')->setParameter('metamodel', $metamodel)
            ->orderBy('_business_function.order')
            ->addOrderBy('_practice.order')
            ->addOrderBy("_stream.id")
            ->addOrderBy("_activity.id")
            ->getQuery()->getResult();
    }
// #BlockEnd number=102

}
