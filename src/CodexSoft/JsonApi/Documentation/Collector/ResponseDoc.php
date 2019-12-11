<?php

namespace CodexSoft\JsonApi\Documentation\Collector;

use function Stringy\create as str;

class ResponseDoc
{
    /** @var string */
    public $class;

    /** @var string */
    public $description;

    /** @var string */
    public $formClass;

    /** @var FormDoc because of optional data-wrapper in response, form should be generated */
    public $formClassDoc;

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
