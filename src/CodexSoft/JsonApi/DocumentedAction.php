<?php

namespace CodexSoft\JsonApi;

use CodexSoft\JsonApi\Response\DefaultErrorResponse;
use CodexSoft\JsonApi\Response\DefaultSuccessResponse;
use CodexSoft\JsonApi\Documentation\Collector\Interfaces\SwagenActionDescriptionInterface;
use CodexSoft\JsonApi\Documentation\Collector\Interfaces\SwagenActionTagsInterface;
use CodexSoft\JsonApi\Documentation\Collector\Interfaces\SwagenActionInterface;

/**
 * Роуты, которые будут автоматически документироваться при помощи swagen-а
 * Должны иметь описание (descriptionForSwagger) и отмечены тегами (tagsForSwagger)
 */
abstract class DocumentedAction extends AbstractAction implements SwagenActionInterface, SwagenActionDescriptionInterface, SwagenActionTagsInterface
{

    public const STATE_INPUT_NOT_IMPLEMENTED = 'Not implemented';
    public const STATE_INPUT_FORMAL_CHECK = 'Automated formal checking';
    public const STATE_INPUT_IMPLEMENTED = 'Implemented';

    public const STATE_OUTPUT_NOT_IMPLEMENTED = 'Not implemented';
    public const STATE_OUTPUT_AUTOMATED_STUB = 'Automated stub';
    public const STATE_OUTPUT_MANUAL_STUB = 'Manual stub';
    public const STATE_OUTPUT_IMPLEMENTED = 'Implemented';

    public const TAG_UNCATEGORIZED = 'Uncategorized';

    protected static $inputStatus = self::STATE_INPUT_NOT_IMPLEMENTED;
    protected static $outputStatus = self::STATE_OUTPUT_NOT_IMPLEMENTED;
    protected static $swagenDescription = '';

    protected static function status(): string
    {
        return static::describeDevState(static::$inputStatus, static::$outputStatus);
    }

    protected static function describeDevState($inputState, $outputState)
    {
        return '<br>Development status | INPUT — '.$inputState.' | OUTPUT — '.$outputState;
    }

    public static function descriptionForSwagger(): ?string
    {
        return static::$swagenDescription.self::status();
    }

    /**
     * Какие классы ответов могут вернуться. Ключ — HTTP-код, значение — класс ответа.
     * Если экшн имплементирует SwagenActionProducesErrorCodesInterface, то будет взят первый
     *
     * @return array
     */
    public static function producesResponses(): array
    {
        return [
            200 => DefaultSuccessResponse::class,
            400 => DefaultErrorResponse::class,
        ];
    }

    /**
     * Коды ошибок, которые возвращаются в этом экшне
     *
     * @return array
     */
    public static function producesErrorCodes(): array
    {
        return [
            ErrorResponse::ERROR_CODE_UNKNOWN,
        ];
    }

    /**
     * Теги, которыми помечен экшн
     *
     * @return string[]
     */
    public static function tagsForSwagger(): array
    {
        return [self::TAG_UNCATEGORIZED];
    }

    /**
     * В каком классе лежит список всех кодов ошибок (константы ERROR_CODE_*)
     *
     * @return string
     */
    final public static function errorCodesClass(): string
    {
        return ErrorResponse::class;
    }

}