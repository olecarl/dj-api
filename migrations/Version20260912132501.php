<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260912132501 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add schema.org music resources and ordered playlist tracks';
    }

    public function up(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform() instanceof SQLitePlatform) {
            $this->addSql('CREATE TABLE music_album (id BLOB NOT NULL, name VARCHAR(255) NOT NULL, url VARCHAR(2048) DEFAULT NULL, PRIMARY KEY (id))');
            $this->addSql('CREATE TABLE music_group (id BLOB NOT NULL, name VARCHAR(255) NOT NULL, url VARCHAR(2048) DEFAULT NULL, PRIMARY KEY (id))');
            $this->addSql('CREATE TABLE music_playlist (id BLOB NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY (id))');
            $this->addSql('CREATE TABLE music_recording (id BLOB NOT NULL, name VARCHAR(255) NOT NULL, duration VARCHAR(50) DEFAULT NULL, url VARCHAR(2048) DEFAULT NULL, album_position INTEGER DEFAULT NULL, album_id BLOB DEFAULT NULL, PRIMARY KEY (id), CONSTRAINT FK_6999BDF21137ABCF FOREIGN KEY (album_id) REFERENCES music_album (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
            $this->addSql('CREATE INDEX IDX_6999BDF21137ABCF ON music_recording (album_id)');
            $this->addSql('CREATE TABLE music_group_album (album_id BLOB NOT NULL, group_id BLOB NOT NULL, PRIMARY KEY (album_id, group_id), CONSTRAINT FK_C7C42C371137ABCF FOREIGN KEY (album_id) REFERENCES music_album (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_C7C42C37FE54D947 FOREIGN KEY (group_id) REFERENCES music_group (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
            $this->addSql('CREATE INDEX IDX_C7C42C371137ABCF ON music_group_album (album_id)');
            $this->addSql('CREATE INDEX IDX_C7C42C37FE54D947 ON music_group_album (group_id)');
            $this->addSql('CREATE TABLE music_group_recording (recording_id BLOB NOT NULL, group_id BLOB NOT NULL, PRIMARY KEY (recording_id, group_id), CONSTRAINT FK_601B88758CA9A845 FOREIGN KEY (recording_id) REFERENCES music_recording (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_601B8875FE54D947 FOREIGN KEY (group_id) REFERENCES music_group (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
            $this->addSql('CREATE INDEX IDX_601B88758CA9A845 ON music_group_recording (recording_id)');
            $this->addSql('CREATE INDEX IDX_601B8875FE54D947 ON music_group_recording (group_id)');
            $this->addSql('CREATE TABLE music_playlist_track (id BLOB NOT NULL, position INTEGER NOT NULL, playlist_id BLOB NOT NULL, recording_id BLOB NOT NULL, PRIMARY KEY (id), CONSTRAINT FK_8FA55DB06BBD148 FOREIGN KEY (playlist_id) REFERENCES music_playlist (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_8FA55DB08CA9A845 FOREIGN KEY (recording_id) REFERENCES music_recording (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
            $this->addSql('CREATE UNIQUE INDEX UNIQ_MUSIC_PLAYLIST_POSITION ON music_playlist_track (playlist_id, position)');
            $this->addSql('CREATE INDEX IDX_8FA55DB06BBD148 ON music_playlist_track (playlist_id)');
            $this->addSql('CREATE INDEX IDX_8FA55DB08CA9A845 ON music_playlist_track (recording_id)');

            return;
        }

        $this->addSql('CREATE TABLE music_album (id UUID NOT NULL, name VARCHAR(255) NOT NULL, url VARCHAR(2048) DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE music_group_album (album_id UUID NOT NULL, group_id UUID NOT NULL, PRIMARY KEY (album_id, group_id))');
        $this->addSql('CREATE INDEX IDX_C7C42C371137ABCF ON music_group_album (album_id)');
        $this->addSql('CREATE INDEX IDX_C7C42C37FE54D947 ON music_group_album (group_id)');
        $this->addSql('CREATE TABLE music_group (id UUID NOT NULL, name VARCHAR(255) NOT NULL, url VARCHAR(2048) DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE music_playlist (id UUID NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE music_playlist_track (id UUID NOT NULL, position INT NOT NULL, playlist_id UUID NOT NULL, recording_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_MUSIC_PLAYLIST_POSITION ON music_playlist_track (playlist_id, position)');
        $this->addSql('CREATE INDEX IDX_8FA55DB06BBD148 ON music_playlist_track (playlist_id)');
        $this->addSql('CREATE INDEX IDX_8FA55DB08CA9A845 ON music_playlist_track (recording_id)');
        $this->addSql('CREATE TABLE music_recording (id UUID NOT NULL, name VARCHAR(255) NOT NULL, duration VARCHAR(50) DEFAULT NULL, url VARCHAR(2048) DEFAULT NULL, album_position INT DEFAULT NULL, album_id UUID DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_6999BDF21137ABCF ON music_recording (album_id)');
        $this->addSql('CREATE TABLE music_group_recording (recording_id UUID NOT NULL, group_id UUID NOT NULL, PRIMARY KEY (recording_id, group_id))');
        $this->addSql('CREATE INDEX IDX_601B88758CA9A845 ON music_group_recording (recording_id)');
        $this->addSql('CREATE INDEX IDX_601B8875FE54D947 ON music_group_recording (group_id)');
        $this->addSql('ALTER TABLE music_group_album ADD CONSTRAINT FK_C7C42C371137ABCF FOREIGN KEY (album_id) REFERENCES music_album (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE music_group_album ADD CONSTRAINT FK_C7C42C37FE54D947 FOREIGN KEY (group_id) REFERENCES music_group (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE music_playlist_track ADD CONSTRAINT FK_8FA55DB06BBD148 FOREIGN KEY (playlist_id) REFERENCES music_playlist (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE music_playlist_track ADD CONSTRAINT FK_8FA55DB08CA9A845 FOREIGN KEY (recording_id) REFERENCES music_recording (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE music_recording ADD CONSTRAINT FK_6999BDF21137ABCF FOREIGN KEY (album_id) REFERENCES music_album (id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('ALTER TABLE music_group_recording ADD CONSTRAINT FK_601B88758CA9A845 FOREIGN KEY (recording_id) REFERENCES music_recording (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE music_group_recording ADD CONSTRAINT FK_601B8875FE54D947 FOREIGN KEY (group_id) REFERENCES music_group (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform() instanceof SQLitePlatform) {
            $this->addSql('DROP TABLE music_playlist_track');
            $this->addSql('DROP TABLE music_group_recording');
            $this->addSql('DROP TABLE music_group_album');
            $this->addSql('DROP TABLE music_recording');
            $this->addSql('DROP TABLE music_playlist');
            $this->addSql('DROP TABLE music_group');
            $this->addSql('DROP TABLE music_album');

            return;
        }

        $this->addSql('ALTER TABLE music_group_album DROP CONSTRAINT FK_C7C42C371137ABCF');
        $this->addSql('ALTER TABLE music_group_album DROP CONSTRAINT FK_C7C42C37FE54D947');
        $this->addSql('ALTER TABLE music_playlist_track DROP CONSTRAINT FK_8FA55DB06BBD148');
        $this->addSql('ALTER TABLE music_playlist_track DROP CONSTRAINT FK_8FA55DB08CA9A845');
        $this->addSql('ALTER TABLE music_recording DROP CONSTRAINT FK_6999BDF21137ABCF');
        $this->addSql('ALTER TABLE music_group_recording DROP CONSTRAINT FK_601B88758CA9A845');
        $this->addSql('ALTER TABLE music_group_recording DROP CONSTRAINT FK_601B8875FE54D947');
        $this->addSql('DROP TABLE music_album');
        $this->addSql('DROP TABLE music_group_album');
        $this->addSql('DROP TABLE music_group');
        $this->addSql('DROP TABLE music_playlist');
        $this->addSql('DROP TABLE music_playlist_track');
        $this->addSql('DROP TABLE music_recording');
        $this->addSql('DROP TABLE music_group_recording');
    }
}
