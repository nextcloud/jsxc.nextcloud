<?php

namespace OCA\OJSXC\Db;

use Sabre\Xml\Reader;
use Sabre\Xml\Writer;
use Sabre\Xml\LibXMLException;
use Sabre\Xml\ParseException;
use OCA\OJSXC\Utility\TestCase;

class PresenceTest extends TestCase
{
	private function generateFactoryData($xml, $from, $to, $presence, $userId)
	{
		$reader = new Reader();
		$reader->xml($xml);
		$reader->elementMap = [
			'{jabber:client}presence' => function (Reader $reader) use ($userId) {
				return Presence::createFromXml($reader, $userId);
			}
		];

		$expected = new Presence();
		$expected->setFrom($from);
		$expected->setTo($to);
		$expected->setPresence($presence);
		$expected->setUserid($userId);

		return [
			$reader,
			$userId,
			$expected
		];
	}

	public function factoryProvider()
	{
		return [
			$this->generateFactoryData("<presence xmlns='jabber:client' type='unavailable'/>", null, null, 'unavailable', 'admin'),
			$this->generateFactoryData("<presence xmlns='jabber:client' type='unavailable'></presence>", null, null, 'unavailable', 'admin'),
			$this->generateFactoryData("<presence xmlns='jabber:client'/>", null, null, 'online', 'admin'),
			$this->generateFactoryData("<presence xmlns='jabber:client'><test>ack</test></presence>", null, null, 'online', 'admin'),
			$this->generateFactoryData("<presence xmlns='jabber:client'></presence>", null, null, 'online', 'admin'),
			$this->generateFactoryData("<presence xmlns='jabber:client'><show>chat</show></presence>", null, null, 'chat', 'admin'),
			$this->generateFactoryData("<presence xmlns='jabber:client'><show>dnd</show></presence>", null, null, 'dnd', 'admin'),
			$this->generateFactoryData("<presence xmlns='jabber:client'><show>ea</show></presence>", null, null, 'ea', 'admin'),
			$this->generateFactoryData("<presence xmlns='jabber:client'><show>away</show></presence>", null, null, 'away', 'admin'),
			$this->generateFactoryData("<presence xmlns='jabber:client'><show>online</show></presence>", null, null, 'online', 'admin'),
		];
	}


	/**
	 * @dataProvider factoryProvider
	 */
	public function testFactory($reader, $userid, Presence $expectedElement)
	{
		$result = $reader->parse();
		$result = $result['value'];
		$this->assertEquals($expectedElement->getTo(), $result->getTo());
		$this->assertEquals($expectedElement->getFrom(), $result->getFrom());
		$this->assertEquals($expectedElement->getPresence(), $result->getPresence());
		$this->assertEquals($expectedElement->getUserid(), $result->getUserid());
	}


	private function generateSerializeData($to, $from, $presence, $expected)
	{
		$writer = new Writer();
		$writer->openMemory();
		$writer->startElement('body');
		$writer->writeAttribute('xmlns', 'http://jabber.org/protocol/httpbind');

		$presenceEntity = new Presence();
		$presenceEntity->setPresence($presence);
		$presenceEntity->setFrom($from);
		$presenceEntity->setTo($to);

		return [
			$writer,
			$presenceEntity,
			$expected,
			$to,
			$from,
			$presence
		];
	}
	public function serializeProvider()
	{
		return [
			$this->generateSerializeData('admin@own.dev', 'derp@own.dev', 'chat', '<body xmlns="http://jabber.org/protocol/httpbind"><presence from="derp@own.dev" to="admin@own.dev" xmlns="jabber:client"><show>chat</show></presence></body>'),
			$this->generateSerializeData('admin@own.dev', 'derp@own.dev', 'online', '<body xmlns="http://jabber.org/protocol/httpbind"><presence from="derp@own.dev" to="admin@own.dev" xmlns="jabber:client" /></body>'),
			$this->generateSerializeData('admin@own.dev', 'derp@own.dev', 'away', '<body xmlns="http://jabber.org/protocol/httpbind"><presence from="derp@own.dev" to="admin@own.dev" xmlns="jabber:client"><show>away</show></presence></body>'),
			$this->generateSerializeData('admin@own.dev', 'derp@own.dev', 'unavailable', '<body xmlns="http://jabber.org/protocol/httpbind"><presence from="derp@own.dev" to="admin@own.dev" xmlns="jabber:client" type="unavailable"/></body>'),
			$this->generateSerializeData('admin@own.dev', 'derp@own.dev', 'ea', '<body xmlns="http://jabber.org/protocol/httpbind"><presence from="derp@own.dev" to="admin@own.dev" xmlns="jabber:client"><show>ea</show></presence></body>'),
			$this->generateSerializeData('admin@own.dev', 'derp@own.dev', 'dnd', '<body xmlns="http://jabber.org/protocol/httpbind"><presence from="derp@own.dev" to="admin@own.dev" xmlns="jabber:client"><show>dnd</show></presence></body>'),

		];
	}

	/**
	 * @dataProvider serializeProvider
	 */
	public function testSerialize(Writer $writer, Presence $presenceEntity, $expected, $to, $from, $presence)
	{
		$writer->write($presenceEntity);
		$writer->endElement();
		$result = $writer->outputMemory();

		$this->assertEquals($to, $presenceEntity->getTo());
		$this->assertEquals($from, $presenceEntity->getFrom());
		$this->assertEquals($presence, $presenceEntity->getPresence());
		$this->assertSabreXmlEqualsXml($expected, $result);
	}
}
