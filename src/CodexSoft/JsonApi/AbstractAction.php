<?php


namespace CodexSoft\JsonApi;

use CodexSoft\JsonApi\Response\DefaultErrorResponse;
use Doctrine\Common\Annotations\DocParser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

abstract class AbstractAction extends AbstractController
{

    /** @var Request  */
    protected $request;

    /** @var FormFactoryInterface */
    protected $formFactory;

    abstract public function __invoke(): Response;

    /**
     * @param FormFactoryInterface $formFactory
     * @param Request $request
     * @param string $formClass
     * @param mixed|null $data
     * @param array $options
     *
     * @return DefaultErrorResponse|mixed
     */
    protected function getDataViaForm(FormFactoryInterface $formFactory, Request $request, string $formClass, $data = null, array $options = [])
    {
        $validator = $formFactory->create($formClass, $data, $options);
        $validator->handleRequest($request);

        if (!$validator->isSubmitted()) {
            return new DefaultErrorResponse('Data not sent', Response::HTTP_BAD_REQUEST);
        }

        if (!$validator->isValid()) {
            return new DefaultErrorResponse('Invalid data sent', Response::HTTP_BAD_REQUEST, null, $this->getFormErrors($validator));
        }

        return $validator->getData();
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
            for ($fieldName = [], $field = $error->getOrigin(); $field; $field = $field->getParent()) {
                if ($field->getName()) {
                    $fieldName[] = $field->getName();
                }
            }
            $fieldName = implode('.', array_reverse($fieldName));

            $formData[] = [
                'field' => $fieldName,
                'message' => $error->getMessage(),
                'parameters' => $error->getMessageParameters(),
            ];
        }
        return $formData;
    }

    /**
     * Default route name generator copied and adapted from
     * \Symfony\Component\Routing\Loader\AnnotationClassLoader::getDefaultRouteName
     *
     * @return string
     */
    public static function getRouteName()
    {
        try {

            $parser = new DocParser();
            $parser->setIgnoreNotImportedAnnotations(true);
            $refAnnotationClass = new \ReflectionClass(Route::class);
            //\class_exists(Route::class); // need in case if class was not already autoloaded
            $parser->addNamespace($refAnnotationClass->getNamespaceName());

            $refClass = new \ReflectionClass(static::class);
            $refMethod = $refClass->getMethod('__invoke');
            $methodDocBlock = $refMethod->getDocComment();
            $annotations = $parser->parse($methodDocBlock);
            foreach ($annotations as $annotation) {

                if (!$annotation instanceof Route) {
                    continue;
                }

                if ($annotation->getName()) {
                    return $annotation->getName();
                }

            }

        } catch (\ReflectionException $e) {
        }
        return strtolower(str_replace('\\', '_', static::class).'__invoke');
    }

    /**
     * Default route name generator copied and adapted from
     * \Symfony\Component\Routing\Loader\AnnotationClassLoader::getDefaultRouteName
     *
     * @return string
     * @throws \Exception
     */
    public static function getRoutePath()
    {
        try {

            $parser = new DocParser();
            $parser->setIgnoreNotImportedAnnotations(true);
            $refAnnotationClass = new \ReflectionClass(Route::class);
            //\class_exists(Route::class); // need in case if class was not already autoloaded
            $parser->addNamespace($refAnnotationClass->getNamespaceName());

            $refClass = new \ReflectionClass(static::class);
            $refMethod = $refClass->getMethod('__invoke');
            $methodDocBlock = $refMethod->getDocComment();
            $annotations = $parser->parse($methodDocBlock);
            foreach ($annotations as $annotation) {

                if (!$annotation instanceof Route) {
                    continue;
                }

                if ($annotation->getPath()) {
                    return $annotation->getPath();
                }

            }

        } catch (\ReflectionException $e) {
        }

        throw new \Exception('Route has no path annotation');

    }

    /**
     * publishing generateUrl method
     *
     * @param string $route
     * @param array $parameters
     * @param int $referenceType
     *
     * @return string
     */
    public function generateUrl(string $route, array $parameters = [], int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): string
    {
        return parent::generateUrl($route, $parameters, $referenceType);
    }

}