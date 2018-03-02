<?php

namespace OCA\OJSXC;

use OCP\IConfig;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUserManager;
use OCP\IUserSession;

class ContactsStoreUserProvider implements IUserProvider
{

	/**
	 * @var \OCP\Contacts\ContactsMenu\IContactsStore
	 */
	private $contactsStore;

	/**
	 * @var User[] $cache
	 */
	private static $cache = null;

	/**
	 * @var IUserSession
	 */
	private $userSession;

	/**
	 * @var IUserManager
	 */
	private $userManager;

	/**
	 * @var IGroupManager
	 */
	private $groupManager;

	/**
	 * @var IConfig
	 */
	private $config;

	public function __construct($contactsStore, IUserSession $userSession, IUserManager $userManager, IGroupManager $groupManager, IConfig $config)
	{
		$this->contactsStore = $contactsStore;
		$this->userSession = $userSession;
		$this->userManager = $userManager;
		$this->groupManager = $groupManager;
		$this->config = $config;
	}

	public function getAllUsers()
	{
		if (is_null(self::$cache)) {
			$result = [];
			$contacts = $this->contactsStore->getContacts($this->userSession->getUser(), '');
			foreach ($contacts as $contact) {
				$uid = $contact->getProperty('UID');
				$user = $this->userManager->get($uid);
				if ($contact->getProperty('isLocalSystemBook')
					&& !$this->isUserExcluded($uid)
					&& !is_null($user)
					&& $user->isEnabled()) {
					$result[] = new User($uid, $contact->getFullName(), $contact);
				}
			}
			self::$cache = $result;
		}

		return self::$cache;
	}

	public function hasUser(User $user)
	{
		return !is_null($this->contactsStore->findOne($this->userSession->getUser(), 0, $user->getUid()));
	}

	public function hasUserByUID($uid)
	{
		return !is_null($this->contactsStore->findOne($this->userSession->getUser(), 0, $uid));
	}

	public function getAllUsersForUser(User $user)
	{
		return $this->getAllUsersForUserByUID($user->getUid());
	}

	public function getAllUsersForUserByUID($uid)
	{
		$result = [];
		$contacts = $this->contactsStore->getContacts($this->userManager->get($uid), '');
		foreach ($contacts as $contact) {
			if ($contact->getProperty('isLocalSystemBook')) {
				$result[] = new User($contact->getProperty('UID'), $contact->getFullName(), $contact);
			}
		}
		return $result;
	}

	public function hasUserForUser(User $user1, User $user2)
	{
		return !is_null($this->contactsStore->findOne($this->userManager->get($user1->getUid()), 0, $user2->getUid()));
	}

	public function hasUserForUserByUID($uid1, $uid2)
	{
		return !is_null($this->contactsStore->findOne($this->userManager->get($uid1), 0, $uid2));
	}

	public function isUserExcluded($userId)
	{
		if ($this->config->getAppValue('core', 'shareapi_exclude_groups', 'no') === 'yes') {
			$user = $this->userManager->get($userId);
			$user_groups = $this->groupManager->getUserGroupIds($user);
			$excludedGroups = $this->config->getAppValue('core', 'shareapi_exclude_groups_list', '');
			$decodedExcludeGroups = json_decode($excludedGroups, true);
			$excludeGroupsList = ($decodedExcludeGroups !== null) ? $decodedExcludeGroups :  [];

			if (count(array_intersect($excludeGroupsList, $user_groups)) !== 0) {
				// a group of the current user is excluded -> filter all local users
				return true;
			}
		}
		return false;
	}
}
