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

// #BlockStart number=93 id=_19_0_3_40d01a2_1635864872516_976335_6324_#_0

use App\Entity\Activity;
use App\Repository\Abstraction\AbstractRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Activity|null find($id, $lockMode = null, $lockVersion = null)
 * @method Activity|null findOneBy(array $criteria, array $orderBy = null)
 * @method Activity[]    findAll()
 * @method Activity[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ActivityRepository extends AbstractRepository
{
    /**
     * ActivityRepository constructor.
     *
     * @return void
     */
    public function __construct(ManagerRegistry $registry, string $entityClassName = Activity::class)
    {
        parent::__construct($registry, $entityClassName);
    }

    /**
     * Duplicate the object and save the duplicate.
     *
     * @param Activity $activity The object to be duplicated
     */
    public function duplicate(Activity $activity): Activity
    {
        $clone = $activity->getCopy();
        $this->getEntityManager()->persist($clone);
        $this->getEntityManager()->flush();

        return $clone;
    }

// #BlockEnd number=93

}
