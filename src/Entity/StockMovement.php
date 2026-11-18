<?php

namespace App\Entity;

use App\Repository\StockMovementRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * One row per stock posting (add) or removal (reduce).
 * Product and actor names are copied onto the row so the history survives deletions.
 */
#[ORM\Entity(repositoryClass: StockMovementRepository::class)]
#[ORM\Table(name: 'stock_movements')]
#[ORM\Index(name: 'IDX_STOCK_MOVEMENT_CREATED', columns: ['created_at'])]
class StockMovement
{
    public const TYPE_ADD = 'add';
    public const TYPE_REMOVE = 'remove';
    public const TYPES = [self::TYPE_ADD, self::TYPE_REMOVE];

    public const SOURCE_ADJUSTMENT = 'adjustment';
    public const SOURCE_PRODUCT_CREATED = 'product_created';
    public const SOURCE_PRODUCT_EDIT = 'product_edit';
    public const SOURCE_LABELS = [
        self::SOURCE_ADJUSTMENT => 'Stock update',
        self::SOURCE_PRODUCT_CREATED => 'New product',
        self::SOURCE_PRODUCT_EDIT => 'Product edit',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Product $product = null;

    #[ORM\Column(length: 255)]
    private string $productName = '';

    #[ORM\Column(length: 2)]
    private string $size = '';

    #[ORM\Column(length: 10)]
    private string $type = self::TYPE_ADD;

    /** Units actually added or removed (always positive). */
    #[ORM\Column]
    private int $quantity = 0;

    #[ORM\Column]
    private int $quantityBefore = 0;

    #[ORM\Column]
    private int $quantityAfter = 0;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $actor = null;

    #[ORM\Column(length: 180)]
    private string $actorName = '';

    #[ORM\Column(length: 20)]
    private string $actorRole = '';

    #[ORM\Column(length: 30, options: ['default' => self::SOURCE_ADJUSTMENT])]
    private string $source = self::SOURCE_ADJUSTMENT;

    /** The only editable part of a log entry: a free-text remark (reason, correction, etc.). */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $note = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public static function record(Product $product, string $size, int $before, int $after, User $actor, string $source): ?self
    {
        if ($before === $after) {
            return null;
        }

        $m = new self();
        $m->product = $product;
        $m->productName = $product->getName();
        $m->size = $size;
        $m->type = $after > $before ? self::TYPE_ADD : self::TYPE_REMOVE;
        $m->quantity = abs($after - $before);
        $m->quantityBefore = $before;
        $m->quantityAfter = $after;
        $m->actor = $actor;
        $m->actorName = $actor->getUsername();
        $m->actorRole = $actor->getRole();
        $m->source = $source;

        return $m;
    }

    /**
     * Correct the details of this history row. "Before" stays as recorded, "after" is recalculated.
     * This never touches the product's real stock level.
     */
    public function revise(string $size, string $type, int $quantity, ?string $note): static
    {
        $this->size = $size;
        $this->type = $type;
        $this->quantity = $quantity;
        $this->quantityAfter = self::TYPE_ADD === $type ? $this->quantityBefore + $quantity : $this->quantityBefore - $quantity;
        $this->note = $note;

        return $this;
    }

    public function getId(): ?int { return $this->id; }
    public function getProduct(): ?Product { return $this->product; }
    public function getProductName(): string { return $this->productName; }
    public function getSize(): string { return $this->size; }
    public function getType(): string { return $this->type; }
    public function isAdd(): bool { return self::TYPE_ADD === $this->type; }
    public function getQuantity(): int { return $this->quantity; }
    public function getQuantityBefore(): int { return $this->quantityBefore; }
    public function getQuantityAfter(): int { return $this->quantityAfter; }
    public function getActor(): ?User { return $this->actor; }
    public function getActorName(): string { return $this->actorName; }
    public function getActorRole(): string { return $this->actorRole; }
    public function getSource(): string { return $this->source; }
    public function getSourceLabel(): string { return self::SOURCE_LABELS[$this->source] ?? $this->source; }
    public function getNote(): ?string { return $this->note; }

    public function setNote(?string $note): static
    {
        $this->note = $note;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
