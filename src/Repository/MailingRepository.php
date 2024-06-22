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

// #BlockStart number=170 id=_19_0_3_40d01a2_1637589778949_213477_4862_#_0

use App\Entity\Mailing;
use App\Repository\Abstraction\AbstractRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Mailing|null find($id, $lockMode = null, $lockVersion = null)
 * @method Mailing|null findOneBy(array $criteria, array $orderBy = null)
 * @method Mailing[]    findAll()
 * @method Mailing[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MailingRepository extends AbstractRepository
{
    /**
     * MailingRepository constructor.
     *
     * @return void
     */
    public function __construct(ManagerRegistry $registry, string $entityClassName = Mailing::class)
    {
        parent::__construct($registry, $entityClassName);
    }

    /**
     * Duplicate the object and save the duplicate.
     *
     * @param Mailing $mailing The object to be duplicated
     */
    public function duplicate(Mailing $mailing): Mailing
    {
        $clone = $mailing->getCopy();
        $this->getEntityManager()->persist($clone);
        $this->getEntityManager()->flush();

        return $clone;
    }

// #BlockEnd number=170

}
