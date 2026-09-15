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
    shortName: 'Music/Album',
    types: ['https://schema.org/MusicAlbum'],
    operations: [
        new GetCollection(uriTemplate: '/music/albums', security: "is_granted('ROLE_USER')"),
        new Get(uriTemplate: '/music/albums/{id}', requirements: ['id' => '[0-9a-fA-F-]{36}'], security: "is_granted('ROLE_USER')"),
    ],
    provider: MusicProvider::class,
    stateOptions: new Options(entityClass: \App\Entity\MusicAlbum::class),
)]
final readonly class MusicAlbum
{
    /**
     * @param list<MusicGroup>     $byArtist
     * @param list<MusicRecording> $track
     */
    public function __construct(
        #[ApiProperty(identifier: true)]
        private Uuid $id,
        private string $name,
        private ?string $url = null,
        #[ApiProperty(readableLink: false)]
        private array $byArtist = [],
        #[ApiProperty(readableLink: true)]
        private array $track = [],
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

    public function getUrl(): ?string
    {
        return $this->url;
    }

    /** @return list<MusicGroup> */
    public function getByArtist(): array
    {
        return $this->byArtist;
    }

    /** @return list<MusicRecording> */
    public function getTrack(): array
    {
        return $this->track;
    }
}
