<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class CourseDto
{
    #[Assert\NotBlank(message: 'Введите тип курса.')]
    #[Assert\Choice(
        choices: ['free', 'buy', 'rent'],
        message: 'Тип курса должен быть free, buy или rent.'
    )]
    public ?string $type = null;

    #[Assert\NotBlank(message: 'Введите название курса.')]
    #[Assert\Length(
        max: 255,
        maxMessage: 'Название курса не должно быть длиннее {{ limit }} символов.'
    )]
    public ?string $title = null;

    #[Assert\NotBlank(message: 'Введите код курса.')]
    #[Assert\Length(
        max: 255,
        maxMessage: 'Код курса не должен быть длиннее {{ limit }} символов.'
    )]
    public ?string $code = null;

    #[Assert\PositiveOrZero(message: 'Стоимость курса не может быть отрицательной.')]
    public ?float $price = null;
}
