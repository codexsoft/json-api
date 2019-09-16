<?php /** @noinspection DuplicatedCode */

namespace CodexSoft\JsonApi\Documentation\Collector;

use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Route;

class ApiDocCollector extends AbstractCollector
{

    /**
     * @return ApiDoc
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function collect(): ApiDoc
    {
        $docApi = new ApiDoc;
        $docApi->actions = $this->collectActions();

        $formsClassMap = $this->formsClassesMap ?: $this->getDefaultFormsClassMap();
        foreach ($formsClassMap as $path => $namespace) {
            \array_push($docApi->forms, ...$this->collectForms($path, $namespace));
        }

        $responsesClassMap = $this->responsesClassesMap ?: $this->getDefaultResponsesClassMap();
        foreach ($responsesClassMap as $path => $namespace) {
            \array_push($docApi->responses, ...$this->collectResponses($this->pathPrefixToRemove, $path, $namespace));
        }

        return $docApi;
    }

    protected function collectResponses(?string $pathPrefixToRemove, string $responsesDir, string $responsesNamespace): array
    {
        $responses = [];
        $responseClasses = $this->lib->findClassesInPath($responsesDir, $responsesNamespace);
        $responseClasses = \array_unique($responseClasses);
        foreach ($responseClasses as $responseClass) {
            $responseDoc = (new ResponseDocCollector($this->lib))->collect($responseClass);
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
     * @throws \ReflectionException
     */
    protected function collectForms($formsDir, $formsNamespace): array
    {
        $forms = [];
        $formClasses = $this->lib->findClassesInPath($formsDir, $formsNamespace);
        foreach ($formClasses as $formClass) {
            $formDoc = (new FormDocCollector($this->lib))->collect($formClass);
            if ($formDoc) {
                $forms[$formClass] = $formDoc;
            }
        }
        return $forms;
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

        $router = $this->lib->getRouter();
        /** @var Route[] $routes */
        $routes = $router->getRouteCollection();

        foreach ($routes as $routeName => $route) {
            try {
                $actionDoc = (new ActionDocCollector($this->lib))->setPathPrefixToRemove($pathPrefixToRemove)->collect($route);
                if ($actionDoc) {
                    $actions[$actionDoc->actionClass] = $actionDoc;
                }

            } catch (\Throwable $e) {
                $this->getLogger()->error('FAILED to collect documentation for '.$routeName.': '.$e->getMessage().', SKIPPED');
            }

        }

        return $actions;
    }

    private function getLogger(): LoggerInterface
    {
        return $this->lib->getLogger();
    }

}