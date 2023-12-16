<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class BeforeActionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'insertJsonContentInRequest',
        ];
    }

    /**
     * @throws BadRequestHttpException Invalid Json body
     */
    public function insertJsonContentInRequest(ControllerEvent $event): void
    {
        $request = $event->getRequest();

        if ('json' != $request->getContentType() || !$request->getContent()) {
            return;
        }

        $data = json_decode($request->getContent(), true);

        if (\JSON_ERROR_NONE !== \json_last_error()) {
            throw new BadRequestHttpException('invalid json body: '.\json_last_error_msg());
        }

        $request->request->replace(\is_array($data) ? $data : []);
    }
}
