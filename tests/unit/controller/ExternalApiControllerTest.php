<?php

namespace OCA\OJSXC\Controller;

use OCA\OJSXC\Controller\ExternalApiController;
use OCA\OJSXC\Controller\SignatureProtectedApiController;
use OCA\OJSXC\Exceptions\UnprocessableException;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\IGroupManager;
use OCP\ILogger;
use OCP\IUser;
use OCP\IGroup;
use PHPUnit\Framework\TestCase;

class ExternalApiControllerTest extends TestCase
{
	private $request;
	private $userManager;
	private $userSession;
	private $groupManager;
	private $logger;
	private $user;
	private $externalApiController;

	public function setUp()
	{
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->logger = $this->createMock(ILogger::class);
		$this->user = $this->createMock(IUser::class);

		$this->externalApiController = new ExternalApiController(
			'ojsxc',
			$this->request,
			$this->userManager,
			$this->userSession,
			$this->groupManager,
			$this->logger
	  );
	}

	public function testSignatureProtected()
	{
		$this->assertInstanceOf(SignatureProtectedApiController::class, $this->externalApiController);
	}

	public function testIndexWithUnsupportedOperation()
	{
		$this->expectException(UnprocessableException::class);
		$this->expectExceptionMessage('Unsupported operation.');

		$this->externalApiController->index('foobar');
	}

	public function testIndexAuth()
	{
		$externalApiController = $this->getMockBuilder(ExternalApiController::class)
			->setConstructorArgs([
				'ojsxc',
				 $this->request,
				 $this->userManager,
				 $this->userSession,
				 $this->groupManager,
				 $this->logger
			])
			->setMethods(['checkPassword'])
			->getMock();

		$this->request
			   ->expects($this->exactly(3))
			   ->method('getParam')
			   ->will($this->returnValueMap([
				  ['username', null, 'dummy_username'],
				  ['password', null, 'dummy_password'],
				  ['domain', null, 'dummy_domain']
			   ]));

		$externalApiController
			->expects($this->once())
			->method('checkPassword')
			->with('dummy_username', 'dummy_password', 'dummy_domain');

		$externalApiController->index('auth');
	}

	public function testIndexIsUser()
	{
		$externalApiController = $this->getMockBuilder(ExternalApiController::class)
			->setConstructorArgs([
				'ojsxc',
				 $this->request,
				 $this->userManager,
				 $this->userSession,
				 $this->groupManager,
				 $this->logger
			])
			->setMethods(['isUser'])
			->getMock();

		$this->request
			   ->expects($this->exactly(2))
			   ->method('getParam')
			   ->will($this->returnValueMap([
				  ['username', null, 'dummy_username'],
				  ['domain', null, 'dummy_domain']
			   ]));

		$externalApiController
			->expects($this->once())
			->method('isUser')
			->with('dummy_username', 'dummy_domain');

		$externalApiController->index('isuser');
	}

	public function testIndexSharedRoster()
	{
		$externalApiController = $this->getMockBuilder(ExternalApiController::class)
			->setConstructorArgs([
				'ojsxc',
				 $this->request,
				 $this->userManager,
				 $this->userSession,
				 $this->groupManager,
				 $this->logger
			])
			->setMethods(['sharedRoster'])
			->getMock();

		$this->request
			   ->expects($this->exactly(2))
			   ->method('getParam')
			   ->will($this->returnValueMap([
				  ['username', null, 'dummy_username'],
				  ['domain', null, 'dummy_domain']
			   ]));

		$externalApiController
			->expects($this->once())
			->method('sharedRoster')
			->with('dummy_username', 'dummy_domain');

		$externalApiController->index('sharedroster');
	}

	public function testCheckPasswordWithInvalidParams()
	{
		$this->userManager
			->expects($this->once())
			->method('userExists')
			->with('foo')
			->willReturn(true);
		$this->userSession
			   ->expects($this->once())
			   ->method('login')
			   ->with('foo', 'bar')
			   ->willReturn(false);

		$return = $this->externalApiController->checkPassword('foo', 'bar');

		$this->assertEquals('noauth', $return['result']);
	}

	public function testCheckPasswordWithInvalidParamsAndDomain()
	{
		$this->userManager
			->expects($this->exactly(2))
			->method('userExists')
			->will($this->returnValueMap([
			  ['foo@localhost', true],
			  ['foo', true]
			]));

		$this->userSession
			   ->expects($this->exactly(2))
			   ->method('login')
			   ->will($this->returnValueMap([
				  ['foo@localhost', 'bar', false],
				  ['foo', 'bar', false]
			   ]));

		$return = $this->externalApiController->checkPassword('foo', 'bar', 'localhost');

		$this->assertEquals('noauth', $return['result']);
	}

	public function testCheckPasswordWithValidParams()
	{
		$uid = 'foo';
		$this->userManager
			->expects($this->once())
			->method('userExists')
			->with($uid)
			->willReturn(true);
		$this->user
			   ->expects($this->once())
			   ->method('getUID')
			   ->willReturn($uid);
		$this->userSession
			   ->expects($this->once())
			   ->method('login')
			   ->with('foo', 'bar')
			   ->willReturn(true);
		$this->userSession
			   ->expects($this->once())
			   ->method('getUser')
			   ->willReturn($this->user);

		$return = $this->externalApiController->checkPassword('foo', 'bar');

		$this->assertEquals('success', $return['result']);
		$this->assertEquals($uid, $return['data']['uid']);
	}

	public function testCheckPasswordWithValidParamsAndDomain()
	{
		$uid = 'foo';
		$this->userManager
			->expects($this->once())
			->method('userExists')
			->with('foo@localhost')
			->willReturn(true);
		$this->user
			   ->expects($this->once())
			   ->method('getUID')
			   ->willReturn($uid);
		$this->userSession
			   ->expects($this->once())
			   ->method('login')
			   ->with('foo@localhost', 'bar')
			   ->willReturn(true);
		$this->userSession
			   ->expects($this->once())
			   ->method('getUser')
			   ->willReturn($this->user);

		$return = $this->externalApiController->checkPassword('foo', 'bar', 'localhost');

		$this->assertEquals('success', $return['result']);
		$this->assertEquals($uid, $return['data']['uid']);
	}

	public function testIsUserFail()
	{
		$this->userManager
			   ->expects($this->once())
			   ->method('userExists')
			   ->with('foo')
			   ->willReturn(false);

		$return = $this->externalApiController->isUser('foo');

		$this->assertEquals('success', $return['result']);
		$this->assertEquals(false, $return['data']['isUser']);
	}

	public function testIsUserFailWithDomain()
	{
		$this->userManager
			   ->expects($this->exactly(2))
			   ->method('userExists')
			   ->will($this->returnValueMap([
				  ['foo@localhost', false],
				  ['foo', false]
			   ]));

		$return = $this->externalApiController->isUser('foo', 'localhost');

		$this->assertEquals('success', $return['result']);
		$this->assertEquals(false, $return['data']['isUser']);
	}

	public function testIsUserSuccess()
	{
		$this->userManager
			   ->expects($this->once())
			   ->method('userExists')
			   ->with('foo')
			   ->willReturn(true);

		$return = $this->externalApiController->isUser('foo');

		$this->assertEquals('success', $return['result']);
		$this->assertEquals(true, $return['data']['isUser']);
	}

	public function testIsUserSuccessWithDomain()
	{
		$this->userManager
			   ->expects($this->once())
			   ->method('userExists')
			   ->with('foo@localhost')
			   ->willReturn(true);

		$return = $this->externalApiController->isUser('foo', 'localhost');

		$this->assertEquals('success', $return['result']);
		$this->assertEquals(true, $return['data']['isUser']);
	}

	public function testSharedRosterWithoutUsername()
	{
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Can\'t find user');

		$this->externalApiController->sharedRoster();
	}

	public function testSharedRosterNoGroups()
	{
		$user = $this->createUserMock('foobar');

		$this->userManager
			   ->expects($this->once())
			   ->method('userExists')
			   ->with($user->getUID())
			   ->willReturn(true);

		$this->userManager
			   ->expects($this->once())
			   ->method('get')
			   ->with($user->getUID())
			   ->willReturn($user);

		$this->groupManager
			   ->expects($this->once())
			   ->method('getUserGroups')
			   ->with($user)
			   ->willReturn([]);

		$return = $this->externalApiController->sharedRoster($user->getUID());

		$this->assertEquals('success', $return['result']);
		$this->assertEquals([], $return['data']['sharedRoster']);
	}

	public function testSharedRosterMultipleDistinctGroups()
	{
		$user = $this->createUserMock('foobar');

		$this->userManager
			   ->expects($this->once())
			   ->method('userExists')
			   ->with($user->getUID())
			   ->willReturn(true);

		$this->userManager
			   ->expects($this->once())
			   ->method('get')
			   ->with($user->getUID())
			   ->willReturn($user);

		$this->groupManager
			   ->expects($this->once())
			   ->method('getUserGroups')
			   ->with($user)
			   ->willReturn([
				  $this->createGroupMock('Group1', ['Foo Bar', 'John Doo', 'user42']),
				  $this->createGroupMock('Group2', ['Fritz', 'John', 'Eve'])
			   ]);

		$expectedResult = [
		 'Foo-Bar' => [
			'name' => 'Foo Bar',
			'groups' => ['Group1']
		 ],
		 'John-Doo' => [
			'name' => 'John Doo',
			'groups' => ['Group1']
		 ],
		 'user42' => [
			'name' => 'user42',
			'groups' => ['Group1']
		 ],
		 'Fritz' => [
			'name' => 'Fritz',
			'groups' => ['Group2']
		 ],
		 'John' => [
			'name' => 'John',
			'groups' => ['Group2']
		 ],
		 'Eve' => [
			'name' => 'Eve',
			'groups' => ['Group2']
		 ]
	  ];

		$return = $this->externalApiController->sharedRoster($user->getUID());

		$this->assertEquals('success', $return['result']);

		foreach ($return['data']['sharedRoster'] as $key => $value) {
			$this->assertEquals($expectedResult[$key], $value);
		}
	}

	public function testSharedRosterMultipleOverlapGroups()
	{
		$user = $this->createUserMock('foobar');

		$this->userManager
			   ->expects($this->once())
			   ->method('userExists')
			   ->with($user->getUID())
			   ->willReturn(true);

		$this->userManager
			   ->expects($this->once())
			   ->method('get')
			   ->with($user->getUID())
			   ->willReturn($user);

		$this->groupManager
			   ->expects($this->once())
			   ->method('getUserGroups')
			   ->with($user)
			   ->willReturn([
				  $this->createGroupMock('Group1', ['Foo Bar', 'John Doo', 'user42']),
				  $this->createGroupMock('Group2', ['user42', 'John', 'Foo Bar'])
			   ]);

		$expectedResult = [
		 'Foo-Bar' => [
			'name' => 'Foo Bar',
			'groups' => ['Group1', 'Group2']
		 ],
		 'John-Doo' => [
			'name' => 'John Doo',
			'groups' => ['Group1']
		 ],
		 'user42' => [
			'name' => 'user42',
			'groups' => ['Group1', 'Group2']
		 ],
		 'John' => [
			'name' => 'John',
			'groups' => ['Group2']
		 ]
	  ];

		$return = $this->externalApiController->sharedRoster($user->getUID());

		$this->assertEquals('success', $return['result']);

		foreach ($return['data']['sharedRoster'] as $key => $value) {
			$this->assertEquals($expectedResult[$key], $value);
		}
	}

	private function createGroupMock($groupName, $displayNames = [])
	{
		$users = [];

		foreach ($displayNames as $displayName) {
			$users[] = $this->createUserMock($displayName);
		}

		$group = $this->createMock(IGroup::class);

		$group
		 ->method('getDisplayName')
		 ->willReturn($groupName);

		$group
		 ->method('getUsers')
		 ->willReturn($users);

		return $group;
	}

	private function createUserMock($displayName)
	{
		$user = $this->createMock(IUser::class);

		$user
		 ->method('getUID')
		 ->willReturn(preg_replace('/ /', '-', $displayName));

		$user
		 ->method('getDisplayName')
		 ->willReturn($displayName);

		return $user;
	}
}
