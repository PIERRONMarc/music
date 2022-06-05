<?php

namespace App\Tests\EventSubscriber;

use App\EventSubscriber\ExceptionSubscriber;
use App\Exception\FormHttpException;
use App\Factory\NormalizerFactory;
use App\Serializer\FormExceptionNormalizer;
use Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class ExceptionSubscriberTest extends TestCase
{
    public function testEventSubscription(): void
    {
        $this->assertArrayHasKey(KernelEvents::EXCEPTION, ExceptionSubscriber::getSubscribedEvents());
    }

    public function testExceptionIsTransformedInApiResponse(): void
    {
        $request = new Request();
        $request->headers->set('Accept', 'application/json');

        $event = $this->getEvent($request, $this->getFormException());
        $subscriber = $this->getSubscriber();
        $subscriber->onKernelException($event);

        $data = json_decode($event->getResponse()->getContent(), true);

        $this->assertSame('Validation failed', $data['title']);
        $this->assertSame('https://tools.ietf.org/html/rfc2616#section-10', $data['type']);
        $this->assertSame(400, $data['status']);
        $this->assertSame('foo', $data['violations'][0]['property']);
        $this->assertSame('Property is invalid.', $data['violations'][0]['message']);
    }

    public function testSubscriberDoNothingWhenNotAcceptingJsonContent(): void
    {
        $event = $this->getEvent(new Request(), $this->getFormException());
        $subscriber = $this->getSubscriber();
        $subscriber->onKernelException($event);

        $this->assertNull($event->getResponse());
    }

    public function testCreateApiResponseOnlyOnHttpException(): void
    {
        $request = new Request();
        $request->headers->set('Accept', 'application/json');

        $eventWithHttpException = $this->getEvent($request, new AccessDeniedHttpException('foo'));
        $eventWithoutHttpException = $this->getEvent($request, new AccessDeniedException());
        $subscriber = $this->getSubscriber();

        $subscriber->onKernelException($eventWithHttpException);
        $data = json_decode($eventWithHttpException->getResponse()->getContent(), true);
        $this->assertSame('foo', $data['title']);

        $subscriber->onKernelException($eventWithoutHttpException);
        $this->assertNull($eventWithoutHttpException->getResponse());
    }

    private function getSubscriber(): ExceptionSubscriber
    {
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];

        $normalizerFactory = $this->createMock(NormalizerFactory::class);
        $normalizerFactory->method('getNormalizer')->willReturn(new FormExceptionNormalizer());

        return new ExceptionSubscriber($normalizerFactory);
    }

    private function getEvent(Request $request, Exception $exception): ExceptionEvent
    {
        return new ExceptionEvent(
            $this->getMockBuilder(KernelInterface::class)->getMock(),
            $request,
            1,
            $exception
        );
    }

    private function getFormException(): FormHttpException
    {
        $form = $this->createMock(FormInterface::class);
        $form->method('getName')->willReturn('foo');
        $formError = $this->createMock(FormError::class);
        $formError->method('getOrigin')->willReturn($form);
        $formError->method('getMessage')->willReturn('Property is invalid.');
        $formErrorIterrator = new FormErrorIterator($form, [$formError]);
        $form->method('getErrors')->willReturn($formErrorIterrator);

        return new FormHttpException($form);
    }
}
