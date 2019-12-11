<?php

namespace CodexSoft\JsonApi\EventListener;

use CodexSoft\JsonApi\Response\DefaultErrorResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ExceptionListener implements EventSubscriberInterface
{

    /** @var LoggerInterface */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return array(
            KernelEvents::EXCEPTION => [
                ['onKernelException', 1],
            ],
        );
    }

    public function onKernelException(ExceptionEvent $event)
    {
        $exception = $event->getThrowable();

        $this->logger->warning('[EXCEPTION] '.\get_class($exception));
        $this->logger->warning(' | code '.$exception->getCode().' message: «'.$exception->getMessage().'»');
        $this->logger->warning("\n\n".((string) $exception)."\n");

        $response = new DefaultErrorResponse('Unhandled exception: '.$exception->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR, $exception);

        $event->setResponse($response);
        $event->allowCustomResponseCode();
    }

}
