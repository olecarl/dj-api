<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add timestamps to users.
 */
final class Version20260805193807 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add created and updated timestamps to users';
    }

    public function up(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform() instanceof SQLitePlatform) {
            $this->addSql('CREATE TABLE __user_new (id BLOB NOT NULL, email VARCHAR(180) NOT NULL, roles CLOB NOT NULL, password VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY (id))');
            $this->addSql('INSERT INTO __user_new (id, email, roles, password, created_at, updated_at) SELECT id, email, roles, password, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP FROM "user"');
            $this->addSql('DROP TABLE "user"');
            $this->addSql('ALTER TABLE __user_new RENAME TO "user"');
            $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON "user" (email)');

            return;
        }

        $this->addSql('ALTER TABLE "user" ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE "user" ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE "user" ALTER COLUMN created_at DROP DEFAULT');
        $this->addSql('ALTER TABLE "user" ALTER COLUMN updated_at DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform() instanceof SQLitePlatform) {
            $this->addSql('CREATE TABLE __user_old (id BLOB NOT NULL, email VARCHAR(180) NOT NULL, roles CLOB NOT NULL, password VARCHAR(255) NOT NULL, PRIMARY KEY (id))');
            $this->addSql('INSERT INTO __user_old (id, email, roles, password) SELECT id, email, roles, password FROM "user"');
            $this->addSql('DROP TABLE "user"');
            $this->addSql('ALTER TABLE __user_old RENAME TO "user"');
            $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON "user" (email)');

            return;
        }

        $this->addSql('ALTER TABLE "user" DROP COLUMN created_at');
        $this->addSql('ALTER TABLE "user" DROP COLUMN updated_at');
    }
}
