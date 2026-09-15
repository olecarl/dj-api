<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\MusicRecordingRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/** @psalm-api */
#[ORM\Entity(repositoryClass: MusicRecordingRepository::class)]
#[ORM\Table(name: 'music_recording')]
final class MusicRecording
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    // @phpstan-ignore-next-line property.unusedType
    private ?Uuid $id = null;

    #[ORM\Column(length: 255)]
    private string $name = '';

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $duration = null;

    #[ORM\Column(length: 2048, nullable: true)]
    private ?string $url = null;

    #[ORM\ManyToOne(targetEntity: MusicAlbum::class, inversedBy: 'tracks')]
    #[ORM\JoinColumn(name: 'album_id', nullable: true, onDelete: 'SET NULL')]
    private ?MusicAlbum $album = null;

    #[ORM\Column(nullable: true)]
    private ?int $albumPosition = null;

    /** @var Collection<int, MusicGroup> */
    #[ORM\ManyToMany(targetEntity: MusicGroup::class, inversedBy: 'recordings')]
    #[ORM\JoinTable(name: 'music_group_recording')]
    #[ORM\JoinColumn(name: 'recording_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'group_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Collection $artists;

    public function __construct()
    {
        $this->artists = new ArrayCollection();
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

    public function getDuration(): ?string
    {
        return $this->duration;
    }

    public function setDuration(?string $duration): static
    {
        $this->duration = $duration;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function getAlbum(): ?MusicAlbum
    {
        return $this->album;
    }

    public function setAlbum(?MusicAlbum $album): static
    {
        $this->album = $album;

        return $this;
    }

    public function getAlbumPosition(): ?int
    {
        return $this->albumPosition;
    }

    public function setAlbumPosition(?int $albumPosition): static
    {
        $this->albumPosition = $albumPosition;

        return $this;
    }

    /** @return Collection<int, MusicGroup> */
    public function getArtists(): Collection
    {
        return $this->artists;
    }

    public function addArtist(MusicGroup $artist): static
    {
        if (!$this->artists->contains($artist)) {
            $this->artists->add($artist);
        }

        return $this;
    }
}
