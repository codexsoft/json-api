<?php

namespace CodexSoft\JsonApi\Response;

use Throwable;

class FormValidationFailedException extends \Exception
{
    /** @var array */
    private $extraData = [];

    public function __construct($message = '', $code = 0, array $extraData = [], Throwable $previous = null)
    {
        $this->extraData = $extraData;
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return array
     */
    public function getExtraData(): array
    {
        return $this->extraData;
    }
}
