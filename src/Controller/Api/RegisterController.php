<?php

namespace App\Controller\Api;

use App\Dto\RegisterUserDto;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RegisterController extends AbstractController
{
    #[Route('/api/v1/register', name: 'api_v1_register', methods: ['POST'])]
    #[OA\Post(
        summary: 'Регистрация пользователя',
        description: 'Создаёт пользователя и возвращает JWT-токен'
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            ref: new Model(type: RegisterUserDto::class)
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Пользователь успешно зарегистрирован',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'token', type: 'string'),
                new OA\Property(
                    property: 'roles',
                    type: 'array',
                    items: new OA\Items(type: 'string')
                ),
            ]
        )
    )]
    #[OA\Response(response: 400, description: 'Ошибка валидации или пользователь уже существует')]
    #[OA\Tag(name: 'Auth')]
    public function register(
        Request $request,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        JWTTokenManagerInterface $jwtManager
    ): JsonResponse {
        /** @var RegisterUserDto $dto */
        $dto = $serializer->deserialize(
            $request->getContent(),
            RegisterUserDto::class,
            'json'
        );

        $errors = $validator->validate($dto);

        if (count($errors) > 0) {
            return $this->json([
                'code' => 400,
                'message' => (string) $errors,
            ], 400);
        }

        $existingUser = $entityManager->getRepository(User::class)->findOneBy([
            'email' => $dto->email,
        ]);

        if ($existingUser) {
            return $this->json([
                'code' => 400,
                'message' => 'Пользователь с таким email уже существует',
            ], 400);
        }

        $user = new User();
        $user->setEmail($dto->email);
        $user->setRoles(['ROLE_USER']);
        $user->setBalance(0);
        $user->setPassword(
            $passwordHasher->hashPassword($user, $dto->password)
        );

        $entityManager->persist($user);
        $entityManager->flush();

        $token = $jwtManager->create($user);

        return $this->json([
            'token' => $token,
            'roles' => $user->getRoles(),
        ], 201);
    }
}
