<?php

declare(strict_types=1);

namespace App\DTO;

use App\Enum\Custom\RemarkType;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class DocumentationDTO
{

    private ?string $text = null;

    private ?string $attachmentTitle = null;

    private ?UploadedFile $attachmentFile = null;

    private array $maturityLevel = [];

    private ?int $remarkId = null;

    private ?RemarkType $remarkType = null;

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(?string $text): void
    {
        $this->text = $text;
    }

    public function getAttachmentTitle(): ?string
    {
        return $this->attachmentTitle;
    }

    public function setAttachmentTitle(?string $attachmentTitle): void
    {
        $this->attachmentTitle = $attachmentTitle;
    }

    public function getAttachmentFile(): ?UploadedFile
    {
        return $this->attachmentFile;
    }

    public function setAttachmentFile(?UploadedFile $attachmentFile): void
    {
        $this->attachmentFile = $attachmentFile;
    }

    public function getMaturityLevel(): array
    {
        return $this->maturityLevel;
    }

    public function setMaturityLevel(array $maturityLevel): void
    {
        $this->maturityLevel = $maturityLevel;
    }

    public function getRemarkId(): ?int
    {
        return $this->remarkId;
    }

    public function setRemarkId(?int $remarkId): void
    {
        $this->remarkId = $remarkId;
    }

    public function getRemarkType(): ?RemarkType
    {
        return $this->remarkType;
    }

    public function setRemarkType(?RemarkType $remarkType): void
    {
        $this->remarkType = $remarkType;
    }
}
