<?php

declare(strict_types=1);

namespace App\Repository;

use Doctrine\Persistence\ObjectRepository;

/**
 * @template T of object
 *
 * @extends ObjectRepository<T>
 */
interface MusicPlaylistRepositoryInterface extends ObjectRepository
{
    /** @param array<string, mixed> $criteria */
    public function count(array $criteria = []): int;
}
