<?php

namespace CodexSoft\JsonApi\Documentation\Collector\Interfaces;

/**
 * @deprecated use http status codes instead of error codes
 */
interface SwagenActionProducesErrorCodesInterface
{
    /** @return int[] */
    public static function producesErrorCodes(): array;

    /** @return string */
    public static function errorCodesClass(): string;

}