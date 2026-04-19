<?php

namespace App\Controller\Api;

use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AuthController extends AbstractController
{
    #[Route('/api/v1/auth', name: 'api_v1_auth', methods: ['POST'])]
    #[OA\Post(
        summary: 'Авторизация пользователя',
        description: 'Принимает username и password в JSON и возвращает JWT-токен'
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['username', 'password'],
            properties: [
                new OA\Property(property: 'username', type: 'string', example: 'user@test.local'),
                new OA\Property(property: 'password', type: 'string', example: '123456'),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Успешная авторизация',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'token', type: 'string'),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Неверный логин или пароль')]
    #[OA\Tag(name: 'Auth')]
    public function auth(): Response
    {
        throw new \LogicException('This code should never be reached.');
    }
}
