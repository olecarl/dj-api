<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity\Trait;

use App\Entity\Trait\TimestampableTrait;
use App\Tests\Support\UnitTester;

final class TimestampableTraitCest
{
    public function testGetCreatedAtReturnsNullInitially(UnitTester $I): void
    {
        $I->wantToTest('that createdAt is null before entity is persisted');

        $entity = $this->createEntityWithTrait();

        $I->assertNull($entity->getCreatedAt());
    }

    public function testGetUpdatedAtReturnsNullInitially(UnitTester $I): void
    {
        $I->wantToTest('that updatedAt is null before entity is persisted');

        $entity = $this->createEntityWithTrait();

        $I->assertNull($entity->getUpdatedAt());
    }

    public function testCreatedAtReturnsDateTimeImmutableWhenSet(UnitTester $I): void
    {
        $I->wantToTest('that createdAt returns DateTimeImmutable when set via reflection');

        $entity = $this->createEntityWithTrait();
        $now = new \DateTimeImmutable();

        $this->setPrivateProperty($entity, 'createdAt', $now);

        $I->assertSame($now, $entity->getCreatedAt());
        $I->assertInstanceOf(\DateTimeImmutable::class, $entity->getCreatedAt());
    }

    public function testUpdatedAtReturnsDateTimeImmutableWhenSet(UnitTester $I): void
    {
        $I->wantToTest('that updatedAt returns DateTimeImmutable when set via reflection');

        $entity = $this->createEntityWithTrait();
        $now = new \DateTimeImmutable();

        $this->setPrivateProperty($entity, 'updatedAt', $now);

        $I->assertSame($now, $entity->getUpdatedAt());
        $I->assertInstanceOf(\DateTimeImmutable::class, $entity->getUpdatedAt());
    }

    /**
     * Creates an anonymous class that uses the TimestampableTrait for testing.
     */
    private function createEntityWithTrait(): object
    {
        return new class {
            use TimestampableTrait;
        };
    }

    /**
     * Sets a private property value using reflection.
     */
    private function setPrivateProperty(object $object, string $property, mixed $value): void
    {
        $reflection = new \ReflectionClass($object);
        $prop = $reflection->getProperty($property);
        $prop->setValue($object, $value);
    }
}
