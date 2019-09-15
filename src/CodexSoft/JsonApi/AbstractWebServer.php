<?php

namespace CodexSoft\JsonApi;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\RouteCollectionBuilder;
use Doctrine\Common\Annotations\AnnotationReader;

abstract class AbstractWebServer extends BaseKernel
{
    use MicroKernelTrait;

    const CONFIG_EXTS = '.{php,xml,yaml,yml}';

    public function boot()
    {
        AnnotationReader::addGlobalIgnoredNamespace('SWG');
        return parent::boot();
    }

    public function getCacheDir()
    {
        return $this->getProjectDir().'/var/cache/'.$this->environment;
    }

    public function getLogDir()
    {
        return $this->getProjectDir().'/var/log';
    }

    public function registerBundles()
    {
        $bundlesFile = $this->getProjectDir().'/config/bundles.php';
        if (!file_exists($bundlesFile)) {
            return;
        }
        $contents = require $bundlesFile;
        foreach ($contents as $class => $envs) {
            if (isset($envs['all']) || isset($envs[$this->environment])) {
                yield new $class();
            }
        }
        //return [
        //    new \Symfony\Bundle\FrameworkBundle\FrameworkBundle,
        //];
    }

    private function getBundlesList(): array
    {

    }

    /**
     * @param ContainerBuilder $container
     * @param LoaderInterface $loader
     */
    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader)
    {
        $container->addResource(new FileResource($this->getProjectDir().'/config/bundles.php'));
        $container->loadFromExtension('framework', [
            'secret' => 'S0ME_SECRET'
        ]);

        $confDir = $this->getProjectDir().'/config';

        //$loader->load($confDir.'/{packages}/*'.self::CONFIG_EXTS, 'glob');
        //$loader->load($confDir.'/{packages}/'.$this->environment.'/**/*'.self::CONFIG_EXTS, 'glob');
        $loader->load($confDir.'/{services}'.self::CONFIG_EXTS, 'glob');
        $loader->load($confDir.'/{services}_'.$this->environment.self::CONFIG_EXTS, 'glob');
    }

    /**
     * @param RouteCollectionBuilder $routes
     *
     * @throws \Symfony\Component\Config\Exception\LoaderLoadException
     */
    protected function configureRoutes(RouteCollectionBuilder $routes)
    {
        $routes->import($this->getProjectDir().'/src/App/Action/', '/', 'annotation');

        //$routes->import($confDir.'/{routes}/*'.self::CONFIG_EXTS, '/', 'glob');
        //$routes->import($confDir.'/{routes}/'.$this->environment.'/**/*'.self::CONFIG_EXTS, '/', 'glob');
        //$routes->import($confDir.'/{routes}'.self::CONFIG_EXTS, '/', 'glob');
        $routes->add('/random/{limit}', 'kernel:randomNumber');
    }

    public function randomNumber($limit)
    {
        return new JsonResponse(array(
            'number' => rand(0, $limit)
        ));
    }

}
