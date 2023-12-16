<?php

namespace App\EventSubscriber;

use App\Exception\FormHttpException;
use App\Factory\NormalizerFactory;
use App\Http\HttpExceptionResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class ExceptionSubscriber implements EventSubscriberInterface
{
    private NormalizerFactory $normalizerFactory;

    public function __construct(NormalizerFactory $normalizerFactory)
    {
        $this->normalizerFactory = $normalizerFactory;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    /**
     * If accepting Json, create a custom Json response.
     */
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $request = $event->getRequest();

        if (\in_array('application/json', $request->getAcceptableContentTypes()) && $this->supportException(
            $exception
        )) {
            $response = $this->createApiResponse($exception);
            $event->setResponse($response);
        }
    }

    private function supportException(\Throwable $exception): bool
    {
        return $exception instanceof HttpExceptionInterface;
    }

    /**
     * Creates a Json response from any Exception.
     */
    private function createApiResponse(\Throwable $exception): JsonResponse
    {
        $statusCode = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode(
        ) : Response::HTTP_INTERNAL_SERVER_ERROR;
        $violations = [];

        $normalizer = $this->normalizerFactory->getNormalizer($exception);
        if ($exception instanceof FormHttpException) {
            $violations = $normalizer ? $normalizer->normalize($exception) : [];
        }

        return new HttpExceptionResponse(
            'https://tools.ietf.org/html/rfc2616#section-10',
            $exception->getMessage(),
            $statusCode,
            $violations,
        );
    }
}
