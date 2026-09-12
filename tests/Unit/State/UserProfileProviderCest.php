<?php

declare(strict_types=1);

namespace App\Tests\Unit\State;

use ApiPlatform\Metadata\Get;
use ApiPlatform\State\Pagination\Pagination;
use App\ApiResource\UserProfile as UserProfileResource;
use App\Entity\User;
use App\Entity\UserProfile;
use App\Repository\UserProfileRepositoryInterface;
use App\Repository\UserRepositoryInterface;
use App\State\UserProfileProvider;
use App\State\UserProvider;
use App\Tests\Support\UnitTester;
use Codeception\Stub;
use Codeception\Stub\Expected;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Uid\Uuid;

final class UserProfileProviderCest
{
    public function testProvidesProfileResourceForUser(UnitTester $I): void
    {
        $user = $this->createUser('user@example.com', ['ROLE_USER']);
        $profile = $this->createProfile($user, 'Hello, world!');
        $repository = Stub::makeEmpty(UserRepositoryInterface::class, [
            'findOneBy' => Expected::once($user),
        ]);
        $profileRepository = Stub::makeEmpty(UserProfileRepositoryInterface::class, [
            'findOneByUser' => Expected::once($profile),
        ]);
        $provider = $this->createProvider($repository, $profileRepository);

        $resource = $provider->provide(new Get(), ['userId' => $user->getId()]);

        $I->assertInstanceOf(UserProfileResource::class, $resource);
        $I->assertSame($profile->getId(), $resource->getId());
        $I->assertSame('Hello, world!', $resource->getAbout());
        $I->assertSame('user@example.com', $resource->getUser()->getEmail());
        $I->assertSame($user->getId(), $resource->getUser()->getId());
    }

    public function testReturnsNullForMissingUserId(UnitTester $I): void
    {
        $repository = Stub::makeEmpty(UserRepositoryInterface::class);
        $profileRepository = Stub::makeEmpty(UserProfileRepositoryInterface::class);
        $provider = $this->createProvider($repository, $profileRepository);

        $I->assertNull($provider->provide(new Get()));
    }

    public function testReturnsNullForUnknownUser(UnitTester $I): void
    {
        $repository = Stub::makeEmpty(UserRepositoryInterface::class, [
            'findOneBy' => Expected::once(null),
        ]);
        $profileRepository = Stub::makeEmpty(UserProfileRepositoryInterface::class);
        $provider = $this->createProvider($repository, $profileRepository);

        $I->assertNull($provider->provide(new Get(), ['userId' => Uuid::v4()]));
    }

    public function testReturnsNullForUserWithoutProfile(UnitTester $I): void
    {
        $user = $this->createUser('user@example.com');
        $repository = Stub::makeEmpty(UserRepositoryInterface::class, [
            'findOneBy' => Expected::once($user),
        ]);
        $profileRepository = Stub::makeEmpty(UserProfileRepositoryInterface::class, [
            'findOneByUser' => Expected::once(null),
        ]);
        $provider = $this->createProvider($repository, $profileRepository);

        $I->assertNull($provider->provide(new Get(), ['userId' => $user->getId()]));
    }

    public function testRejectsProfileWithoutUser(UnitTester $I): void
    {
        $user = $this->createUser('user@example.com');
        $repository = Stub::makeEmpty(UserRepositoryInterface::class, [
            'findOneBy' => Expected::once($user),
        ]);
        $profileRepository = Stub::makeEmpty(UserProfileRepositoryInterface::class, [
            'findOneByUser' => Expected::once(new UserProfile()),
        ]);
        $provider = $this->createProvider($repository, $profileRepository);

        $I->expectThrowable(\LogicException::class, static function () use ($provider, $user): void {
            $provider->provide(new Get(), ['userId' => $user->getId()]);
        });
    }

    public function testRejectsProfileWithoutIdentifier(UnitTester $I): void
    {
        $user = $this->createUser('user@example.com');
        $profile = (new UserProfile())->setUser($user)->setAbout('Hello, world!');
        $repository = Stub::makeEmpty(UserRepositoryInterface::class, [
            'findOneBy' => Expected::once($user),
        ]);
        $profileRepository = Stub::makeEmpty(UserProfileRepositoryInterface::class, [
            'findOneByUser' => Expected::once($profile),
        ]);
        $provider = $this->createProvider($repository, $profileRepository);

        $I->expectThrowable(\LogicException::class, static function () use ($provider, $user): void {
            $provider->provide(new Get(), ['userId' => $user->getId()]);
        });
    }

    public function testRejectsUserWithoutEmail(UnitTester $I): void
    {
        $user = $this->createUser('user@example.com');
        $profile = $this->createProfile($user, 'Hello, world!');
        $email = (new \ReflectionClass($user))->getProperty('email');
        $email->setValue($user, null);
        $repository = Stub::makeEmpty(UserRepositoryInterface::class, [
            'findOneBy' => Expected::once($user),
        ]);
        $profileRepository = Stub::makeEmpty(UserProfileRepositoryInterface::class, [
            'findOneByUser' => Expected::once($profile),
        ]);
        $provider = $this->createProvider($repository, $profileRepository);

        $I->expectThrowable(\LogicException::class, static function () use ($provider, $user): void {
            $provider->provide(new Get(), ['userId' => $user->getId()]);
        });
    }

    private function createProvider(
        UserRepositoryInterface $repository,
        UserProfileRepositoryInterface $profileRepository,
    ): UserProfileProvider {
        $userProvider = new UserProvider(
            Stub::makeEmpty(UserRepositoryInterface::class),
            new Pagination(),
            Stub::makeEmpty(Security::class),
        );

        return new UserProfileProvider($repository, $profileRepository, $userProvider);
    }

    /**
     * @param list<string> $roles
     */
    private function createUser(string $email, array $roles = []): User
    {
        $user = (new User())->setEmail($email)->setRoles($roles)->setPassword('hashed-password');
        $property = (new \ReflectionClass($user))->getProperty('id');
        $property->setValue($user, Uuid::v4());

        return $user;
    }

    private function createProfile(User $user, string $about): UserProfile
    {
        $profile = (new UserProfile())->setUser($user)->setAbout($about);
        $property = (new \ReflectionClass($profile))->getProperty('id');
        $property->setValue($profile, Uuid::v4());
        $user->setUserProfile($profile);

        return $profile;
    }
}
