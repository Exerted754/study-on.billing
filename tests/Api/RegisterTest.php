<?php

namespace App\Tests\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RegisterTest extends WebTestCase
{
    public function testRegisterSuccess(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/v1/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'newuser1@test.local',
                'password' => '123456',
            ])
        );

        $this->assertResponseStatusCodeSame(201);
        $this->assertJson($client->getResponse()->getContent());

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('token', $data);
        $this->assertArrayHasKey('roles', $data);
        $this->assertContains('ROLE_USER', $data['roles']);
    }

    public function testRegisterShortPassword(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/v1/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'shortpass@test.local',
                'password' => '123',
            ])
        );

        $this->assertResponseStatusCodeSame(400);
        $this->assertJson($client->getResponse()->getContent());

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('message', $data);
        $this->assertStringContainsString(
            'Пароль не должен содержать менее 6 символов',
            $data['message']
        );
    }

    public function testRegisterExistingEmail(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/v1/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'user@test.local',
                'password' => '123456',
            ])
        );

        $this->assertResponseStatusCodeSame(400);
        $this->assertJson($client->getResponse()->getContent());

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('message', $data);
        $this->assertSame(
            'Пользователь с таким email уже существует',
            $data['message']
        );
    }
}
