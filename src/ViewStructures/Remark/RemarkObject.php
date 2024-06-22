<?php

declare(strict_types=1);

namespace App\ViewStructures\Remark;

use App\Entity\User;
use App\Enum\AssessmentStatus;
use App\Enum\Custom\RemarkType;

readonly class RemarkObject
{
    public function __construct(
        public RemarkType $type,
        public int $id,
        public ?string $text,
        public \DateTime $date,
        public ?User $user,
        public ?string $file,
        public ?string $title,
        public AssessmentStatus $assessmentStatus,
    ) {
    }
}
