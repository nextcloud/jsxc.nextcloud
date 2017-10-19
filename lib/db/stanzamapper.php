<?php

namespace OCA\OJSXC\Db;

use OCA\OJSXC\StanzaLogger;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\Mapper;
use OCP\IDb;
use OCP\IDBConnection;
use Sabre\Xml\Writer;

/**
 * Class StanzaMapper
 *
 * @package OCA\OJSXC\Db
 */
class StanzaMapper extends Mapper
{
	private $host;

	/**
	 * @var StanzaLogger
	 */
	private $stanzaLogger;

	/**
	 * StanzaMapper constructor.
	 *
	 * @param IDBConnection $db
	 * @param string $host
	 */
	public function __construct(IDBConnection $db, $host, StanzaLogger $stanzaLogger)
	{
		parent::__construct($db, 'ojsxc_stanzas');
		$this->host = $host;
		$this->stanzaLogger = $stanzaLogger;
	}

	/**
	 * @param Entity $entity
	 * @return void
	 */
	public function insert(Entity $entity)
	{
		$writer = new Writer();
		$writer->openMemory();
		$writer->write($entity);
		$xml = $writer->outputMemory();

		$this->stanzaLogger->logRaw($xml, StanzaLogger::STORING);

		$sql = "INSERT INTO `*PREFIX*ojsxc_stanzas` (`to`, `from`, `stanza`) VALUES(?,?,?)";
		$q = $this->db->prepare($sql);
		$q->execute([$entity->getTo(), $entity->getFrom(), $xml]);
	}


	/**
	 * @param string $to
	 * @return Stanza[]
	 * @throws DoesNotExistException
	 */
	public function findByTo($to)
	{
		$stmt = $this->execute("SELECT stanza, id FROM *PREFIX*ojsxc_stanzas WHERE `to`=?", [$to]);
		$results = [];
		while ($row = $stmt->fetch()) {
			$row['stanza'] = preg_replace('/to="([^"]*)"/', "to=\"$1@" .$this->host ."/internal\"", $row['stanza']);
			$row['stanza'] = preg_replace('/from="([^"]*)"/', "from=\"$1@" .$this->host ."/internal\"", $row['stanza']);
			$row['stanza'] = preg_replace('/jid="([^"]*)"/', "jid=\"$1@" .$this->host ."\"", $row['stanza']);
			$results[] = $this->mapRowToEntity($row);
		}
		$stmt->closeCursor();

		if (count($results) === 0) {
			throw new DoesNotExistException('Not Found');
		}

		foreach ($results as $result) {
			$this->delete($result);
		}

		return $results;
	}

	/**
	 * @brief Deletes all stanzas addressed to a user.
	 * @param $uid
	 */
	public function deleteByTo($uid)
	{
		$this->execute("DELETE FROM *PREFIX*ojsxc_stanzas WHERE `to`=?", [$uid]);
	}
}
