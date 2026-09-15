<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\MusicAlbum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MusicAlbum>
 *
 * @implements MusicAlbumRepositoryInterface<MusicAlbum>
 *
 * @psalm-api
 *
 * @psalm-suppress ClassMustBeFinal
 */
class MusicAlbumRepository extends ServiceEntityRepository implements MusicAlbumRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MusicAlbum::class);
    }
}
