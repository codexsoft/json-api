<?php

namespace CodexSoft\JsonApi\Documentation\Collector;

use function Stringy\create as str;

class ResponseDoc
{
    public ?string $class = null;
    public ?string $description = null;
    public ?string $formClass = null;
    public ?FormDoc $formClassDoc = null;
    public ?string $example = null;
    public ?string $title = null;

    public static function generateTitleStatic(string $suggestedResponseTitle): string
    {
        return (string) str($suggestedResponseTitle)->replace('\\', '_')->trimLeft('_');
    }

    public function generateTitle(): string
    {
        return self::generateTitleStatic($this->class);
    }

}
