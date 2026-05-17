<?php

namespace App\Tests\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class CourseApiTest extends WebTestCase
{
    public function testCoursesList(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/v1/courses');

        $this->assertResponseIsSuccessful();
        $this->assertJson($client->getResponse()->getContent());

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($data);
        $this->assertNotEmpty($data);

        $this->assertArrayHasKey('code', $data[0]);
        $this->assertArrayHasKey('title', $data[0]);
        $this->assertArrayHasKey('type', $data[0]);
    }

    public function testCourseShow(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/v1/courses/php-basic');

        $this->assertResponseIsSuccessful();
        $this->assertJson($client->getResponse()->getContent());

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertSame('php-basic', $data['code']);
        $this->assertSame('Основы PHP', $data['title']);
        $this->assertSame('buy', $data['type']);
        $this->assertSame(199.99, $data['price']);
    }

    public function testCourseNotFound(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/v1/courses/not-found');

        $this->assertResponseStatusCodeSame(404);
        $this->assertJson($client->getResponse()->getContent());
    }

    public function testAdminCanCreateCourse(): void
    {
        $client = static::createClient();
        $token = $this->getToken($client, 'admin@test.local', 'Admin_pass');

        $client->request(
            'POST',
            '/api/v1/courses',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            json_encode([
                'code' => 'docker-basic',
                'title' => 'Основы Docker',
                'type' => 'buy',
                'price' => 149.99,
            ])
        );

        $this->assertResponseStatusCodeSame(201);
        $this->assertJson($client->getResponse()->getContent());

        $client->request('GET', '/api/v1/courses/docker-basic');

        $this->assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertSame('docker-basic', $data['code']);
        $this->assertSame('Основы Docker', $data['title']);
        $this->assertSame('buy', $data['type']);
        $this->assertSame(149.99, $data['price']);
    }

    public function testAdminCanUpdateCourse(): void
    {
        $client = static::createClient();
        $token = $this->getToken($client, 'admin@test.local', 'Admin_pass');

        $client->request(
            'POST',
            '/api/v1/courses/php-basic',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            json_encode([
                'code' => 'php-basic',
                'title' => 'Основы PHP обновлено',
                'type' => 'rent',
                'price' => 129.99,
            ])
        );

        $this->assertResponseIsSuccessful();
        $this->assertJson($client->getResponse()->getContent());

        $client->request('GET', '/api/v1/courses/php-basic');

        $this->assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertSame('php-basic', $data['code']);
        $this->assertSame('Основы PHP обновлено', $data['title']);
        $this->assertSame('rent', $data['type']);
        $this->assertSame(129.99, $data['price']);
    }

    public function testUserCannotCreateCourse(): void
    {
        $client = static::createClient();
        $token = $this->getToken($client, 'user@test.local', 'Topparol');

        $client->request(
            'POST',
            '/api/v1/courses',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            json_encode([
                'code' => 'user-course',
                'title' => 'Курс от пользователя',
                'type' => 'buy',
                'price' => 100,
            ])
        );

        $this->assertResponseStatusCodeSame(403);
        $this->assertJson($client->getResponse()->getContent());
    }

    public function testGuestCannotCreateCourse(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/v1/courses',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'code' => 'guest-course',
                'title' => 'Курс без токена',
                'type' => 'buy',
                'price' => 100,
            ])
        );

        $this->assertResponseStatusCodeSame(401);
        $this->assertJson($client->getResponse()->getContent());
    }

    public function testCreateCourseValidation(): void
    {
        $client = static::createClient();
        $token = $this->getToken($client, 'admin@test.local', 'Admin_pass');

        $client->request(
            'POST',
            '/api/v1/courses',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            json_encode([
                'code' => '',
                'title' => '',
                'type' => 'bad-type',
                'price' => -10,
            ])
        );

        $this->assertResponseStatusCodeSame(400);
        $this->assertJson($client->getResponse()->getContent());

        $content = $client->getResponse()->getContent();

        $this->assertStringContainsString('Введите код курса.', $content);
        $this->assertStringContainsString('Введите название курса.', $content);
        $this->assertStringContainsString('Тип курса должен быть free, buy или rent.', $content);
        $this->assertStringContainsString('Стоимость курса не может быть отрицательной.', $content);
    }

    private function getToken(KernelBrowser $client, string $username, string $password): string
    {
        $client->request(
            'POST',
            '/api/v1/auth',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'username' => $username,
                'password' => $password,
            ])
        );

        $this->assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('token', $data);

        return $data['token'];
    }
}
