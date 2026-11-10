<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Port of pages/sales.php — orders that contain artworks owned by the
 * signed-in artist. Sellers can update the status of their own orders.
 */
class SalesController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    #[Route('/sales', name: 'app_sales')]
    #[IsGranted('ROLE_ARTIST')]
    public function index(Request $request): Response
    {
        $user = $this->getUser();

        if ($user->isAdmin()) {
            return $this->redirectToRoute('app_orders');
        }

        $orders = $this->em->createQueryBuilder()
            ->select('DISTINCT o')
            ->from(Order::class, 'o')
            ->join('o.items', 'i')
            ->where('i.artist = :artist')
            ->setParameter('artist', $user)
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('sales.html.twig', [
            'orders' => $orders,
            'statuses' => Order::STATUSES,
        ]);
    }
}
