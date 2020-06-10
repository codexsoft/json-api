<?php

namespace CodexSoft\JsonApi\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class HttpRequestJsonToArraySubscriber implements EventSubscriberInterface
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => [
                ['convertJsonToArray', 10],
            ],
        ];
    }

    /**
     * @param ControllerEvent $event
     */
    public function convertJsonToArray(ControllerEvent $event): void
    {
        $request = $event->getRequest();

        if ($request->getContentType() !== 'json' || !$request->getContent()) {
            return;
        }

        $data = \json_decode($request->getContent(), true);

        if (\json_last_error() !== JSON_ERROR_NONE) {
            throw new BadRequestHttpException('invalid json body: '.\json_last_error_msg());
        }

        $request->request->replace(\is_array($data) ? $data : []);
    }

}
