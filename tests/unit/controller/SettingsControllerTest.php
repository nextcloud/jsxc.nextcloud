<?php

namespace OCA\OJSXC\Controller;

use OCA\OJSXC\Config;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;

class SettingsControllerTest extends TestCase
{
	private $request;
	private $config;
	private $userManager;
	private $userSession;
	private $settingsController;

	public function setUp()
	{
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->config = $this->createMock(Config::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->userSession = $this->createMock(IUserSession::class);

		$this->settingsController = new SettingsController(
		 'ojsxc',
		 $this->request,
		 $this->config,
		 $this->userManager,
		 $this->userSession
	  );
	}

	public function testIndexWithoutUser()
	{
		$return = $this->settingsController->index();

		$this->assertEquals('noauth', $return['result']);
	}

	public function testIndexPreferPersonalEmail()
	{
		$mapGetAppValue = [
			[Config::XMPP_SERVER_TYPE, 'internal', 'external'],
		];

		$this->config->method('getBooleanAppValue')->will($this->returnValueMap([
			[Config::XMPP_PREFER_MAIL, null, true],
			[Config::XMPP_USE_TIME_LIMITED_TOKEN, null, false]
		 ]));

		$node = 'foobar';
		$domain = 'host';

		$mapGetUserValue = [
		 ['Foo', 'settings', 'email', $node.'@'.$domain]
	  ];

		$this->setUpAuthenticatedIndex($mapGetAppValue, $mapGetUserValue);

		$return = $this->settingsController->index();

		$this->assertEquals('success', $return['result']);
		$this->assertEquals($node, $return['data']['xmpp']['node']);
		$this->assertEquals($domain, $return['data']['xmpp']['domain']);
	}

	public function testIndexTimeLimitedToken()
	{
		$mapGetAppValue = [
			[Config::XMPP_SERVER_TYPE, 'internal', 'external'],
			['xmppDomain', null, 'localhost']
		];

		$this->config->method('getBooleanAppValue')->will($this->returnValueMap([
			[Config::XMPP_USE_TIME_LIMITED_TOKEN, null, true]
		 ]));

		$this->setUpAuthenticatedIndex($mapGetAppValue);

		$return = $this->settingsController->index();

		$this->assertEquals('success', $return['result']);
		$this->assertNotEquals(null, $return['data']['xmpp']['password']);
	}

	public function testGetIceServersNoData()
	{
		$this->setUpGetIceServers();

		$return = $this->settingsController->getIceServers();

		$this->assertEquals([], $return);
	}

	public function testGetIceServersStoredDataWithPrefix()
	{
		$ttl = '1234';
		$url = 'turn:localhost';
		$username = 'foobar';
		$password = 'password';
		$this->setUpGetIceServers($url, $ttl, $username, $password, 'secret');

		$return = $this->settingsController->getIceServers();

		$this->assertEquals($ttl, $return['ttl']);
		$this->assertEquals($url, $return['iceServers'][0]['urls'][0]);
		$this->assertEquals($username, $return['iceServers'][0]['username']);
		$this->assertEquals($password, $return['iceServers'][0]['credential']);
	}

	public function testGetIceServersGeneratedToken()
	{
		$ttl = 12345;
		$this->setUpGetIceServers('turn:localhost', ''.$ttl, '', '', 'secret');

		$this->userSession
		 ->expects($this->once())
		 ->method('getUser')
		 ->willReturn($this->createUserMock('Foo'));

		$return = $this->settingsController->getIceServers();

		$this->assertEquals('12345', $return['ttl']);
		$this->assertEquals('turn:localhost', $return['iceServers'][0]['urls'][0]);

		$username = $return['iceServers'][0]['username'];
		list($validUntil, $uid) = explode(':', $username);

		$this->assertGreaterThan(time(), intval($validUntil));
		$this->assertLessThanOrEqual(time() + $ttl, intval($validUntil));
		$this->assertEquals('Foo', $uid);
		$this->assertNotEquals('password', $return['iceServers'][0]['credential']);
		$this->assertFalse(empty($return['iceServers'][0]['credential']));
	}


	public function testServerType()
	{
		$this->config
			->expects($this->at(0))
			->method('getAppValue')
			->with(Config::XMPP_SERVER_TYPE, 'internal')
			->willReturn('internal'); // default value

		$this->assertEquals($this->settingsController->getServerType(), ["serverType" => "internal"]);

		$this->config
			->expects($this->at(0))
			->method('getAppValue')
			->with(Config::XMPP_SERVER_TYPE, 'internal')
			->willReturn('external');

		$this->assertEquals($this->settingsController->getServerType(), ["serverType" => "external"]);
	}

	private function setUpAuthenticatedIndex($mapGetAppValue = [], $mapGetUserValue = [])
	{
		$mapGetParam = [
		 ['username', null, 'foo'],
		 ['password', null, 'bar']
	  ];

		$this->request->method('getParam')->will($this->returnValueMap($mapGetParam));
		$this->config->method('getAppValue')->will($this->returnValueMap($mapGetAppValue));
		$this->config->method('getUserValue')->will($this->returnValueMap($mapGetUserValue));

		$this->userManager
		 ->expects($this->once())
		 ->method('checkPassword')
		 ->with('foo', 'bar')
		 ->willReturn($this->createUserMock('Foo'));
	}

	private function setUpGetIceServers($iceUrl = '', $iceTtl = '', $iceUsername = '', $iceCredential = '', $iceSecret = '')
	{
		$mapGetAppValue = [
		 [Config::ICE_SECRET, null, $iceSecret],
		 [Config::ICE_TTL, 3600 * 24, $iceTtl],
		 [Config::ICE_URL, null, $iceUrl],
		 [Config::ICE_USERNAME, '', $iceUsername],
		 [Config::ICE_CREDENTIAL, '', $iceCredential]
	  ];

		$this->config->method('getAppValue')->will($this->returnValueMap($mapGetAppValue));
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
