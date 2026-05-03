<?php

namespace App\Controller\Api;

use App\Entity\Course;
use App\Entity\User;
use App\Service\PaymentService;
use App\Repository\CourseRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1/courses')]
class CourseController extends AbstractController
{
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
