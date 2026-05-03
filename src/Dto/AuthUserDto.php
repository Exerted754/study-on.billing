<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class AuthUserDto
{
    #[Assert\NotBlank(message: 'Введите email.')]
    #[Assert\Email(message: 'Введите корректный email.')]
    #[Assert\Length(
        max: 180,
        maxMessage: 'Email не должен быть длиннее {{ limit }} символов.'
    )]
    public ?string $username = null;

    #[Assert\NotBlank(message: 'Введите пароль.')]
    #[Assert\Length(
        max: 4096,
        maxMessage: 'Пароль не должен быть длиннее {{ limit }} символов.'
    )]
    public ?string $password = null;
}
