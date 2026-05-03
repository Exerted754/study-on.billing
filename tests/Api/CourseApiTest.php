<?php

namespace App\Tests\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

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
        $this->assertArrayHasKey('type', $data[0]);
    }

    public function testCourseShow(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/v1/courses/symfony-basics');

        $this->assertResponseIsSuccessful();
        $this->assertJson($client->getResponse()->getContent());

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertSame('symfony-basics', $data['code']);
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
}
