<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\State\UserProvider;
use Symfony\Component\Uid\Uuid;

/** @psalm-api */
#[ApiResource(
    shortName: 'User',
    operations: [
        new GetCollection(
            security: "is_granted('ROLE_ADMIN')",
            securityMessage: 'Only administrators can list users.',
        ),
        new Get(
            security: "is_granted('ROLE_ADMIN') or object.getEmail() == user.getUserIdentifier()",
            securityMessage: 'You can only view your own user profile.',
        ),
        new Get(
            uriTemplate: '/me{._format}',
            uriVariables: [],
            security: "is_granted('ROLE_USER')",
            securityMessage: 'Only authenticated users can view their own profile.',
            name: 'me',
        ),
    ],
    provider: UserProvider::class,
    stateOptions: new Options(entityClass: \App\Entity\User::class),
)]
final readonly class User
{
    public function __construct(
        #[ApiProperty(identifier: true)]
        private Uuid $id,
        private string $email,
        /** @var list<string> */
        private array $roles,
    ) {
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        return $this->roles;
    }
}
