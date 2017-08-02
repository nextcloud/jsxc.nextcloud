<?php

namespace OCA\OJSXC\Db;

use OCA\OJSXC\Utility\MapperTestUtility;
use OCP\AppFramework\Db\DoesNotExistException;

/**
 * @group DB
 */
class StanzaMapperTest extends MapperTestUtility
{

	/**
	 * @var StanzaMapper
	 */
	protected $mapper;

	protected function setUp()
	{
		$this->entityName = 'OCA\OJSXC\Db\Stanza';
		$this->mapperName = 'StanzaMapper';
		parent::setUp();
	}

	public function insertProvider()
	{
		return [
			[
				'john@localhost',
				'thomas@localhost',
				'abcd'
			]
		];
	}
	
	/**
	 * @dataProvider insertProvider
	 */
	public function testInsert($from, $to, $data)
	{
		$stanza = new Stanza();
		$stanza->setFrom($from);
		$stanza->setTo($to);
		$stanza->setStanza($data);

		$this->assertEquals($stanza->getFrom(), $from);
		$this->assertEquals($stanza->getTo(), $to);
		$this->assertEquals($stanza->getStanza(), $data);

		$this->mapper->insert($stanza);

		$result = $this->fetchAll();

		$this->assertCount(1, $result);
		$this->assertEquals($stanza->getFrom(), $result[0]->getFrom());
		$this->assertEquals($stanza->getTo(), $result[0]->getTo());
		$this->assertEquals($stanza->getStanza(), $result[0]->getStanza());
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
		$stanza = new Stanza();
		$stanza->setFrom('john@localhost');
		$stanza->setTo('john@localhost');
		$stanza->setStanza('abcd');
		$this->mapper->insert($stanza);

		$this->mapper->findByTo('test');
	}

	public function testFindByToFound()
	{
		$stanza1 = new Stanza();
		$stanza1->setFrom('jan@localhost');
		$stanza1->setTo('john@localhost');
		$stanza1->setStanza('abcd1');
		$this->mapper->insert($stanza1);

		$stanza2 = new Stanza();
		$stanza2->setFrom('thomas@localhost');
		$stanza2->setTo('jan@localhost');
		$stanza2->setStanza('abcd2');
		$this->mapper->insert($stanza2);


		// check if two elements are inserted
		$result = $this->fetchAll();
		$this->assertCount(2, $result);

		// check findByTo
		$result = $this->mapper->findByTo('john@localhost');
		$this->assertCount(1, $result);
		$this->assertEquals($stanza1->getStanza(), $result[0]->getStanza());

		// check if element is deleted
		$result = $this->fetchAll();
		$this->assertCount(1, $result);
		$this->assertEquals($stanza2->getFrom(), $result[0]->getFrom());
		$this->assertEquals($stanza2->getTo(), $result[0]->getTo());
		$this->assertEquals($stanza2->getStanza(), $result[0]->getStanza());
	}


	public function testDeleteByTo()
	{
		$stanza1 = new Stanza();
		$stanza1->setFrom('jan@localhost');
		$stanza1->setTo('john@localhost');
		$stanza1->setStanza('abcd1');
		$this->mapper->insert($stanza1);

		$stanza2 = new Stanza();
		$stanza2->setFrom('thomas@localhost');
		$stanza2->setTo('jan@localhost');
		$stanza2->setStanza('abcd2');
		$this->mapper->insert($stanza2);

		// check if two elements are inserted
		$result = $this->fetchAllAsArray();
		$this->assertArrayDbResultsEqual([
			['from' => 'jan@localhost', 'to' => 'john@localhost', 'stanza' => 'abcd1'],
			['from' => 'thomas@localhost', 'to' => 'jan@localhost', 'stanza' => 'abcd2']
		], $result, ['from', 'to', 'stanza']);


		$this->mapper->deleteByTo('jan@localhost');

		$result = $this->fetchAllAsArray();
		$this->assertArrayDbResultsEqual([
			['from' => 'jan@localhost', 'to' => 'john@localhost', 'stanza' => 'abcd1']
		], $result, ['from', 'to', 'stanza']);
	}
}
