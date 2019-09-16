<?php

namespace CodexSoft\JsonApi\Documentation\Collector\Interfaces;

interface SwagenActionTagsInterface
{
    /** @return string[] */
    public static function tagsForSwagger(): array;
}