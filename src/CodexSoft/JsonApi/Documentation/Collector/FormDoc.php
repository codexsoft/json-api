<?php

namespace CodexSoft\JsonApi\Documentation\Collector;

class FormDoc
{
    public ?string $class = null;

    /** @var string[] */
    public array $requiredFields = [];

    /** @var FormElementDoc[]  */
    public array $items = [];
}
