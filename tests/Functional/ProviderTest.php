<?php

namespace Webfactory\Bundle\WfdMetaBundle\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use Webfactory\Bundle\WfdMetaBundle\Provider;

class ProviderTest extends FunctionalTestCase
{
    private readonly Provider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = $this->container->get(Provider::class);
    }

    #[Test]
    public function getLastTouchedRowReturnsNullIfNoEntriesExist()
    {
        self::assertNull($this->provider->getLastTouchedRow('myTable', 1));
    }

    #[Test]
    public function getLastTouchedRowReturnsNullIfNoMatchingEntriesExist()
    {
        $this->dbal->executeStatement(
            "
            -- correct table, wrong primary key
            INSERT INTO `wfd_table` (id, tablename) VALUES (1, 'myTable');
            INSERT INTO `wfd_meta` (wfd_table_id, data_id, last_touched) VALUES (1, 2, '2000-01-01 00:00:00');

            -- correct primary key, wrong table
            INSERT INTO `wfd_table` (id, tablename) VALUES (2, 'wrongTable');
            INSERT INTO `wfd_meta` (wfd_table_id, data_id, last_touched) VALUES (2, 1, '2000-01-01 00:00:00');
        "
        );

        self::assertNull($this->provider->getLastTouchedRow('myTable', 1));
    }

    #[Test]
    public function getLastTouchedRowReturnsTimestampOfLastChange()
    {
        $this->dbal->executeStatement(
            "
            INSERT INTO `wfd_table` (id, tablename) VALUES (1, 'myTable');
            INSERT INTO `wfd_meta` (wfd_table_id, data_id, last_touched) VALUES (1, 1, '2000-01-01 00:00:00');
        "
        );

        self::assertEquals(
            mktime(0, 0, 0, 1, 1, 2000),
            $this->provider->getLastTouchedRow('myTable', 1)
        );
    }

    #[Test]
    public function getLastTouchedReturnsNullIfNoEntriesExist()
    {
        self::assertNull($this->provider->getLastTouched(['myTable']));
    }

    #[Test]
    public function getLastTouchedReturnsNullIfNoMatchingEntriesExist()
    {
        $this->dbal->executeStatement(
            "
            -- wrong table
            INSERT INTO `wfd_table` (id, tablename) VALUES (1, 'wrongTable');
            INSERT INTO `wfd_meta` (wfd_table_id, data_id, last_touched) VALUES (1, 1, '2000-01-01 00:00:00');
        "
        );

        self::assertNull($this->provider->getLastTouched(['myTable']));
    }

    #[Test]
    public function getLastTouchedReturnsTimestampOfLastChangeOfAnyGivenTable()
    {
        $this->dbal->executeStatement(
            "
            INSERT INTO `wfd_table` (id, tablename) VALUES (1, 'myTable');
            INSERT INTO `wfd_meta` (wfd_table_id, data_id, last_touched) VALUES (1, 1, '1999-01-01 00:00:00');

            INSERT INTO `wfd_table` (id, tablename) VALUES (2, 'myOtherTable');
            INSERT INTO `wfd_meta` (wfd_table_id, data_id, last_touched) VALUES (2, 1, '2000-01-01 00:00:00');

            INSERT INTO `wfd_table` (id, tablename) VALUES (3, 'myThirdTable');
            INSERT INTO `wfd_meta` (wfd_table_id, data_id, last_touched) VALUES (3, 1, '2001-01-01 00:00:00');
        "
        );

        self::assertEquals(
            mktime(0, 0, 0, 1, 1, 2001),
            $this->provider->getLastTouched(['myTable', 'myOtherTable', 'myThirdTable'])
        );
    }

    #[Test]
    public function getLastTouchedReturnsTimestampOfLastChangeForWildcard()
    {
        $this->dbal->executeStatement(
            "
            INSERT INTO `wfd_table` (id, tablename) VALUES (1, 'myTable');
            INSERT INTO `wfd_meta` (wfd_table_id, data_id, last_touched) VALUES (1, 1, '1999-01-01 00:00:00');

            INSERT INTO `wfd_table` (id, tablename) VALUES (2, 'myOtherTable');
            INSERT INTO `wfd_meta` (wfd_table_id, data_id, last_touched) VALUES (2, 1, '2000-01-01 00:00:00');

            INSERT INTO `wfd_table` (id, tablename) VALUES (3, 'myThirdTable');
            INSERT INTO `wfd_meta` (wfd_table_id, data_id, last_touched) VALUES (3, 1, '2001-01-01 00:00:00');
        "
        );

        $timestamp = $this->provider->getLastTouched(['*']);

        self::assertEquals(
            mktime(0, 0, 0, 1, 1, 2001),
            $timestamp
        );
    }

    #[Test]
    public function getLastTouchedOfEachRowReturnsEmptyArrayIfNoEntriesExist()
    {
        $result = $this->provider->getLastTouchedOfEachRow('myTable');
        self::assertIsArray($result);
        self::assertEmpty($result);
    }

    #[Test]
    public function getLastTouchedOfEachRowReturnsTimestampOfLastChangeOfAnyGivenTable()
    {
        $this->dbal->executeStatement(
            "
            INSERT INTO `wfd_table` (id, tablename) VALUES (1, 'myTable');
            INSERT INTO `wfd_meta` (wfd_table_id, data_id, last_touched) VALUES (1, 1, '1999-01-01 00:00:00');
            INSERT INTO `wfd_meta` (wfd_table_id, data_id, last_touched) VALUES (1, 2, '2000-01-01 00:00:00');
            INSERT INTO `wfd_meta` (wfd_table_id, data_id, last_touched) VALUES (1, 3, '2001-01-01 00:00:00');
        "
        );

        $idsAndTimestamps = $this->provider->getLastTouchedOfEachRow('myTable');

        $this->assertCount(3, $idsAndTimestamps);
        $this->assertArrayHasKey(1, $idsAndTimestamps);
        $this->assertArrayHasKey(2, $idsAndTimestamps);
        $this->assertArrayHasKey(3, $idsAndTimestamps);
        $this->assertContains(mktime(0, 0, 0, 1, 1, 1999), $idsAndTimestamps);
        $this->assertContains(mktime(0, 0, 0, 1, 1, 2000), $idsAndTimestamps);
        $this->assertContains(mktime(0, 0, 0, 1, 1, 2001), $idsAndTimestamps);
    }
}
