<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserProfile;
use Doctrine\Persistence\ObjectRepository;

/**
 * @template T of object
 *
 * @extends ObjectRepository<T>
 */
interface UserProfileRepositoryInterface extends ObjectRepository
{
    public function findOneByUser(User $user): ?UserProfile;
}
