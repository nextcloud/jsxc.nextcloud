<?php

namespace OCA\OJSXC\Tests\Integration\Db;

use OCA\OJSXC\AppInfo\Application;
use OCA\OJSXC\Db\Message;
use OCA\OJSXC\Tests\Utility\MapperTestUtility;
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

	protected function setUp(): void
	{
		$this->entityName = 'OCA\OJSXC\Db\Message';
		$this->mapperName = 'MessageMapper';
		parent::setUp();
	}

	public function insertProvider()
	{
		return [
			[
				['john', 'localhost'],
				['thomas', 'localhost'],
				'abcd',
				'test',
				'Test Message',
				// save stanza without host or resource
				'<message to="thomas" from="john" type="test" xmlns="jabber:client" id="4-msg">Test Message</message>'
			]
		];
	}

	/**
	 * @dataProvider insertProvider
	 */
	public function testInsert($from, $to, $data, $type, $msg, $expectedStanza)
	{
		$stanza = new Message();
		$stanza->setFrom($from[0]);
		$stanza->setTo($to[0]);
		$stanza->setStanza($data);
		$stanza->setType($type);
		$stanza->setValue($msg);

		$this->assertEquals($stanza->getUnSanitizedFrom(), $from[0]);
		$this->assertEquals($stanza->getUnSanitizedTo(), $to[0]);
		$this->assertEquals($stanza->getStanza(), $data);
		$this->assertEquals($stanza->getType(), $type);

		$this->mapper->insert($stanza);

		$result = $this->fetchAll();

		$this->assertCount(1, $result);
		$this->assertEquals($stanza->getUnSanitizedFrom(), $result[0]->getFrom());
		$this->assertEquals($stanza->getUnSanitizedTo(), $result[0]->getTo());
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
		$stanza->setFrom('john', 'localhost');
		$stanza->setTo('john', 'localhost');
		$stanza->setStanza('abcd');
		$stanza->setType('test');
		$stanza->setValue('message abc');
		$this->mapper->insert($stanza);

		$this->mapper->findByTo('test');
	}

	public function testFindByToFoundWithoutAtSign()
	{
		// when the username doesn't contain a @ the domain is removed and stored as such in the DB
		// the resulting stanza then contains the full JID
		$stanza1 = new Message();
		$stanza1->setFrom('jan');
		$stanza1->setTo('john');
		$stanza1->setType('test');
		$stanza1->setValue('Messageabc');
		$this->mapper->insert($stanza1);

		$stanza2 = new Message();
		$stanza2->setFrom('thomas');
		$stanza2->setTo('jan');
		$stanza2->setType('test2');
		$stanza2->setValue('Message');
		$this->mapper->insert($stanza2);


		// check if two elements are inserted
		$result = $this->fetchAll();
		$this->assertCount(2, $result);

		// check findByTo
		$result = $this->mapper->findByTo('john');
		$this->assertCount(1, $result);
		$this->assertEquals('<message to="john@localhost/internal" from="jan@localhost/internal" type="test" xmlns="jabber:client" id="4-msg">Messageabc</message>', $result[0]->getStanza());

		// check if element is deleted
		$result = $this->fetchAll();
		$this->assertCount(1, $result);
		$this->assertEquals($stanza2->getUnSanitizedFrom(), $result[0]->getFrom());
		$this->assertEquals($stanza2->getUnSanitizedTo(), $result[0]->getTo());
		$this->assertEquals('<message to="jan" from="thomas" type="test2" xmlns="jabber:client" id="4-msg">Message</message>', $result[0]->getStanza()); // notice that the username isn't replaced by the JID since this tis the task of hte findByTo method
	}

	public function testFindByToFoundWithAtSign()
	{
		// when the username does contain a @ the domain is removed and stored as such in the DB, but with the @ still
		// in the username, the resulting stanza then contains the full JID
		$stanza1 = new Message();
		$stanza1->setFrom('jan@localhost.com');
		$stanza1->setTo('john@localhost.com');
		$stanza1->setStanza('abcd1');
		$stanza1->setType('test');
		$stanza1->setValue('Messageabc');
		$this->mapper->insert($stanza1);

		$stanza2 = new Message();
		$stanza2->setFrom('thomas@localhost.com');
		$stanza2->setTo('jan@localhost.com');
		$stanza2->setStanza('abcd2');
		$stanza2->setType('test2');
		$stanza2->setValue('Message');
		$this->mapper->insert($stanza2);


		// check if two elements are inserted
		$result = $this->fetchAll();
		$this->assertCount(2, $result);

		// check findByTo
		$result = $this->mapper->findByTo('john@localhost.com');
		$this->assertCount(1, $result);
		$this->assertEquals('<message to="john_ojsxc_esc_at_localhost.com@localhost/internal" from="jan_ojsxc_esc_at_localhost.com@localhost/internal" type="test" xmlns="jabber:client" id="4-msg">Messageabc</message>', $result[0]->getStanza());

		// check if element is deleted
		$result = $this->fetchAll();
		$this->assertCount(1, $result);
		$this->assertEquals($stanza2->getFrom(), $result[0]->getFrom());
		$this->assertEquals($stanza2->getTo(), $result[0]->getTo());
		$this->assertEquals('<message to="jan_ojsxc_esc_at_localhost.com" from="thomas_ojsxc_esc_at_localhost.com" type="test2" xmlns="jabber:client" id="4-msg">Message</message>', $result[0]->getStanza());
	}
}
