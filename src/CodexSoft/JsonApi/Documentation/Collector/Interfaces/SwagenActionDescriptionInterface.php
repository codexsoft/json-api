<?php

namespace CodexSoft\JsonApi\Documentation\Collector\Interfaces;

interface SwagenActionDescriptionInterface
{

    /**
     * @return null|string
     */
    public static function descriptionForSwagger(): ?string;
}