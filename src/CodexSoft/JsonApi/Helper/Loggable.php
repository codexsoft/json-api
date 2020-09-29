<?php

namespace CodexSoft\JsonApi\Helper;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

trait Loggable
{
    private ?LoggerInterface $logger;

    /**
     * @param LoggerInterface $logger
     *
     * @return static
     */
    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        if (!isset($this->logger)) {
            $this->logger = new NullLogger();
        }
        return $this->logger;
    }

}
