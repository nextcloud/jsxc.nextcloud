<?php
namespace OCA\OJSXC\Tests\Integration\Db;

use Sabre\Xml\Writer;
use OCA\OJSXC\Tests\Utility\TestCase;
use OCA\OJSXC\Db\IQRosterPush;

class IqRosterPushTest extends TestCase
{
	public function testIqRoster()
	{
		$writer = new Writer();
		$writer->openMemory();
		$writer->startElement('body');
		$writer->writeAttribute('xmlns', 'http://jabber.org/protocol/httpbind');

		$iqRosterPush = new IQRosterPush();
		$iqRosterPush->setAttrId('4');
		$iqRosterPush->setJid('john', 'localhost');
		$iqRosterPush->setTo('jan', 'localhost');
		$iqRosterPush->setName('john');
		$iqRosterPush->setSubscription('both');

		$this->assertEquals('john@localhost', $iqRosterPush->getJid());
		$this->assertEquals('jan', $iqRosterPush->getUnSanitizedTo());
		$this->assertEquals('jan@localhost', $iqRosterPush->to);
		$this->assertEquals('john', $iqRosterPush->getName());
		$this->assertEquals('both', $iqRosterPush->getSubscription());

		$writer->write($iqRosterPush); // needed to test the xmlSerialize function

		$writer->endElement();
		$result = $writer->outputMemory();

		$expected = '<body xmlns="http://jabber.org/protocol/httpbind"><iq to="jan@localhost" type="set" id="4"><query xmlns="jabber:iq:roster"><item jid="john@localhost" name="john" subscription="both"></item></query></iq></body>';

		$this->assertXmlStringEqualsXmlString($expected, $result);
	}
}
