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

// #BlockStart number=66 id=_19_0_3_40d01a2_1635864239167_130089_6058_#_0

use App\Entity\Stream;
use App\Repository\Abstraction\AbstractExpertModeRepository;
use App\Service\MetamodelService;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Stream|null find($id, $lockMode = null, $lockVersion = null)
 * @method Stream|null findOneBy(array $criteria, ?array $orderBy = null, bool $expertMode = false)
 * @method Stream[]    findAll(bool $expertMode = false))
 * @method Stream[]    findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null, bool $expertMode = false)
 */
class StreamRepository extends AbstractExpertModeRepository
{
    /**
     * StreamRepository constructor.
     */
    public function __construct(
        private readonly MetamodelService $metamodelService,
        ManagerRegistry $registry,
        string $entityClassName = Stream::class
    ) {
        parent::__construct($registry, $entityClassName);
    }

    /**
     * Duplicate the object and save the duplicate.
     *
     * @param Stream $stream The object to be duplicated
     */
    public function duplicate(Stream $stream): Stream
    {
        $clone = $stream->getCopy();
        $this->getEntityManager()->persist($clone);
        $this->getEntityManager()->flush();

        return $clone;
    }

    protected function getFromMetamodelService(): array
    {
        return $this->metamodelService->getStreams();
    }
// #BlockEnd number=66

}
