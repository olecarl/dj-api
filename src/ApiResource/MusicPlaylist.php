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
    shortName: 'Music/Playlist',
    types: ['https://schema.org/MusicPlaylist'],
    operations: [
        new GetCollection(uriTemplate: '/music', security: "is_granted('ROLE_USER')"),
        new Get(uriTemplate: '/music/{id}', requirements: ['id' => '[0-9a-fA-F-]{36}'], security: "is_granted('ROLE_USER')"),
    ],
    provider: MusicProvider::class,
    stateOptions: new Options(entityClass: \App\Entity\MusicPlaylist::class),
)]
final readonly class MusicPlaylist
{
    public function __construct(
        #[ApiProperty(identifier: true)]
        private Uuid $id,
        private string $name,
        #[ApiProperty(readableLink: true, types: ['https://schema.org/ItemList'])]
        private ItemList $track,
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

    public function getNumTracks(): int
    {
        return $this->track->getNumberOfItems();
    }

    public function getTrack(): ItemList
    {
        return $this->track;
    }
}
