<?php

namespace OCA\OJSXC\Tests\Integration;

use OCA\OJSXC\AppInfo\Application;
use OCA\OJSXC\Db\IQRosterPushMapper;
use OCP\Contacts\ContactsMenu\IContactsStore;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IUserManager;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class RosterPushTest extends TestCase
{

	/**
	 * @var RosterPush
	 */
	private $rosterPush;

	/**
	 * @var IUserManager
	 */
	private $userManager;

	/**
	 * @var IGroupManager
	 */
	private $groupManager;

	/**
	 * @var IUserSession
	 */
	private $userSession;

	/**
	 * @var MockObject | IQRosterPushMapper
	 */
	private $iqRosterPushMapper;

	/**
	 * @var IDBConnection
	 */
	private $dbConnection;

	/**
	 * @var IUserProvider
	 */
	private $userProvider;

	/**
	 * @var IConfig
	 */
	private $config;

	/**
	 * @var MockObject | IContactsStore
	 */
	private $contactsStore;

	public function setUp(): void
	{
		if (!Application::contactsStoreApiSupported()) {
			$this->markTestSkipped();
			return;
		}

		$this->userManager = \OC::$server->getUserManager();
		$this->groupManager = \OC::$server->getGroupManager();
		$this->userSession = \OC::$server->getUserSession();
		$this->iqRosterPushMapper = $this->getMockBuilder(IQRosterPushMapper::class)->disableOriginalConstructor()->getMock();
		$this->dbConnection = \OC::$server->getDatabaseConnection();
		$this->config = \OC::$server->getConfig();
		$this->contactsStore = $this->getMockBuilder(IContactsStore::class)->disableOriginalConstructor()->getMock();

		$this->userProvider = new ContactsStoreUserProvider(
			$this->contactsStore,
			$this->userSession,
			$this->userManager,
			$this->groupManager,
			$this->config
		);

		$this->rosterPush = new RosterPush(
			$this->userManager,
			$this->userSession,
			'localhost',
			$this->iqRosterPushMapper,
			$this->dbConnection,
			$this->userProvider
		);

		foreach (\OC::$server->getUserManager()->search('') as $user) {
			$user->delete();
		}
		foreach (\OC::$server->getGroupManager()->search('') as $group) {
			$group->delete();
		}
	}

	public function testRemoveRosterItemForUsersInGroup()
	{
		$group1 = $this->groupManager->createGroup('group1');
		$group2 = $this->groupManager->createGroup('group2');
		$group3 = $this->groupManager->createGroup('group3');
		$user1 = $this->userManager->createUser('user1', 'user1');
		$user2 = $this->userManager->createUser('user2', 'user2');
		$user3 = $this->userManager->createUser('user3', 'user3');
		$user4 = $this->userManager->createUser('user4', 'user4');

		$group1->addUser($user1);
		$group1->addUser($user2);
		$group1->addUser($user3);
		$group2->addUser($user1);
		$group2->addUser($user2);
		$group3->addUser($user4);

		// remove $user1 from $group1
		// when no special settings are set this should result in no rosterMessages
		$this->iqRosterPushMapper->expects($this->never())->method('insert');
		$this->contactsStore->expects($this->at(0))
			->method('findOne')
			->with($user2, 0, 'user1')
			->willReturn([$user2]);
		$this->contactsStore->expects($this->at(1))
			->method('findOne')
			->with($user3, 0, 'user1')
			->willReturn([$user3]);
		$this->rosterPush->removeRosterItemForUsersInGroup($group1, 'user1');
	}
	public function testRemoveRosterItemForUsersInGroupOwnGroups()
	{
		$group1 = $this->groupManager->createGroup('group1');
		$group2 = $this->groupManager->createGroup('group2');
		$group3 = $this->groupManager->createGroup('group3');
		$user1 = $this->userManager->createUser('user1', 'user1');
		$user2 = $this->userManager->createUser('user2', 'user2');
		$user3 = $this->userManager->createUser('user3', 'user3');
		$user4 = $this->userManager->createUser('user4', 'user4');

		$group1->addUser($user1);
		$group1->addUser($user2);
		$group1->addUser($user3);
		$group2->addUser($user1);
		$group2->addUser($user2);
		$group3->addUser($user4);

		$this->config->setAppValue('core', 'shareapi_only_share_with_group_members', 'yes');

		// remove $user1 from $group1
		// users can only chat with users in their groups
		// $user2 should still be reachable by $group2
		// $user3 should be become unreachable
		// for $user4 nothing changes
		$this->iqRosterPushMapper->expects($this->once())->method('insert');

		$this->contactsStore->expects($this->at(0))
			->method('findOne')
			->with($user2, 0, 'user1')
			->willReturn([$user2]);

		$this->contactsStore->expects($this->at(1))
			->method('findOne')
			->with($user3, 0, 'user1')
			->willReturn(null);

		$this->rosterPush->removeRosterItemForUsersInGroup($group1, 'user1');

		$this->config->setAppValue('core', 'shareapi_only_share_with_group_members', 'no');
	}
}
