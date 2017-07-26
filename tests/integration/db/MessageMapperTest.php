<?php

namespace OCA\OJSXC\Db;

use OCA\OJSXC\Utility\MapperTestUtility;
use OCP\AppFramework\Db\DoesNotExistException;

function uniqid()
{
	return 4; // chosen by fair dice roll.
			  // guaranteed to be unique.
}

/**
 * @group DB
 */
class MessageMapperTest extends MapperTestUtility
{

	/**
	 * @var StanzaMapper
	 */
	protected $mapper;

	protected function setUp()
	{
		$this->entityName = 'OCA\OJSXC\Db\Message';
		$this->mapperName = 'MessageMapper';
		parent::setUp();
	}

	public function insertProvider()
	{
		return [
			[
				'john@localhost',
				'thomas@localhost',
				'abcd',
				'test',
				'Test Message',
				'<message to="thomas@localhost" from="john@localhost" type="test" xmlns="jabber:client" id="4-msg">Test Message</message>'
			]
		];
	}

	/**
	 * @dataProvider insertProvider
	 */
	public function testInsert($from, $to, $data, $type, $msg, $expectedStanza)
	{
		$stanza = new Message();
		$stanza->setFrom($from);
		$stanza->setTo($to);
		$stanza->setStanza($data);
		$stanza->setType($type);
		$stanza->setValue($msg);

		$this->assertEquals($stanza->getFrom(), $from);
		$this->assertEquals($stanza->getTo(), $to);
		$this->assertEquals($stanza->getStanza(), $data);
		$this->assertEquals($stanza->getType(), $type);

		$this->mapper->insert($stanza);

		$result = $this->fetchAll();

		$this->assertCount(1, $result);
		$this->assertEquals($stanza->getFrom(), $result[0]->getFrom());
		$this->assertEquals($stanza->getTo(), $result[0]->getTo());
		$this->assertEquals($expectedStanza, $result[0]->getStanza());
		$this->assertEquals(null, $result[0]->getType()); // type is saved into the XML string, not the DB.
	}

	/**
	 * @expectedException \OCP\AppFramework\Db\DoesNotExistException
	 */
	public function testFindByToNotFound()
	{
		$this->mapper->findByTo('test');
	}

	/**
	 * @expectedException \OCP\AppFramework\Db\DoesNotExistException
	 */
	public function testFindByToNotFound2()
	{
		$stanza = new Message();
		$stanza->setFrom('john@localhost');
		$stanza->setTo('john@localhost');
		$stanza->setStanza('abcd');
		$stanza->setType('test');
		$stanza->setValue('message abc');
		$this->mapper->insert($stanza);

		$this->mapper->findByTo('test');
	}

	public function testFindByToFound()
	{
		$stanza1 = new Message();
		$stanza1->setFrom('jan@localhost');
		$stanza1->setTo('john@localhost');
		$stanza1->setStanza('abcd1');
		$stanza1->setType('test');
		$stanza1->setValue('Messageabc');
		$this->mapper->insert($stanza1);

		$stanza2 = new Message();
		$stanza2->setFrom('thomas@localhost');
		$stanza2->setTo('jan@localhost');
		$stanza2->setStanza('abcd2');
		$stanza2->setType('test2');
		$stanza2->setValue('Message');
		$this->mapper->insert($stanza2);


		// check if two elements are inserted
		$result = $this->fetchAll();
		$this->assertCount(2, $result);

		// check findByTo
		$result = $this->mapper->findByTo('john@localhost');
		$this->assertCount(1, $result);
		$this->assertEquals('<message to="john@localhost" from="jan@localhost" type="test" xmlns="jabber:client" id="4-msg">Messageabc</message>', $result[0]->getStanza());

		// check if element is deleted
		$result = $this->fetchAll();
		$this->assertCount(1, $result);
		$this->assertEquals($stanza2->getFrom(), $result[0]->getFrom());
		$this->assertEquals($stanza2->getTo(), $result[0]->getTo());
		$this->assertEquals('<message to="jan@localhost" from="thomas@localhost" type="test2" xmlns="jabber:client" id="4-msg">Message</message>', $result[0]->getStanza());
	}
}
