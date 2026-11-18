<?php

namespace App\Dto;

use App\Entity\Order;
use Symfony\Component\Validator\Constraints as Assert;

final class CheckoutInput
{
    public function __construct(
        #[Assert\NotBlank(message: 'Full name is required.')]
        public readonly string $shippingName = '',

        #[Assert\NotBlank(message: 'Phone number is required.')]
        #[Assert\Regex(pattern: '/^\d+$/', message: 'Phone number can only contain numbers.')]
        #[Assert\Length(min: 7, max: 15, minMessage: 'Phone number is too short.', maxMessage: 'Phone number is too long.')]
        public readonly string $shippingPhone = '',

        #[Assert\NotBlank(message: 'Street address is required.')]
        public readonly string $shippingAddress = '',

        #[Assert\NotBlank(message: 'City is required.')]
        public readonly string $shippingCity = '',

        #[Assert\NotBlank(message: 'Postal code is required.')]
        #[Assert\Regex(pattern: '/^\d+$/', message: 'Postal code can only contain numbers.')]
        #[Assert\Length(min: 3, max: 10, minMessage: 'Postal code is too short.', maxMessage: 'Postal code is too long.')]
        public readonly string $shippingPostalCode = '',

        #[Assert\Choice(choices: [Order::PAYMENT_COD, Order::PAYMENT_CARD], message: 'Choose a payment method.')]
        public readonly string $paymentMethod = '',

        #[Assert\When(
            expression: 'this.paymentMethod === "credit_card"',
            constraints: [new Assert\NotBlank(message: 'Name on card is required.')],
        )]
        public readonly string $cardName = '',

        #[Assert\When(
            expression: 'this.paymentMethod === "credit_card"',
            constraints: [new Assert\NotBlank(message: 'Card number is required.')],
        )]
        public readonly string $cardNumber = '',

        #[Assert\When(
            expression: 'this.paymentMethod === "credit_card"',
            constraints: [new Assert\NotBlank(message: 'Expiry date is required.')],
        )]
        public readonly string $cardExpiry = '',

        public readonly string $receiptTarget = 'account',

        #[Assert\When(
            expression: 'this.receiptTarget === "other"',
            constraints: [new Assert\NotBlank(message: 'Receipt email is required.')],
        )]
        public readonly string $receiptEmail = '',
    ) {
    }

    public static function fromArray(array $input): self
    {
        $text = static fn (string $key, string $default = ''): string => is_string($input[$key] ?? null) || is_int($input[$key] ?? null)
            ? trim((string) $input[$key])
            : $default;

        return new self(
            shippingName: $text('shipping_name'),
            shippingPhone: $text('shipping_phone'),
            shippingAddress: $text('shipping_address'),
            shippingCity: $text('shipping_city'),
            shippingPostalCode: $text('shipping_postal_code'),
            paymentMethod: $text('payment_method'),
            cardName: $text('card_name'),
            cardNumber: $text('card_number'),
            cardExpiry: $text('card_expiry'),
            receiptTarget: $text('receipt_target', 'account'),
            receiptEmail: $text('receipt_email'),
        );
    }

    public function isCard(): bool
    {
        return Order::PAYMENT_CARD === $this->paymentMethod;
    }

    public function cardLast4(): ?string
    {
        if (!$this->isCard()) {
            return null;
        }
        $digits = preg_replace('/\D/', '', $this->cardNumber) ?? '';

        return strlen($digits) >= 4 ? substr($digits, -4) : null;
    }

    public function receiptEmailFor(string $accountEmail): string
    {
        return 'other' === $this->receiptTarget && '' !== $this->receiptEmail ? $this->receiptEmail : $accountEmail;
    }
}
