<?php

namespace CodexSoft\JsonApi;

use CodexSoft\JsonApi\Documentation\Collector\FormDocCollector;
use CodexSoft\JsonApi\Form\Extensions\FormFieldDefaultValueExtension;
use CodexSoft\JsonApi\Form\Type\BooleanType\BooleanType;
use CodexSoft\JsonApi\Form\Type\MixedType\MixedType;
use CodexSoft\JsonApi\Response\DefaultErrorResponse;
use CodexSoft\JsonApi\Response\DefaultSuccessResponse;
use CodexSoft\JsonApi\Documentation\Collector\Interfaces\SwagenActionExternalFormInterface;
use CodexSoft\JsonApi\Form\SymfonyFormExampleGenerator;
use CodexSoft\JsonApi\Response\FormValidationFailedException;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * От DocumentedAction отличается тем, что предполагает symfony-form в качестве описания структуры
 * входного JSON, класс которой должен возвращаться из getFormClass().
 *
 * Class DocumentedFormAction
 */
abstract class DocumentedFormAction extends DocumentedAction implements SwagenActionExternalFormInterface
{
    protected FormFactoryInterface $formFactory;
    private ?FormInterface $validatedForm = null;
    protected static bool $allowEmptyForm = false;
    protected static bool $decodeRequestJson = false;

    /** query parameter name, if present, then auto-generated fake response will be sent */
    protected string $fakeRequestQueryParameterName = 'fakeRequest';

    /**
     * @var array default options to be passed into request form
     * allow_extra_fields: whether extra (not declared in RequestForm) fields allowed or not
     */
    protected array $defaultRequestFormOptions = [
        'allow_extra_fields' => true
    ];

    public function __construct(RequestStack $requestStack, FormFactoryInterface $formFactory)
    {
        $request = $requestStack->getCurrentRequest();
        if ($request === null) {
            throw new \InvalidArgumentException('RequestStack is empty, failed to get current Request!');
        }
        $this->request = $request;
        $this->formFactory = $formFactory;
        $this->decodeRequestJson($this->request);
    }

    /**
     * If enabled, decodes string JSON data to PHP variable
     * @param Request $request
     */
    private function decodeRequestJson(Request $request): void
    {
        if (!static::$decodeRequestJson) {
            return;
        }

        // skip processing request with already decoded JSON data
        if ($request->attributes->get('json-body-decoded', false)) {
            return;
        }

        if ($request->getMethod() !== Request::METHOD_POST) {
            return;
        }

        if ($request->getContentType() !== 'json' || !$request->getContent()) {
            return;
        }

        $data = \json_decode($request->getContent(), true);

        if (\json_last_error() !== JSON_ERROR_NONE) {
            throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException('Invalid JSON body: '.\json_last_error_msg());
        }

        $request->attributes->set('json-body-decoded', true);
        $request->request->replace(\is_array($data) ? $data : []);
    }

    /**
     * @param bool $decodeRequestJson
     */
    public static function setDecodeRequestJson(bool $decodeRequestJson): void
    {
        self::$decodeRequestJson = $decodeRequestJson;
    }

    /**
     * Hook method, that is called befor input data validation.
     * Does nothing by default, but can be used for example to modify data in request.
     */
    protected function beforeDataValidation(): void
    {
    }

    /**
     * Произвести валидацию входных данных, для не переданных данных установить значения по
     * умолчанию и отдать в виде массива. В качестве класса с формой входных данных используется
     * naming convention: от наименования класса убирается Action и добавляется "RequestForm"
     * (MyGoodAction -> MyGoodRequestForm). Если валидация входных данных не прошла, вернет готовый
     * JsonResponse с ошибкой.
     *
     * Кроме этого, для JSON-запросов, в которых не передано ни одного параметра, производится поиск
     * скалярного аттрибута с назначенным значением по-умолчанию. Первый из таких найденных
     * элементов подставляется в запрос, что позволяет избежать «хаков» и постановки таких значений
     * в запрос вручную.
     *
     * @return Response
     */
    public function __invoke(): Response
    {
        $this->beforeDataValidation();

        if (static::$allowEmptyForm === true) {
            if ($this->isResponseExampleRequested()) {
                return $this->generateResponseExample();
            }

            return $this->handle([], []);
        }

        $actionInputForm = static::formClass();
        if (!\class_exists($actionInputForm)) {
            return new DefaultErrorResponse('Class '.$actionInputForm.' assumed as input data form class for action '.static::class.' but class does not exists!');
        }

        if (($this->request->getMethod() === Request::METHOD_POST) && ($this->request->request->count() === 0)) {
            $this->addDefaultFormValuesToEmptyRequest($this->request, static::formClass());
        }

        try {
            $validationResult = $this->processInputDataViaForm();
        } catch (FormValidationFailedException $e) {
            return new DefaultErrorResponse($e->getMessage(), $e->getCode(), null, $e->getExtraData());
        }

        if ($this->isResponseExampleRequested()) {
            return $this->generateResponseExample();
        }

        return $this->handle($validationResult->getData(), $validationResult->getExtraData());
    }

    /**
     * @param array $data Does not include extra data if provided any! Only fields that were defined in the input form!
     * @param array $extraData Extra data that was passed (non-declared in input form fields)
     *
     * @return Response
     *
     * is not abstract to allow common __invoke style actions without handle method
     */
    public function handle(array $data, array $extraData = []): Response
    {
        return new JsonResponse(['data' => []]);
    }
    //abstract public function handle(array $data, array $extraData = []): Response;

    /**
     * @return FormInterface
     * @throws \Exception
     */
    protected function getValidatedForm(): FormInterface
    {
        if (!$this->validatedForm instanceof FormInterface) {
            throw new \Exception('Form must be validated before using in action');
        }
        return $this->validatedForm;
    }

    /**
     * @param string $formClass
     * @param null $data
     * @param array $options
     *
     * @return FormInterface
     * @throws FormValidationFailedException
     */
    protected function processInputDataViaForm(string $formClass = null, $data = null, array $options = []): FormInterface
    {
        $validator = $this->formFactory->create($formClass ?: static::formClass(), $data, \array_merge($this->defaultRequestFormOptions, $options));
        $validator->handleRequest($this->request);

        if (!$validator->isSubmitted()) {
            throw new FormValidationFailedException('Data not sent', Response::HTTP_BAD_REQUEST);
        }

        if (!$validator->isValid()) {
            throw new FormValidationFailedException('Invalid data sent', Response::HTTP_BAD_REQUEST, $this->getFormErrors($validator));
        }

        $this->validatedForm = $validator;
        return $validator;
    }

    /**
     * Получить подробные сведения об ошибках в форме
     *
     * @param FormInterface $form
     *
     * @return array
     */
    protected function getFormErrors(FormInterface $form): array
    {
        $formErrors = $form->getErrors(true);
        $formData = [];
        foreach ($formErrors as $error) {
            for ($fieldNames = [], $field = $error->getOrigin(); $field; $field = $field->getParent()) {
                if ($field->getName()) {
                    $fieldNames[] = $field->getName();
                }
            }
            $fieldName = implode('.', array_reverse($fieldNames));

            $formData[] = [
                'field' => $fieldName,
                'message' => $error->getMessage(),
                'parameters' => $error->getMessageParameters(),
            ];
        }
        return $formData;
    }

    protected static function responseClass(): string
    {
        return JsonApiSchema::generateResponseFormClass(static::class);
    }

    protected static function formClass(): string
    {
        return JsonApiSchema::generateActionFormClass(static::class);
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
            Response::HTTP_OK => $responseClass,
            Response::HTTP_BAD_REQUEST => DefaultErrorResponse::class,
            Response::HTTP_INTERNAL_SERVER_ERROR => DefaultErrorResponse::class,
        ];
    }

    /**
     * Важно, эта функция возвращает ТОЛЬКО определенные во входной форме поля
     *
     * @return DefaultErrorResponse|array
     * @throws \Exception
     */
    protected function getJsonData(): array
    {
        return $this->getValidatedForm()->getData();
    }

    /**
     * @return array
     * @throws \Exception
     */
    protected function getJsonExtraData(): array
    {
        return $this->getValidatedForm()->getExtraData();
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

                // дефолтное значение подставляем только для скаляров из приведенного списка
                if (!\in_array($typeClass, [
                    BooleanType::class,
                    MixedType::class,
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

                if ($config->hasAttribute(FormDocCollector::OPTIONS_FIELD_NAME)) {
                    $passedOptions = $config->getAttribute(FormDocCollector::OPTIONS_FIELD_NAME);
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

    public function isResponseExampleRequested(): bool
    {
        return $this->request->query->has($this->fakeRequestQueryParameterName);
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
