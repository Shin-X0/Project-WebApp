<?php

namespace App\Controller;

use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Port of pages/orders.php — the signed-in buyer's orders (view-only).
 * Sellers never see this page (sell-only rule) — they get sales.php.
 */
class OrdersController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    #[Route('/orders', name: 'app_orders')]
    #[IsGranted('IS_AUTHENTICATED')]
    public function index(): Response
    {
        $user = $this->getUser();

        if ($user->isArtist() && !$user->isAdmin()) {
            return $this->redirectToRoute('app_sales');
        }

        $orders = $this->em->getRepository(Order::class)->findBy(
            ['user' => $user],
            ['createdAt' => 'DESC']
        );

        return $this->render('orders.html.twig', ['orders' => $orders]);
    }
}
