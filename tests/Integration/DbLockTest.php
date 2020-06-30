<?php
namespace OCA\OJSXC\Tests\Integration;

use OCP\AppFramework\Db\DoesNotExistException;
use PHPUnit\Framework\TestCase;
use OCA\OJSXC\AppInfo\Application;
use OCA\OJSXC\DbLock;

$time = 0;

function time()
{
	global $time;
	return $time;
}

/**
 * @group DB
 */
class DbLockTest extends TestCase
{

	/**
	 * @var \OCA\OJSXC\DbLock
	 */
	private $dbLock;

	/**
	 * @var \OCA\OJSXC\DbLock
	 */
	private $dbLock2;

	/**
	 * @var \OCP\IDb
	 */
	private $con;


	/**
	 * @var \OCP\AppFramework\IAppContainer
	 */
	private $container;

	public function setUp(): void
	{
		parent::setUp();
		$app = new Application();
		$this->container = $app->getContainer();
		$this->con = $this->container->getServer()->getDatabaseConnection();
		$this->con->executeQuery("DELETE FROM `*PREFIX*preferences` WHERE `appid`='ojsxc' AND `configkey`='longpolling'");
	}

	/**
	 * Tests the setLock and stillLocked function by setting up and lock
	 * and then setting a new lock.
	 */
	public function testLock()
	{
		global $time;
		$time = 4;
		$this->dbLock = new DbLock(
			'john',
			$this->container->getServer()->getConfig(),
			$this->container->getServer()->getDatabaseConnection()
		);
		$this->dbLock->setLock();
		$result = $this->fetchLocks();
		$this->assertCount(1, $result);
		$this->assertEquals($result[0]['userid'], 'john');
		$this->assertEquals($result[0]['appid'], 'ojsxc');
		$this->assertEquals($result[0]['configkey'], 'longpolling');
		$this->assertEquals($result[0]['configvalue'], '4');
		$this->assertTrue($this->dbLock->stillLocked());


		$time = 5;
		$this->dbLock2 = new DbLock(
			'john',
			$this->container->getServer()->getConfig(),
			$this->container->getServer()->getDatabaseConnection()
		); // simulate new lock/request
		$this->dbLock2->setLock();

		$this->assertFalse($this->dbLock->stillLocked());
		$this->assertTrue($this->dbLock2->stillLocked());
		$result = $this->fetchLocks();
		$this->assertCount(1, $result);
		$this->assertEquals($result[0]['userid'], 'john');
		$this->assertEquals($result[0]['appid'], 'ojsxc');
		$this->assertEquals($result[0]['configkey'], 'longpolling');
		$this->assertEquals($result[0]['configvalue'], '5');
		$this->assertTrue($this->dbLock2->stillLocked());
	}

	private function fetchLocks()
	{
		$stmt = $this->con->executeQuery("SELECT * FROM `*PREFIX*preferences` WHERE `appid`='ojsxc' AND `configkey`='longpolling'");

		$reuslt = [];

		while ($row = $stmt->fetch()) {
			$result[] = $row;
		}


		return $result;
	}
}
