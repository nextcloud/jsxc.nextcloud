<?php

namespace OCA\OJSXC\Db;

use OCA\OJSXC\Db\Presence as PresenceEntity;
use OCA\OJSXC\NewContentContainer;
use OCP\AppFramework\Db\Mapper;
use OCP\IDBConnection;
use Sabre\Xml\Service;
use OCP\IDb;

/**
 * Class PresenceMapper
 *
 * @package OCA\OJSXC\Db
 */
class PresenceMapper extends Mapper {

	/**
	 * @var bool this value indicates if we already have updated the presence
	 * of other users so we don't do this more than 1 one times per request.
	 * TODO We could introduce a variable in the DB which indicates this already
	 * TODO happened x minutes ago so we shouldn't do this every request.
	 */
	private static $updatedPresense = false;

	/**
	 * @var array of userid's which are connected.
	 */
	private static $connectedUsers = [];

	/**
	 * @var bool indicates wherever we already fetched the connected users
	 */
	private static $fetchedConnectedUsers = false;

	/**
	 * @var MessageMapper $messageMapper
	 */
	private $messageMapper;

	/**
	 * @var NewContentContainer $newContentContainer
	 */
	private $newContentContainer;

	/**
	 * @var int $timeout
	 */
	private $timeout;

	/**
	 * PresenceMapper constructor.
	 *
	 * @param IDBConnection $db
	 * @param string $host
	 * @param null|string $userId
	 * @param MessageMapper $messageMapper
	 * @param NewContentContainer $newContentContainer
	 * @param int $timeout
	 */
	public function __construct(IDBConnection $db, $host, $userId, MessageMapper $messageMapper, NewContentContainer $newContentContainer, $timeout) {
		parent::__construct($db, 'ojsxc_presence');
		$this->host = $host;
		$this->userId = $userId;
		$this->messageMapper = $messageMapper;
		$this->newContentContainer = $newContentContainer;
		$this->timeout = $timeout;

		$this->updatePresence();
	}

	/**
	 * @brief This function sets or update the presence of a user.
	 * @param PresenceEntity $stanza
	 */
	public function setPresence(PresenceEntity $stanza) {
		$sql = "UPDATE `*PREFIX*ojsxc_presence` SET `presence`=?, `last_active`=? WHERE `userid` = ?";

		$q = $this->db->prepare($sql);
		$q->execute([$stanza->getPresence(), $stanza->getLastActive(), $stanza->getUserid()]);


		if ($q->rowCount() === 0) {
			$sql = "INSERT INTO `*PREFIX*ojsxc_presence` (`userid`, `presence`, `last_active`) VALUES(?,?,?)";
			$q = $this->db->prepare($sql);
			$q->execute([$stanza->getUserid(), $stanza->getPresence(), $stanza->getLastActive()]);
		}
	}

	/**
	 * @brief this function will fetch all the presences of users except
	 * the current user.
	 * @return array
	 */
	public function getPresences() {
		$stmt = $this->execute("SELECT * FROM `*PREFIX*ojsxc_presence` WHERE `userid` != ?", [$this->userId]);
		$results = [];
		while($row = $stmt->fetch()){
			$row['from'] = $row['userid'] . '@' . $this->host;
			$row['to'] = $this->userId . '@' . $this->host;
			$results[] = $this->mapRowToEntity($row);
		}
		$stmt->closeCursor();

		return $results;
	}

	/**
	 * @brief fetch the users who are connected with the server.
	 *  - online
	 *  - chatty
	 *  - away
	 *  - extended away
	 *  - do not disturb
	 * and return it as an array of the userids.
	 * @return array
	 */
	public function getConnectedUsers() {
		if (!self::$fetchedConnectedUsers) {
			self::$fetchedConnectedUsers = true;

			$stmt = $this->execute("SELECT `userid` FROM `*PREFIX*ojsxc_presence` WHERE `presence` != 'unavailable' AND `userid` != ?", [$this->userId]);
			$results = [];
			while ($row = $stmt->fetch()) {
				$results[] = $row['userid'];
			}
			$stmt->closeCursor();

			self::$connectedUsers = $results;
			return $results;
		} else {
			return self::$connectedUsers;
		}
	}

	/**
	 * @brief updates the last_active label in the DB.
	 * @param the user to update the last_active field
	 */
	public function setActive($user) {
		// just do an update since we can assume the user is already online
		// otherwise this wouldn't make sense
		$sql = "UPDATE `*PREFIX*ojsxc_presence` SET `last_active`=? WHERE `userid` = ?";
		$q = $this->db->prepare($sql);
		$q->execute([time(), $user]);
	}


	/**
	 * @brief this function will update the presence of users who doesn't
	 * contacted the server for $this->timeout seconds.
	 */
	public function updatePresence() {
		if (!self::$updatedPresense) {
			self::$updatedPresense = true;

			$time = time() - $this->timeout;

			// first find all users who where offline for more than 30 seconds TOOD
			$stmt = $this->execute("SELECT `userid` FROM `*PREFIX*ojsxc_presence` WHERE `presence` != 'unavailable' AND `userid` != ? AND `last_active` < ?",
				[$this->userId, $time]);

			$inactiveUsers = [];
			while ($row = $stmt->fetch()) {
				$inactiveUsers[] = $row['userid'];
			}
			$stmt->closeCursor();

			$this->execute("UPDATE `*PREFIX*ojsxc_presence` SET `presence` = 'unavailable' WHERE `presence` != 'unavailable' AND `userid` != ? AND `last_active` < ?", [$this->userId, $time]);

			// broadcast the new presence
			$connectedUsers = $this->getConnectedUsers();


			$onlineUsers = array_diff($connectedUsers, $inactiveUsers); // filter out the inactive users, since we use a cache mechanism

			$presenceToSend = new PresenceEntity();
			$presenceToSend->setPresence('unavailable');
			foreach ($inactiveUsers as $inactiveUser) {
				$presenceToSend->setFrom($inactiveUser);
				foreach ($onlineUsers as $user) {
					$presenceToSend->setTo($user);
					$this->messageMapper->insert($presenceToSend);
				}
				$presenceToSend->setTo($this->userId . '@' . $this->host);
				$presenceToSend->setFrom($inactiveUser . '@' . $this->host);
				$this->newContentContainer->addStanza($presenceToSend);
			}

		}
	}

}