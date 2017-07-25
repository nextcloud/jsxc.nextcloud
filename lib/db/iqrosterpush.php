<?php

namespace OCA\OJSXC\Db;

use Sabre\Xml\Reader;
use Sabre\Xml\Writer;
use Sabre\Xml\XmlDeserializable;
use Sabre\Xml\XmlSerializable;


/**
 * This entity represents a roster push.
 * @see https://tools.ietf.org/html/rfc6121#section-2.1.6
 * Class IQRosterPush
 *
 * @package OCA\OJSXC\Db
 * @method void setJid($jid)
 * @method void setName($name)
 * @method void setSubscription($subscription)
 * @method string getJid()
 * @method string getName()
 * @method string getSubscription()
 */
class IQRosterPush extends Stanza implements XmlSerializable{

	/**
	 * @var string jid of the user, when inserting this into the DB, only userid
	 * is needed.
	 */
	public $jid;

	/**
	 * @var string displayname of the user
	 */
	public $name;

	/**
	 * @var string subscription type. Both and remove are used.
	 */
	public $subscription;

	public function xmlSerialize(Writer $writer) {
		$writer->write([
			[
				'name' => 'iq',
				'attributes' => [
					'to' => $this->to,
					'type' => 'set',
					'id' => uniqid()
				],
				'value' => [[
					'name' => 'query',
					'attributes' => [
						'xmlns' => 'jabber:iq:roster',
					],
					'value' => [
						"name" => "item",
						"attributes" => [
							"jid" => $this->jid,
							"name" => $this->name,
							"subscription" => $this->subscription
						],
						"value" => ''
					]
				]]
			]
		]);
	}

}