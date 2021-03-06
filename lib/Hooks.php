<?php

namespace OCA\OJSXC;

use OCA\OJSXC\AppInfo\Application;
use OCA\OJSXC\Db\PresenceMapper;
use OCA\OJSXC\Db\StanzaMapper;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUserManager;

use OCP\IUser;
use OCP\IUserSession;

class Hooks
{

	/**
	 * @var IUserManager
	 */
	private $userManager;

	/**
	 * @var IUserSession
	 */
	private $userSession;

	/**
	 * @var PresenceMapper
	 */
	private $presenceMapper;

	/**
	 * @var StanzaMapper
	 */
	private $stanzaMapper;

	/**
	 * @var RosterPush
	 */
	private $rosterPush;

	/**
	 * @var IGroupManager
	 */
	private $groupManager;

	public function __construct(
		IUserManager $userManager,
		IUserSession $userSession,
		RosterPush $rosterPush,
		PresenceMapper $presenceMapper,
		StanzaMapper $stanzaMapper,
		IGroupManager $groupManager
	) {
		$this->userManager = $userManager;
		$this->userSession = $userSession;
		$this->rosterPush = $rosterPush;
		$this->presenceMapper = $presenceMapper;
		$this->stanzaMapper = $stanzaMapper;
		$this->groupManager = $groupManager;
	}

	public static function getInstance()
	{
		$app = new Application();
		return $app->getContainer()->query('UserHooks');
	}

	public static function register()
	{
		\OC::$server->getUserManager()->listen('\OC\User', 'postCreateUser', function (IUser $user, $password) {
			self::getInstance()->onCreateUser($user, $password);
		});

		\OC::$server->getUserManager()->listen('\OC\User', 'postDelete', function (IUser $user) {
			self::getInstance()->onDeleteUser($user);
		});

		\OC::$server->getUserSession()->listen('\OC\User', 'changeUser', function (IUser $user, $feature, $value) {
			self::getInstance()->onChangeUser($user, $feature, $value);
		});

		\OC::$server->getGroupManager()->listen('\OC\Group', 'postAddUser', function (IGroup $group, IUser $user) {
			self::getInstance()->onAddUserToGroup($group, $user);
		});

		\OC::$server->getGroupManager()->listen('\OC\Group', 'postRemoveUser', function (IGroup $group, IUser $user) {
			self::getInstance()->onRemoveUserFromGroup($group, $user);
		});
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
	public function onCreateUser(IUser $user, $password)
	{
		$this->rosterPush->createOrUpdateRosterItem($user);
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
	public function onDeleteUser(IUser $user)
	{
		$this->rosterPush->removeRosterItem($user->getUID());

		// delete the presence record of this user
		$this->presenceMapper->deletePresence($user->getUID());

		// delete all stanzas addressed to this user
		$this->stanzaMapper->deleteByTo($user->getUID());
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
	public function onChangeUser(IUser $user, $feature, $value)
	{
		if ($feature === "enabled") {
			if ($value === "true") {
				// if user is enabled, add to roster
				$this->onCreateUser($user, '');
			} elseif ($value === "false") {
				// if user is enabled, remove from roster
				$this->onDeleteUser($user);
			}
		} elseif ($feature === "displayName") {
			// if the user was changed, resend the whole roster item
			$this->onCreateUser($user, '');
		}
	}

	public function onAddUserToGroup(IGroup $group, IUser $user)
	{
		$this->rosterPush->createOrUpdateRosterItem($user);
		$this->rosterPush->addUserToGroup($user, $group);
	}

	public function onRemoveUserFromGroup(IGroup $group, IUser $user)
	{
		if (Application::contactsStoreApiSupported()) {
			$this->rosterPush->removeRosterItemForUsersInGroup($group, $user->getUID());
		}
		$this->rosterPush->removeUserFromGroup($user, $group);
	}
}
