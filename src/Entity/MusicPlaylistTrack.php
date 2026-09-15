<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/** @psalm-api */
#[ORM\Entity]
#[ORM\Table(name: 'music_playlist_track')]
#[ORM\UniqueConstraint(name: 'UNIQ_MUSIC_PLAYLIST_POSITION', columns: ['playlist_id', 'position'])]
final class MusicPlaylistTrack
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    // @phpstan-ignore-next-line property.unusedType
    private ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: MusicPlaylist::class, inversedBy: 'tracks')]
    #[ORM\JoinColumn(name: 'playlist_id', nullable: false, onDelete: 'CASCADE')]
    private MusicPlaylist $playlist;

    #[ORM\ManyToOne(targetEntity: MusicRecording::class)]
    #[ORM\JoinColumn(name: 'recording_id', nullable: false, onDelete: 'CASCADE')]
    private MusicRecording $recording;

    #[ORM\Column]
    private int $position;

    public function __construct(MusicPlaylist $playlist, MusicRecording $recording, int $position)
    {
        $this->playlist = $playlist;
        $this->recording = $recording;
        $this->position = $position;
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getPlaylist(): MusicPlaylist
    {
        return $this->playlist;
    }

    public function getRecording(): MusicRecording
    {
        return $this->recording;
    }

    public function getPosition(): int
    {
        return $this->position;
    }
}
