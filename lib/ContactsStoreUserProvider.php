<?php

namespace OCA\OJSXC;

class ContactsStoreUserProvider implements IUserProvider {

	/**
	 * @var \OCP\Contacts\ContactsMenu\IContactsStore
	 */
	private $contactsStore;

	public function __construct($contactsStore) {
		$this->contactsStore = $contactsStore;
	}

	public function getAllUsers() {
		$result = [];
		$contacts = $this->contactsStore->getContacts(\OC::$server->getUserSession()->getUser(), '');
		foreach ($contacts as $contact) {
			if ($contact->getProperty('isLocalSystemBook')) {
				$result[] = new User($contact->getProperty('UID'), $contact->getFullName(), $contact);
			}
		}

		return $result;
	}

	public function hasUser(User $user) {
		return !is_null($this->contactsStore->findOne(\OC::$server->getUserSession()->getUser(), 0, $user->getUid()));
	}
}