<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/** Port of pages/cart.php — items render client-side from localStorage (js/script.js). */
class CartController extends AbstractController
{
    #[Route('/cart', name: 'app_cart')]
    public function index(): Response
    {
        $viewer      = $this->getUser();
        $isSellerView = $viewer && $viewer->isArtist() && !$viewer->isAdmin();

        return $this->render('cart.html.twig', [
            'isSellerView' => $isSellerView,
        ]);
    }
}
