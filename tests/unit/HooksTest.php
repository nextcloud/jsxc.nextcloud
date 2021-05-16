<?php


namespace OCA\OJSXC\Tests\Unit;

use OCA\OJSXC\AppInfo\Application;
use OCA\OJSXC\Db\Presence;
use OCA\OJSXC\Db\PresenceMapper;
use OCA\OJSXC\Db\StanzaMapper;
use OCA\OJSXC\Hooks;
use OCA\OJSXC\RosterPush;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class HooksTest extends TestCase
{

	/**
	 * @var Hooks $hooks
	 */
	private $hooks;

	/**
	 * @var MockObject
	 */
	private $userManager;

	/**
	 * @var MockObject
	 */
	private $userSession;

	/**
	 * @var MockObject
	 */
	private $rosterPush;

	/**
	 * @var MockObject
	 */
	private $presenceMapper;

	/**
	 * @var MockObject
	 */
	private $stanzaMapper;

	/**
	 * @var MockObject
	 */
	private $groupManager;

	public function setUp(): void
	{
		/** @var IUserManager */
		$this->userManager = $this->getMockBuilder(IUserManager::class)->setMethods(['listen', 'registerBackend', 'getBackends', 'removeBackend', 'clearBackends', 'get', 'userExists', 'checkPassword', 'search', 'searchDisplayName', 'createUser', 'createUserFromBackend', 'countUsers', 'callForAllUsers', 'countDisabledUsers', 'countSeenUsers', 'callForSeenUsers', 'getByEmail'])->getMock();
		/** @var IUserSession */
		$this->userSession = $this->getMockBuilder(IUserSession::class)->setMethods(['listen', 'login', 'logout', 'setUser', 'getUser', 'isLoggedIn', 'getImpersonatingUserID', 'setImpersonatingUserID'])->getMock();
		/** @var RosterPush */
		$this->rosterPush = $this->getMockBuilder(RosterPush::class)->disableOriginalConstructor()->getMock();
		/** @var PresenceMapper */
		$this->presenceMapper = $this->getMockBuilder(PresenceMapper::class)->disableOriginalConstructor()->getMock();
		/** @var StanzaMapper */
		$this->stanzaMapper = $this->getMockBuilder(StanzaMapper::class)->disableOriginalConstructor()->getMock();
		/** @var IGroupManager */
		$this->groupManager = $this->getMockBuilder(IGroupManager::class)->disableOriginalConstructor()->setMethods(['listen', 'isBackendUsed', 'addBackend', 'clearBackends', 'get', 'groupExists', 'createGroup', 'search', 'getUserGroups', 'getUserGroupIds', 'displayNamesInGroup', 'isAdmin', 'isInGroup', 'getBackends'])->getMock();

		$this->hooks = new Hooks(
			$this->userManager,
			$this->userSession,
			$this->rosterPush,
			$this->presenceMapper,
			$this->stanzaMapper,
			$this->groupManager
		);
	}

	private function buildUser()
	{
		/** @var IUser */
		$user = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();

		return $user;
	}

	public function testOnCreateUser()
	{
		$user = $this->buildUser();

		$this->rosterPush->expects($this->once())
			->method('createOrUpdateRosterItem')
			->with($user);

		$this->hooks->onCreateUser($user, 'abc');
	}

	public function testOnDeleteUser()
	{
		$user = $this->buildUser();

		$user->expects($this->exactly(3))
			->method('getUID')
			->willReturn('test');

		$this->rosterPush->expects($this->once())
			->method('removeRosterItem')
			->with('test');

		$this->presenceMapper->expects($this->once())
			->method('deletePresence')
			->with('test');

		$this->stanzaMapper->expects($this->once())
			->method('deleteByTo')
			->with('test');

		$this->hooks->onDeleteUser($user);
	}


	public function testOnChangeUserEnabled()
	{
		$user = $this->buildUser();

		$hooks = $this->getMockBuilder(Hooks::class)->disableOriginalConstructor()->setMethods(['onCreateUser'])->getMock();

		$hooks->expects($this->once())
			->method('onCreateUser')
			->with($user, '');

		/** @var Hooks $hooks */
		$hooks->onChangeUser($user, 'enabled', 'true');
	}

	public function testOnChangeUserDisabled()
	{
		$user = $this->buildUser();

		$hooks = $this->getMockBuilder(Hooks::class)->disableOriginalConstructor()->setMethods(['onDeleteUser'])->getMock();

		$hooks->expects($this->once())
			->method('onDeleteUser')
			->with($user);

		/** @var Hooks $hooks */
		$hooks->onChangeUser($user, 'enabled', 'false');
	}

	public function testOnChangeUserDisplayName()
	{
		$user = $this->buildUser();

		$hooks = $this->getMockBuilder(Hooks::class)->disableOriginalConstructor()->setMethods(['onCreateUser'])->getMock();

		$hooks->expects($this->once())
			->method('onCreateUser')
			->with($user);

		/** @var Hooks $hooks */
		$hooks->onChangeUser($user, 'displayName', 'abc');
	}

	public function testOnAddUserToGroup()
	{
		$user = $this->buildUser();
		/** @var IGroup */
		$group = $this->getMockBuilder(IGroup::class)->disableOriginalConstructor()->getMock();

		$this->rosterPush->expects($this->once())->method('createOrUpdateRosterItem')->with($user);
		$this->rosterPush->expects($this->once())->method('addUserToGroup')->with($user, $group);

		$this->hooks->onAddUserToGroup($group, $user);
	}

	public function testOnRemoveUserFromGroup()
	{
		$user = $this->buildUser();
		/** @var IGroup */
		$group = $this->getMockBuilder(IGroup::class)->disableOriginalConstructor()->getMock();

		if (Application::contactsStoreApiSupported()) {
			$user->expects($this->once())->method('getUID')->willReturn('uid1');
			$this->rosterPush->expects($this->once())->method('removeRosterItemForUsersInGroup')->with($group, 'uid1');
		}

		$this->rosterPush->expects($this->once())->method('removeUserFromGroup')->with($user, $group);

		$this->hooks->onRemoveUserFromGroup($group, $user);
	}
}
