<?php
namespace OCA\OJSXC\StanzaHandlers;

use Sabre\Xml\Reader;
use Sabre\Xml\Writer;

/**
 * Class StanzaHandler
 *
 * @package OCA\OJSXC\StanzaHandlers
 */
abstract class StanzaHandler
{

	/**
	 * @var string $userId
	 */
	protected $userId;

	/**
	 * @var string $to
	 */
	protected $to;

	/**
	 * StanzaHandler constructor.
	 *
	 * @param string 1$userId
	 */
	public function __construct($userId)
	{
		$this->userId = $userId;
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
