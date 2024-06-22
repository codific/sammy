<?php

declare(strict_types=1);

namespace App\ViewStructures\Timeline;

use App\Entity\User;

readonly class TimelineEvent
{
    public function __construct(
        public EventIcon $eventIcon,
        public string $title,
        public ?\DateTime $completedAt,
        public ?User $user,
        public string $action,
        public ?User $assignee,
        public bool $newline,
    ) {
    }
}
