<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/** Port of pages/artists.php — every artist with their artwork count. */
class ArtistsController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    #[Route('/artists', name: 'app_artists')]
    public function index(): Response
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

        return $this->render('artists.html.twig', [
            'artists' => array_map(fn ($row) => ['user' => $row[0], 'artworkCount' => (int) $row['artworkCount']], $artists),
        ]);
    }
}
