<?php

namespace CodexSoft\JsonApi;

use CodexSoft\JsonApi\Form\Extensions\FormFieldDefaultValueExtension;
use CodexSoft\JsonApi\Form\Type\BooleanType\BooleanType;
use CodexSoft\JsonApi\Response\DefaultErrorResponse;
use CodexSoft\JsonApi\Response\DefaultSuccessResponse;
use CodexSoft\JsonApi\Swagen\Interfaces\SwagenActionExternalFormInterface;
use CodexSoft\JsonApi\Swagen\Interfaces\SwagenActionProducesErrorCodesInterface;
use CodexSoft\JsonApi\Form\SymfonyFormExampleGenerator;
use Symfony\Component\HttpFoundation\Response;
use function CodexSoft\Code\str;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * От DocumentedAction отличается тем, что предполагает symfony-form в качестве описания структуры
 * входного JSON, класс которой должен возвращаться из getFormClass().
 *
 * Class DocumentedFormAction
 */
abstract class DocumentedFormAction extends DocumentedAction implements SwagenActionExternalFormInterface, SwagenActionProducesErrorCodesInterface
{

    /**
     * Коды ошибок, которые возвращаются в этом экшне
     *
     * @return array
     */
    public static function producesErrorCodes(): array
    {
        return \array_merge(static::getDefaultErrorCodes(), static::getSpecieficErrorCodes());
    }

    /**
     * @return array дополнительные коды ошибок, которые могут возникнуть в этом экшне
     */
    public static function getSpecieficErrorCodes(): array
    {
        return [];
    }

    /**
     * @return array стандартные коды ошибок, которые могут возникнуть в любом DocumentedFormAction
     *     экшне
     */
    public static function getDefaultErrorCodes()
    {
        return [
            ErrorResponse::ERROR_CODE_UNKNOWN,
            ErrorResponse::ERROR_CODE_FORM_NOT_SUBMITTED,
            ErrorResponse::ERROR_CODE_FORM_PARAMS_ERROR,
        ];
    }

    protected static function responseClass()
    {
        return str(static::class)->removeRight('Action').'Response';
    }

    protected static function formClass()
    {
        return static::class.'Form';
    }

    public static function getFormClass(): string
    {
        return static::formClass();
    }

    public static function producesResponses(): array
    {
        $defaultResponseClass = static::responseClass();
        if (\class_exists($defaultResponseClass)) {
            $responseClass = $defaultResponseClass;
        } else {
            $responseClass = DefaultSuccessResponse::class;
        }

        return [
            200 => $responseClass,
        ];
    }

    /**
     * Произвести валидацию входных данных, для не переданных данных установить значения по
     * умолчанию и отдать в виде массива. В качестве класса с формой входных данных используется
     * naming convention: к наименованию класса добавляется "Form" (MyGoodAction ->
     * MyGoodActionForm). Если валидация входных данных не прошла, вернет готовый JsonResponse с
     * ошибкой.
     *
     * Кроме этого, для JSON-запросов, в которых не передано ни одного параметра, производится поиск
     * скалярного аттрибута с назначенным значением по-умолчанию. Первый из таких найденных
     * элементов подставляется в запрос, что позволяет избежать «хаков» и постановки таких значений
     * в запрос вручную, как это делалось ранее.
     *
     * @return DefaultErrorResponse|array
     */
    protected function getJsonData()
    {
        $actionInputForm = static::formClass();
        if (!\class_exists($actionInputForm)) {
            throw new \RuntimeException($actionInputForm.' assumed as input data form class for action '.static::class.' but class does not exists!');
        }

        if (($this->request->getMethod() === Request::METHOD_POST) && ($this->request->request->count() === 0)) {
            $this->addDefaultFormValuesToEmptyRequest($this->request, static::formClass());
        }

        return $this->getDataViaForm($this->formFactory, $this->request, static::formClass());
    }

    /**
     * If passed empty request, but default values for scalar fields is set in form, modifying
     * request with default value for first found scalar field.
     *
     * @param Request $request
     * @param string $formClass
     */
    protected function addDefaultFormValuesToEmptyRequest(Request $request, string $formClass)
    {
        try {
            $formBuilder = $this->formFactory->create($formClass);
            $elements = $formBuilder->all();
            foreach ($elements as $name => $element) {
                $config = $element->getConfig();
                $type = $element->getConfig()->getType()->getInnerType();
                $typeClass = \get_class($type);

                // дефолтное значение подставляем только для скаляров
                if (!\in_array($typeClass, [
                    BooleanType::class,
                    Type\EmailType::class,
                    Type\IntegerType::class,
                    Type\NumberType::class,
                    Type\PasswordType::class,
                    Type\PercentType::class,
                    Type\TelType::class,
                    Type\TextType::class,
                    Type\TimeType::class,
                    Type\UrlType::class,
                ], true)) {
                    continue;
                }

                if ($config->hasAttribute('data_collector/passed_options')) {
                    $passedOptions = $config->getAttribute('data_collector/passed_options');
                    if ($passedOptions === null) {
                        $passedOptions = [];
                    }
                } else {
                    $passedOptions = $config->getOptions();
                }

                // берем дефольное значение только из default опции (не из empty_data)
                if (\array_key_exists('default', $passedOptions) && ($passedOptions['default'] !== FormFieldDefaultValueExtension::UNDEFINED)) {
                    if ($this->request->request->count() === 0) {
                        $this->request->request->set($name, $passedOptions['default']);
                    }
                    return; // одного достаточно
                }
            }
        } catch (\Throwable $e) {
            // do nothing...
        }
    }

    /**
     * @return JsonResponse
     */
    protected function generateResponseExample(): JsonResponse
    {
        $defaultActionResponseClass = static::responseClass();
        $responseClass = \class_exists($defaultActionResponseClass) ? $defaultActionResponseClass : DefaultSuccessResponse::class;
        return $this->generateExampleByForm($responseClass);
    }

    /**
     * Генератор заглушек, основанных на симфони-форме ответа
     *
     * @param string $responseFormClass
     *
     * @return JsonResponse|mixed
     */
    protected function generateExampleByForm(string $responseFormClass): JsonResponse
    {
        try {
            $data = (new SymfonyFormExampleGenerator)->setFormFactory($this->formFactory)->generateExample($responseFormClass);
            return new JsonResponse($data);
        } catch (\ReflectionException $e) {
            return new DefaultErrorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR, $e);
        }
    }

}