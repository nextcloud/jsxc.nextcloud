<?php

namespace OCA\OJSXC;

use OCA\OJSXC\Db\IQRosterPush;
use OCA\OJSXC\Db\IQRosterPushMapper;
use OCA\OJSXC\Db\PresenceMapper;
use OCP\IUserManager;

use OCP\IUser;
use OCP\IUserSession;

class RosterPush {

	/**
	 * @var IUserManager
	 */
	private $userManager;

	/**
	 * @var IQRosterPushMapper
	 */
	private $iqRosterPushMapper;

	private $host;

	/**
	 * @var IUserSession
	 */
	private $userSession;

	public function __construct(IUserManager $userManager,
								IUserSession $userSession, $host,
								IQRosterPushMapper $iqRosterPushMapper) {
		$this->userManager = $userManager;
		$this->userSession = $userSession;
		$this->host = $host;
		$this->iqRosterPushMapper = $iqRosterPushMapper;
	}

	/**
	 * @see https://tools.ietf.org/html/rfc6121#section-2.1.6
	 * @param IUser $user
	 */
	public function createOrUpdateRosterItem(IUser $user) {
		$iq = new IQRosterPush();
		$iq->setJid($user->getUID());
		$iq->setName($user->getDisplayName());
		$iq->setSubscription('both');
		$iq->setFrom('');


		foreach ($this->userManager->search('') as $recipient) {
			if($recipient->getUID() !== $user->getUID()) {
				$iq->setTo($recipient->getUID());
				$this->iqRosterPushMapper->insert($iq);
			}
		}
	}

	/**
	 * @see https://tools.ietf.org/html/rfc6121#section-2.1.6
	 * @param IUser $user
	 */
	public function removeRosterItem(IUser $user) {
		$iq = new IQRosterPush();
		$iq->setJid($user->getUID());
		$iq->setName($user->getDisplayName());
		$iq->setSubscription('remove');
		$iq->setFrom('');


		foreach ($this->userManager->search('') as $recipient) {
			if($recipient->getUID() !== $user->getUID()) {
				$iq->setTo($recipient->getUID());
				$this->iqRosterPushMapper->insert($iq);
			}
		}
	}
}