<?php

namespace App\Controller;

use App\Entity\ContactMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Port of pages/messages.php — "My messages": the signed-in user's contact
 * messages and any admin replies.
 */
class MessagesController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    #[Route('/messages', name: 'app_messages')]
    #[IsGranted('IS_AUTHENTICATED')]
    public function index(): Response
    {
        $messages = $this->em->getRepository(ContactMessage::class)->findBy(
            ['user' => $this->getUser()],
            ['createdAt' => 'DESC']
        );

        return $this->render('messages.html.twig', ['messages' => $messages]);
    }
}
