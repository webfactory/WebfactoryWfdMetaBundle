<?php

declare(strict_types=1);

namespace Webfactory\Bundle\WfdMetaBundle\Tests\Functional;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Webfactory\Bundle\WfdMetaBundle\Tests\Fixtures\CreateSchemaHelper;

class FunctionalTestCase extends KernelTestCase
{
    protected readonly ContainerInterface $container;
    protected readonly Connection $dbal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = self::getContainer();
        $this->dbal = $this->container->get('doctrine.dbal.default_connection');
        CreateSchemaHelper::createSchema($this->dbal);
    }
}
