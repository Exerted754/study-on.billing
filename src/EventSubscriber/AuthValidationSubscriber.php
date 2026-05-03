<?php

namespace App\EventSubscriber;

use App\Dto\AuthUserDto;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AuthValidationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ValidatorInterface $validator
    ) {
    }

    private function createJsonResponse(array $data, int $statusCode): JsonResponse
    {
        $response = new JsonResponse(null, $statusCode);
        $response->setEncodingOptions(JsonResponse::DEFAULT_ENCODING_OPTIONS | JSON_UNESCAPED_UNICODE);
        $response->setData($data);

        return $response;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 20],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if ($request->getPathInfo() !== '/api/v1/auth') {
            return;
        }

        if ($request->getMethod() !== 'POST') {
            return;
        }

        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            $event->setResponse($this->createJsonResponse([
                'code' => 400,
                'message' => 'Некорректный JSON.',
            ], 400));

            return;
        }

        $dto = new AuthUserDto();
        $dto->username = $data['username'] ?? null;
        $dto->password = $data['password'] ?? null;

        $errors = $this->validator->validate($dto);

        if (count($errors) > 0) {
            $event->setResponse($this->createJsonResponse([
                'code' => 400,
                'message' => $this->getErrorMessages($errors),
            ], 400));
        }
    }

    private function getErrorMessages(ConstraintViolationListInterface $errors): string
    {
        $messages = [];

        foreach ($errors as $error) {
            $messages[] = $error->getMessage();
        }

        return implode("\n", $messages);
    }
}
