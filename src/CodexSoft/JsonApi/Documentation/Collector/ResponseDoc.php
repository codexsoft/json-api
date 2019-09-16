<?php

namespace CodexSoft\JsonApi\Documentation\Collector;

use function CodexSoft\Code\str;

class ResponseDoc
{
    /** @var string */
    public $class;

    /** @var string */
    public $description;

    /** @var string */
    public $formClass;

    /** @var string */
    public $example;

    /** @var string */
    public $title;

    public static function generateTitleStatic(string $suggestedResponseTitle): string
    {
        return (string) str($suggestedResponseTitle)->replace('\\', '_')->trimLeft('_');
    }

    public function generateTitle(): string
    {
        return self::generateTitleStatic($this->class);
    }

}