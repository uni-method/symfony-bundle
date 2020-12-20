<?php declare(strict_types=1);

namespace UniMethod\Bundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;

class UniMethodExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );
        $loader->load('services.yml');

        if ($config['default_path']) {
            $container->setParameter('jsonapi-default_path', $config['default_path']);
        }
        if ($config['prefix']) {
            $container->setParameter('jsonapi-prefix', $config['prefix']);
        }
        if ($config['available']) {
            $container->setParameter('jsonapi-available', $config['available']);
        }
    }

    public function getConfiguration(array $config, ContainerBuilder $container): Configuration
    {
        return new Configuration();
    }
}
