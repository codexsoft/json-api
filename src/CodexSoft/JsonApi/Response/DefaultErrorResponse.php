<?php

namespace CodexSoft\JsonApi\Response;

use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\FormBuilderInterface;

class DefaultErrorResponse extends AbstractBaseResponse
{

    /** custom error message */
    protected ?string $errorMessage = null;

    /** source of the error — for debug and logging purposes */
    protected ?\Throwable $exception = null;

    /** extra arrayed data to include in response JSON body */
    protected array $extraData = [];

    /**
     * ErrorResponse constructor.
     *
     * @param string $errorMessage сообщение об ошибке
     * @param int $statusCode код ошибки
     * @param \Throwable|null $exception экземпляр исключения (если передан — код, сообщение и
     *     трейс исключения будет залогирован в БД)
     * @param array $extraData дополнительные данные, которые следует передать с ответом
     */
    public function __construct(
        ?string $errorMessage = 'Unknown error',
        $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR,
        ?\Throwable $exception = null,
        array $extraData = []
    ) {
        $this->errorMessage = $errorMessage ?: Response::$statusTexts[(int) $statusCode];
        $this->statusCode = $statusCode;
        $this->exception = $exception;
        $this->extraData = $extraData;

        $data = [
            'message' => $this->errorMessage,
            'data' => $this->extraData,
        ];

        parent::__construct($data, $statusCode);
    }

    final public static function getErrorMessageByStatusCode(int $statusCode): string
    {
        return Response::$statusTexts[$statusCode];
        //return Annotations::getRusAnnotation($errorCode, static::class, self::_ERROR_CODE_PREFIX) ?? '';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('message', Type\TextType::class, [
            'label' => 'Текст ошибки',
        ]);

        $builder->add('data', Type\FormType::class, [
            'label' => 'Дополнительная информация об ошибке',
        ]);

    }

    /**
     * @return \Throwable
     */
    public function getException(): ?\Throwable
    {
        return $this->exception;
    }

    /**
     * @return array
     */
    public function getExtraData(): array
    {
        return $this->extraData;
    }

    /**
     * @return string|null
     */
    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public static function getSwaggerResponseDescription(): string
    {
        return 'Common error response';
    }
}
