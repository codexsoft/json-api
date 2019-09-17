<?php

namespace CodexSoft\JsonApi\Response;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\FormBuilderInterface;

class DefaultSuccessResponse extends AbstractBaseResponse
{

    public function __construct(array $data = [], int $status = Response::HTTP_OK, array $headers = [])
    {
        if ($this instanceof ResponseWrappedDataInterface) {
            $data = $this->wrapData($data);
        }
        parent::__construct($data, $status, $headers);
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($this instanceof ResponseWrappedDataInterface) {
            /** @noinspection PhpUndefinedMethodInspection */
            if (static::isGeneratingWrappedDataForResponseDefinition()) {
                return;
            }
            $this->wrapDefinition($builder);
        }
        parent::buildForm($builder, $options);
    }

    public static function getSwaggerResponseDescription(): string
    {
        return 'Common success response';
    }
}