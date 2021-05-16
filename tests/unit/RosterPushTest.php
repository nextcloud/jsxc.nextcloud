<?php

namespace OCA\OJSXC;

use OCA\OJSXC\Db\IQRosterPush;
use OCA\OJSXC\Db\IQRosterPushMapper;
use OCP\IDBConnection;
use OCP\IGroup;
use OCP\IUserManager;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;

class RosterPushTest extends TestCase
{

	/**
	 * @var RosterPush
	 */
	private $rosterPush;

	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject | IUserManager
	 */
	private $userManager;

	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject | IUserSession
	 */
	private $userSession;

	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject | IQRosterPushMapper
	 */
	private $iqRosterPushMapper;

	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject | IDBConnection
	 */
	private $db;

	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject | IUserProvider
	 */
	private $userProvider;

	public function setUp(): void
	{
		$this->userManager = $this->getMockBuilder('OCP\IUserManager')
			->disableOriginalConstructor()->getMock();

		$this->userSession = $this->getMockBuilder('OCP\IUserSession')
			->disableOriginalConstructor()->getMock();

		$this->iqRosterPushMapper = $this->getMockBuilder('OCA\OJSXC\Db\IQRosterPushMapper')
			->disableOriginalConstructor()->getMock();

		$this->db = $this->getMockBuilder('OCP\IDbConnection')
			->disableOriginalConstructor()->getMock();

		$this->userProvider = $this->getMockBuilder('OCA\OJSXC\IUserProvider')->disableOriginalConstructor()->getMock();

		$this->rosterPush = new RosterPush(
			$this->userManager,
			$this->userSession,
			'localhost',
			$this->iqRosterPushMapper,
			$this->db,
			$this->userProvider
		);
	}

	public function testRefreshRoster()
	{

		/** @var \PHPUnit_Framework_MockObject_MockObject | RosterPush $rosterPush */
		$rosterPush = $this->getMockBuilder('OCA\OJSXC\RosterPush')
			->setConstructorArgs([$this->userManager, $this->userSession, 'host', $this->iqRosterPushMapper, $this->db, $this->userProvider])
			->setMethods(['createOrUpdateRosterItem', 'removeRosterItem'])->getMock();

		$user1 = $this->getMockBuilder('OCP\IUser')->getMock();
		$user2 = $this->getMockBuilder('OCP\IUser')->getMock();
		$user3 = $this->getMockBuilder('OCP\IUser')->getMock();

		$this->userManager->expects($this->once())
			->method('search')
			->willReturn([$user1, $user2, $user3]);

		$rosterPush->expects($this->at(0))
			->method('createOrUpdateRosterItem')
			->with($user1);

		$rosterPush->expects($this->at(1))
			->method('createOrUpdateRosterItem')
			->with($user2);

		$rosterPush->expects($this->at(2))
			->method('createOrUpdateRosterItem')
			->with($user3);

		$resultStatement = $this->getMockBuilder('OCP\DB\IResult')->getMock();

		$resultStatement->expects($this->at(0))
			->method('fetchAll')
			->willReturn([["id" => 10]]);

		$resultStatement->expects($this->at(1))
			->method('fetchAll')
			->willReturn([
				["uri" => 'Database:user1.vcf'],
				["uri" => 'Database:user2.vcf'],
				["uri" => 'Database:user3.vcf'],
				["uri" => 'Database:user4.vcf']
			]);

		$this->db->expects($this->at(0))
			->method('executeQuery')
			->with('SELECT `id` FROM `*PREFIX*addressbooks` WHERE `principaluri`=\'principals/system/system\' LIMIT 1')
			->willReturn($resultStatement);

		$this->db->expects($this->at(1))
			->method('executeQuery')
			->with('SELECT `uri` FROM `*PREFIX*addressbookchanges` AS ac1 WHERE `addressbookid` = ? AND `operation` = 3 AND `id`=(SELECT MAX(id) FROM `*PREFIX*addressbookchanges` AS ac2 WHERE `uri`=ac1.uri)', [10])
			->willReturn($resultStatement);


		$rosterPush->expects($this->at(3))
			->method('removeRosterItem')
			->with('user1');

		$rosterPush->expects($this->at(4))
			->method('removeRosterItem')
			->with('user2');

		$rosterPush->expects($this->at(5))
			->method('removeRosterItem')
			->with('user3');

		$rosterPush->expects($this->at(6))
			->method('removeRosterItem')
			->with('user4');

		$stats = $rosterPush->refreshRoster();

		$this->assertEquals($stats, ["removed" => 4, "updated" => 3]);
	}

	public function testRefreshRosterThrowsDuringRemove()
	{

		/** @var \PHPUnit_Framework_MockObject_MockObject | RosterPush $rosterPush */
		$rosterPush = $this->getMockBuilder('OCA\OJSXC\RosterPush')
			->setConstructorArgs([$this->userManager, $this->userSession, 'host', $this->iqRosterPushMapper, $this->db, $this->userProvider])
			->setMethods(['createOrUpdateRosterItem', 'removeRosterItem'])->getMock();

		$user1 = $this->getMockBuilder('OCP\IUser')->getMock();
		$user2 = $this->getMockBuilder('OCP\IUser')->getMock();
		$user3 = $this->getMockBuilder('OCP\IUser')->getMock();

		$this->userManager->expects($this->once())
			->method('search')
			->willReturn([$user1, $user2, $user3]);

		$rosterPush->expects($this->at(0))
			->method('createOrUpdateRosterItem')
			->with($user1);

		$rosterPush->expects($this->at(1))
			->method('createOrUpdateRosterItem')
			->with($user2);

		$rosterPush->expects($this->at(2))
			->method('createOrUpdateRosterItem')
			->with($user3);

		$this->db->expects($this->at(0))
			->method('executeQuery')
			->with('SELECT `id` FROM `*PREFIX*addressbooks` WHERE `principaluri`=\'principals/system/system\' LIMIT 1')
			->willThrowException(new \Exception("A random exception"));

		$stats = $rosterPush->refreshRoster();

		$this->assertEquals($stats, ["removed" => 0, "updated" => 3]);
	}

	public function testRemoveRosterItem()
	{
		$user1 = $this->getMockBuilder('OCP\IUser')->getMock();
		$user1->expects($this->once())
			->method('getUID')
			->willReturn('user1');
		$user2 = $this->getMockBuilder('OCP\IUser')->getMock();
		$user2->expects($this->exactly(2))
			->method('getUID')
			->willReturn('user2');
		$user3 = $this->getMockBuilder('OCP\IUser')->getMock();
		$user3->expects($this->exactly(2))
			->method('getUID')
			->willReturn('user3');

		$this->userManager->expects($this->once())
			->method('search')
			->willReturn([$user1, $user2, $user3]);

		$stanza1 = new IQRosterPush();
		$stanza1->setJid('user1');
		$stanza1->setSubscription('remove');
		$stanza1->setFrom('');
		$stanza1->setTo('user2');

		$stanza2 = new IQRosterPush();
		$stanza2->setJid('user1');
		$stanza2->setSubscription('remove');
		$stanza2->setFrom('');
		$stanza2->setTo('user3');

		$this->iqRosterPushMapper->expects($this->at(0))
			->method('insert')
			->with($stanza1);

		$this->iqRosterPushMapper->expects($this->at(1))
			->method('insert')
			->with($stanza2);

		$this->rosterPush->removeRosterItem('user1');
	}

	public function testCreateOrUpdateRosterItem()
	{
		$user1 = $this->getMockBuilder('OCP\IUser')->getMock();
		$user1->expects($this->exactly(6))
			->method('getUID')
			->willReturn('user1');
		$user2 = $this->getMockBuilder('OCP\IUser')->getMock();
		$user2->expects($this->exactly(2))
			->method('getUID')
			->willReturn('user2');
		$user3 = $this->getMockBuilder('OCP\IUser')->getMock();
		$user3->expects($this->exactly(2))
			->method('getUID')
			->willReturn('user3');

		$this->userProvider->expects($this->once())
			->method('getAllUsersForUserByUID')
			->willReturn([$user1, $user2, $user3]);

		$stanza1 = new IQRosterPush();
		$stanza1->setJid('user1');
		$stanza1->setSubscription('both');
		$stanza1->setFrom('');
		$stanza1->setTo('user2');

		$stanza2 = new IQRosterPush();
		$stanza2->setJid('user1');
		$stanza2->setSubscription('both');
		$stanza2->setFrom('');
		$stanza2->setTo('user3');

		$this->iqRosterPushMapper->expects($this->at(0))
			->method('insert')
			->with($stanza1);

		$this->iqRosterPushMapper->expects($this->at(1))
			->method('insert')
			->with($stanza2);

		$this->rosterPush->createOrUpdateRosterItem($user1);
	}

	// removeRosterItemForUsersInGroup, addUserToGroup and removeUserFromGroup covered by integration tests
}
