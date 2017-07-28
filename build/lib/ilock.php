<?php

namespace OCA\OJSXC;

/**
 * Interface ILock
 *
 * @package OCA\OJSXC
 */
interface ILock
{

	/**
	 * @return void
	 */
	public function setLock();

	/**
	 * @return bool
	 */
	public function stillLocked();
}
