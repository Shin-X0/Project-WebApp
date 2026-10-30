<?php

namespace App\Controller;

use App\Entity\Artwork;
use App\Entity\CartItem;
use App\Repository\CartItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Port of pages/cart-sync.php — persists the localStorage cart to the
 * cart_items table for signed-in users (survives logout/login).
 * Accepts action=save / action=load, form-encoded or JSON body.
 */
class CartSyncController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CartItemRepository $cartItemRepository,
    ) {
    }

    #[Route('/cart-sync', name: 'app_cart_sync', methods: ['POST'])]
    public function sync(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['ok' => false, 'error' => 'not_signed_in'], 401);
        }

        /* Action may arrive as a form field or inside a JSON body. */
        $action = (string) $request->request->get('action', '');
        $itemsJson = (string) $request->request->get('items', '');

        if ($action === '') {
            $body = json_decode((string) $request->getContent(), true);
            if (is_array($body) && isset($body['action'])) {
                $action = (string) $body['action'];
                $itemsJson = isset($body['items']) ? json_encode($body['items']) : '';
            }
        }

        if ($action === 'load') {
            return $this->load($user);
        }

        if ($action === 'save') {
            return $this->save($user, $itemsJson);
        }

        return $this->json(['ok' => false, 'error' => 'unknown_action'], 400);
    }

    private function load($user): JsonResponse
    {
        $items = array_map(
            fn (CartItem $cartItem) => [
                'id'     => $cartItem->getArtwork()?->getId(),
                'title'  => $cartItem->getTitle(),
                'artist' => $cartItem->getArtistName(),
                'price'  => (float) $cartItem->getPrice(),
                'qty'    => $cartItem->getQty(),
            ],
            $this->cartItemRepository->findBy(['user' => $user], ['createdAt' => 'ASC'])
        );

        return $this->json(['ok' => true, 'items' => array_values($items)]);
    }

    private function save($user, string $itemsJson): JsonResponse
    {
        $incoming = json_decode($itemsJson, true);
        if (!is_array($incoming)) {
            return $this->json(['ok' => false, 'error' => 'invalid_items'], 400);
        }

        /* Replace the user's persisted cart with the incoming snapshot. */
        foreach ($this->cartItemRepository->findBy(['user' => $user]) as $existing) {
            $this->em->remove($existing);
        }

        foreach ($incoming as $row) {
            if (!is_array($row) || !isset($row['title'])) {
                continue;
            }

            $cartItem = new CartItem();
            $cartItem->setUser($user);
            $cartItem->setTitle((string) ($row['title'] ?? ''));

            $artworkId = isset($row['id']) ? (int) $row['id'] : 0;
            if ($artworkId > 0) {
                $cartItem->setArtwork($this->em->find(Artwork::class, $artworkId));
            }

            $cartItem->setArtistName(isset($row['artist']) ? (string) $row['artist'] : null);
            $cartItem->setPrice(number_format((float) ($row['price'] ?? 0), 2, '.', ''));
            $cartItem->setQty(max(1, (int) ($row['qty'] ?? 1)));
            $this->em->persist($cartItem);
        }

        $this->em->flush();

        return $this->json(['ok' => true]);
    }
}
