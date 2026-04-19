<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class RegisterUserDto
{
    #[Assert\NotBlank(message: 'Email обязателен!')]
    #[Assert\Email(message: 'Некорректный email!')]
    public ?string $email = null;

    #[Assert\NotBlank(message: 'Пароль обязателен!')]
    #[Assert\Length(
        min: 6,
        minMessage: 'Пароль не должен содержать менее 6 символов!'
    )]
    public ?string $password = null;
}
