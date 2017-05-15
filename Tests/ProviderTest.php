<?php

namespace Webfactory\Bundle\WfdMetaBundle\Tests;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDOSqlite\Driver;
use Webfactory\Bundle\WfdMetaBundle\Provider;

/**
 * Tests for the Provider.
 */
final class ProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var Provider */
    private $provider;

    /** @var Connection */
    private $connection;

    protected function setUp()
    {
        // Possible parameters are documented at {@link http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/configuration.html}.
        $connectionParameter = array(
            'driver'   => 'pdo_sqlite',
            'user'     => 'root',
            'password' => '',
            'memory'   => true
        );
        $this->connection = new Connection($connectionParameter, new Driver());

        $this->connection->exec("
            CREATE TABLE `wfd_table` (
              `id` INTEGER ,
              `tablename` varchar(100) NOT NULL DEFAULT ''
            );
            CREATE TABLE `wfd_meta` (
              `wfd_table_id` smallint(5)  NOT NULL DEFAULT '0',
              `data_id` mediumint(8)  NOT NULL DEFAULT '0',
              `last_touched` datetime DEFAULT NULL
            );
        ");

        $this->provider = new Provider($this->connection);
    }

    /**
     * @test
     */
    public function getLastTouchedRowReturnsNullIfNoEntriesExist()
    {
        $this->assertNull($this->provider->getLastTouchedRow('myTable', 1));
    }

    /**
     * @test
     */
    public function getLastTouchedRowReturnsNullIfNoMatchingEntriesExist()
    {
        $this->connection->exec("
            -- correct table, wrong primary key
            INSERT INTO `wfd_table` (id, tablename) VALUES (1, 'myTable');
            INSERT INTO `wfd_meta` (wfd_table_id, data_id, last_touched) VALUES (1, 2, '2000-01-01 00:00:00');
            
            -- correct primary key, wrong table
            INSERT INTO `wfd_table` (id, tablename) VALUES (2, 'wrongTable');
            INSERT INTO `wfd_meta` (wfd_table_id, data_id, last_touched) VALUES (2, 1, '2000-01-01 00:00:00');
        ");
        
        $this->assertNull($this->provider->getLastTouchedRow('myTable', 1));
    }

    /**
     * @test
     */
    public function getLastTouchedRowReturnsTimestampOfLastChange()
    {
        $this->connection->exec("
            INSERT INTO `wfd_table` (id, tablename) VALUES (1, 'myTable');
            INSERT INTO `wfd_meta` (wfd_table_id, data_id, last_touched) VALUES (1, 1, '2000-01-01 00:00:00');
        ");

        $this->assertEquals(946681200, $this->provider->getLastTouchedRow('myTable', 1));
    }
}
