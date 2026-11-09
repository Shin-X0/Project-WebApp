<?php

namespace App\Controller;

use App\Entity\Artwork;
use App\Entity\Order;
use App\Entity\OrderItem;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Port of pages/checkout.php. Cart items arrive as a JSON snapshot in
 * "cart_json" (filled by js/script.js from localStorage). Sellers are
 * sell-only and cannot checkout (old is_artist rule).
 */
class CheckoutController extends AbstractController
{
    private const ORDER_STATUSES = Order::STATUSES;

    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    #[Route('/checkout', name: 'app_checkout', methods: ['GET'])]
    public function index(): Response
    {
        if ($viewer = $this->getUser()) {
            if ($viewer->isArtist() && !$viewer->isAdmin()) {
                $this->addFlash('error', 'Seller accounts are sell-only — collectors do the buying.');

                return $this->redirectToRoute('app_cart');
            }
        }

        return $this->render('checkout.html.twig');
    }

    #[Route('/checkout', name: 'app_checkout_submit', methods: ['POST'])]
    public function submit(Request $request): Response
    {
        $viewer = $this->getUser();

        if ($viewer && $viewer->isArtist() && !$viewer->isAdmin()) {
            $this->addFlash('error', 'Seller accounts are sell-only — collectors do the buying.');

            return $this->redirectToRoute('app_cart');
        }

        $name    = trim((string) $request->request->get('name', ''));
        $email   = strtolower(trim((string) $request->request->get('email', '')));
        $address = trim((string) $request->request->get('address', ''));
        $city    = trim((string) $request->request->get('city', ''));
        $zip     = trim((string) $request->request->get('zip', ''));
        $payment = $request->request->get('payment', 'cod') === 'card' ? Order::PAYMENT_CARD : Order::PAYMENT_COD;

        /* Card payment — digits only / MM/YY / 3-digit CVV (old validation). */
        if ($payment === Order::PAYMENT_CARD) {
            $cardNumber = preg_replace('/\D/', '', (string) $request->request->get('card', ''));
            $expiry     = trim((string) $request->request->get('expiry', ''));
            $cvv        = (string) $request->request->get('cvv', '');

            if (strlen((string) $cardNumber) < 12
                || !preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $expiry)
                || !preg_match('/^\d{3,4}$/', $cvv)) {
                $this->addFlash('error', 'Please enter valid card details (12+ digit number, MM/YY expiry, 3–4 digit CVV).');

                return $this->redirectToRoute('app_checkout');
            }
        }

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $address === '') {
            $this->addFlash('error', 'Please fill in your name, email, and delivery address.');

            return $this->redirectToRoute('app_checkout');
        }

        /* Cart items (JSON snapshot from the browser cart). */
        $cartItems = [];
        $cartRaw = json_decode((string) $request->request->get('cart_json', '[]'), true);
        if (is_array($cartRaw)) {
            foreach ($cartRaw as $row) {
                if (!is_array($row) || empty($row['title'])) {
                    continue;
                }
                $cartItems[] = [
                    'id'     => isset($row['id']) ? (int) $row['id'] : 0,
                    'title'  => (string) $row['title'],
                    'artist' => isset($row['artist']) ? (string) $row['artist'] : null,
                    'price'  => (float) ($row['price'] ?? 0),
                    'qty'    => max(1, (int) ($row['qty'] ?? 1)),
                ];
            }
        }

        if (!$cartItems) {
            $this->addFlash('error', 'Your cart is empty — add an artwork first.');

            return $this->redirectToRoute('app_artworks');
        }

        $order = new Order();
        $order->setOrderNo('AT-' . strtoupper(bin2hex(random_bytes(4))));
        $order->setUser($viewer);
        $order->setName($name);
        $order->setEmail($email);
        $order->setAddress($address);
        $order->setCity($city !== '' ? $city : null);
        $order->setZip($zip !== '' ? $zip : null);
        $order->setPaymentMethod($payment);

        $total = 0.0;
        foreach ($cartItems as $row) {
            $item = new OrderItem();
            $item->setOrder($order);
            $item->setTitle($row['title']);
            $item->setArtistName($row['artist']);
            $item->setPrice(number_format($row['price'], 2, '.', ''));
            $item->setQty($row['qty']);

            if ($row['id'] > 0) {
                $artwork = $this->em->find(Artwork::class, $row['id']);
                if ($artwork && $artwork->isAvailable()) {
                    $item->setArtwork($artwork);
                    $item->setArtist($artwork->getArtist());

                    /* Mark the piece sold immediately (old checkout behavior). */
                    $artwork->setStatus(Artwork::STATUS_SOLD);
                }
            }

            $total += $row['price'] * $row['qty'];
            $this->em->persist($item);
        }

        $order->setTotal(number_format($total, 2, '.', ''));
        $this->em->persist($order);
        $this->em->flush();

        return $this->redirectToRoute('app_order_pending', ['no' => $order->getOrderNo()]);
    }
}
