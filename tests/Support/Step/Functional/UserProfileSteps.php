<?php

declare(strict_types=1);

namespace App\Tests\Support\Step\Functional;

use App\Entity\User;
use App\Tests\Support\FunctionalTester;
use Codeception\Attribute\Given;
use Codeception\Attribute\Then;
use Codeception\Attribute\When;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

final class UserProfileSteps
{
    /** @var array<string, User> */
    private array $users = [];

    private ?string $authenticatedEmail = null;

    private string $responseFormat = 'application/ld+json';

    public function __construct(private readonly FunctionalTester $I)
    {
    }

    #[Given('a user exists with email :email')]
    public function aUserExistsWithEmail(string $email): void
    {
        $this->users[$email] = $this->I->haveUser($email);
    }

    #[Given('another user exists with email :email')]
    public function anotherUserExistsWithEmail(string $email): void
    {
        $this->users[$email] = $this->I->haveUser($email);
    }

    #[Given('an administrator exists with email :email')]
    public function anAdministratorExistsWithEmail(string $email): void
    {
        $this->users[$email] = $this->I->haveUser($email, roles: ['ROLE_ADMIN']);
    }

    #[Given('I am authenticated as :email')]
    public function iAmAuthenticatedAs(string $email): void
    {
        $user = $this->users[$email] ?? throw new \LogicException('The test user must exist before authentication.');
        $token = $this->I->grabService(JWTTokenManagerInterface::class)->create($user);

        $this->I->haveHttpHeader('Authorization', 'Bearer '.$token);
        $this->authenticatedEmail = $email;
    }

    #[Given('I request the response as :contentType')]
    public function iRequestTheResponseAs(string $contentType): void
    {
        $this->I->haveHttpHeader('Accept', $contentType);
        $this->responseFormat = $contentType;
    }

    #[When('I send a GET request to :path')]
    public function iSendAGetRequestTo(string $path): void
    {
        $this->I->sendGet($path);
    }

    #[Then('the response status code should be :status')]
    public function theResponseStatusCodeShouldBe(string $status): void
    {
        $this->I->seeResponseCodeIs((int) $status);
    }

    #[Then('the response should contain the profile for :email')]
    public function theResponseShouldContainTheProfileFor(string $email): void
    {
        $this->I->seeResponseContainsJson(['email' => $email]);
    }

    #[Then('the response should not contain the profile for :email')]
    public function theResponseShouldNotContainTheProfileFor(string $email): void
    {
        $response = $this->responseBody();
        $this->I->assertNotSame($email, $response['email'] ?? null);
    }

    #[Then('the response should contain the user\'s ID')]
    public function theResponseShouldContainTheUsersId(): void
    {
        $this->I->seeResponseContainsJson(['id' => (string) $this->authenticatedUser()->getId()]);
    }

    #[Then('the response should contain the user\'s roles')]
    public function theResponseShouldContainTheUsersRoles(): void
    {
        $this->I->seeResponseContainsJson(['roles' => ['ROLE_USER']]);
    }

    #[Then('the response should not contain a password')]
    public function theResponseShouldNotContainAPassword(): void
    {
        $this->I->assertArrayNotHasKey('password', $this->responseBody());
    }

    #[Then('the response self link should be :expected')]
    public function theResponseSelfLinkShouldBe(string $expected): void
    {
        $userId = (string) $this->authenticatedUser()->getId();
        $expected = str_replace('<user_id>', $userId, $expected);
        $response = $this->responseBody();

        $selfLink = match ($this->responseFormat) {
            'application/ld+json' => $response['@id'] ?? null,
            'application/hal+json' => $response['_links']['self']['href'] ?? null,
            'application/vnd.api+json' => $response['data']['id'] ?? null,
            default => null,
        };

        $this->I->assertIsString($selfLink);
        $this->I->assertStringEndsWith($expected, $selfLink);
    }

    private function authenticatedUser(): User
    {
        $email = $this->authenticatedEmail ?? throw new \LogicException('The test user must be authenticated first.');

        return $this->users[$email] ?? throw new \LogicException('The authenticated test user is not available.');
    }

    /** @return array<string, mixed> */
    private function responseBody(): array
    {
        $response = json_decode($this->I->grabResponse(), true, 512, \JSON_THROW_ON_ERROR);
        if (!\is_array($response)) {
            throw new \UnexpectedValueException('The API response must be a JSON object.');
        }

        return $response;
    }
}
