<?php

namespace OCA\OJSXC\Migration;

use OCA\OJSXC\Config;
use OCA\OJSXC\Migration\MigrateConfig;
use OCP\Migration\IOutput;
use PHPUnit\Framework\TestCase;

class MigrateConfigTest extends TestCase
{
	private $migrateConfig;
	private $config;
	private $output;

	public function setUp(): void
	{
		parent::setUp();

		$this->config = $this->createMock(Config::class);
		$this->output = $this->createMock(IOutput::class);

		$this->migrateConfig = new MigrateConfig($this->config);
	}

	public function testNewInstallation()
	{
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('installed_version', null)
			->willReturn(null);

		$this->output
			->expects($this->never())
			->method('info');

		$this->output
			->expects($this->never())
			->method('startProgress');

		$this->migrateConfig->run($this->output);
	}

	public function testVersion4()
	{
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('installed_version', null)
			->willReturn('4.0.0');

		$this->output
			->expects($this->once())
			->method('info');

		$this->output
			->expects($this->never())
			->method('startProgress');

		$this->migrateConfig->run($this->output);
	}

	public function testVersion3()
	{
		$mapping = [
			'serverType' => Config::XMPP_SERVER_TYPE,
			'boshUrl' => Config::XMPP_URL,
			'xmppDomain' => Config::XMPP_DOMAIN,
			'xmppPreferMail' => Config::XMPP_PREFER_MAIL,
			'xmppResource' => Config::XMPP_RESOURCE,
			'xmppOverwrite' => Config::XMPP_ALLOW_OVERWRITE,
			'xmppStartMinimized' => Config::XMPP_START_MINIMIZED,
			'loginFormEnable' => Config::XMPP_START_ON_LOGIN,
			'iceUrl' => Config::ICE_URL,
			'iceUsername' => Config::ICE_USERNAME,
			'iceCredential' => Config::ICE_CREDENTIAL,
			'iceSecret' => Config::ICE_SECRET,
			'iceTtl' => Config::ICE_TTL,
			'timeLimitedToken' => Config::XMPP_USE_TIME_LIMITED_TOKEN,
			'managedServer' => Config::MANAGED_SERVER_STATUS,
		];

		$getAppValueMap = [
			['installed_version', null, '3.99.0'],
		];

		$setAppValueMap = [];

		foreach ($mapping as $old => $new) {
			$value = 'foo-' . $old;

			$getAppValueMap[] = [$old, null, $value];
			$getAppValueMap[] = [$new, null, null];

			$setAppValueMap[] = [$new, $value];
		}

		$this->config
			->expects($this->exactly(count($getAppValueMap)))
			->method('getAppValue')
			->will($this->returnValueMap($getAppValueMap));

		$this->config
			->expects($this->exactly(count($setAppValueMap)))
			->method('setAppValue')
			->will($this->returnValueMap($setAppValueMap));

		$this->migrateConfig->run($this->output);
	}
}
