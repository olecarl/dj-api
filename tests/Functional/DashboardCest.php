<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Tests\Support\FunctionalTester;

final class DashboardCest
{
    public function testUnauthenticatedVisitorIsRedirectedToLogin(FunctionalTester $I): void
    {
        $I->amOnPage('/dashboard');

        $I->seeCurrentUrlEquals('/login');
        $I->see('Sign in');
    }

    public function testUserCanLoginAndSeeDashboard(FunctionalTester $I): void
    {
        $I->haveUser('user@example.com', 'correct horse battery staple');

        $I->amOnPage('/login');
        $I->submitForm('form', [
            '_username' => 'user@example.com',
            '_password' => 'correct horse battery staple',
        ]);

        $I->seeCurrentUrlEquals('/dashboard');
        $I->see('Signed in as user@example.com');
        $I->see('ROLE_USER');
    }

    public function testLoginReturnsUserToOriginallyRequestedDashboard(FunctionalTester $I): void
    {
        $I->haveUser('user@example.com', 'correct horse battery staple');

        $I->amOnPage('/dashboard');
        $I->submitForm('form', [
            '_username' => 'user@example.com',
            '_password' => 'correct horse battery staple',
        ]);

        $I->seeCurrentUrlEquals('/dashboard');
    }

    public function testInvalidCredentialsRedisplayLoginError(FunctionalTester $I): void
    {
        $I->haveUser('user@example.com', 'correct horse battery staple');

        $I->amOnPage('/login');
        $I->submitForm('form', [
            '_username' => 'user@example.com',
            '_password' => 'wrong password',
        ]);

        $I->seeCurrentUrlEquals('/login');
        $I->see('Invalid email or password.');
    }

    public function testLoginRequiresCsrfToken(FunctionalTester $I): void
    {
        $I->haveUser('user@example.com', 'correct horse battery staple');
        $I->sendPost('/login', [
            '_username' => 'user@example.com',
            '_password' => 'correct horse battery staple',
        ]);

        $I->amOnPage('/dashboard');

        $I->seeCurrentUrlEquals('/login');
    }

    public function testUserCanLogout(FunctionalTester $I): void
    {
        $I->haveUser('user@example.com', 'correct horse battery staple');

        $I->amOnPage('/login');
        $I->submitForm('form', [
            '_username' => 'user@example.com',
            '_password' => 'correct horse battery staple',
        ]);
        $I->click('Log out');

        $I->seeCurrentUrlEquals('/login');
        $I->amOnPage('/dashboard');
        $I->seeCurrentUrlEquals('/login');
    }

    public function testLogoutRequiresCsrfToken(FunctionalTester $I): void
    {
        $I->haveUser('user@example.com', 'correct horse battery staple');
        $I->amOnPage('/login');
        $I->submitForm('form', [
            '_username' => 'user@example.com',
            '_password' => 'correct horse battery staple',
        ]);

        $I->sendPost('/logout', []);
        $I->amOnPage('/dashboard');

        $I->see('Signed in as user@example.com');
    }
}
