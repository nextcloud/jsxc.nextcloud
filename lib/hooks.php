<?php

namespace OCA\OJSXC;

use OCA\OJSXC\Db\IQRosterPush;
use OCA\OJSXC\Db\IQRosterPushMapper;
use OCP\IUserManager;

use OCP\IUser;
use OCP\IUserSession;

class Hooks {

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

	public function __construct(IUserManager $userManager, IUserSession $userSession, $host, IQRosterPushMapper $iqRosterPushMapper){
		$this->userManager = $userManager;
		$this->userSession = $userSession;
		$this->host = $host;
		$this->iqRosterPushMapper = $iqRosterPushMapper;
	}

	public function register() {
		$this->userManager->listen('\OC\User', 'postCreateUser', [$this, 'onCreateUser']);
		$this->userManager->listen('\OC\User', 'postDelete', [$this, 'onDeleteUser']);
		$this->userSession->listen('\OC\User', 'changeUser', [$this, 'onChangeUser']);
	}

	/**
	 * @brief when a new user is created, the roster of the users must be updated,
	 * by sending a roster push.
	 * Note that this can still be useful when the roster and contacts menu are
	 * merged, for the internal state.
	 * @see https://tools.ietf.org/html/rfc6121#section-2.1.6
	 * @param IUser $user
	 * @param string $password
	 */
	public function onCreateUser(IUser $user, $password) {
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
	 * @brief when a new user is created, the roster of the users must be updated,
	 * by sending a roster push.
	 * Note that this can still be useful when the roster and contacts menu are
	 * merged, for the internal state. E.g. JSXC removes a chat window, when it
	 * receives this stanza.
	 * @see https://tools.ietf.org/html/rfc6121#section-2.1.6
	 * @param IUser $user
	 */
	public function onDeleteUser(IUser $user) {
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

	/**
	 * @brief when a use is changed, adapt the roster of the users.
	 * Note that this can still be useful when the roster and contacts menu are
	 * merged, for the internal state. E.g. JSXC removes a chat window, when it
	 * receives this stanza.
	 * @see https://tools.ietf.org/html/rfc6121#section-2.1.6
	 * @param IUser $user
	 * @param string $feature feature which was changed. Enabled and displayName are supported.
	 * @param string $value
	 */
	public function onChangeUser(IUser $user, $feature, $value) {
		if ($feature === "enabled") {
			if ($value === "true") {
				// if user is enabled, add to roster
				$this->onCreateUser($user, '');

			} else if ($value === "false") {
				// if user is enabled, remove from roster
				$this->onDeleteUser($user);
			}
		} else if ($feature === "displayName") {
			// if the user was changed, resend the whole roster item
			$this->onCreateUser($user, '');
		}
	}

}