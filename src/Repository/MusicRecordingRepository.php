<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\MusicRecording;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MusicRecording>
 *
 * @implements MusicRecordingRepositoryInterface<MusicRecording>
 *
 * @psalm-api
 *
 * @psalm-suppress ClassMustBeFinal
 */
class MusicRecordingRepository extends ServiceEntityRepository implements MusicRecordingRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MusicRecording::class);
    }
}
