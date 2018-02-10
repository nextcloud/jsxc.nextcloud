<?php

namespace OCA\OJSXC;

use OC\Contacts\ContactsMenu\ContactsStore;
use OCA\DAV\CardDAV\AddressBookImpl;
use OCA\OJSXC\AppInfo\Application;
use OCP\Contacts\ContactsMenu\IContactsStore;
use OCP\Contacts\IManager;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IUserManager;
use OCP\IUserSession;
use OCA\DAV\AppInfo\Application as DavApp;
use OCA\DAV\CardDAV\CardDavBackend;
use PHPUnit\Framework\Constraint\IsEqual;
use Sabre\VObject\Component\VCard;
use OCA\OJSXC\Utility\TestCase;

class ContactsStoreUserProviderTest extends TestCase
{

	/**
	 * @var ContactsStoreUserProvider
	 */
	private $contactsStoreUserProvider;

	/**
	 * @var IContactsStore
	 */
	private $contactsStore;

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

	/**
	 * @var CardDavBackend
	 */
	private $cardDavBackend;

	/**
	 * @var IManager
	 */
	private $contactsManager;

	public function setUp()
	{
		if (!Application::contactsStoreApiSupported()) {
			$this->markTestSkipped();
			return;
		}
		$this->config = \OC::$server->getConfig();
		$this->config->setAppValue('core', 'shareapi_only_share_with_group_members', 'no');
		foreach (\OC::$server->getUserManager()->search('') as $user) {
			$user->delete();
		}

		$users[] = \OC::$server->getUserManager()->createUser('admin', 'admin');
		$users[] = \OC::$server->getUserManager()->createUser('derp', 'derp');
		$users[] = \OC::$server->getUserManager()->createUser('derpina', 'derpina');
		$users[] = \OC::$server->getUserManager()->createUser('herp', 'herp');
		$users[] = \OC::$server->getUserManager()->createUser('foo', 'foo');

		$currentUser = \OC::$server->getUserManager()->createUser('autotest', 'autotest');
		$this->userSession = \OC::$server->getUserSession();
		$this->userSession->setUser($currentUser);
		$this->userManager = \OC::$server->getUserManager();
		$this->groupManager = \OC::$server->getGroupManager();
		$this->contactsStore = \OC::$server->query(ContactsStore::class);
		$this->contactsManager = \OC::$server->getContactsManager();

		/** @var \OCA\DAV\CardDAV\SyncService $syncService */
		$syncService = \OC::$server->query('CardDAVSyncService');
		$syncService->getLocalSystemAddressBook();
		$syncService->updateUser($currentUser);


		$davApp = new DavApp();

		$this->cardDavBackend = $davApp->getContainer()->query(CardDavBackend::class);

		// create some contacts
		$vCard = new VCard();
		$vCard->VERSION = '3.0';
		$vCard->UID = 'Test1';
		$vCard->FN = 'Test1';

		$id = $this->setupAddressBook('autotest');
		$this->cardDavBackend->createCard($id, 'Alice.Test1.vcf', $vCard->serialize());

		foreach ($users as $user) {
			$syncService->updateUser($user);
		}

		$davApp->setupSystemContactsProvider($this->contactsManager);
		\OC_User::setIncognitoMode(false);
		\OC::$server->getDatabaseConnection()->executeQuery("DELETE FROM *PREFIX*ojsxc_stanzas");


		$this->contactsStoreUserProvider = new ContactsStoreUserProvider(
			$this->contactsStore,
			$this->userSession,
			$this->userManager,
			$this->groupManager,
			$this->config
		);
	}

	private function setupAddressBook($userId)
	{
		$addressBooks = $this->cardDavBackend->getAddressBooksForUser("principals/users/$userId");
		foreach ($addressBooks as $addressBookInfo) {
			$this->cardDavBackend->deleteAddressBook($addressBookInfo['id']);
		}
		$addressBookId = $this->cardDavBackend->createAddressBook('principals/users/' . $userId, 'principals/users/' . $userId, []);

		$addressBooks = $this->cardDavBackend->getAddressBooksForUser("principals/users/$userId");

		foreach ($addressBooks as $addressBookInfo) {
			$addressBook = new \OCA\DAV\CardDAV\AddressBook($this->cardDavBackend, $addressBookInfo, \OC::$server->getL10N('dav'));
			$this->contactsManager->registerAddressBook(
				new AddressBookImpl(
					$addressBook,
					$addressBookInfo,
					$this->cardDavBackend,
					\OC::$server->getURLGenerator()
				)
			);
		}

		return $addressBookId;
	}

	protected function tearDown()
	{
		$config = \OC::$server->getConfig();
		$config->setAppValue('core', 'shareapi_only_share_with_group_members', 'no');
		$config->setAppValue('core', 'shareapi_exclude_groups', 'no');
		$config->setAppValue('core', 'shareapi_exclude_groups_list', json_encode([]));
		foreach (\OC::$server->getUserManager()->search('') as $user) {
			$user->delete();
		}
	}

	public function testNormalSituation()
	{

		// no special settings set
		$derp = new User('derp', 'derp', $this->contactsStore->findOne($this->userManager->get('autotest'), 6, 'derp'));
		$derpina = new User('derpina', 'derpina', $this->contactsStore->getContacts($this->userManager->get('autotest'), 'derpina')[0]);
		$herp = new User('herp', 'herp', $this->contactsStore->getContacts($this->userManager->get('autotest'), 'herp')[0]);
		$foo = new User('foo', 'foo', $this->contactsStore->getContacts($this->userManager->get('autotest'), 'foo')[0]);
		$admin = new User('admin', 'admin', $this->contactsStore->getContacts($this->userManager->get('autotest'), 'admin')[0]);
		$autotest = new User('autotest', 'autotest', $this->contactsStore->getContacts($this->userManager->get('derpina'), 'autotest')[0]);

		$expected = [
			$foo,
			$admin,
			$derp,
			$derpina,
			$herp
		];

		$this->assertCanonicalizeEquals($expected, $this->contactsStoreUserProvider->getAllUsers());
		$this->assertCanonicalizeEquals([$autotest, $derp, $herp, $foo, $admin], $this->contactsStoreUserProvider->getAllUsersForUserByUID('derpina'));
		$this->assertCanonicalizeEquals([$autotest, $derp, $herp, $foo, $admin], $this->contactsStoreUserProvider->getAllUsersForUser($derpina));

		$this->assertTrue($this->contactsStoreUserProvider->hasUserForUserByUID('derp', 'derpina'));
		$this->assertTrue($this->contactsStoreUserProvider->hasUserForUser($derp, $derpina));
		$this->assertTrue($this->contactsStoreUserProvider->hasUserByUID('derpina'));
		$this->assertTrue($this->contactsStoreUserProvider->hasUser($derpina));
		$this->assertFalse($this->contactsStoreUserProvider->isUserExcluded('derpina'));
	}

	public function testGroupsOnly()
	{
		$this->setValueOfPrivateProperty($this->contactsStoreUserProvider, 'cache', null);
		$group1 = $this->groupManager->createGroup('group1');
		$group2 = $this->groupManager->createGroup('group2');
		$group1->addUser($this->userManager->get('derp'));
		$group1->addUser($this->userManager->get('foo'));
		$group2->addUser($this->userManager->get('derpina'));
		$group2->addUser($this->userManager->get('herp'));

		$this->config->setAppValue('core', 'shareapi_only_share_with_group_members', 'yes');

		// no special settings set
		$derp = new User('derp', 'derp', $this->contactsStore->getContacts($this->userManager->get('foo'), 'derp')[0]);
		$derpina = new User('derpina', 'derpina', $this->contactsStore->getContacts($this->userManager->get('herp'), 'derpina')[0]);
		$herp = new User('herp', 'herp', $this->contactsStore->getContacts($this->userManager->get('derpina'), 'herp')[0]);
		$foo = new User('foo', 'foo', $this->contactsStore->getContacts($this->userManager->get('derp'), 'foo')[0]);

		$this->assertCanonicalizeEquals([], $this->contactsStoreUserProvider->getAllUsers()); // running as autotest -> not in any group
		$this->assertCanonicalizeEquals([$herp], $this->contactsStoreUserProvider->getAllUsersForUserByUID('derpina'));
		$this->assertCanonicalizeEquals([$herp], $this->contactsStoreUserProvider->getAllUsersForUser($derpina));
		$this->assertCanonicalizeEquals([$foo], $this->contactsStoreUserProvider->getAllUsersForUserByUID('derp'));
		$this->assertCanonicalizeEquals([$foo], $this->contactsStoreUserProvider->getAllUsersForUser($derp));

		$this->assertFalse($this->contactsStoreUserProvider->hasUserForUserByUID('derp', 'derpina'));
		$this->assertFalse($this->contactsStoreUserProvider->hasUserForUser($derp, $derpina));
		$this->assertFalse($this->contactsStoreUserProvider->hasUserForUserByUID('derpina', 'derp'));
		$this->assertFalse($this->contactsStoreUserProvider->hasUserForUser($derpina, $derp));

		$this->assertTrue($this->contactsStoreUserProvider->hasUserForUserByUID('derp', 'foo'));
		$this->assertTrue($this->contactsStoreUserProvider->hasUserForUser($derp, $foo));
		$this->assertTrue($this->contactsStoreUserProvider->hasUserForUserByUID('foo', 'derp'));
		$this->assertTrue($this->contactsStoreUserProvider->hasUserForUser($foo, $derp));

		$this->assertFalse($this->contactsStoreUserProvider->hasUserByUID('derpina'));
		$this->assertFalse($this->contactsStoreUserProvider->hasUser($derpina));
		$this->assertFalse($this->contactsStoreUserProvider->isUserExcluded('derpina'));
	}

	public function testExcluded()
	{
		$this->setValueOfPrivateProperty($this->contactsStoreUserProvider, 'cache', null);
		$groupExcluded = $this->groupManager->createGroup('excluded');
		$this->config->setAppValue('core', 'shareapi_exclude_groups', 'yes');
		$this->config->setAppValue('core', 'shareapi_exclude_groups_list', json_encode(['excluded']));
		$groupExcluded->addUser($this->userManager->get('derp'));
		$groupExcluded->addUser($this->userManager->get('derpina'));

		$herp = new User('herp', 'herp', $this->contactsStore->getContacts($this->userManager->get('autotest'), 'herp')[0]);
		$foo = new User('foo', 'foo', $this->contactsStore->getContacts($this->userManager->get('autotest'), 'foo')[0]);
		$admin = new User('admin', 'admin', $this->contactsStore->getContacts($this->userManager->get('autotest'), 'admin')[0]);
		$autotest = new User('autotest', 'autotest', $this->contactsStore->getContacts($this->userManager->get('admin'), 'autotest')[0]);

		$this->assertCanonicalizeEquals([$admin, $herp, $foo], $this->contactsStoreUserProvider->getAllUsers());
		$this->assertTrue($this->contactsStoreUserProvider->isUserExcluded('derp'));
		$this->assertTrue($this->contactsStoreUserProvider->isUserExcluded('derpina'));
		$this->assertFalse($this->contactsStoreUserProvider->isUserExcluded('autotest'));
		$this->assertFalse($this->contactsStoreUserProvider->isUserExcluded('admin'));
		$this->assertFalse($this->contactsStoreUserProvider->isUserExcluded('foo'));
		$this->assertFalse($this->contactsStoreUserProvider->isUserExcluded('herp'));

		$this->config->setAppValue('core', 'shareapi_exclude_groups', 'no');
		$this->config->setAppValue('core', 'shareapi_exclude_groups_list', json_encode([]));
	}

	public function testDisabled()
	{
		$this->setValueOfPrivateProperty($this->contactsStoreUserProvider, 'cache', null);
		$derpina = new User('derpina', 'derpina', $this->contactsStore->getContacts($this->userManager->get('autotest'), 'derpina')[0]);
		$herp = new User('herp', 'herp', $this->contactsStore->getContacts($this->userManager->get('autotest'), 'herp')[0]);
		$foo = new User('foo', 'foo', $this->contactsStore->getContacts($this->userManager->get('autotest'), 'foo')[0]);
		$admin = new User('admin', 'admin', $this->contactsStore->getContacts($this->userManager->get('autotest'), 'admin')[0]);

		$this->userManager->get('derp')->setEnabled(false);

		$expected = [
			$admin,
			$derpina,
			$herp,
			$foo
		];

		$this->assertCanonicalizeEquals($expected, $this->contactsStoreUserProvider->getAllUsers());
	}

	private function assertCanonicalizeEquals($expected, $actual, $message = '', $delta = 0.0, $maxDepth = 10, $ignoreCase = false)
	{
		$this->assertEquals($expected, $actual, $message, $delta, $maxDepth, true, $ignoreCase);
	}
}
