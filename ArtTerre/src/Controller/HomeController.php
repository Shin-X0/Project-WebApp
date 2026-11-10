<?php

namespace App\Controller;

use App\Entity\Artwork;
use App\Entity\User;
use App\Repository\ArtistLikeRepository;
use App\Repository\ArtworkRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(ArtworkRepository $artworkRepository, ArtistLikeRepository $artistLikes): Response
    {
        $currentUser = $this->getUser();

        return $this->render('home.html.twig', [
            'featured' => $artworkRepository->findBy(
                ['status' => Artwork::STATUS_AVAILABLE],
                ['createdAt' => 'DESC'],
                3
            ),
            /* Top Artists = artists that have been hearted, most likes first. */
            'topArtists' => $artistLikes->topArtists(3),
            'likedByUser' => $currentUser instanceof User
                ? $artistLikes->likedArtistIds($currentUser)
                : [],
        ]);
    }
}
