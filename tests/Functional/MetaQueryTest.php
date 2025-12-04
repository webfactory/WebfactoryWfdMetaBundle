<?php

declare(strict_types=1);

namespace Webfactory\Bundle\WfdMetaBundle\Tests\Functional;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Webfactory\Bundle\WfdMetaBundle\MetaQueryFactory;
use Webfactory\Bundle\WfdMetaBundle\Tests\Fixtures\CreateSchemaHelper;
use Webfactory\Bundle\WfdMetaBundle\Tests\Fixtures\Entity\TestEntity;

class MetaQueryTest extends FunctionalTestCase
{
    private readonly MetaQueryFactory $metaQueryFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->metaQueryFactory = $this->container->get(MetaQueryFactory::class);
    }

    #[Test]
    public function getLastTouched_for_entity_class(): void
    {
        $time = new \DateTimeImmutable();

        $this->dbal->insert('wfd_table', ['id' => 42, 'tablename' => 'test_table']);
        $this->dbal->insert('wfd_meta', ['data_id' => 1, 'wfd_table_id' => 42, 'last_touched' => $time], ['last_touched' => Types::DATETIME_IMMUTABLE]);

        $metaQuery = $this->metaQueryFactory->create();
        $metaQuery->addEntity(TestEntity::class);

        self::assertEquals($time->getTimestamp(), $metaQuery->getLastTouched());
    }
}
