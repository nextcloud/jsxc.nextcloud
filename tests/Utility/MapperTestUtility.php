<?php

namespace OCA\OJSXC\Tests\Utility;

use OCA\OJSXC\AppInfo\Application;

/**
 * @group DB
 */
class MapperTestUtility extends TestCase
{

	/**
	 * @var \OCP\AppFramework\IAppContainer
	 */
	protected $container;

	protected $entityName;

	protected $mapperName;

	protected $host;

	protected $userId;

	protected function setUp(): void
	{
		parent::setUp();
		$app = new Application();
		$this->overwriteApplicationService($app, 'Host', 'localhost');
		$this->overwriteApplicationService($app, 'UserId', 'admin');
		$this->container = $app->getContainer();
		$this->mapper = $this->container[$this->mapperName];

		$this->host = 'localhost';
		$this->userId = 'admin';

		$con = $this->container->getServer()->getDatabaseConnection();
		$con->executeQuery('DELETE FROM ' . $this->mapper->getTableName());
	}

	protected function tearDown(): void
	{
		$con = $this->container->getServer()->getDatabaseConnection();
		$con->executeQuery('DELETE FROM ' . $this->mapper->getTableName());
	}

	protected function fetchAll()
	{
		$con = $this->container->getServer()->getDatabaseConnection();
		$stmt = $con->executeQuery('SELECT * FROM ' . $this->mapper->getTableName());
		$entities = [];

		while ($row = $stmt->fetch()) {
			$entities[] = call_user_func($this->entityName . '::fromRow', $row);
			;
		}

		$stmt->closeCursor();

		return $entities;
	}

	protected function fetchAllAsArray($tableName = null)
	{
		if (is_null($tableName)) {
			$tableName = $this->mapper->getTableName();
		} else {
		}
		$con = $this->container->getServer()->getDatabaseConnection();
		$stmt = $con->executeQuery('SELECT * FROM ' . $tableName);

		$result = [];
		while ($row = $stmt->fetch()) {
			$result[] = $row;
		}
		$stmt->closeCursor();

		return $result;
	}

	public function getLastInsertedId()
	{
		return $this->container->getServer()->getDatabaseConnection()->lastInsertId();
	}
}
