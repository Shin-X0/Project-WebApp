<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

/**
 * Sign-in against the users table — direct port of the old site's
 * pages/login.php ("Sign In" tab) incl. the session-based lockout
 * from includes/auth.php: 5 failed tries, then a 300-second lock.
 */
class LoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    /* Matches includes/auth.php constants. */
    public const MAX_LOGIN_ATTEMPTS    = 5;
    public const LOGIN_LOCKOUT_SECONDS = 300;

    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly UserRepository $userRepository,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function authenticate(Request $request): Passport
    {
        $session  = $request->getSession();
        $email    = strtolower(trim((string) $request->request->get('email', '')));
        $password = (string) $request->request->get('password', '');
        $csrf     = (string) $request->request->get('_csrf_token', $request->request->get('csrf_token', ''));

        $request->request->set('email', $email); // keep value visible on failure redirect

        /* Locked out after too many failed attempts — reject before any lookup. */
        if ($this->isLocked($session)) {
            $request->attributes->set('lockout_message', $this->lockoutMessage($session));
        }

        return new Passport(
            new UserBadge($email, fn (string $userIdentifier): ?User => $this->userRepository->findOneBy(['email' => $userIdentifier])),
            new PasswordCredentials($password),
            [new CsrfTokenBadge('authenticate', $csrf)]
        );
    }

    /**
     * Records the failed attempt (locks when the limit is reached) and
     * surfaces the same messages the old login page showed.
     */
    public function onAuthenticationFailure(Request $request, \Symfony\Component\Security\Core\Exception\AuthenticationException $exception): Response
    {
        $session = $request->getSession();
        $lockout = $request->attributes->get('lockout_message');

        if ($lockout !== null) {
            $session->set('login_error', $lockout);

            return new RedirectResponse($this->getLoginUrl($request));
        }

        $attempts = (int) $session->get('login_attempts', 0) + 1;
        $message  = 'Sign in failed — check your email and password.';

        if ($attempts >= self::MAX_LOGIN_ATTEMPTS) {
            $session->set('login_lock_until', time() + self::LOGIN_LOCKOUT_SECONDS);
            $session->set('login_attempts', $attempts);
            $message = 'Too many failed attempts — sign in is locked. Try again in about '
                . max(1, (int) ceil(self::LOGIN_LOCKOUT_SECONDS / 60)) . ' minute(s).';
        } else {
            $message .= sprintf(' (%d attempt%s left.)', self::MAX_LOGIN_ATTEMPTS - $attempts, self::MAX_LOGIN_ATTEMPTS - $attempts === 1 ? '' : 's');
            $session->set('login_attempts', $attempts);
        }

        $session->set('login_error', $message);

        return new RedirectResponse($this->getLoginUrl($request));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $session = $request->getSession();

        /* Old auth.php login_clear_attempts(). */
        $session->remove('login_attempts');
        $session->remove('login_lock_until');
        $session->remove('login_error');

        if ($targetPath = $this->getTargetPath($session, $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        /* Old behavior: admins land on the admin dashboard, everyone else home. */
        $user = $token->getUser();
        if ($user instanceof User && $user->isAdmin()) {
            return new RedirectResponse($this->urlGenerator->generate('app_admin_dashboard'));
        }

        return new RedirectResponse($this->urlGenerator->generate('app_home'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }

    private function isLocked($session): bool
    {
        return (int) ($session->get('login_lock_until', 0)) > time();
    }

    private function lockoutMessage($session): string
    {
        $seconds = max(0, (int) $session->get('login_lock_until', 0) - time());

        return sprintf(
            'Too many failed attempts — sign in is locked. Try again in about %d minute(s).',
            max(1, (int) ceil($seconds / 60))
        );
    }
}
