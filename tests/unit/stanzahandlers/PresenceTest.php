<?php

namespace OCA\OJSXC\Tests\Unit\StanzaHandlers;

use OCA\OJSXC\Db\MessageMapper;
use OCA\OJSXC\StanzaHandlers\Presence;
use OCA\OJSXC\Db\Presence as PresenceEntity;
use OCA\OJSXC\Db\PresenceMapper;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;

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

	public function setUp(): void
	{
		$this->host = 'localhost';
		$this->userId = 'john';

		/** @var PresenceMapper */
		$this->presenceMapper = $this->getMockBuilder(PresenceMapper::class)->disableOriginalConstructor()->getMock();

		/** @var MessageMapper */
		$this->messageMapper = $this->getMockBuilder(MessageMapper::class)->disableOriginalConstructor()->getMock();

		$this->presence = new Presence($this->userId, $this->presenceMapper, $this->messageMapper);
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
			->method('insert');

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
		$insert1->setPresence('unavailable');
		$insert1->setFrom('john');
		$insert1->setTo('derp');

		$insert2 = new PresenceEntity();
		$insert2->setPresence('unavailable');
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
			->method('insert');

		$this->presenceMapper->expects($this->never())
			->method('getPresences');

		$result = $this->presence->handle($presenceEntity);
		$this->assertEquals($presences, $result);
	}
}
