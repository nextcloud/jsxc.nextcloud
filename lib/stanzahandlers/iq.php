<?php

namespace OCA\OJSXC\StanzaHandlers;

use OCA\OJSXC\Db\IQRoster;
use OCA\OJSXC\IUserProvider;
use OCP\IConfig;
use OCP\IUserManager;
use Sabre\Xml\Reader;
use Sabre\Xml\Writer;
use OCA\OJSXC\AppInfo\Application;

/**
 * Class IQ
 *
 * @package OCA\OJSXC\StanzaHandlers
 */
class IQ extends StanzaHandler
{

	/**
	 * @var IUserManager
	 */
	private $userManager;

	/**
	 * @var IConfig
	 */
	private $config;

	/**
	 * @var IUserProvider
	 */
	private $userProvider;

	/**
	 * IQ constructor.
	 *
	 * @param string $userId
	 * @param string $host
	 * @param IUserManager $userManager
	 * @param IConfig $config
	 */
	public function __construct($userId, $host, IUserManager $userManager, IConfig $config, IUserProvider $userProvider)
	{
		parent::__construct($userId, $host);
		$this->userManager = $userManager;
		$this->config = $config;
		$this->userProvider = $userProvider;
	}


	/**
	 * @param array $stanza
	 * @return IQRoster
	 */
	public function handle(array $stanza)
	{
		$this->to = $this->getAttribute($stanza, 'to');

		// if in debug mode we show the own username in the roster for testing
		$debugMode = $this->config->getSystemValue("debug");

		if ($stanza['value'][0]['name'] === '{jabber:iq:roster}query') {
			$id = $stanza['attributes']['id'];
			$iqRoster = new IQRoster();
			$iqRoster->setType('result');
			$iqRoster->setTo($this->userId);
			$iqRoster->setQid($id);
			//$userId = Application::santizeUserId($user->getUID()); TODO
//				if ($debugMode || ($userId !== $this->userId && $user->isEnabled())) { // TODO
			foreach ($this->userProvider->getAllUsers() as $user) {
				if ($debugMode || (strtolower($user->getUID()) !== $this->userId)) {
					$iqRoster->addItem($user->getUID() . '@' . $this->host, $user->getFullName());
				}
			}
			return $iqRoster;
		}
	}
}
