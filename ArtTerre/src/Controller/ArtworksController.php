<?php

namespace App\Controller;

use App\Entity\Artwork;
use App\Repository\ArtworkRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/** Port of pages/artworks.php — full catalogue with category filter. */
class ArtworksController extends AbstractController
{
    public function __construct(private readonly ArtworkRepository $artworkRepository)
    {
    }

    #[Route('/artworks', name: 'app_artworks')]
    public function index(Request $request): Response
    {
        $category = (string) $request->query->get('category', 'all');
        $criteria = ['status' => Artwork::STATUS_AVAILABLE];
        if (in_array($category, [Artwork::CATEGORY_PAINTING, Artwork::CATEGORY_PHOTOGRAPHY], true)) {
            $criteria['category'] = $category;
        }

        return $this->render('artworks.html.twig', [
            'artworks' => $this->artworkRepository->findBy($criteria, ['createdAt' => 'DESC']),
            'category' => $category,
        ]);
    }
}
