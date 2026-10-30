<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/** Port of pages/faq.php, privacy.php, terms.php, support.php (static pages). */
class PagesController extends AbstractController
{
    private const PAGES = [
        'faq'     => ['app_faq', 'FAQ — Art Terre Creations', 'faq', 'Frequently Asked Questions'],
        'privacy' => ['app_privacy', 'Privacy Policy — Art Terre Creations', 'privacy', 'Privacy Policy'],
        'terms'   => ['app_terms', 'Terms of Services — Art Terre Creations', 'terms', 'Terms of Services'],
        'support' => ['app_support', 'Support & Help — Art Terre Creations', 'support', 'Support & Help'],
    ];

    #[Route('/faq', name: 'app_faq')]
    public function faq(): Response
    {
        return $this->staticPage('faq');
    }

    #[Route('/privacy', name: 'app_privacy')]
    public function privacy(): Response
    {
        return $this->staticPage('privacy');
    }

    #[Route('/terms', name: 'app_terms')]
    public function terms(): Response
    {
        return $this->staticPage('terms');
    }

    #[Route('/support', name: 'app_support')]
    public function support(): Response
    {
        return $this->staticPage('support');
    }

    private function staticPage(string $slug): Response
    {
        [$routeName, $title, $template, $heading] = self::PAGES[$slug];

        return $this->render('pages/' . $template . '.html.twig', [
            'pageTitle' => $title,
            'heading'   => $heading,
        ]);
    }
}
