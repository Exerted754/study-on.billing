<?php

namespace App\Tests\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AuthTest extends WebTestCase
{
    public function testAuthSuccess(): void
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
        $this->assertJson($client->getResponse()->getContent());

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('token', $data);
        $this->assertNotEmpty($data['token']);
    }

    public function testAuthBadPassword(): void
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
                'password' => 'wrongpass',
            ])
        );

        $this->assertResponseStatusCodeSame(401);
        $this->assertJson($client->getResponse()->getContent());
    }
}
