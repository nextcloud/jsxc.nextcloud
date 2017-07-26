<?php

namespace OCA\OJSXC\Db;

use Sabre\Xml\Reader;
use Sabre\Xml\Writer;
use Sabre\Xml\XmlDeserializable;
use Sabre\Xml\XmlSerializable;

/**
 * This is an entity used by the IqHandler, but not stored/mapped in the database.
 * Class IQRoster
 *
 * @package OCA\OJSXC\Db
 * @method void setType($type)
 * @method void setQid($qid)
 * @method void setItems(array $items)
 * @method string getType()
 * @method string getQid()
 * @method array getItems()
 */
class IQRoster extends Stanza implements XmlSerializable
{

	/**
	 * @var string $type
	 */
	public $type;

	/**
	 * @var string $qid
	 */
	public $qid;

	/**
	 * @var array $items
	 */
	public $items;

	public function xmlSerialize(Writer $writer)
	{
		$writer->write([
			[
				'name' => 'iq',
				'attributes' => [
					'to' => $this->to,
					'type' => $this->type,
					'id' => $this->qid
				],
				'value' => [[
					'name' => 'query',
					'attributes' => [
						'xmlns' => 'jabber:iq:roster',
					],
					'value' => $this->items
				]]
			]
		]);
	}

	/**
	 * @param string $jid
	 * @param string $name
	 */
	public function addItem($jid, $name)
	{
		$this->items[] = [
			"name" => "item",
			"attributes" => [
				"jid" => $jid,
				"name" => $name,
				"subscription" => "both"
			],
			"value" => ''
		];
	}
}
