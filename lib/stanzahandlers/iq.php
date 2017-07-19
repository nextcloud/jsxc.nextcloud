<?php

namespace OCA\OJSXC\StanzaHandlers;

use OCA\OJSXC\Db\IQRoster;
use OCP\IUserManager;
use Sabre\Xml\Reader;
use Sabre\Xml\Writer;

/**
 * Class IQ
 *
 * @package OCA\OJSXC\StanzaHandlers
 */
class IQ extends StanzaHandler {

	/**
	 * @var IUserManager
	 */
	private $userManager;

	/**
	 * IQ constructor.
	 *
	 * @param string $userId
	 * @param string $host
	 * @param IUserManager $userManager
	 */
	public function __construct($userId, $host, IUserManager $userManager) {
		parent::__construct($userId, $host);
		$this->userManager = $userManager;
	}


	/**
	 * @param array $stanza
	 * @return IQRoster
	 */
	public function handle(array $stanza) {
		$this->to = $this->getAttribute($stanza, 'to');

		if ($stanza['value'][0]['name'] === '{jabber:iq:roster}query') {
			$id = $stanza['attributes']['id'];
			$iqRoster = new IQRoster();
			$iqRoster->setType('result');
			$iqRoster->setTo($this->from);
			$iqRoster->setQid($id);
			foreach ($this->userManager->search('') as $user) {
				if($user->getUID() !== $this->userId) {
					$iqRoster->addItem($user->getUID() . '@' . $this->host, $user->getDisplayName());
				}
			}
			return $iqRoster;
		}

	}

}