<?php

namespace OCA\OJSXC\StanzaHandlers;

use OCA\OJSXC\Db\IQRoster;
use OCA\OJSXC\Exceptions\TerminateException;
use OCA\OJSXC\IUserProvider;
use OCA\OJSXC\NewContentContainer;
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
	 * @param IUserProvider $userProvider
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
	 * @throws TerminateException
	 */
	public function handle(array $stanza)
	{
		$this->to = $this->getAttribute($stanza, 'to');

		// if in debug mode we show the own username in the roster for testing
		$debugMode = $this->config->getSystemValue("debug");

		if ($stanza['value'][0]['name'] === '{http://jabber.org/protocol/disco#items}query' || $stanza['value'][0]['name'] === '{http://jabber.org/protocol/disco#info}query') {
			// the disco queries are currently not implemented but these are the first stanzas send to the server so
			// they are ideal to terminate the connection if a user is excluded from chatting.
			if ($this->userProvider->isUserExcluded($this->userId)) {
				throw new TerminateException();
			}
		} elseif ($stanza['value'][0]['name'] === '{jabber:iq:roster}query') {
			$id = $stanza['attributes']['id'];
			$iqRoster = new IQRoster();
			$iqRoster->setType('result');
			$iqRoster->setTo($this->userId);
			$iqRoster->setQid($id);
			foreach ($this->userProvider->getAllUsers() as $user) {
				if ($debugMode || $user->getUID() !== $this->userId) {
					$iqRoster->addItem($user->getUID() . '@' . $this->host, $user->getFullName());
				}
			}
			return $iqRoster;
		}
	}
}
