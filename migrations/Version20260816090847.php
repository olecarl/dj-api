<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\Uid\Uuid;

/**
 * Create a user profile for every existing user.
 */
final class Version20260816090847 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create a user profile for every existing user';
    }

    public function up(Schema $schema): void
    {
        $userIds = $this->connection->fetchFirstColumn('SELECT id FROM "user"');
        $now = new \DateTimeImmutable();

        foreach ($userIds as $userId) {
            $this->addSql(
                'INSERT INTO user_profile (id, user_id, about, created_at, updated_at) VALUES (?, ?, ?, ?, ?)',
                [
                    (string) Uuid::v7(),
                    $userId,
                    null,
                    $now,
                    $now,
                ],
                [
                    Types::STRING,
                    Types::STRING,
                    Types::TEXT,
                    Types::DATETIME_IMMUTABLE,
                    Types::DATETIME_IMMUTABLE,
                ],
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM user_profile');
    }
}
