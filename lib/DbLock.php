<?php

namespace OCA\OJSXC;

use OCP\IConfig;
use OCP\IDBConnection;

/**
 * Class DbLock
 *
 * @package OCA\OJSXC
 */
class DbLock implements ILock
{
	/**
	 * @var IConfig $config
	 */
	private $config;

	/**
	 * @var string $userId
	 */
	private $userId;

	/**
	 * @var string $pollingId
	 */
	private $pollingId;

	/** @var IDBConnection */
	private $con;

	/**
	 * DbLock constructor.
	 *
	 * @param string $userId
	 * @param IConfig $config
	 */
	public function __construct($userId, IConfig $config, IDBConnection $con)
	{
		$this->userId = $userId;
		$this->config = $config;
		$this->pollingId = microtime();
		$this->con = $con;
	}

	public function setLock()
	{
		$this->config->setUserValue($this->userId, 'ojsxc', 'longpolling', $this->pollingId);
	}

	/**
	 * @return bool
	 */
	public function stillLocked()
	{
		$storedPollingId = $this->config->getUserValue($this->userId, 'ojsxc', 'longpolling');

		return $storedPollingId === $this->pollingId;
	}
}
