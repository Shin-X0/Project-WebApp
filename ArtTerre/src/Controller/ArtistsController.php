<?php

namespace App\Controller;

use App\Entity\ArtistLike;
use App\Entity\User;
use App\Repository\ArtistLikeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/** Port of pages/artists.php — every artist with their artwork count. */
class ArtistsController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    #[Route('/artists', name: 'app_artists')]
    public function index(ArtistLikeRepository $artistLikes): Response
    {
        $artists = $this->em->createQueryBuilder()
            ->select('u', 'COUNT(DISTINCT a.id) AS artworkCount')
            ->from(User::class, 'u')
            ->leftJoin('u.artworks', 'a')
            ->where('u.role = :role')
            ->setParameter('role', User::ROLE_ARTIST)
            ->groupBy('u.id')
            ->orderBy('artworkCount', 'DESC')
            ->getQuery()
            ->getResult();

        $currentUser = $this->getUser();

        return $this->render('artists.html.twig', [
            'artists' => array_map(
                fn ($row) => [
                    'user'         => $row[0],
                    'artworkCount' => (int) $row['artworkCount'],
                    'likeCount'    => $artistLikes->countForArtist($row[0]),
                ],
                $artists
            ),
            'likedByUser' => $currentUser instanceof User
                ? $artistLikes->likedArtistIds($currentUser)
                : [],
        ]);
    }

    /**
     * Toggle a signed-in user's heart on an artist (POST, returns JSON).
     * A guest gets 401 so the front end can send them to the sign-in page.
     */
    #[Route('/artists/like/{id}', name: 'app_artist_like', methods: ['POST'])]
    public function toggleLike(
        int $id,
        Request $request,
        CsrfTokenManagerInterface $csrf,
        ArtistLikeRepository $artistLikes,
    ): JsonResponse {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json(['success' => false, 'requireLogin' => true], Response::HTTP_UNAUTHORIZED);
        }

        $token = new CsrfToken('like_artist', (string) $request->request->get('_csrf_token', ''));
        if (!$csrf->isTokenValid($token)) {
            return $this->json(['success' => false, 'message' => 'Your session expired — please try again.'], Response::HTTP_BAD_REQUEST);
        }

        $artist = $this->em->getRepository(User::class)->find($id);
        if (!$artist instanceof User || !$artist->isArtist()) {
            return $this->json(['success' => false, 'message' => 'Artist not found.'], Response::HTTP_NOT_FOUND);
        }

        $existing = $artistLikes->findLike($user, $artist);

        if ($existing !== null) {
            $this->em->remove($existing);
            $liked = false;
        } else {
            $like = new ArtistLike();
            $like->setArtist($artist);
            $like->setUser($user);
            $this->em->persist($like);
            $liked = true;
        }

        $this->em->flush();

        return $this->json([
            'success' => true,
            'liked'   => $liked,
            'count'   => $artistLikes->countForArtist($artist),
        ]);
    }
}
