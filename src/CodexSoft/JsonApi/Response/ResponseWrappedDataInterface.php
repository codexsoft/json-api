<?php

namespace CodexSoft\JsonApi\Response;

use Symfony\Component\Form\FormBuilderInterface;

interface ResponseWrappedDataInterface
{
    /**
     * @param bool $generatingWrappedDataForResponseDefinition
     */
    public static function setGeneratingWrappedDataForResponseDefinition(bool $generatingWrappedDataForResponseDefinition): void;

    /**
     * @return bool
     */
    public static function isGeneratingWrappedDataForResponseDefinition(): bool;

    function wrapData($data): array;

    function wrapDefinition(FormBuilderInterface $builder);

    /**
     * Override and return something like 'data' in order to auto-wrap response data into key 'data'
     * @return null|string
     */
    function getDataWrapper(): ?string;

    function getDataWrapperOptions(): array;

    public static function generateFormFactory();

}