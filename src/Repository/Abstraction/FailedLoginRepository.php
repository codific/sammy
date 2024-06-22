<?php
/**
 * This is automatically generated file using the Codific Prototizer.
 *
 * PHP version 8
 *
 * @category PHP
 *
 * @author   CODIFIC <info@codific.com>
 *
 * @see     http://codific.com
 */

declare(strict_types=1);

namespace App\Repository\Abstraction;

use App\Entity\Abstraction\FailedLogin;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class FailedLoginRepository.
 *
 * @method FailedLogin|null find($id, $lockMode = null, $lockVersion = null)
 * @method FailedLogin|null findOneBy(array $criteria, array $orderBy = null)
 * @method FailedLogin[]    findAll()
 * @method FailedLogin[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FailedLoginRepository extends AbstractRepository
{
    /**
     * FailedLoginRepository constructor.
     */
    public function __construct(ManagerRegistry $registry, string $entityClassName = FailedLogin::class)
    {
        parent::__construct($registry, $entityClassName);
    }
}
