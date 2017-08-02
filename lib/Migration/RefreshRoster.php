<?php

namespace OCA\OJSXC\Migration;

use OCA\OJSXC\RosterPush;
use OCP\IConfig;
use OCP\ILogger;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class RefreshRoster implements IRepairStep
{

	/**
	 * @var RosterPush
	 */
	private $rosterPush;

	/**
	 * @var IConfig
	 */
	private $config;

	/**
	 * @var ILogger
	 */
	private $logger;

	/**
	 * RefreshRoster constructor.
	 *
	 * @param RosterPush $rosterPush
	 * @param IConfig $config
	 * @param ILogger $logger
	 */
	public function __construct(RosterPush $rosterPush, IConfig $config, ILogger $logger)
	{
		$this->rosterPush = $rosterPush;
		$this->config = $config;
		$this->logger = $logger;
	}

	/**
	 * Returns the step's name
	 *
	 * @return string
	 */
	public function getName()
	{
		return "Refresh the roster of all users when the app has been installed before.";
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
		/**
	     * We want only to refresh the rosters if this app was installed before,
	     * since only then the rosters can be outdated.
	     */
		if ($this->config->getAppValue('ojsxc', 'installed_version', null) !== null
			&& $this->config->getAppValue('ojsxc', 'serverType', 'internal') === 'internal') {
			$stats = $this->rosterPush->refreshRoster();
			$output->info("Updated " . $stats["updated"] . " roster items");
			$this->logger->info("Updated " . $stats["updated"] . " roster items", ["app" => "OJSXC"]);
			$output->info("Removed " . $stats["removed"] . " roster items");
			$this->logger->info("Removed " . $stats["removed"] . " roster items", ["app" => "OJSXC"]);
		}
	}
}
