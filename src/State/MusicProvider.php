<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\Pagination;
use ApiPlatform\State\Pagination\TraversablePaginator;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\ItemList;
use App\ApiResource\ListItem;
use App\ApiResource\MusicAlbum as MusicAlbumResource;
use App\ApiResource\MusicGroup as MusicGroupResource;
use App\ApiResource\MusicPlaylist as MusicPlaylistResource;
use App\ApiResource\MusicRecording as MusicRecordingResource;
use App\Entity\MusicAlbum;
use App\Entity\MusicGroup;
use App\Entity\MusicPlaylist;
use App\Entity\MusicPlaylistTrack;
use App\Entity\MusicRecording;
use App\Repository\MusicAlbumRepositoryInterface;
use App\Repository\MusicGroupRepositoryInterface;
use App\Repository\MusicPlaylistRepositoryInterface;
use App\Repository\MusicRecordingRepositoryInterface;

/**
 * @implements ProviderInterface<MusicPlaylistResource|MusicRecordingResource|MusicAlbumResource|MusicGroupResource>
 *
 * @psalm-api
 */
final readonly class MusicProvider implements ProviderInterface
{
    public function __construct(
        /** @var MusicPlaylistRepositoryInterface<MusicPlaylist> */
        private MusicPlaylistRepositoryInterface $playlistRepository,
        /** @var MusicRecordingRepositoryInterface<MusicRecording> */
        private MusicRecordingRepositoryInterface $recordingRepository,
        /** @var MusicAlbumRepositoryInterface<MusicAlbum> */
        private MusicAlbumRepositoryInterface $albumRepository,
        /** @var MusicGroupRepositoryInterface<MusicGroup> */
        private MusicGroupRepositoryInterface $groupRepository,
        private Pagination $pagination,
    ) {
    }

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     *
     * @return MusicPlaylistResource|MusicRecordingResource|MusicAlbumResource|MusicGroupResource|iterable<MusicPlaylistResource|MusicRecordingResource|MusicAlbumResource|MusicGroupResource>|null
     */
    #[\Override]
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $resourceClass = $operation->getClass();
        if (null === $resourceClass) {
            throw new \LogicException('A music operation must have a resource class.');
        }

        $repository = $this->repositoryFor($resourceClass);
        if ($operation instanceof GetCollection) {
            return $this->provideCollection($operation, $repository, $context, $resourceClass);
        }

        $entity = $repository->findOneBy(['id' => $uriVariables['id'] ?? null]);

        return null === $entity ? null : $this->toResource($entity, $resourceClass);
    }

    /**
     * @param MusicPlaylistRepositoryInterface<MusicPlaylist>|MusicRecordingRepositoryInterface<MusicRecording>|MusicAlbumRepositoryInterface<MusicAlbum>|MusicGroupRepositoryInterface<MusicGroup> $repository
     * @param array<string, mixed>                                                                                                                                                                  $context
     *
     * @return iterable<MusicPlaylistResource|MusicRecordingResource|MusicAlbumResource|MusicGroupResource>
     */
    private function provideCollection(
        Operation $operation,
        object $repository,
        array $context,
        string $resourceClass,
    ): iterable {
        if (!$this->pagination->isEnabled($operation, $context)) {
            return array_map(
                fn (object $entity): object => $this->toResource($entity, $resourceClass),
                $repository->findBy([], ['id' => 'ASC']),
            );
        }

        /** @var array{0: int, 1: int, 2: int} $pagination */
        $pagination = $this->pagination->getPagination($operation, $context);
        [$page, $offset, $limit] = $pagination;
        $entities = 0 === $limit ? [] : $repository->findBy([], ['id' => 'ASC'], $limit, $offset);
        $resources = array_map(
            fn (object $entity): object => $this->toResource($entity, $resourceClass),
            $entities,
        );

        return new TraversablePaginator(
            new \ArrayIterator($resources),
            $page,
            $limit,
            $repository->count([]),
        );
    }

    /**
     * @return MusicPlaylistRepositoryInterface<MusicPlaylist>|MusicRecordingRepositoryInterface<MusicRecording>|MusicAlbumRepositoryInterface<MusicAlbum>|MusicGroupRepositoryInterface<MusicGroup>
     */
    private function repositoryFor(string $resourceClass): object
    {
        return match ($resourceClass) {
            MusicPlaylistResource::class => $this->playlistRepository,
            MusicRecordingResource::class => $this->recordingRepository,
            MusicAlbumResource::class => $this->albumRepository,
            MusicGroupResource::class => $this->groupRepository,
            default => throw new \LogicException(\sprintf('Unsupported music resource class "%s".', $resourceClass)),
        };
    }

    private function toResource(object $entity, string $resourceClass): MusicPlaylistResource|MusicRecordingResource|MusicAlbumResource|MusicGroupResource
    {
        return match ($resourceClass) {
            MusicPlaylistResource::class => $entity instanceof MusicPlaylist ? $this->playlistResource($entity) : throw $this->unexpectedEntity(MusicPlaylist::class, $entity),
            MusicRecordingResource::class => $entity instanceof MusicRecording ? $this->recordingResource($entity) : throw $this->unexpectedEntity(MusicRecording::class, $entity),
            MusicAlbumResource::class => $entity instanceof MusicAlbum ? $this->albumResource($entity) : throw $this->unexpectedEntity(MusicAlbum::class, $entity),
            MusicGroupResource::class => $entity instanceof MusicGroup ? $this->groupResource($entity) : throw $this->unexpectedEntity(MusicGroup::class, $entity),
            default => throw new \LogicException(\sprintf('Unsupported music resource class "%s".', $resourceClass)),
        };
    }

    private function playlistResource(MusicPlaylist $playlist): MusicPlaylistResource
    {
        $id = $playlist->getId();
        if (null === $id) {
            throw new \LogicException('A music playlist must have an identifier.');
        }

        $items = [];
        /** @var list<MusicPlaylistTrack> $playlistTracks */
        $playlistTracks = $playlist->getTracks()->toArray();
        usort($playlistTracks, static fn (MusicPlaylistTrack $first, MusicPlaylistTrack $second): int => $first->getPosition() <=> $second->getPosition());
        foreach ($playlistTracks as $track) {
            $items[] = new ListItem($track->getPosition(), $this->recordingResource($track->getRecording()));
        }

        return new MusicPlaylistResource(
            $id,
            $playlist->getName(),
            new ItemList(
                \count($items),
                'https://schema.org/ItemListOrderAscending',
                $items,
            ),
        );
    }

    private function recordingResource(MusicRecording $recording): MusicRecordingResource
    {
        $id = $recording->getId();
        if (null === $id) {
            throw new \LogicException('A music recording must have an identifier.');
        }

        $artists = [];
        foreach ($recording->getArtists() as $artist) {
            $artists[] = $this->groupReference($artist);
        }

        return new MusicRecordingResource(
            $id,
            $recording->getName(),
            $recording->getDuration(),
            $recording->getUrl(),
            null === $recording->getAlbum() ? null : $this->albumReference($recording->getAlbum()),
            $artists,
        );
    }

    private function albumResource(MusicAlbum $album): MusicAlbumResource
    {
        $id = $album->getId();
        if (null === $id) {
            throw new \LogicException('A music album must have an identifier.');
        }

        $artists = [];
        foreach ($album->getArtists() as $artist) {
            $artists[] = $this->groupReference($artist);
        }

        $albumTracks = $album->getTracks()->toArray();
        usort($albumTracks, static fn (MusicRecording $first, MusicRecording $second): int => ($first->getAlbumPosition() ?? \PHP_INT_MAX) <=> ($second->getAlbumPosition() ?? \PHP_INT_MAX));
        $tracks = [];
        foreach ($albumTracks as $track) {
            $tracks[] = $this->recordingResource($track);
        }

        return new MusicAlbumResource($id, $album->getName(), $album->getUrl(), $artists, $tracks);
    }

    private function groupResource(MusicGroup $group): MusicGroupResource
    {
        $id = $group->getId();
        if (null === $id) {
            throw new \LogicException('A music group must have an identifier.');
        }

        $albums = [];
        foreach ($group->getAlbums() as $album) {
            $albums[] = $this->albumReference($album);
        }

        $tracks = [];
        foreach ($group->getRecordings() as $recording) {
            $tracks[] = $this->recordingReference($recording);
        }

        return new MusicGroupResource($id, $group->getName(), $group->getUrl(), $albums, $tracks);
    }

    private function groupReference(MusicGroup $group): MusicGroupResource
    {
        $id = $group->getId();
        if (null === $id) {
            throw new \LogicException('A music group must have an identifier.');
        }

        return new MusicGroupResource($id, $group->getName(), $group->getUrl());
    }

    private function albumReference(MusicAlbum $album): MusicAlbumResource
    {
        $id = $album->getId();
        if (null === $id) {
            throw new \LogicException('A music album must have an identifier.');
        }

        return new MusicAlbumResource($id, $album->getName(), $album->getUrl());
    }

    private function recordingReference(MusicRecording $recording): MusicRecordingResource
    {
        $id = $recording->getId();
        if (null === $id) {
            throw new \LogicException('A music recording must have an identifier.');
        }

        return new MusicRecordingResource($id, $recording->getName());
    }

    private function unexpectedEntity(string $expectedClass, object $entity): \LogicException
    {
        return new \LogicException(\sprintf('Expected entity "%s", got "%s".', $expectedClass, $entity::class));
    }
}
