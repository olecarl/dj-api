<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\MusicPlaylistRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/** @psalm-api */
#[ORM\Entity(repositoryClass: MusicPlaylistRepository::class)]
#[ORM\Table(name: 'music_playlist')]
final class MusicPlaylist
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    // @phpstan-ignore-next-line property.unusedType
    private ?Uuid $id = null;

    #[ORM\Column(length: 255)]
    private string $name = '';

    /** @var Collection<int, MusicPlaylistTrack> */
    #[ORM\OneToMany(mappedBy: 'playlist', targetEntity: MusicPlaylistTrack::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $tracks;

    public function __construct()
    {
        $this->tracks = new ArrayCollection();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /** @return Collection<int, MusicPlaylistTrack> */
    public function getTracks(): Collection
    {
        return $this->tracks;
    }

    public function addTrack(MusicRecording $recording, int $position): static
    {
        $this->tracks->add(new MusicPlaylistTrack($this, $recording, $position));

        return $this;
    }
}
