<?php

namespace OCA\OJSXC\Migration;

use OCA\OJSXC\Config;
use OCP\ILogger;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class MigrateConfig implements IRepairStep
{
	/**
	 * @var Config
	 */
	private $config;

	/**
	 * @param Config $config
	 * @param ILogger $logger
	 */
	public function __construct(Config $config)
	{
		$this->config = $config;
	}

	/**
	 * Returns the step's name
	 *
	 * @return string
	 */
	public function getName()
	{
		return "Migrate config of OJSXC from 3.x to 4.0";
	}

	/**
	 * Run repair step.
	 * Must throw exception on error.
	 *
	 * @param IOutput $output
	 * @throws \Exception in case of failure
	 */
	public function run(IOutput $output)
	{
		$installedVersion = $this->config->getAppValue('installed_version', null);

		if ($installedVersion === null) {
			return;
		}

		if (!preg_match('/^[1-3]\./', $installedVersion)) {
			$output->info("Skip migration, because installed version ($installedVersion) is not affected.");

			return;
		}

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

		$output->startProgress(count($mapping));

		foreach ($mapping as $old => $new) {
			$value = $this->config->getAppValue($old);

			if ($value !== null && $this->config->getAppValue($new) === null) {
				$this->config->setAppValue($new, $value);
			}

			$this->config->deleteAppValue($old);

			$output->advance(1);
		}

		$output->finishProgress();
	}
}
