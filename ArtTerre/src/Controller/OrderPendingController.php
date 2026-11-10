<?php

namespace App\Controller;

use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Port of pages/order-pending.php — order confirmation page, showing the
 * status form for admins and for sellers who own the artworks on the order.
 */
class OrderPendingController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    #[Route('/order-pending', name: 'app_order_pending')]
    public function index(Request $request): Response
    {
        $orderNo = trim((string) $request->query->get('no', ''));
        $order = $orderNo !== ''
            ? $this->em->getRepository(Order::class)->findOneBy(['orderNo' => $orderNo])
            : null;

        if (!$order) {
            throw new NotFoundHttpException('Order not found.');
        }

        return $this->render('order_pending.html.twig', [
            'order'     => $order,
            'canUpdate' => $this->canUpdateStatus($order),
            'statuses'  => Order::STATUSES,
        ]);
    }

    #[Route('/order-pending/status', name: 'app_order_pending_status', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED')]
    public function updateStatus(Request $request): Response
    {
        $orderNo = trim((string) $request->query->get('no', ''));
        $order = $orderNo !== ''
            ? $this->em->getRepository(Order::class)->findOneBy(['orderNo' => $orderNo])
            : null;

        if (!$order) {
            throw new NotFoundHttpException('Order not found.');
        }

        $token = (string) $request->request->get('_token', '');
        if (!$this->isCsrfTokenValid('order_status_update' . $order->getId(), $token)) {
            return $this->statusResponse($request, $order, false, 'Invalid security token. Please try again.');
        }

        if (!$this->canUpdateStatus($order)) {
            return $this->statusResponse($request, $order, false, 'You are not allowed to update this order.');
        }

        $status = (string) $request->request->get('status', '');
        if (!in_array($status, Order::STATUSES, true)) {
            return $this->statusResponse($request, $order, false, 'Unknown status value.');
        }

        $order->setStatus($status);
        $this->em->flush();

        return $this->statusResponse($request, $order, true, 'Order status updated to “' . $status . '”.');
    }

    /* Shared response helper: JSON payload for AJAX requests, otherwise a
       flash message plus redirect (progressive enhancement). */
    private function statusResponse(Request $request, Order $order, bool $success, string $message): Response
    {
        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([
                'success' => $success,
                'message' => $message,
                'status'  => $order->getStatus(),
            ]);
        }

        $this->addFlash($success ? 'success' : 'error', $message);

        $referer = $request->headers->get('referer');
        if ($referer !== null && str_starts_with($referer, $request->getSchemeAndHttpHost())) {
            return $this->redirect($referer);
        }

        return $this->redirectToRoute('app_order_pending', ['no' => $order->getOrderNo()]);
    }

    /* Port of auth.php can_update_order_status(): admin always; artist only
       when at least one line of the order belongs to them; buyer never. */
    private function canUpdateStatus(Order $order): bool
    {
        $user = $this->getUser();
        if (!$user) {
            return false;
        }
        if ($user->isAdmin()) {
            return true;
        }
        if (!$user->isArtist()) {
            return false;
        }

        foreach ($order->getItems() as $item) {
            if ($item->getArtist()?->getId() === $user->getId()) {
                return true;
            }
        }

        return false;
    }
}
