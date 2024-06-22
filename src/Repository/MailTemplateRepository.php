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

// #BlockStart number=184 id=_19_0_3_40d01a2_1637589778954_493687_4863_#_0

use App\Entity\MailTemplate;
use App\Repository\Abstraction\AbstractRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method MailTemplate|null find($id, $lockMode = null, $lockVersion = null)
 * @method MailTemplate|null findOneBy(array $criteria, array $orderBy = null)
 * @method MailTemplate[]    findAll()
 * @method MailTemplate[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MailTemplateRepository extends AbstractRepository
{
    /**
     * MailTemplateRepository constructor.
     *
     * @return void
     */
    public function __construct(ManagerRegistry $registry, string $entityClassName = MailTemplate::class)
    {
        parent::__construct($registry, $entityClassName);
    }

    /**
     * Duplicate the object and save the duplicate.
     *
     * @param MailTemplate $mailTemplate The object to be duplicated
     */
    public function duplicate(MailTemplate $mailTemplate): MailTemplate
    {
        $clone = $mailTemplate->getCopy();
        $this->getEntityManager()->persist($clone);
        $this->getEntityManager()->flush();

        return $clone;
    }

// #BlockEnd number=184

}
