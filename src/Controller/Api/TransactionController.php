<?php

namespace App\Controller\Api;

use App\Entity\Transaction;
use App\Entity\User;
use OpenApi\Attributes as OA;
use App\Repository\TransactionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag(name: 'Transactions')]
#[Route('/api/v1/transactions')]
class TransactionController extends AbstractController
{
    #[OA\Get(
        path: '/api/v1/transactions',
        description: 'Получение истории начислений и списаний текущего пользователя',
        summary: 'История транзакций',
        security: [['Bearer' => []]]
    )]
    #[OA\Parameter(
        name: 'filter[type]',
        description: 'Тип транзакции: payment или deposit',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: 'filter[course_code]',
        description: 'Символьный код курса',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: 'filter[skip_expired]',
        description: 'Не показывать просроченные аренды',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'boolean')
    )]
    #[OA\Response(
        response: 200,
        description: 'История транзакций'
    )]
    #[OA\Response(
        response: 401,
        description: 'JWT Token not found'
    )]
    #[Route('', name: 'api_transaction_list', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function list(
        Request $request,
        TransactionRepository $transactionRepository
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        $filters = [
            'type' => $request->query->all('filter')['type'] ?? null,
            'course_code' => $request->query->all('filter')['course_code'] ?? null,
            'skip_expired' => $request->query->all('filter')['skip_expired'] ?? null,
        ];

        $transactions = $transactionRepository->findByUserWithFilters($user, $filters);

        $data = [];

        foreach ($transactions as $transaction) {
            $data[] = $this->formatTransaction($transaction);
        }

        return $this->json($data);
    }

    private function formatTransaction(Transaction $transaction): array
    {
        $data = [
            'id' => $transaction->getId(),
            'created_at' => $transaction->getCreatedAt()?->format(DATE_ATOM),
            'type' => $transaction->getType(),
            'amount' => $transaction->getAmount(),
        ];

        if ($transaction->getCourse() !== null) {
            $data['course_code'] = $transaction->getCourse()->getCode();
        }

        if ($transaction->getExpiresAt() !== null) {
            $data['expires_at'] = $transaction->getExpiresAt()->format(DATE_ATOM);
        }

        return $data;
    }
}
