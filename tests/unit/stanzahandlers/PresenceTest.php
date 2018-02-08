<?php

namespace OCA\OJSXC\StanzaHandlers;

use OCA\OJSXC\StanzaHandlers\Presence;
use OCA\OJSXC\Db\Presence as PresenceEntity;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase;

class PresenceTest extends TestCase
{
	private $host;

	private $userId;

	/**
	 * @var PHPUnit_Framework_MockObject_MockObject $presenceMapper
	 */
	private $presenceMapper;

	/**
	 * @var PHPUnit_Framework_MockObject_MockObject $presenceMapper
	 */
	private $messageMapper;

	/**
	 * @var Presence
	 */
	private $presence;

	public function setUp()
	{
		$this->host = 'localhost';
		$this->userId = 'john';
		$this->presenceMapper = $this->getMockBuilder('OCA\OJSXC\Db\PresenceMapper')->disableOriginalConstructor()->getMock();
		$this->messageMapper = $this->getMockBuilder('OCA\OJSXC\Db\MessageMapper')->disableOriginalConstructor()->getMock();

		$this->presence = new Presence($this->userId, $this->host, $this->presenceMapper, $this->messageMapper);
	}

	public function handleProvider()
	{
		$presence = new PresenceEntity();
		$presence->setPresence('online');
		$presence->setUserid('john');
		$presence->setLastActive(time());

		// broadcast presence
		$insert1 = new PresenceEntity();
		$insert1->setPresence('online');
		$insert1->setFrom('john');
		$insert1->setTo('derp');

		$insert2 = new PresenceEntity();
		$insert2->setPresence('online');
		$insert2->setFrom('john');
		$insert2->setTo('herp');
		return [
			[
				$presence,
				['derp', 'herp'],
				'testValue',
				[$insert1, $insert2]
			]
		];
	}
	
	/**
	 * @dataProvider handleProvider
	 */
	public function testHandle($presenceEntity, $connectedUsers, $presences, $insert)
	{
		$this->presenceMapper->expects($this->once())
			->method('setPresence')
			->with($presenceEntity);


		$this->presenceMapper->expects($this->once())
			->method('getConnectedUsers')
			->will($this->returnValue($connectedUsers));

		$this->messageMapper->expects($this->exactly(2))
			->method('insert')
			->withConsecutive(
				$this->equalTo($insert[0]),
				$this->equalTo($insert[1])
			);


		$this->presenceMapper->expects($this->once())
			->method('getPresences')
			->will($this->returnValue($presences));


		$result = $this->presence->handle($presenceEntity);
		$this->assertEquals($presences, $result);
	}


	public function unavailableHandleProvider()
	{
		$presence = new PresenceEntity();
		$presence->setPresence('unavailable');
		$presence->setUserid('john');
		$presence->setLastActive(time());

		// broadcast presence
		$insert1 = new PresenceEntity();
		$insert1->setPresence('online');
		$insert1->setFrom('john');
		$insert1->setTo('derp');

		$insert2 = new PresenceEntity();
		$insert2->setPresence('online');
		$insert2->setFrom('john');
		$insert2->setTo('herp');

		return [
			[
				$presence,
				['derp', 'herp'],
				[],
				[$insert1, $insert2]
			]
		];
	}

	/**
	 * @dataProvider UnavailableHandleProvider
	 */
	public function testUnavailableHandle($presenceEntity, $connectedUsers, $presences, $insert)
	{
		$this->presenceMapper->expects($this->once())
			->method('setPresence')
			->with($presenceEntity);


		$this->presenceMapper->expects($this->once())
			->method('getConnectedUsers')
			->will($this->returnValue($connectedUsers));

		$this->messageMapper->expects($this->exactly(2))
			->method('insert')
			->withConsecutive(
				$this->equalTo($insert[0]),
				$this->equalTo($insert[1])
			);

		$this->presenceMapper->expects($this->never())
			->method('getPresences');

		$result = $this->presence->handle($presenceEntity);
		$this->assertEquals($presences, $result);
	}
}
