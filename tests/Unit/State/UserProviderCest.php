<?php

declare(strict_types=1);

namespace App\Tests\Unit\State;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\State\Pagination\Pagination;
use ApiPlatform\State\Pagination\TraversablePaginator;
use App\ApiResource\User as UserResource;
use App\Entity\User;
use App\Repository\UserRepositoryInterface;
use App\State\UserProvider;
use App\Tests\Support\UnitTester;
use Codeception\Stub;
use Codeception\Stub\Expected;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Uid\Uuid;

final class UserProviderCest
{
    public function testProvidesSingleUserResource(UnitTester $I): void
    {
        $user = $this->createUser('user@example.com', ['ROLE_ADMIN']);
        $repository = Stub::makeEmpty(UserRepositoryInterface::class, [
            'findOneBy' => Expected::once($user),
        ]);
        $provider = new UserProvider($repository, new Pagination(), Stub::makeEmpty(Security::class));

        $resource = $provider->provide(new Get(), ['id' => $user->getId()]);

        $I->assertInstanceOf(UserResource::class, $resource);
        $I->assertSame($user->getId(), $resource->getId());
        $I->assertSame('user@example.com', $resource->getEmail());
        $I->assertSame(['ROLE_ADMIN', 'ROLE_USER'], $resource->getRoles());
    }

    public function testReturnsNullForUnknownUser(UnitTester $I): void
    {
        $repository = Stub::makeEmpty(UserRepositoryInterface::class, [
            'findOneBy' => Expected::once(null),
        ]);
        $provider = new UserProvider($repository, new Pagination(), Stub::makeEmpty(Security::class));

        $I->assertNull($provider->provide(new Get(), ['id' => Uuid::v4()]));
    }

    public function testProvidesAuthenticatedUserForMeOperation(UnitTester $I): void
    {
        $user = $this->createUser('user@example.com');
        $repository = Stub::makeEmpty(UserRepositoryInterface::class);
        $security = Stub::makeEmpty(Security::class, ['getUser' => Expected::once($user)]);
        $provider = new UserProvider($repository, new Pagination(), $security);

        $resource = $provider->provide(new Get(uriTemplate: '/me', name: 'me'));

        $I->assertInstanceOf(UserResource::class, $resource);
        $I->assertSame($user->getId(), $resource->getId());
        $I->assertSame('user@example.com', $resource->getEmail());
    }

    public function testReturnsNullForMeWithoutAuthenticatedUser(UnitTester $I): void
    {
        $repository = Stub::makeEmpty(UserRepositoryInterface::class);
        $security = Stub::makeEmpty(Security::class, ['getUser' => Expected::once(null)]);
        $provider = new UserProvider($repository, new Pagination(), $security);

        $I->assertNull($provider->provide(new Get(uriTemplate: '/me', name: 'me')));
    }

    public function testProvidesSortedUnpaginatedCollection(UnitTester $I): void
    {
        $first = $this->createUser('first@example.com');
        $second = $this->createUser('second@example.com');
        $repository = Stub::makeEmpty(UserRepositoryInterface::class, [
            'findBy' => Expected::once([$first, $second]),
        ]);
        $pagination = new Pagination(['enabled' => false]);
        $provider = new UserProvider($repository, $pagination, Stub::makeEmpty(Security::class));

        $resources = $provider->provide(new GetCollection());

        $I->assertCount(2, $resources);
        $I->assertSame('first@example.com', $resources[0]->getEmail());
        $I->assertSame('second@example.com', $resources[1]->getEmail());
    }

    public function testProvidesPaginatedCollection(UnitTester $I): void
    {
        $user = $this->createUser('user@example.com');
        $repository = Stub::makeEmpty(UserRepositoryInterface::class, [
            'findBy' => Expected::once([$user]),
            'count' => Expected::once(3),
        ]);
        $pagination = new Pagination([
            'client_items_per_page' => true,
            'items_per_page_parameter_name' => 'items',
        ]);
        $provider = new UserProvider($repository, $pagination, Stub::makeEmpty(Security::class));

        $result = $provider->provide(new GetCollection(), [], ['filters' => ['items' => 1]]);

        $I->assertInstanceOf(TraversablePaginator::class, $result);
        $I->assertSame(1.0, $result->getCurrentPage());
        $I->assertSame(1.0, $result->getItemsPerPage());
        $I->assertSame(3.0, $result->getTotalItems());
        $I->assertCount(1, iterator_to_array($result));
    }

    public function testMapsRolesAsAList(UnitTester $I): void
    {
        $user = $this->createUser('user@example.com', [2 => 'ROLE_ADMIN', 5 => 'ROLE_USER']);
        $repository = Stub::makeEmpty(UserRepositoryInterface::class, [
            'findOneBy' => Expected::once($user),
        ]);
        $provider = new UserProvider($repository, new Pagination(), Stub::makeEmpty(Security::class));

        $resource = $provider->provide(new Get(), ['id' => $user->getId()]);

        $I->assertSame(['ROLE_ADMIN', 'ROLE_USER'], $resource->getRoles());
    }

    public function testUserWithoutIdentifierIsRejected(UnitTester $I): void
    {
        $user = (new User())->setEmail('user@example.com')->setPassword('hashed-password');
        $repository = Stub::makeEmpty(UserRepositoryInterface::class, ['findOneBy' => Expected::once($user)]);
        $provider = new UserProvider($repository, new Pagination(), Stub::makeEmpty(Security::class));

        $I->expectThrowable(\LogicException::class, static function () use ($provider): void {
            $provider->provide(new Get(), ['id' => Uuid::v4()]);
        });
    }

    public function testUserWithoutEmailIsRejected(UnitTester $I): void
    {
        $user = $this->createUser('user@example.com');
        $email = (new \ReflectionClass($user))->getProperty('email');
        $email->setValue($user, null);
        $repository = Stub::makeEmpty(UserRepositoryInterface::class, ['findOneBy' => Expected::once($user)]);
        $provider = new UserProvider($repository, new Pagination(), Stub::makeEmpty(Security::class));

        $I->expectThrowable(\LogicException::class, static function () use ($provider, $user): void {
            $provider->provide(new Get(), ['id' => $user->getId()]);
        });
    }

    public function testZeroLimitProducesEmptyPaginatedPage(UnitTester $I): void
    {
        $repository = Stub::makeEmpty(UserRepositoryInterface::class, [
            'count' => Expected::once(3),
        ]);
        $pagination = new Pagination([
            'client_items_per_page' => true,
            'items_per_page_parameter_name' => 'items',
        ]);
        $provider = new UserProvider($repository, $pagination, Stub::makeEmpty(Security::class));

        $result = $provider->provide(new GetCollection(), [], ['filters' => ['items' => 0]]);

        $I->assertInstanceOf(TraversablePaginator::class, $result);
        $I->assertSame([], iterator_to_array($result));
        $I->assertSame(3.0, $result->getTotalItems());
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
}
