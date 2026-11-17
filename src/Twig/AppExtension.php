<?php

namespace App\Twig;

use Twig\Attribute\AsTwigFilter;

final class AppExtension
{
    #[AsTwigFilter('price')]
    public function price(string|float|int|null $amount): string
    {
        return '₱'.number_format((float) $amount, 2);
    }
}
