<?php
namespace OCA\OJSXC;

use OCP\AppFramework\Db\DoesNotExistException;
use Test\TestCase;
use OCA\OJSXC\AppInfo\Application;
use OCA\OJSXC\MemLock;

//function time() {
//	return DbLockTest::$time;
//}

/**
 * @group DB
 */
class MemLockTest extends TestCase
{

	/**
	 * @var \OCA\OJSXC\MemLock
	 */
	private $memLock;

	/**
	 * @var \OCA\OJSXC\MemLock
	 */
	private $memLock2;

	/**
	 * @var \OCP\AppFramework\IAppContainer
	 */
	private $container;

	/**
	 * @var \OCP\ICache
	 */
	private $memCache;

	public static $time;

	public function setUp(): void
	{
		parent::setUp();
		$app = new Application();
		$this->container = $app->getContainer();

		$version = \OC::$server->getSession()->get('OC_Version');
		if ($version[0] === 8 && $version[1] == 0) {
			$this->markTestSkipped();
		}
	}

	/**
	 * Tests the setLock and stillLocked function by setting up and lock
	 * and then setting a new lock.
	 */
	public function testLock()
	{
		global $time;
		$time = 4;
		$cache = $this->container->getServer()->getMemCacheFactory();
		if ($cache->isAvailable()) {
			$this->memCache = $cache->create('ojsxc');
		} else {
			die('No memcache available');
		}

		$this->memLock = new MemLock(
			'john',
			$this->memCache
		);
		$this->memLock->setLock();
		$this->assertTrue($this->memLock->stillLocked());


		$result = $this->fetchLock();
		$this->assertEquals('4', $result);


		global $time;
		$time = 5;
		$this->memLock2 = new MemLock(
			'john',
			$this->memCache
		); // simulate new lock/request
		$this->memLock2->setLock();

		$this->assertFalse($this->memLock->stillLocked());
		$this->assertTrue($this->memLock2->stillLocked());
		$result = $this->fetchLock();
		$this->assertEquals('5', $result);
	}

	private function fetchLock()
	{
		return $this->memCache->get('-john-ojxsc-lock');
	}
}
