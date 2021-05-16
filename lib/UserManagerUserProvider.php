<?php

namespace OCA\OJSXC;

use OCP\IUserManager;

/**
 * Class UserManagerUserProvider
 *
 * @package OCA\OJSXC
 * @codeCoverageIgnore
 */
class UserManagerUserProvider implements IUserProvider
{

	/**
	 * @var IUserManager
	 */
	private $userManager;

	/**
	 * @var User[] Cache
	 */
	private static $cache = null;

	public function __construct(IUserManager $userManager)
	{
		$this->userManager = $userManager;
	}

	public function getAllUsers()
	{
		if (is_null(self::$cache)) {
			$result = [];
			foreach ($this->userManager->search('') as $user) {
				if ($user->isEnabled()) {
					$result[] = new User($user->getUID(), $user->getDisplayName(), $user);
				}
			}

			self::$cache = $result;
		}
		return self::$cache;
	}

	public function hasUser(User $user)
	{
		return !is_null($this->userManager->get($user->getUid()));
	}

	public function hasUserByUID($uid)
	{
		return !is_null($this->userManager->get($uid));
	}

	public function getAllUsersForUser(User $user)
	{
		// since we don't have access to the ContactsStore, we don't apply the enhancement privacy rules.
		return $this->getAllUsers();
	}

	public function getAllUsersForUserByUID($uid)
	{
		// since we don't have access to the ContactsStore, we don't apply the enhancement privacy rules.
		return $this->getAllUsers();
	}

	public function hasUserForUser(User $user1, User $user2)
	{
		// since we don't have access to the ContactsStore, we don't apply the enhancement privacy rules.
		return $this->hasUser($user2);
	}

	public function hasUserForUserByUID($uid1, $uid2)
	{
		// since we don't have access to the ContactsStore, we don't apply the enhancement privacy rules.
		return $this->hasUserByUID($uid2);
	}

	public function isUserExcluded($userId)
	{
		// to limit inconsistency we only support the settings in NC > 13.0.0
		return false;
	}
}
