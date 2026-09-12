<?php

declare(strict_types=1);

namespace App\Repository;

use Doctrine\Persistence\ObjectRepository;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @template T of object
 *
 * @extends ObjectRepository<T>
 */
interface UserRepositoryInterface extends ObjectRepository, PasswordUpgraderInterface
{
    /**
     * Counts objects matching the given criteria.
     *
     * @param array<string, mixed> $criteria
     */
    public function count(array $criteria = []): int;
}
