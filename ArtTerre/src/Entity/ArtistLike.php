<?php

namespace App\Entity;

use App\Repository\ArtistLikeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * A single user's heart/like on an artist.
 *
 * The Top Artists list on the home page is driven by these rows — an artist
 * only shows up there once at least one person has liked them, ranked by the
 * number of likes received.
 */
#[ORM\Entity(repositoryClass: ArtistLikeRepository::class)]
#[ORM\Table(name: 'artist_likes')]
#[ORM\UniqueConstraint(name: 'uq_artist_user', columns: ['artist_id', 'user_id'])]
class ArtistLike
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(options: ['unsigned' => true])]
    private ?int $id = null;

    /** The artist being liked. */
    #[ORM\ManyToOne(inversedBy: 'artistLikes', targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'artist_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $artist;

    /** The signed-in user who left the like. */
    #[ORM\ManyToOne(inversedBy: 'givenArtistLikes', targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getArtist(): User
    {
        return $this->artist;
    }

    public function setArtist(User $artist): static
    {
        $this->artist = $artist;

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
