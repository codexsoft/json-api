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

    /** @var string ендпойнт никак не обрабатывает входные данные */
    public const STATE_INPUT_NOT_IMPLEMENTED = 'Not implemented';

    /** @var string ендпойнт проверяет входные данные на формальное соответствие задокументированным входным данным */
    public const STATE_INPUT_FORMAL_CHECK = 'Automated formal checking';

    /** @var string ендпойнт корректно обрабатывает входные данные */
    public const STATE_INPUT_IMPLEMENTED = 'Implemented';

    /** @var string ендпойнт не реализован и не выдает задокументированный ответ */
    public const STATE_OUTPUT_NOT_IMPLEMENTED = 'Not implemented';

    /** @var string ендпойнт выдает формально валидный, генерируемый автоматически ответ */
    public const STATE_OUTPUT_AUTOMATED_STUB = 'Automated stub';

    /** @var string ендпойнт выдает формально валидный, заданный вручную ответ (всегда один и тот же) */
    public const STATE_OUTPUT_MANUAL_STUB = 'Manual stub';

    /** @var string ендпойнт реализован */
    public const STATE_OUTPUT_IMPLEMENTED = 'Implemented';

    public const TAG_UNCATEGORIZED = 'Uncategorized';

    protected static $inputStatus = self::STATE_INPUT_NOT_IMPLEMENTED;
    protected static $outputStatus = self::STATE_OUTPUT_NOT_IMPLEMENTED;
    protected static $swagenDescription = '';

    /**
     * Для разработчиков, которые будут работать с документацией к API, важно понимать статус
     * завершенности роута (можно ли им пользоваться, выдает ли он формально валидные данные).
     *
     * @param $inputState
     * @param $outputState
     *
     * @return string
     */
    protected static function describeDevState($inputState, $outputState)
    {
        return '<br>Development status | INPUT — '.$inputState.' | OUTPUT — '.$outputState;
    }

    public static function descriptionForSwagger(): ?string
    {
        return static::$swagenDescription.static::describeDevState(static::$inputStatus, static::$outputStatus);
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
     * Теги, которыми помечен экшн
     *
     * @return string[]
     */
    public static function tagsForSwagger(): array
    {
        return [self::TAG_UNCATEGORIZED];
    }

}
