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
				'john',
				'thomas',
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

		$this->assertEquals($stanza->getUnSanitizedFrom(), $from);
		$this->assertEquals($stanza->getUnSanitizedTo(), $to);
		$this->assertEquals($stanza->getStanza(), $data);

		$this->mapper->insert($stanza);

		$result = $this->fetchAll();

		$this->assertCount(1, $result);
		$this->assertEquals($stanza->getUnSanitizedFrom(), $result[0]->getFrom());
		$this->assertEquals($stanza->getUnSanitizedTo(), $result[0]->getTo());
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
		$stanza1->setFrom('jan');
		$stanza1->setTo('john');
		$stanza1->setStanza('abcd1');
		$this->mapper->insert($stanza1);

		$stanza2 = new Stanza();
		$stanza2->setFrom('thomas');
		$stanza2->setTo('jan');
		$stanza2->setStanza('abcd2');
		$this->mapper->insert($stanza2);


		// check if two elements are inserted
		$result = $this->fetchAll();
		$this->assertCount(2, $result);

		// check findByTo
		$result = $this->mapper->findByTo('john');
		$this->assertCount(1, $result);
		$this->assertEquals($stanza1->getStanza(), $result[0]->getStanza());

		// check if element is deleted
		$result = $this->fetchAll();
		$this->assertCount(1, $result);
		$this->assertEquals($stanza2->getUnSanitizedFrom(), $result[0]->getFrom());
		$this->assertEquals($stanza2->getUnSanitizedTo(), $result[0]->getTo());
		$this->assertEquals($stanza2->getStanza(), $result[0]->getStanza());
	}


	public function testDeleteByTo()
	{
		$stanza1 = new Stanza();
		$stanza1->setFrom('jan');
		$stanza1->setTo('john');
		$stanza1->setStanza('abcd1');
		$this->mapper->insert($stanza1);

		$stanza2 = new Stanza();
		$stanza2->setFrom('thomas');
		$stanza2->setTo('jan');
		$stanza2->setStanza('abcd2');
		$this->mapper->insert($stanza2);

		// check if two elements are inserted
		$result = $this->fetchAllAsArray();
		$this->assertArrayDbResultsEqual([
			['from' => 'jan', 'to' => 'john', 'stanza' => 'abcd1'],
			['from' => 'thomas', 'to' => 'jan', 'stanza' => 'abcd2']
		], $result, ['from', 'to', 'stanza']);


		$this->mapper->deleteByTo('jan');

		$result = $this->fetchAllAsArray();
		$this->assertArrayDbResultsEqual([
			['from' => 'jan', 'to' => 'john', 'stanza' => 'abcd1']
		], $result, ['from', 'to', 'stanza']);
	}
}
