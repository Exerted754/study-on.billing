<?php

namespace App\Tests\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CurrentUserTest extends WebTestCase
{
    public function testCurrentUserWithoutToken(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/v1/users/current');

        $this->assertResponseStatusCodeSame(401);
        $this->assertJson($client->getResponse()->getContent());

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('code', $data);
        $this->assertArrayHasKey('message', $data);
    }

    public function testCurrentUserWithToken(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/v1/auth',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'username' => 'user@test.local',
                'password' => 'Topparol',
            ])
        );

        $this->assertResponseStatusCodeSame(200);

        $authData = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('token', $authData);

        $token = $authData['token'];

        $client->request(
            'GET',
            '/api/v1/users/current',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );

        $this->assertResponseStatusCodeSame(200);
        $this->assertJson($client->getResponse()->getContent());

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertSame('user@test.local', $data['username']);
        $this->assertContains('ROLE_USER', $data['roles']);
        $this->assertArrayHasKey('balance', $data);
    }
}
