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

// #BlockStart number=198 id=_19_0_3_40d01a2_1637590051717_771589_5093_#_0

use App\Entity\SystemConfig;
use App\Repository\Abstraction\AbstractRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SystemConfig|null find($id, $lockMode = null, $lockVersion = null)
 * @method SystemConfig|null findOneBy(array $criteria, array $orderBy = null)
 * @method SystemConfig[]    findAll()
 * @method SystemConfig[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SystemConfigRepository extends AbstractRepository
{
    /**
     * SystemConfigRepository constructor.
     *
     * @return void
     */
    public function __construct(ManagerRegistry $registry, string $entityClassName = SystemConfig::class)
    {
        parent::__construct($registry, $entityClassName);
    }

    /**
     * Duplicate the object and save the duplicate.
     *
     * @param SystemConfig $systemConfig The object to be duplicated
     */
    public function duplicate(SystemConfig $systemConfig): SystemConfig
    {
        $clone = $systemConfig->getCopy();
        $this->getEntityManager()->persist($clone);
        $this->getEntityManager()->flush();

        return $clone;
    }

// #BlockEnd number=198

}
