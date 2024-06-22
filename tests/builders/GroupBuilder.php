<?php
declare(strict_types=1);

namespace App\Tests\builders;

use App\Entity\Group;
use Doctrine\ORM\EntityManagerInterface;

class GroupBuilder
{
    private $entityManager;
    private $name;

    public function __construct(EntityManagerInterface $entityManager = null)
    {
        $this->entityManager = $entityManager;
    }

    public function withName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function build(bool $persist = true): Group
    {
        $group = new Group();
        $group->setName($this->name ?? "test group ".bin2hex(random_bytes(5)));

        if ($persist && $this->entityManager !== null) {
            $this->entityManager->persist($group);
            $this->entityManager->flush();
        }

        return $group;
    }
}