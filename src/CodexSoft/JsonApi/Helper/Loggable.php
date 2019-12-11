<?php

namespace CodexSoft\JsonApi\Helper;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

trait Loggable
{

    /** @var LoggerInterface */
    private $logger;

    /**
     * @param LoggerInterface $logger
     *
     * @return static
     */
    public function setLogger($logger): self
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger ?: $this->logger = new NullLogger;
    }

}
