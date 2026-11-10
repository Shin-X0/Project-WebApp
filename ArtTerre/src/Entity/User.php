<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const ROLE_COLLECTOR = 'collector';
    public const ROLE_ARTIST    = 'artist';
    public const ROLE_ADMIN     = 'admin';

    /* Old site: includes/config.php ADMIN_EMAILS — these addresses are
       treated as admins regardless of their stored role. */
    public const ADMIN_EMAILS = ['demo@artterre.com'];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\Column(name: 'name', length: 100)]
    private string $name = '';

    #[ORM\Column(name: 'email', length: 190, unique: true)]
    private string $email = '';

    /* Maps to the old users.password_hash column. */
    #[ORM\Column(name: 'password_hash', length: 255)]
    private string $password = '';

    #[ORM\Column(name: 'dob', type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $dob = null;

    #[ORM\Column(name: 'gender', length: 20, nullable: true)]
    private ?string $gender = null;

    /* Stored role — matches the old users.role ENUM('collector','artist','admin'). */
    #[ORM\Column(name: 'role', length: 20, options: ['default' => self::ROLE_COLLECTOR])]
    private string $role = self::ROLE_COLLECTOR;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    /* Uploaded profile photo, e.g. "uploads/profiles/20261110_123456_abc123.jpg". */
    #[ORM\Column(name: 'profile_image_path', length: 255, nullable: true)]
    private ?string $profileImagePath = null;

    #[ORM\OneToMany(mappedBy: 'artist', targetEntity: Artwork::class)]
    private Collection $artworks;

    /** Likes/hearts this artist has received. */
    #[ORM\OneToMany(mappedBy: 'artist', targetEntity: ArtistLike::class)]
    private Collection $artistLikes;

    /** Likes this user has given to other artists. */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: ArtistLike::class)]
    private Collection $givenArtistLikes;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Order::class)]
    private Collection $orders;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: CartItem::class, orphanRemoval: true)]
    private Collection $cartItems;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: ContactMessage::class)]
    private Collection $contactMessages;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->artworks = new ArrayCollection();
        $this->artistLikes = new ArrayCollection();
        $this->givenArtistLikes = new ArrayCollection();
        $this->orders = new ArrayCollection();
        $this->cartItems = new ArrayCollection();
        $this->contactMessages = new ArrayCollection();
    }

    public function getId(): ?int
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

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = strtolower(trim($email));

        return $this;
    }

    public function getDob(): ?\DateTimeImmutable
    {
        return $this->dob;
    }

    public function setDob(?\DateTimeImmutable $dob): static
    {
        $this->dob = $dob;

        return $this;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(?string $gender): static
    {
        $this->gender = $gender;

        return $this;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): static
    {
        $this->role = $role;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getProfileImagePath(): ?string
    {
        return $this->profileImagePath;
    }

    public function setProfileImagePath(?string $profileImagePath): static
    {
        $this->profileImagePath = $profileImagePath;

        return $this;
    }

    public function hasProfilePhoto(): bool
    {
        return $this->profileImagePath !== null && $this->profileImagePath !== '';
    }

    /* ---------- Security bridge (old includes/auth.php equivalents) ---------- */

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN
            || in_array(strtolower($this->email), self::ADMIN_EMAILS, true);
    }

    public function isArtist(): bool
    {
        return $this->role === self::ROLE_ARTIST;
    }

    public function isCollector(): bool
    {
        return $this->role === self::ROLE_COLLECTOR;
    }

    public function getRoles(): array
    {
        if ($this->isAdmin()) {
            return ['ROLE_ADMIN', 'ROLE_ARTIST', 'ROLE_COLLECTOR'];
        }

        return match ($this->role) {
            self::ROLE_ARTIST => ['ROLE_ARTIST', 'ROLE_COLLECTOR'],
            default => ['ROLE_COLLECTOR'],
        };
    }

    public function eraseCredentials(): void
    {
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    /* ---------- Relations ---------- */

    /** @return Collection<int, Artwork> */
    public function getArtworks(): Collection
    {
        return $this->artworks;
    }

    /** @return Collection<int, ArtistLike> */
    public function getArtistLikes(): Collection
    {
        return $this->artistLikes;
    }

    /** @return Collection<int, ArtistLike> */
    public function getGivenArtistLikes(): Collection
    {
        return $this->givenArtistLikes;
    }

    /** @return Collection<int, Order> */
    public function getOrders(): Collection
    {
        return $this->orders;
    }

    /** @return Collection<int, CartItem> */
    public function getCartItems(): Collection
    {
        return $this->cartItems;
    }

    /** @return Collection<int, ContactMessage> */
    public function getContactMessages(): Collection
    {
        return $this->contactMessages;
    }
}
