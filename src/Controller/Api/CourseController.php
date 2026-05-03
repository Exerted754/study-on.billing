<?php

namespace App\Controller\Api;

use App\Entity\Course;
use App\Repository\CourseRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

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
}
