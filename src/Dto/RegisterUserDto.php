<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class RegisterUserDto
{
    #[Assert\NotBlank(message: 'Введите email.')]
    #[Assert\Email(message: 'Введите корректный email.')]
    #[Assert\Length(
        max: 180,
        maxMessage: 'Email не должен быть длиннее {{ limit }} символов.'
    )]
    public ?string $email = null;

    #[Assert\NotBlank(message: 'Введите пароль.')]
    #[Assert\Length(
        min: 6,
        max: 20,
        minMessage: 'Пароль не должен содержать менее {{ limit }} символов.',
        maxMessage: 'Пароль не должен быть длиннее {{ limit }} символов.'
    )]
    public ?string $password = null;
}
