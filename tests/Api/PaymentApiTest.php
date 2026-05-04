<?php

namespace App\Tests\Api;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PaymentApiTest extends WebTestCase
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

    public function testPayBuyCourse(): void
    {
        $client = static::createClient();
        $token = $this->getToken($client);

        $client->request(
            'POST',
            '/api/v1/courses/php-basic/pay',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );

        $this->assertResponseIsSuccessful();
        $this->assertJson($client->getResponse()->getContent());

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertTrue($data['success']);
        $this->assertSame('buy', $data['course_type']);
        $this->assertArrayNotHasKey('expires_at', $data);
    }

    public function testPayRentCourse(): void
    {
        $client = static::createClient();
        $token = $this->getToken($client);

        $client->request(
            'POST',
            '/api/v1/courses/symfony-start/pay',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );

        $this->assertResponseIsSuccessful();
        $this->assertJson($client->getResponse()->getContent());

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertTrue($data['success']);
        $this->assertSame('rent', $data['course_type']);
        $this->assertArrayHasKey('expires_at', $data);
    }

    public function testPayFreeCourseReturnsError(): void
    {
        $client = static::createClient();
        $token = $this->getToken($client);

        $client->request(
            'POST',
            '/api/v1/courses/postgresql-base/pay',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );

        $this->assertResponseStatusCodeSame(406);
        $this->assertJson($client->getResponse()->getContent());
        $this->assertStringContainsString(
            'Курс бесплатный',
            $client->getResponse()->getContent()
        );
    }

    public function testPayCourseWithoutTokenReturns401(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/v1/courses/php-basic/pay');

        $this->assertResponseStatusCodeSame(401);
        $this->assertJson($client->getResponse()->getContent());
    }

    public function testPayUnknownCourseReturns404(): void
    {
        $client = static::createClient();
        $token = $this->getToken($client);

        $client->request(
            'POST',
            '/api/v1/courses/unknown-course/pay',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );

        $this->assertResponseStatusCodeSame(404);
        $this->assertJson($client->getResponse()->getContent());
    }

    public function testPayCourseWithoutEnoughMoney(): void
    {
        $client = static::createClient();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $user = $entityManager->getRepository(User::class)->findOneBy([
            'email' => 'user@test.local',
        ]);

        $user->setBalance(1);
        $entityManager->flush();

        $token = $this->getToken($client);

        $client->request(
            'POST',
            '/api/v1/courses/php-basic/pay',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );

        $this->assertResponseStatusCodeSame(406);
        $this->assertJson($client->getResponse()->getContent());
        $this->assertStringContainsString(
            'На вашем счету недостаточно средств',
            $client->getResponse()->getContent()
        );
    }

    public function testCannotPayAlreadyPaidCourse(): void
    {
        $client = static::createClient();
        $token = $this->getToken($client);

        $client->request(
            'POST',
            '/api/v1/courses/php-basic/pay',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );

        $this->assertResponseIsSuccessful();

        $client->request(
            'POST',
            '/api/v1/courses/php-basic/pay',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );

        $this->assertResponseStatusCodeSame(406);
        $this->assertJson($client->getResponse()->getContent());
        $this->assertStringContainsString(
            'Курс уже оплачен',
            $client->getResponse()->getContent()
        );
    }

    public function testCannotPayAlreadyRentedCourse(): void
    {
        $client = static::createClient();
        $token = $this->getToken($client);

        $client->request(
            'POST',
            '/api/v1/courses/symfony-start/pay',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );

        $this->assertResponseIsSuccessful();

        $client->request(
            'POST',
            '/api/v1/courses/symfony-start/pay',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );

        $this->assertResponseStatusCodeSame(406);
        $this->assertJson($client->getResponse()->getContent());
        $this->assertStringContainsString(
            'Курс уже арендован',
            $client->getResponse()->getContent()
        );
    }
}
