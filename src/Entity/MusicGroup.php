<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\MusicGroupRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/** @psalm-api */
#[ORM\Entity(repositoryClass: MusicGroupRepository::class)]
#[ORM\Table(name: 'music_group')]
final class MusicGroup
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

    /** @var Collection<int, MusicAlbum> */
    #[ORM\ManyToMany(targetEntity: MusicAlbum::class, mappedBy: 'artists')]
    private Collection $albums;

    /** @var Collection<int, MusicRecording> */
    #[ORM\ManyToMany(targetEntity: MusicRecording::class, mappedBy: 'artists')]
    private Collection $recordings;

    public function __construct()
    {
        $this->albums = new ArrayCollection();
        $this->recordings = new ArrayCollection();
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

    /** @return Collection<int, MusicAlbum> */
    public function getAlbums(): Collection
    {
        return $this->albums;
    }

    /** @return Collection<int, MusicRecording> */
    public function getRecordings(): Collection
    {
        return $this->recordings;
    }
}
