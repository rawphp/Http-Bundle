<?php

namespace RawPHP\HttpBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Class HttpExtension
 *
 * @package RawPHP\HttpBundle\DependencyInjection
 */
class HttpExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $config, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

        $appDir = $container->getParameter('kernel.root_dir');

        $container->setParameter('rawphp_http.resource_dir', $appDir . '/Features/Resources');
    }
}
