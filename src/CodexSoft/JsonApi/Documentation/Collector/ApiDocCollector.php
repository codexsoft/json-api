<?php /** @noinspection DuplicatedCode */

namespace CodexSoft\JsonApi\Documentation\Collector;

use CodexSoft\Code\Helpers\Files;
use CodexSoft\Code\Traits\Loggable;
use CodexSoft\JsonApi\JsonApiSchema;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Router;

class ApiDocCollector
{

    use Loggable;

    /** @var JsonApiSchema */
    private $jsonApiSchema;

    /** @var Router */
    private $router;

    /** @var FormFactory */
    private $formFactory;

    public function __construct(Router $router, FormFactory $formFactory, JsonApiSchema $jsonApiSchema, LoggerInterface $logger = null)
    {
        $this->logger = $logger;
        $this->router = $router;
        $this->jsonApiSchema = $jsonApiSchema;
        $this->formFactory = $formFactory;
    }

    /**
     * @return ApiDoc
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function collect(array $paths): ApiDoc
    {
        $docApi = new ApiDoc;

        $pathPrefixToRemove = '';
        $docApi->actions = $this->collectActions($pathPrefixToRemove);

        foreach ($paths as $path => $namespace) {
            /** @noinspection SlowArrayOperationsInLoopInspection */
            $docApi->forms = \array_merge($docApi->forms, $this->collectForms($path, $namespace));
        }

        foreach ($paths as $path => $namespace) {
            /** @noinspection SlowArrayOperationsInLoopInspection */
            $docApi->responses = \array_merge($docApi->responses, $this->collectResponses($path, $namespace));
        }

        return $docApi;
    }

    /**
     * @param string $responsesDir
     * @param string $responsesNamespace
     *
     * @return array
     * @throws \ReflectionException
     */
    protected function collectResponses(string $responsesDir, string $responsesNamespace): array
    {
        $responses = [];
        $responseClasses = $this->findClassesInPath($responsesDir, $responsesNamespace);
        $responseClasses = \array_unique($responseClasses);
        foreach ($responseClasses as $responseClass) {

            try {
                $responseDoc = (new ResponseDocCollector($this->formFactory, $this->logger))->collect($responseClass);
            } catch (\Throwable $e) {
                $this->logger->notice((string) $e);
                continue;
            }

            if ($responseDoc) {
                $responses[$responseClass] = $responseDoc;
            }
        }
        return $responses;
    }

    /**
     * @param $formsDir
     * @param $formsNamespace
     *
     * @return FormDoc[]
     */
    protected function collectForms($formsDir, $formsNamespace): array
    {
        $forms = [];
        $formClasses = $this->findClassesInPath($formsDir, $formsNamespace);
        foreach ($formClasses as $formClass) {

            //if (\is_subclass_of($formClass, Response::class)) {
            //    continue;
            //}

            try {
                $formDoc = (new FormDocCollector($this->formFactory, $this->logger))->collect($formClass);
            } catch (\Throwable $e) {
                $this->logger->notice((string) $e);
                continue;
            }

            if ($formDoc instanceof FormDoc) {
                $forms[$formClass] = $formDoc;
            }
        }
        return $forms;
    }

    /**
     * Найти все классы, расположенные в заданном пути
     *
     * @param string $path
     * @param $namespace
     *
     * @return array
     */
    public function findClassesInPath(string $path, $namespace = ''): array
    {
        $files = Files::listFiles($path, false); // ищем не рекурсивно

        $classes = [];

        foreach ($files as $fileName) {

            $fullFilePath = $path.'/'.$fileName;
            if (\is_dir($fullFilePath)) {
                /** @noinspection SlowArrayOperationsInLoopInspection */
                $classes = \array_merge($classes, $this->findClassesInPath($path.'/'.$fileName, $namespace.$fileName.'\\'));
            } else {

                // убедимся что файл — PHP
                if (!(pathinfo($fileName, PATHINFO_EXTENSION) === 'php')) {
                    continue;
                }

                $className = Files::removeExtension($fileName);
                $fqnClassName = $namespace.$className;
                $classes[] = $fqnClassName;
            }

        }

        return $classes;
    }

    /**
     * @param null|string $pathPrefixToRemove
     *
     * @return ActionDoc[]
     * @throws \Throwable
     */
    protected function collectActions(?string $pathPrefixToRemove = null): array
    {
        $actions = [];

        $router = $this->router;
        $routes = $router->getRouteCollection();

        foreach ($routes as $routeName => $route) {
            try {
                $actionDoc = (new ActionDocCollector($this->logger))->setPathPrefixToRemove($pathPrefixToRemove)->collect($route);
                if ($actionDoc) {
                    $actions[$actionDoc->actionClass] = $actionDoc;
                }

            } catch (\Throwable $e) {
                $this->getLogger()->error('FAILED to collect documentation for '.$routeName.': '.$e->getMessage().', SKIPPED');
            }

        }

        return $actions;
    }

}