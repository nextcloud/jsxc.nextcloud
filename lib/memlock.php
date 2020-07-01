<?php

namespace OCA\OJSXC;

use OCP\ICache;

/**
 * Class MemLock
 *
 * @package OCA\OJSXC
 */
class MemLock implements ILock
{

	/**
	 * @var \OCP\ICache $memcache
	 */
	private $memcache;

	/**
	 * @var string $userId
	 */
	private $userId;

	/**
	 * @var string $pollingId
	 */
	private $pollingId;

	/**
	 * MemLock constructor.
	 *
	 * @param $userId
	 * @param ICache $cache
	 */
	public function __construct($userId, ICache $cache)
	{
		$this->userId = $userId;
		$this->memcache = $cache;
		$this->pollingId = microtime();
	}

	public function setLock()
	{
		$this->memcache->remove('-' . $this->userId . '-ojxsc-lock');
		$this->memcache->add('-' . $this->userId . '-ojxsc-lock', $this->pollingId);
	}

	/**
	 * @return bool
	 */
	public function stillLocked()
	{
		$r = $this->memcache->get('-' . $this->userId . '-ojxsc-lock');
		return $r === $this->pollingId;
	}
}
