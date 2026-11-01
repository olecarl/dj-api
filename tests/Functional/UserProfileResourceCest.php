<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Tests\Support\FunctionalTester;

final class UserProfileResourceCest
{
    public function testAuthenticatedUserCanReadOwnProfile(FunctionalTester $I): void
    {
        $user = $I->haveUserWithProfile('user@example.com', 'correct horse battery staple', 'Hello, world!');
        $I->amLoggedInAs($user, 'api');
        $I->sendGet('/users/'.$user->getId().'/profile');

        $I->seeResponseCodeIs(200);
        $response = json_decode($I->grabResponse(), true, 512, \JSON_THROW_ON_ERROR);

        $I->assertSame((string) $user->getUserProfile()->getId(), $response['id']);
        $I->assertSame('Hello, world!', $response['about']);
        $I->assertSame('user@example.com', $response['user']['email']);
        $I->assertSame((string) $user->getId(), $response['user']['id']);
        $I->assertArrayNotHasKey('password', $response['user']);
    }

    public function testAuthenticatedUserCannotReadAnotherUsersProfile(FunctionalTester $I): void
    {
        $user = $I->haveUserWithProfile('user@example.com', 'correct horse battery staple', 'Hello, world!');
        $otherUser = $I->haveUser('other@example.com', 'correct horse battery staple');
        $I->amLoggedInAs($otherUser, 'api');
        $I->sendGet('/users/'.$user->getId().'/profile');

        $I->seeResponseCodeIs(403);
    }

    public function testAdministratorCanReadAnyProfile(FunctionalTester $I): void
    {
        $admin = $I->haveUser('admin@example.com', 'correct horse battery staple', ['ROLE_ADMIN']);
        $user = $I->haveUserWithProfile('user@example.com', 'correct horse battery staple', 'Hello, world!');
        $I->amLoggedInAs($admin, 'api');
        $I->sendGet('/users/'.$user->getId().'/profile');

        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson([
            'id' => (string) $user->getUserProfile()->getId(),
            'about' => 'Hello, world!',
            'user' => ['id' => (string) $user->getId(), 'email' => 'user@example.com'],
        ]);
    }

    public function testAnonymousUserCannotReadProfile(FunctionalTester $I): void
    {
        $user = $I->haveUserWithProfile('user@example.com', 'correct horse battery staple', 'Hello, world!');
        $I->sendGet('/users/'.$user->getId().'/profile');

        $I->seeResponseCodeIs(401);
    }

    public function testProfileOfMissingUserIsNotFound(FunctionalTester $I): void
    {
        $user = $I->haveUser('user@example.com', 'correct horse battery staple');
        $I->amLoggedInAs($user, 'api');
        $I->sendGet('/users/00000000-0000-4000-8000-000000000000/profile');

        $I->seeResponseCodeIs(404);
    }

    public function testUserWithoutProfileIsNotFound(FunctionalTester $I): void
    {
        $user = $I->haveUser('user@example.com', 'correct horse battery staple');
        $I->amLoggedInAs($user, 'api');
        $I->sendGet('/users/'.$user->getId().'/profile');

        $I->seeResponseCodeIs(404);
    }

    public function testProfileIriUsesCanonicalPath(FunctionalTester $I): void
    {
        $user = $I->haveUserWithProfile('user@example.com', 'correct horse battery staple', 'Hello, world!');
        $I->amLoggedInAs($user, 'api');
        $I->haveHttpHeader('Accept', 'application/ld+json');
        $I->sendGet('/users/'.$user->getId().'/profile');

        $I->seeResponseCodeIs(200);
        $response = json_decode($I->grabResponse(), true, 512, \JSON_THROW_ON_ERROR);

        $I->assertSame('/users/'.$user->getId().'/profile', $response['@id']);
    }
}
