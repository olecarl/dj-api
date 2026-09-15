<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiProperty;

/** @psalm-api */
final readonly class ListItem
{
    public function __construct(
        private int $position,
        #[ApiProperty(readableLink: true, types: ['https://schema.org/MusicRecording'])]
        private MusicRecording $item,
    ) {
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function getItem(): MusicRecording
    {
        return $this->item;
    }
}
