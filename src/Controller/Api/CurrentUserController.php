<?php

namespace App\Controller\Api;

use App\Entity\User;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class CurrentUserController extends AbstractController
{
    #[Route('/api/v1/users/current', name: 'api_v1_users_current', methods: ['GET'])]
    #[OA\Get(
        summary: 'Текущий пользователь',
        description: 'Возвращает email, роли и баланс текущего пользователя'
    )]
    #[OA\Response(
        response: 200,
        description: 'Данные текущего пользователя',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'username', type: 'string', example: 'user@test.local'),
                new OA\Property(
                    property: 'roles',
                    type: 'array',
                    items: new OA\Items(type: 'string')
                ),
                new OA\Property(property: 'balance', type: 'number', format: 'float', example: 1000),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'JWT Token not found or invalid')]
    #[OA\Tag(name: 'User')]
    #[Security(name: 'Bearer')]
    public function currentUser(): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        return $this->json([
            'username' => $user->getUserIdentifier(),
            'roles' => $user->getRoles(),
            'balance' => $user->getBalance(),
        ]);
    }
}
