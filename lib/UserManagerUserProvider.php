<?php

namespace OCA\OJSXC;

use OCP\IUserManager;

class UserManagerUserProvider implements IUserProvider {

	/**
	 * @var IUserManager
	 */
	private $userManager;

	public function __construct(IUserManager $userManager) {
		$this->userManager = $userManager;
	}

	public function getAllUsers() {
		$result = [];
		foreach ($this->userManager->search('') as $user) {
			$result[] = new User($user->getUID(), $user->getDisplayName(), $user);
		}

		return $result;
	}

	public function hasUser(User $user) {
		return !is_null($this->userManager->get($user->getUid()));

	}
}