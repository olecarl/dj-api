<?php

declare(strict_types=1);

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Support;

use Codeception\Actor;

/**
 * Inherited Methods.
 *
 * @method void wantTo(string $text)
 * @method void wantToTest(string $text)
 * @method void execute(callable $callable)
 * @method void expectTo(string $prediction)
 * @method void expect(string $prediction)
 * @method void amGoingTo(string $argumentation)
 * @method void am(string $role)
 * @method void lookForwardTo(string $achieveValue)
 * @method void comment(string $description)
 * @method void pause(array $vars = [])
 */
final class AcceptanceTester extends Actor
{
    use _generated\AcceptanceTesterActions;
}
