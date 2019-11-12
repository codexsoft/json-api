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

    abstract public function __invoke(): Response;

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
