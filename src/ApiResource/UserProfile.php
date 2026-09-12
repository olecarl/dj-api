<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Link;
use App\State\UserProfileProvider;
use Symfony\Component\Uid\Uuid;

/** @psalm-api */
#[ApiResource(
    shortName: 'User',
    operations: [
        new Get(
            uriTemplate: '/users/{userId}/profile',
            uriVariables: ['userId' => new Link(toProperty: 'user', fromClass: User::class)],
            security: "is_granted('ROLE_ADMIN') or object.getUser().getEmail() == user.getUserIdentifier()",
            securityMessage: 'You can only view your own profile.',
        ),
    ],
    provider: UserProfileProvider::class,
    stateOptions: new Options(entityClass: \App\Entity\UserProfile::class),
)]
final readonly class UserProfile
{
    public function __construct(
        #[ApiProperty(identifier: true)]
        private Uuid $id,
        #[ApiProperty(readableLink: true)]
        private User $user,
        private ?string $about = null,
    ) {
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getAbout(): ?string
    {
        return $this->about;
    }
}
