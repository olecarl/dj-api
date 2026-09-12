<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\User;
use App\Tests\Support\FunctionalTester;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class ApiContractCest
{
    public function testCollectionContainsExpectedFieldsAndNoPassword(FunctionalTester $I): void
    {
        $admin = $this->createUser($I, 'admin@example.com', 'correct horse battery staple', ['ROLE_ADMIN']);
        $this->createUser($I, 'user@example.com', 'correct horse battery staple');
        $I->amLoggedInAs($admin, 'api');
        $I->sendGet('/users');

        $I->seeResponseCodeIs(200);
        $response = json_decode($I->grabResponse(), true, 512, \JSON_THROW_ON_ERROR);
        $I->assertSame(2, $response['totalItems']);
        $I->assertCount(2, $response['member']);
        foreach ($response['member'] as $member) {
            $I->assertArrayHasKey('id', $member);
            $I->assertArrayHasKey('email', $member);
            $I->assertArrayHasKey('roles', $member);
            $I->assertArrayNotHasKey('password', $member);
        }
    }

    public function testEmptyCollectionHasZeroItems(FunctionalTester $I): void
    {
        $admin = $this->createUser($I, 'admin@example.com', 'correct horse battery staple', ['ROLE_ADMIN']);
        $I->amLoggedInAs($admin, 'api');
        $this->deleteAllUsers($I);
        $I->sendGet('/users');

        $I->seeResponseCodeIs(200);
        $response = json_decode($I->grabResponse(), true, 512, \JSON_THROW_ON_ERROR);
        $I->assertSame([], $response['member']);
        $I->assertSame(0, $response['totalItems']);
    }

    public function testSecondPageReturnsDifferentItem(FunctionalTester $I): void
    {
        $admin = $this->createUser($I, 'admin@example.com', 'correct horse battery staple', ['ROLE_ADMIN']);
        $this->createUser($I, 'first@example.com', 'correct horse battery staple');
        $this->createUser($I, 'second@example.com', 'correct horse battery staple');
        $I->amLoggedInAs($admin, 'api');

        $I->sendGet('/users?items=1&page=2');

        $I->seeResponseCodeIs(200);
        $response = json_decode($I->grabResponse(), true, 512, \JSON_THROW_ON_ERROR);
        $I->assertCount(1, $response['member']);
        $I->assertSame(3, $response['totalItems']);
    }

    public function testPageBeyondCollectionIsEmpty(FunctionalTester $I): void
    {
        $admin = $this->createUser($I, 'admin@example.com', 'correct horse battery staple', ['ROLE_ADMIN']);
        $this->createUser($I, 'user@example.com', 'correct horse battery staple');
        $I->amLoggedInAs($admin, 'api');
        $I->sendGet('/users?items=1&page=3');

        $I->seeResponseCodeIs(200);
        $response = json_decode($I->grabResponse(), true, 512, \JSON_THROW_ON_ERROR);
        $I->assertSame([], $response['member']);
        $I->assertSame(2, $response['totalItems']);
    }

    public function testPaginationMaximumIsEnforced(FunctionalTester $I): void
    {
        $admin = $this->createUser($I, 'admin@example.com', 'correct horse battery staple', ['ROLE_ADMIN']);
        for ($index = 1; $index <= 55; ++$index) {
            $this->createUser($I, \sprintf('user%d@example.com', $index), 'correct horse battery staple');
        }
        $I->amLoggedInAs($admin, 'api');
        $I->sendGet('/users?items=51');

        $I->seeResponseCodeIs(200);
        $response = json_decode($I->grabResponse(), true, 512, \JSON_THROW_ON_ERROR);
        $I->assertCount(50, $response['member']);
        $I->assertSame(56, $response['totalItems']);
    }

    public function testZeroItemsReturnsEmptyPage(FunctionalTester $I): void
    {
        $admin = $this->createUser($I, 'admin@example.com', 'correct horse battery staple', ['ROLE_ADMIN']);
        $I->amLoggedInAs($admin, 'api');
        $I->sendGet('/users?items=0');

        $I->seeResponseCodeIs(200);
        $response = json_decode($I->grabResponse(), true, 512, \JSON_THROW_ON_ERROR);
        $I->assertSame([], $response['member']);
        $I->assertSame(1, $response['totalItems']);
    }

    public function testNegativeItemsAreRejected(FunctionalTester $I): void
    {
        $admin = $this->createUser($I, 'admin@example.com', 'correct horse battery staple', ['ROLE_ADMIN']);
        $I->amLoggedInAs($admin, 'api');
        $I->sendGet('/users?items=-1');

        $I->seeResponseCodeIs(400);
    }

    public function testApiSupportsConfiguredResponseFormats(FunctionalTester $I): void
    {
        $admin = $this->createUser($I, 'admin@example.com', 'correct horse battery staple', ['ROLE_ADMIN']);
        $formats = [
            'application/ld+json',
            'application/hal+json',
            'application/vnd.api+json',
            'application/json',
            'application/xml',
            'application/x-yaml',
        ];
        $I->amLoggedInAs($admin, 'api');
        $token = $I->grabService(JWTTokenManagerInterface::class)->create($admin);
        $I->haveHttpHeader('Authorization', 'Bearer '.$token);

        foreach ($formats as $format) {
            $I->haveHttpHeader('Accept', $format);
            $I->sendGet('/users');

            $I->seeResponseCodeIs(200);
            $contentType = $I->grabHttpHeader('Content-Type');
            $I->assertIsString($contentType);
            $I->assertStringContainsString($format, $contentType);
        }
    }

    public function testUnsupportedResponseFormatIsRejected(FunctionalTester $I): void
    {
        $admin = $this->createUser($I, 'admin@example.com', 'correct horse battery staple', ['ROLE_ADMIN']);
        $I->amLoggedInAs($admin, 'api');
        $I->haveHttpHeader('Accept', 'application/octet-stream');
        $I->sendGet('/users');

        $I->seeResponseCodeIs(406);
    }

    public function testCorsRejectsUnconfiguredOrigin(FunctionalTester $I): void
    {
        $I->haveHttpHeader('Origin', 'https://evil.example');
        $I->haveHttpHeader('Access-Control-Request-Method', 'GET');
        $I->sendOptions('/users');

        $I->seeResponseCodeIs(200);
        $I->dontSeeHttpHeader('Access-Control-Allow-Origin');
    }

    public function testOpenApiUsesVersionlessPaths(FunctionalTester $I): void
    {
        $admin = $this->createUser($I, 'admin@example.com', 'correct horse battery staple', ['ROLE_ADMIN']);
        $I->amLoggedInAs($admin, 'api');
        $I->sendGet('/docs.jsonopenapi');

        $I->seeResponseCodeIs(200);
        $response = json_decode($I->grabResponse(), true, 512, \JSON_THROW_ON_ERROR);
        $I->assertArrayHasKey('/users', $response['paths']);
        $I->assertArrayNotHasKey('/v1/users', $response['paths']);
    }

    public function testOpenApiDocumentsUserProfileSubresource(FunctionalTester $I): void
    {
        $admin = $this->createUser($I, 'admin@example.com', 'correct horse battery staple', ['ROLE_ADMIN']);
        $I->amLoggedInAs($admin, 'api');
        $I->sendGet('/docs.jsonopenapi');

        $I->seeResponseCodeIs(200);
        $response = json_decode($I->grabResponse(), true, 512, \JSON_THROW_ON_ERROR);
        $I->assertArrayHasKey('/users/{userId}/profile', $response['paths']);
    }

    public function testOpenApiDocumentsMeWithoutAnIdentifier(FunctionalTester $I): void
    {
        $admin = $this->createUser($I, 'admin@example.com', 'correct horse battery staple', ['ROLE_ADMIN']);
        $I->amLoggedInAs($admin, 'api');
        $I->sendGet('/docs.jsonopenapi');

        $I->seeResponseCodeIs(200);
        $response = json_decode($I->grabResponse(), true, 512, \JSON_THROW_ON_ERROR);
        $operation = $response['paths']['/me']['get'];

        $I->assertSame('me', $operation['operationId']);
        $I->assertSame([], $operation['parameters']);
        $I->assertArrayHasKey('JWT', $response['components']['securitySchemes']);
    }

    public function testDocsArePubliclyAccessibleAndOldPathIsGone(FunctionalTester $I): void
    {
        $I->sendGet('/docs');
        $I->seeResponseCodeIs(200);

        $I->sendGet('/docs.jsonopenapi');
        $I->seeResponseCodeIs(200);

        $I->sendGet('/v1/docs');
        $I->seeResponseCodeIs(404);
    }

    public function testCorsPreflightAllowsConfiguredLocalOrigin(FunctionalTester $I): void
    {
        $I->haveHttpHeader('Origin', 'https://localhost');
        $I->haveHttpHeader('Access-Control-Request-Method', 'GET');
        $I->sendOptions('/users');

        $I->seeResponseCodeIs(200);
        $I->seeHttpHeader('Access-Control-Allow-Origin', 'https://localhost');
        $I->seeHttpHeader('Access-Control-Allow-Methods');
        $I->seeHttpHeader('Access-Control-Allow-Headers');
    }

    /**
     * @param list<string> $roles
     */
    private function createUser(FunctionalTester $I, string $email, string $password, array $roles = []): User
    {
        $user = (new User())->setEmail($email)->setRoles($roles);
        $user->setPassword($I->grabService(UserPasswordHasherInterface::class)->hashPassword($user, $password));

        $entityManager = $I->grabService(EntityManagerInterface::class);
        $entityManager->persist($user);
        $entityManager->flush();

        return $user;
    }

    private function deleteAllUsers(FunctionalTester $I): void
    {
        $entityManager = $I->grabService(EntityManagerInterface::class);
        foreach ($entityManager->getRepository(User::class)->findAll() as $user) {
            $entityManager->remove($user);
        }
        $entityManager->flush();
    }
}
