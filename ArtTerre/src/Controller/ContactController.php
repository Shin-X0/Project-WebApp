<?php

namespace App\Controller;

use App\Entity\ContactMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Port of pages/contact.php — the "Contact Us" form in the footer posts here.
 */
class ContactController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    #[Route('/contact', name: 'app_contact', methods: ['POST'])]
    public function submit(Request $request, CsrfTokenManagerInterface $csrf): Response
    {
        if (!$csrf->isTokenValid('contact', (string) $request->request->get('_csrf_token', ''))) {
            return $this->redirect($request->request->get('redirect', '/') . (str_contains($request->request->get('redirect', '/'), '?') ? '&' : '?') . 'contact=csrf');
        }

        $name    = trim((string) $request->request->get('name', ''));
        $email   = strtolower(trim((string) $request->request->get('email', '')));
        $message = trim((string) $request->request->get('message', ''));

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($message) < 5) {
            return $this->redirect($request->request->get('redirect', '/') . (str_contains($request->request->get('redirect', '/'), '?') ? '&' : '?') . 'contact=error');
        }

        $contact = new ContactMessage();
        $contact->setUser($this->getUser());
        $contact->setName($name);
        $contact->setEmail($email);
        $contact->setMessage($message);

        $this->em->persist($contact);
        $this->em->flush();

        return $this->redirect($request->request->get('redirect', '/') . (str_contains($request->request->get('redirect', '/'), '?') ? '&' : '?') . 'contact=sent');
    }
}
