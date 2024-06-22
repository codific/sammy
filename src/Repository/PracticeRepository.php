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

// #BlockStart number=57 id=_19_0_3_40d01a2_1635864210817_220463_6011_#_0

use App\Entity\Practice;
use App\Repository\Abstraction\AbstractExpertModeRepository;
use App\Service\MetamodelService;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Practice|null find($id, $lockMode = null, $lockVersion = null)
 * @method Practice|null findOneBy(array $criteria, ?array $orderBy = null, bool $expertMode = false)
 * @method Practice[]    findAll(bool $expertMode = false)
 * @method Practice[]    findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null, bool $expertMode = false)
 */
class PracticeRepository extends AbstractExpertModeRepository
{
    /**
     * PracticeRepository constructor.
     */
    public function __construct(
        private readonly MetamodelService $metamodelService,
        ManagerRegistry $registry,
        string $entityClassName = Practice::class
    ) {
        parent::__construct($registry, $entityClassName);
    }

    /**
     * Duplicate the object and save the duplicate.
     *
     * @param Practice $practice The object to be duplicated
     */
    public function duplicate(Practice $practice): Practice
    {
        $clone = $practice->getCopy();
        $this->getEntityManager()->persist($clone);
        $this->getEntityManager()->flush();

        return $clone;
    }

    protected function getFromMetamodelService(): array
    {
        return $this->metamodelService->getPractices();
    }

// #BlockEnd number=57

}
