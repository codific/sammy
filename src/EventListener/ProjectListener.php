<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Project;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class ProjectListener
{
    public function __construct(private readonly string $projectDir)
    {
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!($entity instanceof Project)) {
            return;
        }

        $project = $entity;
        if (!$project->isTemplate() && !file_exists($this->projectDir."/private/projects/{$project->getId()}")) {
            mkdir(
                $this->projectDir."/private/projects/{$project->getId()}",
                0770,
                true
            );
        }
    }
}
