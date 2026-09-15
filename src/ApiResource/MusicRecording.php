<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\State\MusicProvider;
use Symfony\Component\Uid\Uuid;

/** @psalm-api */
#[ApiResource(
    shortName: 'Music/Recordings',
    types: ['https://schema.org/MusicRecording'],
    operations: [
        new GetCollection(uriTemplate: '/music/recordings', security: "is_granted('ROLE_USER')"),
        new Get(uriTemplate: '/music/recordings/{id}', requirements: ['id' => '[0-9a-fA-F-]{36}'], security: "is_granted('ROLE_USER')"),
    ],
    provider: MusicProvider::class,
    stateOptions: new Options(entityClass: \App\Entity\MusicRecording::class),
)]
final readonly class MusicRecording
{
    /** @param list<MusicGroup> $byArtist */
    public function __construct(
        #[ApiProperty(identifier: true)]
        private Uuid $id,
        private string $name,
        private ?string $duration = null,
        private ?string $url = null,
        #[ApiProperty(readableLink: false)]
        private ?MusicAlbum $inAlbum = null,
        #[ApiProperty(readableLink: false)]
        private array $byArtist = [],
    ) {
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDuration(): ?string
    {
        return $this->duration;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function getInAlbum(): ?MusicAlbum
    {
        return $this->inAlbum;
    }

    /** @return list<MusicGroup> */
    public function getByArtist(): array
    {
        return $this->byArtist;
    }
}
