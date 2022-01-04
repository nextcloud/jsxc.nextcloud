<?php

namespace OCA\OJSXC\Migration;

use OCA\OJSXC\Config;
use OCP\ILogger;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use OCP\Security\ISecureRandom;

class InitApiSecret implements IRepairStep
{
	/**
	 * @var Config
	 */
	private $config;

	/**
	 * @var ISecureRandom
	 */
	private $secureRandom;

	/**
	 * @param Config $config
	 * @param ILogger $logger
	 */
	public function __construct(Config $config, ISecureRandom $secureRandom)
	{
		$this->config = $config;
		$this->secureRandom = $secureRandom;
	}

	/**
	 * Returns the step's name
	 *
	 * @return string
	 */
	public function getName()
	{
		return "Initialize API secret";
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
		$apiSecret = $this->config->getAppValue(Config::API_SECRET);

		if (!$apiSecret) {
			$apiSecret = $this->secureRandom->generate(23);

			$this->config->setAppValue(Config::API_SECRET, $apiSecret);

			$output->info("API secret initialized");
		}
	}
}
