<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: 'orders')]
class Order
{
    public const PAYMENT_COD = 'cod';
    public const PAYMENT_CARD = 'credit_card';
    public const PAYMENT_STATUS_PAID = 'paid';
    public const PAYMENT_STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_CANCELLED = 'cancelled';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Product $product = null;

    #[ORM\Column(length: 2)]
    private string $size = '';

    #[ORM\Column(options: ['default' => 1])]
    private int $quantity = 1;

    #[ORM\Column(length: 20)]
    private string $paymentMethod = self::PAYMENT_COD;

    #[ORM\Column(length: 20)]
    private string $paymentStatus = self::PAYMENT_STATUS_PENDING;

    #[ORM\Column(length: 20, options: ['default' => 'active'])]
    private string $status = self::STATUS_ACTIVE;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cardName = null;

    #[ORM\Column(length: 4, nullable: true)]
    private ?string $cardLast4 = null;

    #[ORM\Column(length: 255)]
    private string $shippingName = '';

    #[ORM\Column(length: 50)]
    private string $shippingPhone = '';

    #[ORM\Column(length: 255)]
    private string $shippingAddress = '';

    #[ORM\Column(length: 100)]
    private string $shippingCity = '';

    #[ORM\Column(length: 20)]
    private string $shippingPostalCode = '';

    #[ORM\Column(length: 255)]
    private string $receiptEmail = '';

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getUser(): ?User { return $this->user; }
    public function setUser(User $user): static { $this->user = $user; return $this; }
    public function getProduct(): ?Product { return $this->product; }
    public function setProduct(Product $product): static { $this->product = $product; return $this; }
    public function getSize(): string { return $this->size; }
    public function setSize(string $size): static { $this->size = $size; return $this; }
    public function getQuantity(): int { return $this->quantity; }
    public function setQuantity(int $quantity): static { $this->quantity = $quantity; return $this; }
    public function getPaymentMethod(): string { return $this->paymentMethod; }
    public function setPaymentMethod(string $paymentMethod): static { $this->paymentMethod = $paymentMethod; return $this; }
    public function getPaymentStatus(): string { return $this->paymentStatus; }
    public function setPaymentStatus(string $paymentStatus): static { $this->paymentStatus = $paymentStatus; return $this; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): static { $this->status = $status; return $this; }
    public function getCardName(): ?string { return $this->cardName; }
    public function setCardName(?string $cardName): static { $this->cardName = $cardName; return $this; }
    public function getCardLast4(): ?string { return $this->cardLast4; }
    public function setCardLast4(?string $cardLast4): static { $this->cardLast4 = $cardLast4; return $this; }
    public function getShippingName(): string { return $this->shippingName; }
    public function setShippingName(string $v): static { $this->shippingName = $v; return $this; }
    public function getShippingPhone(): string { return $this->shippingPhone; }
    public function setShippingPhone(string $v): static { $this->shippingPhone = $v; return $this; }
    public function getShippingAddress(): string { return $this->shippingAddress; }
    public function setShippingAddress(string $v): static { $this->shippingAddress = $v; return $this; }
    public function getShippingCity(): string { return $this->shippingCity; }
    public function setShippingCity(string $v): static { $this->shippingCity = $v; return $this; }
    public function getShippingPostalCode(): string { return $this->shippingPostalCode; }
    public function setShippingPostalCode(string $v): static { $this->shippingPostalCode = $v; return $this; }
    public function getReceiptEmail(): string { return $this->receiptEmail; }
    public function setReceiptEmail(string $v): static { $this->receiptEmail = $v; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function isPaid(): bool
    {
        return self::PAYMENT_STATUS_PAID === $this->paymentStatus;
    }

    public function isCancelled(): bool
    {
        return self::STATUS_CANCELLED === $this->status;
    }
}
