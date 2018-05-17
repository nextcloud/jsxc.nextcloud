<?php

namespace OCA\OJSXC\Command;

use OCA\OJSXC\Db\PresenceMapper;
use OCA\OJSXC\RosterPush;
use OCP\IUserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RefreshRoster extends Command
{

	/**
	 * @var IUserManager
	 */
	private $userManager;

	/**
	 * @var RosterPush
	 */
	private $rosterPush;

	/**
	 * @var PresenceMapper
	 */
	private $presenceMapper;

	public function __construct(
		IUserManager $userManager,
								RosterPush $rosterPush,
	PresenceMapper $presenceMapper
	) {
		parent::__construct();
		$this->userManager = $userManager;
		$this->rosterPush = $rosterPush;
		$this->presenceMapper = $presenceMapper;
	}

	protected function configure()
	{
		$this->setName('ojsxc:refresh-roster');
		$this->setDescription('Refresh the roster of all users');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$stats = $this->rosterPush->refreshRoster();
		$output->writeln("Updated " . $stats["updated"] . " roster items");
		$output->writeln("Removed " . $stats["removed"] . " roster items");
	}
}
