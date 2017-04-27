<?php

namespace OCA\OJSXC;

use OCP\IConfig;

/**
 * Class DbLock
 *
 * @package OCA\OJSXC
 */
class DbLock implements ILock {
	/**
	 * @var IConfig $config
	 */
	private $config;

	/**
	 * @var string $userId
	 */
	private $userId;

	/**
	 * @var int $pollingId
	 */
	private $pollingId;

	/**
	 * DbLock constructor.
	 *
	 * @param string $userId
	 * @param IConfig $config
	 */
	public function __construct($userId, IConfig $config) {
		$this->userId = $userId;
		$this->config = $config;
		$this->pollingId = time();
	}

	public function setLock() {
		$this->config->setUserValue($this->userId, 'ojsxc', 'longpolling', $this->pollingId);
	}

	/**
	 * @return bool
	 */
	public function stillLocked() {
		$configValue = $this->config->getAppValue('ojsxc', 'longpolling');
		return (int) $configValue === (int) $this->pollingId;
	}

}
