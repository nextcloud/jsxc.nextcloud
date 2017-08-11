<?php

namespace OCA\OJSXC;

class ContactsStoreUserProvider implements IUserProvider {

	/**
	 * @var \OCP\Contacts\ContactsMenu\IContactsStore
	 */
	private $contactsStore;

	/**
	 * @var User[] $cache
	 */
	private static $cache = null;

	public function __construct($contactsStore) {
		$this->contactsStore = $contactsStore;
	}

	public function getAllUsers() {
		if (is_null(self::$cache)) {
			$result = [];
			$contacts = $this->contactsStore->getContacts(\OC::$server->getUserSession()->getUser(), '');
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
		return !is_null($this->contactsStore->findOne(\OC::$server->getUserSession()->getUser(), 0, $user->getUid()));
	}

	/**
	 * @brief Checks if the current user can interact with the provided user identified by it's UID.
	 * @param string $uid the uid of the user
	 * @return bool
	 */
	public function hasUserByUID($uid) {
		return !is_null($this->contactsStore->findOne(\OC::$server->getUserSession()->getUser(), 0, $uid));
	}
}