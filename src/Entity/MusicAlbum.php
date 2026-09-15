<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\MusicAlbumRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/** @psalm-api */
#[ORM\Entity(repositoryClass: MusicAlbumRepository::class)]
#[ORM\Table(name: 'music_album')]
final class MusicAlbum
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    // @phpstan-ignore-next-line property.unusedType
    private ?Uuid $id = null;

    #[ORM\Column(length: 255)]
    private string $name = '';

    #[ORM\Column(length: 2048, nullable: true)]
    private ?string $url = null;

    /** @var Collection<int, MusicGroup> */
    #[ORM\ManyToMany(targetEntity: MusicGroup::class, inversedBy: 'albums')]
    #[ORM\JoinTable(name: 'music_group_album')]
    #[ORM\JoinColumn(name: 'album_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'group_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Collection $artists;

    /** @var Collection<int, MusicRecording> */
    #[ORM\OneToMany(mappedBy: 'album', targetEntity: MusicRecording::class, cascade: ['persist'])]
    private Collection $tracks;

    public function __construct()
    {
        $this->artists = new ArrayCollection();
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

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): static
    {
        $this->url = $url;

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

    /** @return Collection<int, MusicRecording> */
    public function getTracks(): Collection
    {
        return $this->tracks;
    }
}
