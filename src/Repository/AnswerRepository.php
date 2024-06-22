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

// #BlockStart number=120 id=_19_0_3_40d01a2_1635865267453_727556_6531_#_0

use App\Entity\Answer;
use App\Repository\Abstraction\AbstractRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Answer|null find($id, $lockMode = null, $lockVersion = null)
 * @method Answer|null findOneBy(array $criteria, array $orderBy = null)
 * @method Answer[]    findAll()
 * @method Answer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AnswerRepository extends AbstractRepository
{
    /**
     * AnswerRepository constructor.
     *
     * @return void
     */
    public function __construct(ManagerRegistry $registry, string $entityClassName = Answer::class)
    {
        parent::__construct($registry, $entityClassName);
    }

    /**
     * Duplicate the object and save the duplicate.
     *
     * @param Answer $answer The object to be duplicated
     */
    public function duplicate(Answer $answer): Answer
    {
        $clone = $answer->getCopy();
        $this->getEntityManager()->persist($clone);
        $this->getEntityManager()->flush();

        return $clone;
    }

// #BlockEnd number=120

}
