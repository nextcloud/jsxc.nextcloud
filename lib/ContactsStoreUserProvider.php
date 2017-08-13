<?php

namespace OCA\OJSXC;

use OCP\IUserManager;
use OCP\IUserSession;

class ContactsStoreUserProvider implements IUserProvider {

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

	public function __construct($contactsStore, IUserSession $userSession, IUserManager $userManager) {
		$this->contactsStore = $contactsStore;
		$this->userSession = $userSession;
		$this->userManager = $userManager;
	}

	public function getAllUsers() {
		if (is_null(self::$cache)) {
			$result = [];
			$contacts = $this->contactsStore->getContacts($this->userSession->getUser(), '');
			foreach ($contacts as $contact) {
				if ($contact->getProperty('isLocalSystemBook')) {
					$result[] = new User($contact->getProperty('UID'), $contact->getFullName(), $contact);
				}
			}
			self::$cache = $result;
		}

		return self::$cache;

	}

	public function hasUser(User $user) {
		return !is_null($this->contactsStore->findOne($this->userSession->getUser(), 0, $user->getUid()));
	}

	public function hasUserByUID($uid) {
		return !is_null($this->contactsStore->findOne($this->userSession->getUser(), 0, $uid));
	}

	public function getAllUsersForUser(User $user) {
		return $this->getAllUsersForUserByUID($user->getUid());
	}

	public function getAllUsersForUserByUID($uid) {
		$result = [];
		$contacts = $this->contactsStore->getContacts($this->userManager->get($uid), '');
		foreach ($contacts as $contact) {
			if ($contact->getProperty('isLocalSystemBook')) {
				$result[] = new User($contact->getProperty('UID'), $contact->getFullName(), $contact);
			}
		}
		return $result;
	}

	public function hasUserForUser(User $user1, User $user2) {
		return !is_null($this->contactsStore->findOne($this->userManager->get($user1->getUid()), 0, $user2->getUid()));
	}

	public function hasUserForUserByUID($uid1, $uid2) {
		return !is_null($this->contactsStore->findOne($this->userManager->get($uid1), 0, $uid2));
	}

}