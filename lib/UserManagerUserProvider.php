<?php

namespace OCA\OJSXC;

use OCP\IUserManager;

class UserManagerUserProvider implements IUserProvider {

	/**
	 * @var IUserManager
	 */
	private $userManager;

	/**
	 * @var User[] Cache
	 */
	private static $cache = null;

	public function __construct(IUserManager $userManager) {
		$this->userManager = $userManager;
	}

	public function getAllUsers() {
		if (is_null(self::$cache)) {
			$result = [];
			foreach ($this->userManager->search('') as $user) {
				$result[] = new User($user->getUID(), $user->getDisplayName(), $user);
			}

			self::$cache = $result;
		}
		return self::$cache;
	}

	public function hasUser(User $user) {
		return !is_null($this->userManager->get($user->getUid()));

	}

	/**
	 * @brief Checks if the current user can interact with the provided user identified by it's UID.
	 * @param string $uid the uid of the user
	 * @return bool
	 */
	public function hasUserByUID($uid) {
		return !is_null($this->userManager->get($uid));
	}
}