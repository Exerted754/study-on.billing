<?php

namespace App\Controller\Api;

use App\Entity\Course;
use App\Entity\User;
use OpenApi\Attributes as OA;
use App\Service\PaymentService;
use App\Repository\CourseRepository;
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

    private function createJsonResponse(array $data, int $statusCode): JsonResponse
    {
        $response = new JsonResponse(null, $statusCode);
        $response->setEncodingOptions(JsonResponse::DEFAULT_ENCODING_OPTIONS | JSON_UNESCAPED_UNICODE);
        $response->setData($data);

        return $response;
    }
}
