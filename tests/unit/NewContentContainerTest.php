<?php

namespace OCA\OJSXC;

use OCA\OJSXC\Db\Message;
use OCA\OJSXC\Db\Stanza;
use PHPUnit_Framework_TestCase;

class NewContentContainerTest extends PHPUnit_Framework_TestCase
{

	/**
	 * @var NewContentContainer $newContentContainer
	 */
	private $newContentContainer;

	public function setUp()
	{
		$this->newContentContainer = new NewContentContainer();
	}

	public function testProvider()
	{
		$stanza1 = new Stanza();
		$stanza1->setFrom('test@own.dev');
		$stanza1->setTo('adsffdsst@own.dev');

		$stanza2 = new Message();
		$stanza2->setFrom('test@own.dev');
		$stanza2->setTo('addsf@own.dev');
		$stanza2->setType('chat');
		$stanza2->setValue('abc');
		return [
			[
				[$stanza1, $stanza2],
				2
			]
		];
	}

	/**
	 * @dataProvider testProvider
	 */
	public function test($stanzas, $count)
	{
		foreach ($stanzas as $stanza) {
			$this->newContentContainer->addStanza($stanza);
		}
		$this->assertEquals($count, $this->newContentContainer->getCount());

		$result = $this->newContentContainer->getStanzas();
		$this->assertEquals(sort($stanzas), sort($result));
	}
}
