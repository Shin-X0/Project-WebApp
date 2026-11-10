<?php

namespace App\Repository;

use App\Entity\ArtistLike;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ArtistLike>
 */
class ArtistLikeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ArtistLike::class);
    }

    /** The like a given user currently holds on an artist, if any. */
    public function findLike(User $user, User $artist): ?ArtistLike
    {
        return $this->findOneBy(['user' => $user, 'artist' => $artist]);
    }

    /** How many hearts an artist has received. */
    public function countForArtist(User $artist): int
    {
        return (int) $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->where('l.artist = :artist')
            ->setParameter('artist', $artist)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** IDs of the artists the given user has liked (may be empty). */
    public function likedArtistIds(User $user): array
    {
        $rows = $this->createQueryBuilder('l')
            ->select('IDENTITY(l.artist) AS artistId')
            ->where('l.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getArrayResult();

        return array_map('intval', array_column($rows, 'artistId'));
    }

    /**
     * The "Top Artists": artists that have received at least one like,
     * ranked by the number of likes (most first).
     *
     * @return list<array{artist: User, likeCount: int}>
     */
    public function topArtists(int $limit): array
    {
        $rows = $this->createQueryBuilder('l')
            ->select('IDENTITY(l.artist) AS artistId', 'COUNT(l.id) AS likeCount')
            ->join('l.artist', 'a')
            ->where('a.role = :role')
            ->setParameter('role', User::ROLE_ARTIST)
            ->groupBy('l.artist')
            ->orderBy('likeCount', 'DESC')
            ->addOrderBy('a.name', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $em = $this->getEntityManager();

        return array_map(
            static fn (array $row): array => [
                'artist'    => $em->find(User::class, (int) $row['artistId']),
                'likeCount' => (int) $row['likeCount'],
            ],
            $rows
        );
    }
}
