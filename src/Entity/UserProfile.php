<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Trait\TimestampableTrait;
use App\Repository\UserProfileRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

/** @psalm-api */
#[ORM\Entity(repositoryClass: UserProfileRepository::class)]
#[ORM\Table(name: 'user_profile')]
final class UserProfile
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    // @phpstan-ignore-next-line property.unusedType
    private ?Uuid $id = null;

    #[ORM\OneToOne(inversedBy: 'userProfile', cascade: ['persist'])]
    #[ORM\JoinColumn(unique: true, nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'A user is required.')]
    private ?User $user = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $about = null;

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getAbout(): ?string
    {
        return $this->about;
    }

    public function setAbout(?string $about): static
    {
        $this->about = $about;

        return $this;
    }
}
