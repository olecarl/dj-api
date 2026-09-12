<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserProfile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserProfile>
 *
 * @implements UserProfileRepositoryInterface<UserProfile>
 *
 * @psalm-api
 *
 * @psalm-suppress ClassMustBeFinal
 */
class UserProfileRepository extends ServiceEntityRepository implements UserProfileRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserProfile::class);
    }

    #[\Override]
    public function findOneByUser(User $user): ?UserProfile
    {
        return $this->findOneBy(['user' => $user]);
    }
}
