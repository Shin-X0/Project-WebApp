<?php

namespace App\Controller;

use App\Entity\Artwork;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Port of pages/upload-artwork.php — artists upload artwork to sell.
 * Images land in public/uploads/artworks/ (same layout as the old site).
 */
class UploadArtworkController extends AbstractController
{
    public const UPLOAD_DIR = 'uploads/artworks';
    private const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    #[Route('/upload-artwork', name: 'app_upload_artwork')]
    #[IsGranted('ROLE_ARTIST')]
    public function index(): Response
    {
        return $this->render('upload_artwork.html.twig', ['errors' => [], 'old' => []]);
    }

    #[Route('/upload-artwork', name: 'app_upload_artwork_submit', methods: ['POST'])]
    #[IsGranted('ROLE_ARTIST')]
    public function submit(Request $request): Response
    {
        $old = [
            'title'    => trim((string) $request->request->get('title', '')),
            'category' => (string) $request->request->get('category', Artwork::CATEGORY_PAINTING),
            'story'    => trim((string) $request->request->get('story', '')),
            'price'    => trim((string) $request->request->get('price', '')),
        ];

        $errors = [];
        if (mb_strlen($old['title']) < 2) {
            $errors['title'] = 'Title must be at least 2 characters.';
        }
        if (!in_array($old['category'], [Artwork::CATEGORY_PAINTING, Artwork::CATEGORY_PHOTOGRAPHY], true)) {
            $errors['category'] = 'Please choose a valid category.';
        }
        if (!preg_match('/^\d{1,6}(\.\d{1,2})?$/', $old['price']) || (float) $old['price'] <= 0) {
            $errors['price'] = 'Enter a valid price (e.g. 12.50).';
        }

        /* Image is optional — placeholder art renders when absent. */
        $imagePath = null;
        $file = $request->files->get('image');
        if ($file) {
            if (!in_array($file->getMimeType(), self::ALLOWED_MIMES, true)) {
                $errors['image'] = 'Only JPG, PNG, WEBP, or GIF images are allowed.';
            } elseif ($file->getSize() > 4 * 1024 * 1024) {
                $errors['image'] = 'Image is too large — 4 MB maximum.';
            } else {
                $dir = 'public/' . self::UPLOAD_DIR;
                if (!is_dir($dir)) {
                    mkdir($dir, 0777, true);
                }
                $filename = date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . strtolower($file->getClientOriginalExtension() ?: 'jpg');
                $file->move($dir, $filename);
                $imagePath = self::UPLOAD_DIR . '/' . $filename;
            }
        }

        if ($errors) {
            return $this->render('upload_artwork.html.twig', ['errors' => $errors, 'old' => $old]);
        }

        $artwork = new Artwork();
        $artwork->setArtist($this->getUser());
        $artwork->setTitle($old['title']);
        $artwork->setCategory($old['category']);
        $artwork->setStory($old['story'] !== '' ? $old['story'] : null);
        $artwork->setPrice(number_format((float) $old['price'], 2, '.', ''));
        $artwork->setImagePath($imagePath);

        $this->em->persist($artwork);
        $this->em->flush();

        $this->addFlash('success', 'Artwork uploaded — it is now listed for sale.');

        return $this->redirectToRoute('app_artworks');
    }
}
