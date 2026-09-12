<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Create the user profile table.
 */
final class Version20260816065030 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add user profile entity with a one-to-one relation to users';
    }

    public function up(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform() instanceof SQLitePlatform) {
            $this->addSql('CREATE TABLE user_profile (id BLOB NOT NULL, about TEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, user_id BLOB NOT NULL, PRIMARY KEY (id), CONSTRAINT FK_D95AB405A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
            $this->addSql('CREATE UNIQUE INDEX UNIQ_D95AB405A76ED395 ON user_profile (user_id)');

            return;
        }

        $this->addSql('CREATE TABLE user_profile (id UUID NOT NULL, about TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, user_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D95AB405A76ED395 ON user_profile (user_id)');
        $this->addSql('ALTER TABLE user_profile ADD CONSTRAINT FK_D95AB405A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform() instanceof SQLitePlatform) {
            $this->addSql('DROP TABLE user_profile');

            return;
        }

        $this->addSql('ALTER TABLE user_profile DROP CONSTRAINT FK_D95AB405A76ED395');
        $this->addSql('DROP TABLE user_profile');
    }
}
