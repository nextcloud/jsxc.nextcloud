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
	 * @var string $to The sanitized userId of the recipient of this stanza.
	 */
	public $to;

	/**
	 * @var string $from The sanitized userId of the sender of this stanza.
	 */
	public $from;

	/**
	 * @var string $to The userId (as stored in NC) of the recipient of this stanza.
	 */
	public $unSanitizedTo;

	/**
	 * @var string $from The userId (as stored in NC) of the sender of this stanza.
	 */
	public $unSanitizedFrom;

	/**
	 * @var string $stanza
	 */
	public $stanza;

	public function getUnSanitizedTo()
	{
		return $this->unSanitizedTo;
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

		$this->unSanitizedTo = $userId;
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

		$this->unSanitizedFrom = $userId;
		$this->from = Application::sanitizeUserId($userId);
		if (!is_null($host_and_or_resource)) {
			$this->from .= '@' . $host_and_or_resource;
		}
	}

	public function getUnSanitizedFrom()
	{
		return $this->unSanitizedFrom;
	}

	public function xmlSerialize(Writer $writer)
	{
		$writer->writeRaw($this->getStanza());
	}
}
