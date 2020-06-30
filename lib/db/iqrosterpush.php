<?php

namespace OCA\OJSXC\Db;

use OCA\OJSXC\AppInfo\Application;
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
 * @method void setName($name)
 * @method void setSubscription($subscription)
 * @method string getJid()
 * @method string getName()
 * @method string getSubscription()
 */
class IQRosterPush extends Stanza implements XmlSerializable
{

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

	/**
	 * Sets the to user as a `user`.
	 *
	 * @see setFullJid
	 * @param $userId
	 * @param null $host_and_or_resource
	 */
	public function setJid($userId, $host_and_or_resource = null)
	{
		$this->jid = Application::sanitizeUserId($userId);
		if (!is_null($host_and_or_resource)) {
			$this->jid .= '@' . $host_and_or_resource;
		}
	}

	public function xmlSerialize(Writer $writer)
	{
		$item = [
			"name" => "item",
			"attributes" => [
				"jid" => $this->jid,
				"subscription" => $this->subscription
			],
			"value" => ''
		];

		if ($this->name !== null) {
			$item['attributes']['name'] = $this->name;
		}

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
					'value' => $item,
				]]
			]
		]);
	}
}
