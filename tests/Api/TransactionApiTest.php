<?php

namespace App\Tests\Api;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TransactionApiTest extends WebTestCase
{
    private function getToken(KernelBrowser $client): string
    {
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

        $this->assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('token', $data);

        return $data['token'];
    }

    public function testTransactionsRequiresToken(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/v1/transactions');

        $this->assertResponseStatusCodeSame(401);
        $this->assertJson($client->getResponse()->getContent());
    }

    public function testTransactionsList(): void
    {
        $client = static::createClient();
        $token = $this->getToken($client);

        $client->request(
            'GET',
            '/api/v1/transactions',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );

        $this->assertResponseIsSuccessful();
        $this->assertJson($client->getResponse()->getContent());

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($data);
        $this->assertNotEmpty($data);
        $this->assertArrayHasKey('type', $data[0]);
        $this->assertArrayHasKey('amount', $data[0]);
        $this->assertArrayHasKey('created_at', $data[0]);
    }

    public function testTransactionsFilterByType(): void
    {
        $client = static::createClient();
        $token = $this->getToken($client);

        $client->request(
            'GET',
            '/api/v1/transactions?filter[type]=deposit',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );

        $this->assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);

        foreach ($data as $transaction) {
            $this->assertSame('deposit', $transaction['type']);
        }
    }

    public function testTransactionsFilterByCourseCode(): void
    {
        $client = static::createClient();
        $token = $this->getToken($client);

        $client->request(
            'POST',
            '/api/v1/courses/symfony-basics/pay',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );

        $this->assertResponseIsSuccessful();

        $client->request(
            'GET',
            '/api/v1/transactions?filter[course_code]=symfony-basics',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );

        $this->assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertNotEmpty($data);

        foreach ($data as $transaction) {
            $this->assertSame('symfony-basics', $transaction['course_code']);
        }
    }
}
