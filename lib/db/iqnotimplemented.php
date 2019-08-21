<?php

namespace OCA\OJSXC\Db;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

/**
 * This is an entity used by the IqHandler, but not stored/mapped in the database.
 * Class IQRoster
 *
 * @package OCA\OJSXC\Db
 * @method void setQid($qid)
 * @method string getQid()
 */
class IQNotImplemented extends Stanza implements XmlSerializable
{

	/**
	 * @var string $qid
	 */
	public $qid;

	public function xmlSerialize(Writer $writer)
	{
		$writer->write([
			[
				'name' => 'iq',
				'attributes' => [
					'type' => 'error',
					'id' => $this->qid
				],
				'value' => [[
					'name' => 'error',
					'attributes' => [
						'type' => 'cancel',
					],
					'value' => [
						'name' => 'feature-not-implemented',
						'attributes' => [
							'xmlns' => 'urn:ietf:params:xml:ns:xmpp-stanzas'
						]
					]
				]]
			]
		]);
	}
}
