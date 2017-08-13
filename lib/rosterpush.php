<?php

namespace OCA\OJSXC;

use OCA\OJSXC\Db\IQRosterPush;
use OCA\OJSXC\Db\IQRosterPushMapper;
use OCP\IDBConnection;
use OCP\IUserManager;

use OCP\IUser;
use OCP\IUserSession;

// TODO listen for changes to groups

class RosterPush
{

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

	/**
	 * @var IDBConnection
	 */
	private $db;

	/**
	 * @var IUserProvider
	 */
	private $userProvider;

	/**
	 * RosterPush constructor.
	 *
	 * @param IUserManager $userManager
	 * @param IUserSession $userSession
	 * @param string $host
	 * @param IQRosterPushMapper $iqRosterPushMapper
	 * @param IDBConnection $db
	 * @param IUserProvider $userProvider
	 */
	public function __construct(
		IUserManager $userManager,
								IUserSession $userSession,
		$host,
								IQRosterPushMapper $iqRosterPushMapper,
								IDbConnection $db,
								IUserProvider $userProvider
	) {
		$this->userManager = $userManager;
		$this->userSession = $userSession;
		$this->host = $host;
		$this->iqRosterPushMapper = $iqRosterPushMapper;
		$this->db = $db;
		$this->userProvider = $userProvider;
	}

	/**
	 * @see https://tools.ietf.org/html/rfc6121#section-2.1.6
	 * @param IUser $user
	 */
	public function createOrUpdateRosterItem(IUser $user)
	{
		$iq = new IQRosterPush();
		$iq->setJid($user->getUID());
		$iq->setName($user->getDisplayName());
		$iq->setSubscription('both');
		$iq->setFrom('');


		foreach ($this->userProvider->getAllUsersForUserByUID($user->getUID()) as $recipient) {
			if ($recipient->getUID() !== $user->getUID()) {
				$iq->setTo($recipient->getUID());
				$this->iqRosterPushMapper->insert($iq);
			}
		}
	}

	/**
	 * @see https://tools.ietf.org/html/rfc6121#section-2.1.6
	 * @param $userId
	 */
	public function removeRosterItem($userId)
	{
		$iq = new IQRosterPush();
		$iq->setJid($userId);
		$iq->setSubscription('remove');
		$iq->setFrom('');


		foreach ($this->userManager->search('') as $recipient) {
			if ($recipient->getUID() !== $userId) {
				$iq->setTo($recipient->getUID());
				$this->iqRosterPushMapper->insert($iq);
			}
		}
	}

	/**
	 * @brief performs a completely roster fresh of all users. This will send
	 * a rosterPush for every existing user and a rosterPush for every
	 * user which was ever deleted. The deleted user is fetched from the
	 * `addressbookchanges` table.
	 */
	public function refreshRoster()
	{
		$stats = [
			"updated" => 0,
			"removed" => 0
		];


		foreach ($this->userManager->search('') as $user) {
			$this->createOrUpdateRosterItem($user);
			$stats["updated"]++;
		}

		/**
		 * Here we look into the addressbookchanges table for deletions
		 * of "contacts" in the system addressbook. This are actual users of the
		 * Nextcloud instance. Because this is a private API of Nextcloud it's
		 * encapsulated in a try/catch block.
		 */
		try {
			$query = "SELECT `id` FROM `*PREFIX*addressbooks` WHERE `principaluri`='principals/system/system' LIMIT 1";
			$addressbooks = $this->db->executeQuery($query)->fetchAll();
			$id = $addressbooks[0]['id'];

			$query = "SELECT `uri` FROM `*PREFIX*addressbookchanges` AS ac1 WHERE `addressbookid` = ? AND `operation` = 3 AND `id`=(SELECT MAX(id) FROM `*PREFIX*addressbookchanges` AS ac2 WHERE `uri`=ac1.uri)"; // we use the subquery to always fetch the latest change

			// Fetching all changes
			$deletions = $this->db->executeQuery($query, [$id])->fetchAll();

			foreach ($deletions as $deletion) {
				$userid = $deletion['uri'];
				$colonPlace = strpos($userid, ':');
				$dotPlace = strrpos($userid, '.');
				$userid = substr($userid, $colonPlace + 1, strlen($userid) - $dotPlace - $colonPlace);
				$this->removeRosterItem($userid);
				$stats["removed"]++;
			}
		} catch (\Exception $e) {
			\OC::$server->getLogger()->logException($e);
		}

		return $stats;
	}
}
