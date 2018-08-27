<?php
namespace OCA\OJSXC\Db;

use Sabre\Xml\Writer;
use OCA\OJSXC\Utility\TestCase;

class IqRosterTest extends TestCase
{
	public function testIqRoster()
	{
		$expected = '<body xmlns="http://jabber.org/protocol/httpbind"><iq to="john@localhost" type="result" id="4434"><query xmlns="jabber:iq:roster"><item jid="test@test.be" name="Test Test" subscription="both"></item><item jid="test2@test.be" name="Test2 Test" subscription="both"></item></query></iq></body>';

		$writer = new Writer();
		$writer->openMemory();
		$writer->startElement('body');
		$writer->writeAttribute('xmlns', 'http://jabber.org/protocol/httpbind');

		$iqRoster = new IQRoster();
		$iqRoster->setType('result');
		$iqRoster->setTo('john', 'localhost');
		$iqRoster->setQid(4434);
		$iqRoster->addItem('test@test.be', 'Test Test');
		$iqRoster->addItem('test2@test.be', 'Test2 Test');

		$this->assertEquals('result', $iqRoster->getType());
		$this->assertEquals('john@localhost', $iqRoster->getUnSanitizedTo());
		$this->assertEquals(4434, $iqRoster->getQid());
		$this->assertEquals([
			[
				"name" => "item",
				"attributes" => [
					"jid" => "test@test.be",
					"name" => "Test Test",
					"subscription" => "both"
				],
				"value" => ''
			],
			[
				"name" => "item",
				"attributes" => [
					"jid" => "test2@test.be",
					"name" => "Test2 Test",
					"subscription" => "both"
				],
				"value" => ''
			],
		], $iqRoster->getItems());

		$writer->write($iqRoster); // needed to test the xmlSerialize function

		$writer->endElement();
		$result = $writer->outputMemory();

		$this->assertEquals($expected, $result);
	}
}
