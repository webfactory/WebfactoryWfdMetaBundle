<?php

namespace Webfactory\Bundle\WfdMetaBundle\Tests\Fixtures;

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;

class TestKernel extends Kernel
{
    public function registerBundles(): iterable
    {
        return [
            new \Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new \Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new \Symfony\Bundle\TwigBundle\TwigBundle(),
            new \Webfactory\Bundle\WfdMetaBundle\WebfactoryWfdMetaBundle(),
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(static function (ContainerBuilder $container): void {
            $container->loadFromExtension('framework', [
                'secret' => 'dont-tell-mum',
                'test' => true,
                'http_method_override' => false,
                'handle_all_throwables' => true,
                'php_errors' => [
                    'log' => true,
                ],
            ] + (Kernel::VERSION_ID < 70000 ? ['annotations' => false] : []));

            $container->loadFromExtension('doctrine', [
                'dbal' => [
                    'driver' => 'pdo_sqlite',
                    'memory' => true,
                ],
                'orm' => [
                    'controller_resolver' => [
                        'auto_mapping' => false,
                    ],
                    'mappings' => [
                        'Webfactory\Bundle\WfdMetaBundle\Tests\Fixtures\Entity' => [
                            'type' => 'attribute',
                            'dir' => '%kernel.project_dir%/Entity',
                            'is_bundle' => false,
                            'prefix' => 'Webfactory\Bundle\WfdMetaBundle\Tests\Fixtures\Entity',
                        ],
                    ],
                ],
            ]);
        });
    }

    public function getProjectDir(): string
    {
        return __DIR__;
    }
}
