<?php

namespace App\Controller;

use App\Dto\CheckoutInput;
use App\Entity\ProductStock;
use App\Entity\User;
use App\Exception\SoldOutException;
use App\Service\OrderService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class CheckoutController extends AbstractController
{
    #[Route('/checkout', name: 'app_checkout', methods: ['POST'])]
    public function __invoke(Request $request, ValidatorInterface $validator, OrderService $orders): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->fail('Please log in to complete checkout.', 401);
        }

        $payload = json_decode($request->getContent(), true);
        $input = is_array($payload) ? $payload : $request->request->all();

        if (!$this->isCsrfTokenValid('checkout', is_string($input['csrf_token'] ?? null) ? $input['csrf_token'] : null)) {
            return $this->fail('Your session expired. Please refresh the page and try again.', 403);
        }

        $productId = (int) ($input['product_id'] ?? 0);
        $size = strtoupper(trim((string) ($input['size'] ?? '')));
        if ($productId <= 0 || !in_array($size, ProductStock::SIZES, true)) {
            return $this->fail('Please choose a valid size.', 400);
        }

        $checkout = CheckoutInput::fromArray($input);
        $violations = $validator->validate($checkout);
        if (count($violations) > 0) {
            $messages = [];
            foreach ($violations as $violation) {
                $messages[] = (string) $violation->getMessage();
            }

            return $this->fail(implode(' ', $messages), 400);
        }

        try {
            $remaining = $orders->place($user, $productId, $size, $checkout);
        } catch (SoldOutException $e) {
            return $this->fail($e->getMessage(), 409);
        } catch (\Throwable $e) {
            error_log('Checkout failed: '.$e->getMessage());

            return $this->fail('Something went wrong. Please try again.', 500);
        }

        $receipt = $checkout->receiptEmailFor($user->getEmail());

        return $this->json([
            'success' => true,
            'message' => $checkout->isCard()
                ? 'Payment received! A receipt is on its way to '.$receipt.'.'
                : 'Order placed! Pay in cash when it arrives. A receipt is on its way to '.$receipt.'.',
            'remaining' => $remaining,
        ]);
    }

    private function fail(string $message, int $status): JsonResponse
    {
        return $this->json(['success' => false, 'message' => $message], $status);
    }
}
