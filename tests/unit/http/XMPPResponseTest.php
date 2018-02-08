<?php

namespace  OCA\OJSXC\Db {
	function uniqid()
	{
		return 4; // chosen by fair dice roll.
		// guaranteed to be unique.
	}
}

namespace OCA\OJSXC\Http {

	use OCA\OJSXC\Db\Message;
	use OCA\OJSXC\Db\Stanza;
	use PHPUnit\Framework\TestCase;
	use PHPUnit_Framework_TestCase;

	class XMPPResponseTest extends TestCase
	{
		public function writingProvider()
		{
			$stanza1 = new Stanza();
			$stanza1->setFrom('test', 'test.be');
			$stanza1->setTo('test.be');
			$stanza1->setStanza('abc');

			$stanza2 = new Message();
			$stanza2->setFrom('test', 'test.be');
			$stanza2->setTo('test.be');
			$stanza2->setStanza('abc');
			$stanza2->setType('testtype');
			$stanza2->setValue('abcvalue');

			return [
				[
					[new Stanza('')],
					'<body xmlns="http://jabber.org/protocol/httpbind"></body>'
				],
				[
					[$stanza1],
					'<body xmlns="http://jabber.org/protocol/httpbind">abc</body>'
				],
				[
					[$stanza1, $stanza2],
					'<body xmlns="http://jabber.org/protocol/httpbind">abc<message to="test.be" from="test@test.be" type="testtype" xmlns="jabber:client" id="4-msg">abcvalue</message></body>'
				],
				[
					[$stanza1, new Stanza(''), $stanza2],
					'<body xmlns="http://jabber.org/protocol/httpbind">abc<message to="test.be" from="test@test.be" type="testtype" xmlns="jabber:client" id="4-msg">abcvalue</message></body>'
				],
			];
		}

		/**
		 * @dataProvider writingProvider
		 */
		public function testWriting($stanzas, $expected)
		{
			$stanzaLogger = $this->getMockBuilder('OCA\OJSXC\StanzaLogger')->disableOriginalConstructor()->getMock();
			$response = new XMPPResponse($stanzaLogger);
			foreach ($stanzas as $stanza) {
				$response->write($stanza);
			}
			$result = $response->render();
			$this->assertEquals($expected, $result);
		}
	}

}
