<?php

namespace App\Controller;

use App\Entity\Artwork;
use App\Entity\ContactMessage;
use App\Entity\Order;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/** Port of admin/index.php — the admin dashboard with quick stats. */
class DashboardController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    #[Route('/admin', name: 'app_admin_dashboard')]
    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig', [
            'userCount'    => $this->em->getRepository(User::class)->count([]),
            'artistCount'  => $this->em->getRepository(User::class)->count(['role' => User::ROLE_ARTIST]),
            'artworkCount' => $this->em->getRepository(Artwork::class)->count([]),
            'orderCount'   => $this->em->getRepository(Order::class)->count([]),
            'messageCount' => $this->em->getRepository(ContactMessage::class)->count(['isRead' => false]),
        ]);
    }
}
