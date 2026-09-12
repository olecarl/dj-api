<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\Pagination;
use ApiPlatform\State\Pagination\TraversablePaginator;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\User as UserResource;
use App\Entity\User;
use App\Repository\UserRepositoryInterface;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @implements ProviderInterface<UserResource>
 *
 * @psalm-api
 */
final readonly class UserProvider implements ProviderInterface
{
    /**
     * @param UserRepositoryInterface<User> $userRepository
     */
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private Pagination $pagination,
        private Security $security,
    ) {
    }

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     *
     * @return iterable<UserResource>|UserResource|null
     */
    #[\Override]
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if ($operation instanceof GetCollection) {
            if (!$this->pagination->isEnabled($operation, $context)) {
                return array_map(
                    $this->toResource(...),
                    $this->userRepository->findBy([], ['id' => 'ASC']),
                );
            }

            /** @var array{0: int, 1: int, 2: int} $pagination */
            $pagination = $this->pagination->getPagination($operation, $context);
            [$page, $offset, $limit] = $pagination;
            $users = 0 === $limit
                ? []
                : $this->userRepository->findBy([], ['id' => 'ASC'], $limit, $offset);
            $resources = array_map($this->toResource(...), $users);

            return new TraversablePaginator(
                new \ArrayIterator($resources),
                $page,
                $limit,
                $this->userRepository->count([]),
            );
        }

        if ('me' === $operation->getName()) {
            $user = $this->security->getUser();

            return $user instanceof User ? $this->toResource($user) : null;
        }

        $user = $this->userRepository->findOneBy(['id' => $uriVariables['id'] ?? null]);

        return null === $user ? null : $this->toResource($user);
    }

    public function toResource(User $user): UserResource
    {
        $id = $user->getId();
        if (null === $id) {
            throw new \LogicException('A user must have an identifier.');
        }

        return new UserResource(
            $id,
            $user->getEmail() ?? throw new \LogicException('A user must have an email address.'),
            array_values($user->getRoles()),
        );
    }
}
