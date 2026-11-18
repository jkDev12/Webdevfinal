<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class LogNoteData
{
    #[Assert\Length(max: 255, maxMessage: 'The note can be at most {{ limit }} characters.')]
    public ?string $note = null;
}
