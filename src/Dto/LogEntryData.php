<?php

namespace App\Dto;

use App\Entity\ProductStock;
use App\Entity\StockMovement;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class LogEntryData
{
    #[Assert\NotBlank(message: 'Choose a size.')]
    #[Assert\Choice(choices: ProductStock::SIZES, message: 'Choose a valid size.')]
    public ?string $size = null;

    #[Assert\NotBlank(message: 'Choose whether stock was posted or removed.')]
    #[Assert\Choice(choices: StockMovement::TYPES, message: 'Choose posted or removed.')]
    public ?string $type = null;

    #[Assert\NotNull(message: 'Enter a quantity.')]
    #[Assert\Positive(message: 'Quantity must be at least 1.')]
    public ?int $quantity = null;

    #[Assert\Length(max: 255, maxMessage: 'The note can be at most {{ limit }} characters.')]
    public ?string $note = null;

    /** Stock level before this entry happened (fixed; used to validate removals). */
    public int $quantityBefore = 0;

    public static function fromMovement(StockMovement $m): self
    {
        $data = new self();
        $data->size = $m->getSize();
        $data->type = $m->getType();
        $data->quantity = $m->getQuantity();
        $data->note = $m->getNote();
        $data->quantityBefore = $m->getQuantityBefore();

        return $data;
    }

    #[Assert\Callback]
    public function validateRemoval(ExecutionContextInterface $context): void
    {
        if (StockMovement::TYPE_REMOVE === $this->type && null !== $this->quantity && $this->quantity > $this->quantityBefore) {
            $context->buildViolation('You cannot remove more than the {{ before }} unit(s) that were in stock.')
                ->setParameter('{{ before }}', (string) $this->quantityBefore)
                ->atPath('quantity')
                ->addViolation();
        }
    }
}
