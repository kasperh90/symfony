<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Lock\Tests\Store;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\DefaultSchemaManagerFactory;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\ORMSetup;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\Lock\Key;
use Symfony\Component\Lock\PersistingStoreInterface;
use Symfony\Component\Lock\Store\PdoStore;

/**
 * @author Jérémy Derussé <jeremy@derusse.com>
 *
 * @requires extension pdo_sqlite
 *
 * @group legacy
 */
class PdoDbalStoreTest extends AbstractStoreTestCase
{
    use ExpectDeprecationTrait;
    use ExpiringStoreTestTrait;

    protected static $dbFile;

    public static function setUpBeforeClass(): void
    {
        self::$dbFile = tempnam(sys_get_temp_dir(), 'sf_sqlite_lock');

        $config = class_exists(ORMSetup::class) ? ORMSetup::createConfiguration(true) : new Configuration();
        if (class_exists(DefaultSchemaManagerFactory::class)) {
            $config->setSchemaManagerFactory(new DefaultSchemaManagerFactory());
        }

        $store = new PdoStore(DriverManager::getConnection(['driver' => 'pdo_sqlite', 'path' => self::$dbFile], $config));
        $store->createTable();
    }

    public static function tearDownAfterClass(): void
    {
        @unlink(self::$dbFile);
    }

    /**
     * {@inheritdoc}
     */
    protected function getClockDelay()
    {
        return 1500000;
    }

    /**
     * {@inheritdoc}
     */
    public function getStore(): PersistingStoreInterface
    {
        $this->expectDeprecation('Since symfony/lock 5.4: Usage of a DBAL Connection with "Symfony\Component\Lock\Store\PdoStore" is deprecated and will be removed in symfony 6.0. Use "Symfony\Component\Lock\Store\DoctrineDbalStore" instead.');

        $config = class_exists(ORMSetup::class) ? ORMSetup::createConfiguration(true) : new Configuration();
        if (class_exists(DefaultSchemaManagerFactory::class)) {
            $config->setSchemaManagerFactory(new DefaultSchemaManagerFactory());
        }

        return new PdoStore(DriverManager::getConnection(['driver' => 'pdo_sqlite', 'path' => self::$dbFile], $config));
    }

    public function testAbortAfterExpiration()
    {
        $this->markTestSkipped('Pdo expects a TTL greater than 1 sec. Simulating a slow network is too hard');
    }

    public function testConfigureSchema()
    {
        $this->expectDeprecation('Since symfony/lock 5.4: Usage of a DBAL Connection with "Symfony\Component\Lock\Store\PdoStore" is deprecated and will be removed in symfony 6.0. Use "Symfony\Component\Lock\Store\DoctrineDbalStore" instead.');

        $store = new PdoStore($this->createMock(Connection::class), ['db_table' => 'lock_table']);
        $schema = new Schema();
        $store->configureSchema($schema);
        $this->assertTrue($schema->hasTable('lock_table'));
    }

    /**
     * @dataProvider provideDsn
     */
    public function testDsn(string $dsn, string $file = null)
    {
        $this->expectDeprecation('Since symfony/lock 5.4: Usage of a DBAL Connection with "Symfony\Component\Lock\Store\PdoStore" is deprecated and will be removed in symfony 6.0. Use "Symfony\Component\Lock\Store\DoctrineDbalStore" instead.');
        $key = new Key(uniqid(__METHOD__, true));

        try {
            $store = new PdoStore($dsn);
            $store->createTable();

            $store->save($key);
            $this->assertTrue($store->exists($key));
        } finally {
            if (null !== $file) {
                @unlink($file);
            }
        }
    }

    public static function provideDsn()
    {
        $dbFile = tempnam(sys_get_temp_dir(), 'sf_sqlite_cache');
        yield ['sqlite://localhost/'.$dbFile.'1', $dbFile.'1'];
        yield ['sqlite3:///'.$dbFile.'3', $dbFile.'3'];
        yield ['sqlite://localhost/:memory:'];
    }
}
