<?php

namespace App\Controller\Api;

use App\Entity\Course;
use App\Entity\User;
use OpenApi\Attributes as OA;
use App\Service\PaymentService;
use App\Dto\CourseDto;
use App\Repository\CourseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag(name: 'Courses')]
#[Route('/api/v1/courses')]
class CourseController extends AbstractController
{
    #[OA\Get(
        path: '/api/v1/courses',
        description: 'Получение списка курсов с типом оплаты и стоимостью',
        summary: 'Список курсов'
    )]
    #[OA\Response(
        response: 200,
        description: 'Список курсов'
    )]
    #[Route('', name: 'api_course_list', methods: ['GET'])]
    public function list(CourseRepository $courseRepository): JsonResponse
    {
        $courses = $courseRepository->findAll();

        $data = [];

        foreach ($courses as $course) {
            $data[] = $this->formatCourse($course);
        }

        return $this->json($data);
    }

    #[OA\Get(
        path: '/api/v1/courses/{code}',
        description: 'Получение информации о курсе по символьному коду',
        summary: 'Информация о курсе'
    )]
    #[OA\Parameter(
        name: 'code',
        description: 'Символьный код курса',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Response(
        response: 200,
        description: 'Информация о курсе'
    )]
    #[OA\Response(
        response: 404,
        description: 'Курс не найден'
    )]
    #[Route('/{code}', name: 'api_course_show', methods: ['GET'])]
    public function show(string $code, CourseRepository $courseRepository): JsonResponse
    {
        $course = $courseRepository->findOneBy([
            'code' => $code,
        ]);

        if (!$course) {
            throw $this->createNotFoundException('Курс не найден.');
        }

        return $this->json($this->formatCourse($course));
    }

    private function formatCourse(Course $course): array
    {
        $data = [
            'code' => $course->getCode(),
            'title' => $course->getTitle(),
            'type' => $course->getType(),
        ];

        if ($course->getPrice() !== null) {
            $data['price'] = $course->getPrice();
        }

        return $data;
    }

    #[OA\Post(
        path: '/api/v1/courses/{code}/pay',
        description: 'Оплата или аренда курса текущим пользователем',
        summary: 'Оплата курса',
        security: [['Bearer' => []]]
    )]
    #[OA\Parameter(
        name: 'code',
        description: 'Символьный код курса',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Response(
        response: 200,
        description: 'Курс успешно оплачен'
    )]
    #[OA\Response(
        response: 401,
        description: 'JWT Token not found'
    )]
    #[OA\Response(
        response: 404,
        description: 'Курс не найден'
    )]
    #[OA\Response(
        response: 406,
        description: 'Ошибка оплаты'
    )]
    #[Route('/{code}/pay', name: 'api_course_pay', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function pay(
        string $code,
        CourseRepository $courseRepository,
        PaymentService $paymentService
    ): JsonResponse {
        $course = $courseRepository->findOneBy([
            'code' => $code,
        ]);

        if (!$course) {
            throw $this->createNotFoundException('Курс не найден.');
        }

        /** @var User $user */
        $user = $this->getUser();

        try {
            $transaction = $paymentService->pay($user, $course);
        } catch (\Exception $exception) {
            return $this->createJsonResponse([
                'code' => Response::HTTP_NOT_ACCEPTABLE,
                'message' => $exception->getMessage(),
            ], Response::HTTP_NOT_ACCEPTABLE);
        }

        $data = [
            'success' => true,
            'course_type' => $course->getType(),
        ];

        if ($transaction->getExpiresAt() !== null) {
            $data['expires_at'] = $transaction->getExpiresAt()->format(DATE_ATOM);
        }

        return $this->createJsonResponse($data, Response::HTTP_OK);
    }

    #[OA\Post(
        path: '/api/v1/courses',
        description: 'Создание курса в биллинге',
        summary: 'Создание курса',
        security: [['Bearer' => []]]
    )]
    #[OA\Response(
        response: 201,
        description: 'Курс успешно создан'
    )]
    #[OA\Response(
        response: 400,
        description: 'Ошибка валидации'
    )]
    #[OA\Response(
        response: 403,
        description: 'Доступ запрещён'
    )]
    #[Route('', name: 'api_course_create', methods: ['POST'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function create(
        Request $request,
        CourseRepository $courseRepository,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ): JsonResponse {
        $dto = $this->createCourseDto($request);

        $errors = $validator->validate($dto);

        if (count($errors) > 0) {
            return $this->createJsonResponse([
                'code' => Response::HTTP_BAD_REQUEST,
                'message' => $this->getErrorMessages($errors),
            ], Response::HTTP_BAD_REQUEST);
        }

        if ($courseRepository->findOneBy(['code' => $dto->code])) {
            return $this->createJsonResponse([
                'code' => Response::HTTP_BAD_REQUEST,
                'message' => 'Курс с таким кодом уже существует.',
            ], Response::HTTP_BAD_REQUEST);
        }

        if ($dto->type !== Course::TYPE_FREE && ($dto->price === null || $dto->price <= 0)) {
            return $this->createJsonResponse([
                'code' => Response::HTTP_BAD_REQUEST,
                'message' => 'Для платного курса необходимо указать стоимость.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $course = new Course();
        $course->setCode($dto->code);
        $course->setTitle($dto->title);
        $course->setType($dto->type);
        $course->setPrice($dto->type === Course::TYPE_FREE ? null : $dto->price);

        $entityManager->persist($course);
        $entityManager->flush();

        return $this->createJsonResponse([
            'success' => true,
        ], Response::HTTP_CREATED);
    }

    #[OA\Post(
        path: '/api/v1/courses/{code}',
        description: 'Редактирование курса в биллинге',
        summary: 'Редактирование курса',
        security: [['Bearer' => []]]
    )]
    #[OA\Parameter(
        name: 'code',
        description: 'Текущий символьный код курса',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Response(
        response: 200,
        description: 'Курс успешно обновлён'
    )]
    #[OA\Response(
        response: 400,
        description: 'Ошибка валидации'
    )]
    #[OA\Response(
        response: 403,
        description: 'Доступ запрещён'
    )]
    #[OA\Response(
        response: 404,
        description: 'Курс не найден'
    )]
    #[Route('/{code}', name: 'api_course_update', methods: ['POST'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function update(
        string $code,
        Request $request,
        CourseRepository $courseRepository,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ): JsonResponse {
        $course = $courseRepository->findOneBy([
            'code' => $code,
        ]);

        if (!$course) {
            throw $this->createNotFoundException('Курс не найден.');
        }

        $dto = $this->createCourseDto($request);

        $errors = $validator->validate($dto);

        if (count($errors) > 0) {
            return $this->createJsonResponse([
                'code' => Response::HTTP_BAD_REQUEST,
                'message' => $this->getErrorMessages($errors),
            ], Response::HTTP_BAD_REQUEST);
        }

        $courseWithNewCode = $courseRepository->findOneBy([
            'code' => $dto->code,
        ]);

        if ($courseWithNewCode !== null && $courseWithNewCode->getId() !== $course->getId()) {
            return $this->createJsonResponse([
                'code' => Response::HTTP_BAD_REQUEST,
                'message' => 'Курс с таким кодом уже существует.',
            ], Response::HTTP_BAD_REQUEST);
        }

        if ($dto->type !== Course::TYPE_FREE && ($dto->price === null || $dto->price <= 0)) {
            return $this->createJsonResponse([
                'code' => Response::HTTP_BAD_REQUEST,
                'message' => 'Для платного курса необходимо указать стоимость.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $course->setCode($dto->code);
        $course->setTitle($dto->title);
        $course->setType($dto->type);
        $course->setPrice($dto->type === Course::TYPE_FREE ? null : $dto->price);

        $entityManager->flush();

        return $this->createJsonResponse([
            'success' => true,
        ], Response::HTTP_OK);
    }

    private function createJsonResponse(array $data, int $statusCode): JsonResponse
    {
        $response = new JsonResponse(null, $statusCode);
        $response->setEncodingOptions(JsonResponse::DEFAULT_ENCODING_OPTIONS | JSON_UNESCAPED_UNICODE);
        $response->setData($data);

        return $response;
    }

    private function createCourseDto(Request $request): CourseDto
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            $data = [];
        }

        $dto = new CourseDto();
        $dto->type = $data['type'] ?? null;
        $dto->title = $data['title'] ?? null;
        $dto->code = $data['code'] ?? null;
        $dto->price = isset($data['price']) ? (float) $data['price'] : null;

        return $dto;
    }

    private function getErrorMessages(iterable $errors): string
    {
        $messages = [];

        foreach ($errors as $error) {
            $messages[] = $error->getMessage();
        }

        return implode("\n", $messages);
    }
}
