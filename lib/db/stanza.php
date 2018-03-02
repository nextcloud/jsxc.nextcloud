<?php

namespace OCA\OJSXC\Db;

use OCA\OJSXC\AppInfo\Application;
use \OCP\AppFramework\Db\Entity;
use Sabre\Xml\Reader;
use Sabre\Xml\Writer;
use Sabre\Xml\XmlDeserializable;
use Sabre\Xml\XmlSerializable;

/**
 * Class Stanza
 * @package OCA\OJSXC\Db
 * @brief this class is used as the entity which is fetched from the stanza table OR extended by a specific stanza
 * for inserting into the stanza table
 * @method string getStanza()
 * @method void setStanza($stanza)
 */
class Stanza extends Entity implements XmlSerializable
{
	public function __construct($stanza = '')
	{
		$this->setStanza($stanza);
	}

	/**
	 * @var string $to
	 */
	public $to;

	/**
	 * @var string $to
	 */
	public $from;

	/**
	 * @var string $stanza
	 */
	public $stanza;

	public function getTo()
	{
		return $this->to;
	}

	/**
	 * Sets the to user as a `user`.
	 *
	 * @see setFullTo
	 * @param $userId
	 * @param null $host_and_or_resource
	 */
	public function setTo($userId, $host_and_or_resource = null)
	{
		if (is_array($userId)) {
			// support mapFromRow
			$host_and_or_resource = $userId[1];
			$userId = $userId[0];
		}

		$this->to = Application::sanitizeUserId($userId);
		if (!is_null($host_and_or_resource)) {
			$this->to .= '@' . $host_and_or_resource;
		}
	}

	/**
	 * Sets the from user as a `user`.
	 *
	 * @see setFullFrom
	 * @param $userId
	 * @param null $host_and_or_resource
	 */
	public function setFrom($userId, $host_and_or_resource = null)
	{
		if (is_array($userId)) {
			// support mapFromRow
			$host_and_or_resource = $userId[1];
			$userId = $userId[0];
		}
		$this->from = Application::sanitizeUserId($userId);
		if (!is_null($host_and_or_resource)) {
			$this->from .= '@' . $host_and_or_resource;
		}
	}

	public function getFrom()
	{
		return $this->from;
	}

	public function xmlSerialize(Writer $writer)
	{
		$writer->writeRaw($this->getStanza());
	}
}
