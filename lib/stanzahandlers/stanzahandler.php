<?php
namespace OCA\OJSXC\StanzaHandlers;

use Sabre\Xml\Reader;
use Sabre\Xml\Writer;

/**
 * Class StanzaHandler
 *
 * @package OCA\OJSXC\StanzaHandlers
 */
class StanzaHandler
{

	/**
	 * @var string $userId
	 */
	protected $userId;

	/**
	 * @var string $host
	 */
	protected $host;

	/**
	 * @var string $to
	 */
	protected $to;

	/**
	 * StanzaHandler constructor.
	 *
	 * @param string 1$userId
	 * @param string $host
	 */
	public function __construct($userId, $host)
	{
		$this->userId = $userId;
		$this->host = $host;
		$this->from = $this->userId;
	}

	/**
	 * @brief Gets an attribute $attr from $stanza, returns null if it doens't
	 * exists.
	 * @param $stanza
	 * @param $attr
	 * @return null|string
	 */
	protected function getAttribute($stanza, $attr)
	{
		return isset($stanza['attributes'][$attr]) ? (string) $stanza['attributes'][$attr] : null;
	}
}
