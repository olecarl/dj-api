<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\User;
use App\Entity\UserProfile;
use App\Tests\Support\UnitTester;

final class UserProfileCest
{
    public function testNewProfileHasNoIdentityOrUser(UnitTester $I): void
    {
        $profile = new UserProfile();

        $I->assertNull($profile->getId());
        $I->assertNull($profile->getUser());
        $I->assertNull($profile->getAbout());
    }

    public function testProfileStoresUserAndAbout(UnitTester $I): void
    {
        $user = new User();
        $profile = new UserProfile();

        $I->assertSame($profile, $profile->setUser($user));
        $I->assertSame($profile, $profile->setAbout('Hello, world!'));

        $I->assertSame($user, $profile->getUser());
        $I->assertSame('Hello, world!', $profile->getAbout());
    }

    public function testAboutCanBeCleared(UnitTester $I): void
    {
        $profile = (new UserProfile())->setAbout('Some text');

        $I->assertSame($profile, $profile->setAbout(null));
        $I->assertNull($profile->getAbout());
    }

    public function testUserKnowsItsProfile(UnitTester $I): void
    {
        $user = new User();
        $profile = (new UserProfile())->setUser($user);

        $I->assertSame($profile, $user->setUserProfile($profile)->getUserProfile());
        $I->assertSame($user, $profile->getUser());
    }
}
