<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Port of pages/login.php — Sign in / Create Account tabs.
 * The sign-in POST is intercepted by LoginFormAuthenticator;
 * registration is handled here.
 */
class LoginController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $hasher,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        return $this->render('security/login.html.twig', [
            'activeTab'   => 'signin',
            'lastEmail'   => $authenticationUtils->getLastUsername(),
            'loginError'  => $authenticationUtils->getLastAuthenticationError() !== null,
            'registerError' => null,
            'errors'      => [],
            'old'         => ['name' => '', 'email' => '', 'dob' => '', 'gender' => '', 'role' => 'collector'],
        ]);
    }

    #[Route('/register', name: 'app_register', methods: ['POST'])]
    public function register(Request $request, CsrfTokenManagerInterface $csrf): Response
    {
        if (!$csrf->isTokenValid('authenticate', (string) $request->request->get('_csrf_token', ''))) {
            return $this->redirectToRoute('app_login');
        }

        $old = [
            'name'   => trim((string) $request->request->get('name', '')),
            'email'  => strtolower(trim((string) $request->request->get('email', ''))),
            'dob'    => (string) $request->request->get('dob', ''),
            'gender' => (string) $request->request->get('gender', ''),
            'role'   => $request->request->get('role') === 'artist' ? 'artist' : 'collector',
        ];

        $pass  = (string) $request->request->get('password', '');
        $pass2 = (string) $request->request->get('password2', '');

        $errors = [];
        if (mb_strlen($old['name']) < 2) {
            $errors['name'] = 'Name must be at least 2 characters.';
        }
        if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Enter a valid email address.';
        }
        if (mb_strlen($pass) < 8) {
            $errors['password'] = 'Password must be at least 8 characters.';
        }
        if ($pass !== $pass2) {
            $errors['password2'] = 'Passwords do not match.';
        }

        try {
            $dob = new \DateTimeImmutable($old['dob']);
        } catch (\Exception) {
            $dob = false;
        }
        if (!$dob) {
            $errors['dob'] = 'Pick your date of birth from the calendar.';
        } elseif ($dob > new \DateTimeImmutable('today')) {
            $errors['dob'] = 'Date of birth cannot be in the future.';
        } elseif ($dob > new \DateTimeImmutable('-13 years')) {
            $errors['dob'] = 'You must be at least 13 years old to join.';
        }

        $genders = ['female', 'male', 'non-binary', 'other', 'prefer-not'];
        if (!in_array($old['gender'], $genders, true)) {
            $errors['gender'] = 'Please choose a gender option.';
        }

        if (!$errors && $this->em->getRepository(User::class)->findOneBy(['email' => $old['email']])) {
            $errors['email'] = 'That email is already registered — sign in instead.';
        }

        if ($errors) {
            return $this->render('security/login.html.twig', [
                'activeTab'     => 'register',
                'lastEmail'     => '',
                'loginError'    => false,
                'registerError' => null,
                'errors'        => $errors,
                'old'           => $old,
            ]);
        }

        $user = new User();
        $user->setName($old['name']);
        $user->setEmail($old['email']);
        $user->setRole($old['role']);
        $user->setDob($dob);
        $user->setGender($old['gender']);
        $user->setPassword($this->hasher->hashPassword($user, $pass));

        $violations = $this->validator->validate($user);
        if (count($violations) > 0) {
            return $this->redirectToRoute('app_login');
        }

        $this->em->persist($user);
        $this->em->flush();

        return $this->redirectToRoute('app_login', ['registered' => '1']);
    }
}
