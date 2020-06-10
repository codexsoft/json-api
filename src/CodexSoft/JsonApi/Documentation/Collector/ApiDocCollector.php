<?php /** @noinspection DuplicatedCode */

namespace CodexSoft\JsonApi\Documentation\Collector;

use function Stringy\create as str;
use CodexSoft\Code\Files\Files;
use CodexSoft\JsonApi\Helper\Loggable;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Routing\Router;

class ApiDocCollector
{
    use Loggable;

    private Router $router;
    private FormFactory $formFactory;

    public function __construct(Router $router, FormFactory $formFactory, LoggerInterface $logger = null)
    {
        $this->logger = $logger;
        $this->router = $router;
        $this->formFactory = $formFactory;
    }

    /**
     * @param array $paths
     *
     * @return ApiDoc
     */
    public function collect(array $paths): ApiDoc
    {
        $docApi = new ApiDoc;

        $pathPrefixToRemove = '';
        $docApi->actions = $this->collectActions($paths, $pathPrefixToRemove);

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
                $this->logger->notice($e->getMessage());
                continue;
            }

            if ($responseDoc instanceof ResponseDoc) {
                $this->logger->info('ADDED response '.$responseClass);
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

            try {
                $formDoc = (new FormDocCollector($this->formFactory, $this->logger))->collect($formClass);
            } catch (\Throwable $e) {
                $this->logger->notice($e->getMessage());
                continue;
            }

            if ($formDoc instanceof FormDoc) {
                $this->logger->info('ADDED form '.$formClass);
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
     * @param string[]|null $paths
     * @param string|null $pathPrefixToRemove
     *
     * @return ActionDoc[]
     */
    protected function collectActions(array $paths = null, ?string $pathPrefixToRemove = null): array
    {
        $actions = [];

        $router = $this->router;
        $routes = $router->getRouteCollection();

        foreach ($routes as $routeName => $route) {
            try {
                $routeClass = $route->getDefault('_controller');
                if ($paths && \class_exists($routeClass) && !str((new \ReflectionClass($routeClass))->getFileName())->startsWithAny(\array_keys($paths))) {
                    $this->logger->debug('action '.$route->getPath().' skipped because it is not in paths whitelist');
                    continue;
                }
                $actionDoc = (new ActionDocCollector($this->logger))->setPathPrefixToRemove($pathPrefixToRemove)->collect($route);
                if ($actionDoc instanceof ActionDoc) {
                    $this->logger->info('ADDED action '.$route->getPath());
                    $actions[$actionDoc->actionClass] = $actionDoc;
                }

            } catch (\Throwable $e) {
                $this->getLogger()->notice('FAILED to collect documentation for '.$routeName.': '.$e->getMessage().', SKIPPED');
            }

        }

        return $actions;
    }

}
