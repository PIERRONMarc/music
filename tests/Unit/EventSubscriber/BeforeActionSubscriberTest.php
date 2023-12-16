<?php

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\BeforeActionSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\KernelInterface;

class BeforeActionSubscriberTest extends TestCase
{
    public function testEventSubscription(): void
    {
        $this->assertArrayHasKey(KernelEvents::CONTROLLER, BeforeActionSubscriber::getSubscribedEvents());
    }

    public function testJsonIsConvertedToArray(): void
    {
        $subscriber = new BeforeActionSubscriber();
        $event = $this->getEvent('{"foo":"bar"}');
        $subscriber->insertJsonContentInRequest($event);

        $this->assertSame('bar', $event->getRequest()->request->get('foo'));
    }

    public function testInvalidJsonReturnException(): void
    {
        $this->expectException(BadRequestHttpException::class);

        $subscriber = new BeforeActionSubscriber();
        $event = $this->getEvent('{"foo:"bar"}');
        $subscriber->insertJsonContentInRequest($event);
    }

    public function testNonJsonRequestIsNotHandled(): void
    {
        $subscriber = new BeforeActionSubscriber();
        $event = $this->getEvent('{"foo":"bar"}', ['CONTENT_TYPE' => 'application/html']);
        $subscriber->insertJsonContentInRequest($event);

        $this->assertNull($event->getRequest()->request->get('foo'));
    }

    /**
     * @param mixed[] $server
     */
    private function getEvent(string $content = null, array $server = ['CONTENT_TYPE' => 'application/json']): ControllerEvent
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            $server,
            $content
        );

        return new ControllerEvent(
            $this->getMockBuilder(KernelInterface::class)->getMock(),
            function () {},
            $request,
            1
        );
    }
}
