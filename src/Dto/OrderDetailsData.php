<?php

namespace App\Dto;

use App\Entity\Order;
use Symfony\Component\Validator\Constraints as Assert;

/** The editable details of an order (same rules as checkout). Product, size, quantity and payment are not editable here. */
final class OrderDetailsData
{
    #[Assert\NotBlank(message: 'Full name is required.')]
    #[Assert\Length(max: 255)]
    public ?string $shippingName = null;

    #[Assert\NotBlank(message: 'Phone number is required.')]
    #[Assert\Regex(pattern: '/^\d+$/', message: 'Phone number can only contain numbers.')]
    #[Assert\Length(min: 7, max: 15, minMessage: 'Phone number is too short.', maxMessage: 'Phone number is too long.')]
    public ?string $shippingPhone = null;

    #[Assert\NotBlank(message: 'Street address is required.')]
    #[Assert\Length(max: 255)]
    public ?string $shippingAddress = null;

    #[Assert\NotBlank(message: 'City is required.')]
    #[Assert\Length(max: 100)]
    public ?string $shippingCity = null;

    #[Assert\NotBlank(message: 'Postal code is required.')]
    #[Assert\Regex(pattern: '/^\d+$/', message: 'Postal code can only contain numbers.')]
    #[Assert\Length(min: 3, max: 10, minMessage: 'Postal code is too short.', maxMessage: 'Postal code is too long.')]
    public ?string $shippingPostalCode = null;

    #[Assert\NotBlank(message: 'Receipt email is required.')]
    #[Assert\Email(message: 'Enter a valid email address.')]
    #[Assert\Length(max: 255)]
    public ?string $receiptEmail = null;

    public static function fromOrder(Order $order): self
    {
        $data = new self();
        $data->shippingName = $order->getShippingName();
        $data->shippingPhone = $order->getShippingPhone();
        $data->shippingAddress = $order->getShippingAddress();
        $data->shippingCity = $order->getShippingCity();
        $data->shippingPostalCode = $order->getShippingPostalCode();
        $data->receiptEmail = $order->getReceiptEmail();

        return $data;
    }

    public function applyTo(Order $order): void
    {
        $order
            ->setShippingName(trim((string) $this->shippingName))
            ->setShippingPhone(trim((string) $this->shippingPhone))
            ->setShippingAddress(trim((string) $this->shippingAddress))
            ->setShippingCity(trim((string) $this->shippingCity))
            ->setShippingPostalCode(trim((string) $this->shippingPostalCode))
            ->setReceiptEmail(trim((string) $this->receiptEmail));
    }
}
