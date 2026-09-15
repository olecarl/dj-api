<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\MusicGroup;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MusicGroup>
 *
 * @implements MusicGroupRepositoryInterface<MusicGroup>
 *
 * @psalm-api
 *
 * @psalm-suppress ClassMustBeFinal
 */
class MusicGroupRepository extends ServiceEntityRepository implements MusicGroupRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MusicGroup::class);
    }
}
