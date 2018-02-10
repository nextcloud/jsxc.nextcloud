<?php


namespace OCA\OJSXC;

use OCA\OJSXC\AppInfo\Application;
use OCA\OJSXC\Db\Presence;
use OCA\OJSXC\Db\PresenceMapper;
use OCA\OJSXC\Db\StanzaMapper;
use OCP\IGroup;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit\Framework\TestCase;

class HooksTest extends TestCase
{

	/**
	 * @var Hooks $hooks
	 */
	private $hooks;

	/**
	 * @var PHPUnit_Framework_MockObject_MockObject | IUserManager
	 */
	private $userManager;

	/**
	 * @var PHPUnit_Framework_MockObject_MockObject | IuserSession
	 */
	private $userSession;

	/**
	 * @var PHPUnit_Framework_MockObject_MockObject | RosterPush
	 */
	private $rosterPush;

	/**
	 * @var PHPUnit_Framework_MockObject_MockObject | PresenceMapper
	 */
	private $presenceMapper;

	/**
	 * @var PHPUnit_Framework_MockObject_MockObject | StanzaMapper
	 */
	private $stanzaMapper;

	/**
	 * @var PHPUnit_Framework_MockObject_MockObject | \OCP\IGroupManager
	 */
	private $groupManager;

	public function setUp()
	{
		$this->userManager = $this->getMockBuilder('OCP\IUserManager')->setMethods(['listen', 'registerBackend', 'getBackends', 'removeBackend', 'clearBackends', 'get', 'userExists', 'checkPassword', 'search', 'searchDisplayName', 'createUser', 'createUserFromBackend', 'countUsers', 'callForAllUsers', 'countDisabledUsers', 'countSeenUsers', 'callForSeenUsers', 'getByEmail'])->getMock();

		$this->userSession = $this->getMockBuilder('OCP\IUserSession')->setMethods(['listen', 'login', 'logout', 'setUser', 'getUser', 'isLoggedIn'])->getMock();


		$this->rosterPush = $this->getMockBuilder('OCA\OJSXC\RosterPush')->disableOriginalConstructor()->getMock();

		$this->presenceMapper = $this->getMockBuilder('OCA\OJSXC\Db\PresenceMapper')->disableOriginalConstructor()->getMock();

		$this->stanzaMapper = $this->getMockBuilder('OCA\OJSXC\Db\StanzaMapper')->disableOriginalConstructor()->getMock();

		$this->groupManager = $this->getMockBuilder('OCP\IGroupManager')->disableOriginalConstructor()->setMethods(['listen', 'isBackendUsed', 'addBackend', 'clearBackends', 'get', 'groupExists', 'createGroup', 'search', 'getUserGroups', 'getUserGroupIds', 'displayNamesInGroup', 'isAdmin', 'isInGroup', 'getBackends'])->getMock();

		$this->hooks = new Hooks(
			$this->userManager,
			$this->userSession,
			$this->rosterPush,
			$this->presenceMapper,
			$this->stanzaMapper,
			$this->groupManager
		);
	}


	public function testRegister()
	{
		$this->userManager->expects($this->at(0))
			->method('listen')
			->with('\OC\User', 'postCreateUser', [$this->hooks, 'onCreateUser']);

		$this->userManager->expects($this->at(1))
			->method('listen')
			->with('\OC\User', 'postDelete', [$this->hooks, 'onDeleteUser']);

		$this->userSession->expects($this->once())
			->method('listen')
			->with('\OC\User', 'changeUser', [$this->hooks, 'onChangeUser']);

		$this->hooks->register();
	}

	public function testOnCreateUser()
	{
		$user = $this->getMockBuilder('OCP\IUser')->disableOriginalConstructor()->getMock();

		$this->rosterPush->expects($this->once())
			->method('createOrUpdateRosterItem')
			->with($user);

		$this->hooks->onCreateUser($user, 'abc');
	}

	public function testOnDeleteUser()
	{
		$user = $this->getMockBuilder('OCP\IUser')->disableOriginalConstructor()->getMock();

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
		$user = $this->getMockBuilder('OCP\IUser')->disableOriginalConstructor()->getMock();

		$hooks = $this->getMockBuilder('OCA\OJSXC\Hooks')->disableOriginalConstructor()->setMethods(['onCreateUser'])->getMock();

		$hooks->expects($this->once())
			->method('onCreateUser')
			->with($user, '');

		$hooks->onChangeUser($user, 'enabled', 'true');
	}

	public function testOnChangeUserDisabled()
	{
		$user = $this->getMockBuilder('OCP\IUser')->disableOriginalConstructor()->getMock();

		$hooks = $this->getMockBuilder('OCA\OJSXC\Hooks')->disableOriginalConstructor()->setMethods(['onDeleteUser'])->getMock();

		$hooks->expects($this->once())
			->method('onDeleteUser')
			->with($user);

		$hooks->onChangeUser($user, 'enabled', 'false');
	}

	public function testOnChangeUserDisplayName()
	{
		$user = $this->getMockBuilder('OCP\IUser')->disableOriginalConstructor()->getMock();

		$hooks = $this->getMockBuilder('OCA\OJSXC\Hooks')->disableOriginalConstructor()->setMethods(['onCreateUser'])->getMock();

		$hooks->expects($this->once())
			->method('onCreateUser')
			->with($user);

		$hooks->onChangeUser($user, 'displayName', 'abc');
	}

	public function testOnAddUserToGroup()
	{
		$user = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$group = $this->getMockBuilder(IGroup::class)->disableOriginalConstructor()->getMock();

		$this->rosterPush->expects($this->once())->method('createOrUpdateRosterItem')->with($user);
		$this->rosterPush->expects($this->once())->method('addUserToGroup')->with($user, $group);

		$this->hooks->onAddUserToGroup($group, $user);
	}

	public function testOnRemoveUserFromGroup()
	{
		$user = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$group = $this->getMockBuilder(IGroup::class)->disableOriginalConstructor()->getMock();

		if (Application::contactsStoreApiSupported()) {
			$user->expects($this->once())->method('getUID')->willReturn('uid1');
			$this->rosterPush->expects($this->once())->method('removeRosterItemForUsersInGroup')->with($group, 'uid1');
		}

		$this->rosterPush->expects($this->once())->method('removeUserFromGroup')->with($user, $group);

		$this->hooks->onRemoveUserFromGroup($group, $user);
	}
}
