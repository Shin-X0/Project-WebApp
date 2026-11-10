<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Self-service profile page — every signed-in user can upload their own
 * profile picture here (reached from the account dropdown).
 *
 * Rules: JPG/PNG/WEBP/GIF, at least 200x200 px, 4 MB maximum.
 * Files land in public/uploads/profiles/ (same layout as the artworks dir).
 */
class ProfileController extends AbstractController
{
    public const UPLOAD_DIR  = 'uploads/profiles';
    public const MIN_SIZE_PX = 200;
    public const MAX_BYTES   = 4 * 1024 * 1024;

    private const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    #[Route('/profile', name: 'app_profile', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('account/profile.html.twig', ['errors' => []]);
    }

    #[Route('/profile', name: 'app_profile_photo', methods: ['POST'])]
    public function upload(Request $request, CsrfTokenManagerInterface $csrf): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $token = new CsrfToken('profile_photo', (string) $request->request->get('_csrf_token', ''));
        if (!$csrf->isTokenValid($token)) {
            $this->addFlash('error', 'Your session expired — please try again.');

            return $this->redirectToRoute('app_profile');
        }

        $file = $request->files->get('photo');

        if (!$file instanceof UploadedFile || !$file->isValid()) {
            $this->addFlash('error', 'Please choose a picture to upload.');

            return $this->redirectToRoute('app_profile');
        }

        if ($error = $this->validateImage($file)) {
            $this->addFlash('error', $error);

            return $this->redirectToRoute('app_profile');
        }

        /* Valid — store it, replacing any previous photo. */
        $dir = __DIR__ . '/../../public/' . self::UPLOAD_DIR;
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $ext      = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $filename = date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        $file->move($dir, $filename);

        $this->removeOldPhoto($user);

        $user->setProfileImagePath(self::UPLOAD_DIR . '/' . $filename);
        $this->em->flush();

        $this->addFlash('success', 'Profile picture updated.');

        return $this->redirectToRoute('app_profile');
    }

    /** Null when the upload is fine, otherwise a message to show the user. */
    private function validateImage(UploadedFile $file): ?string
    {
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $fileExt = strtolower($file->getClientOriginalExtension() ?: '');

        /* MIME check first; fall back to the extension if fileinfo is off. */
        try {
            $mimeOk = in_array($file->getMimeType(), self::ALLOWED_MIMES, true);
        } catch (\LogicException) {
            $mimeOk = in_array($fileExt, $allowedExtensions, true);
        }

        if (!$mimeOk) {
            return 'Only JPG, PNG, WEBP, or GIF images are allowed.';
        }

        if ($file->getSize() > self::MAX_BYTES) {
            return 'Image is too large — 4 MB maximum.';
        }

        $dims = @getimagesize($file->getPathname());
        if ($dims === false) {
            return 'That file does not look like a valid image.';
        }

        [$width, $height] = $dims;
        if ($width < self::MIN_SIZE_PX || $height < self::MIN_SIZE_PX) {
            return sprintf(
                'Image is too small — it must be at least %dx%d px (yours is %dx%d).',
                self::MIN_SIZE_PX,
                self::MIN_SIZE_PX,
                $width,
                $height
            );
        }

        return null;
    }

    /** Delete the previous profile photo file, if any. */
    private function removeOldPhoto(User $user): void
    {
        $old = $user->getProfileImagePath();
        if ($old === null || $old === '') {
            return;
        }

        $path = __DIR__ . '/../../public/' . $old;
        if (str_starts_with($old, self::UPLOAD_DIR . '/') && is_file($path)) {
            @unlink($path);
        }
    }
}
