<?php

declare(strict_types=1);

namespace App\Tests\Acceptance;

use App\Tests\Support\AcceptanceTester;

final class ApiSmokeCest
{
    public function testProtectedApiCollectionRequiresAuthentication(AcceptanceTester $I): void
    {
        $I->amOnPage('/users');

        $I->seeResponseCodeIsBetween(400, 401);
    }

    public function testLoginEndpointRemainsAvailable(AcceptanceTester $I): void
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendAjaxPostRequest('/login_check', []);

        $I->seeResponseCodeIsBetween(400, 401);
    }

    public function testVersionlessDocumentationRouteIsPubliclyAccessible(AcceptanceTester $I): void
    {
        $I->amOnPage('/docs');

        $I->seeResponseCodeIs(200);
    }
}
