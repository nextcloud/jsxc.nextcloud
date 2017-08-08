<?php

namespace OCA\OJSXC;

use OCP\IUser;

class User {

	/**
	 * @var string
	 */
	private $uid;

	/**
	 * @var string
	 */
	private $fullName;


	/**
	 * @brief The original object where this user is created from.
	 * @var IUser | \OCP\Contacts\ContactsMenu\IEntry
	 */
	private $origin;

	/**
	 * @param string $uid UID of the user
	 * @param string $fullName Fullname of the user
	 */
	public function __construct($uid, $fullName, $origin) {
		$this->uid = $uid;
		$this->fullName = $fullName;
		$this->origin = $origin;
	}

	/**
	 * @return string
	 */
	public function getUid() {
		return $this->uid;
	}

	/**
	 * @param string $uid
	 */
	public function setUid($uid) {
		$this->uid = $uid;
	}

	/**
	 * @return string
	 */
	public function getFullName() {
		return $this->fullName;
	}

	/**
	 * @param string $fullName
	 */
	public function setFullName($fullName) {
		$this->fullName = $fullName;
	}


}