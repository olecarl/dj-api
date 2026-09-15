<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\MusicPlaylist;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MusicPlaylist>
 *
 * @implements MusicPlaylistRepositoryInterface<MusicPlaylist>
 *
 * @psalm-api
 *
 * @psalm-suppress ClassMustBeFinal
 */
class MusicPlaylistRepository extends ServiceEntityRepository implements MusicPlaylistRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MusicPlaylist::class);
    }
}
