<?php

namespace OCA\OJSXC\StanzaHandlers;

use OCA\OJSXC\Db\IQRoster;
use OCA\OJSXC\Exceptions\TerminateException;
use OCA\OJSXC\IUserProvider;
use OCA\OJSXC\User;
use OCP\IConfig;
use PHPUnit\Framework\TestCase;
use OCP\IUserManager;
use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase;

class IQTest extends TestCase
{

	/**
	 * @var IQ $iq
	 */
	private $iq;

	/**
	 * @var PHPUnit_Framework_MockObject_MockObject | IUserManager
	 */
	private $userManager;

	/**
	 * @var string userId
	 */
	private $userId;

	/**
	 * @var string $host ;
	 */
	private $host;

	/**
	 * @var PHPUnit_Framework_MockObject_MockObject | IConfig
	 */
	private $config;


	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject | IUserProvider
	 */
	private $userProvider;

	public function setUp()
	{
		$this->host = 'localhost';
		$this->userId = 'john';
		$this->userManager = $this->getMockBuilder('OCP\IUserManager')->disableOriginalConstructor()->getMock();
		$this->config = $this->getMockBuilder('OCP\IConfig')->disableOriginalConstructor()->getMock();
		$this->userProvider = $this->getMockBuilder('OCA\OJSXC\IUserProvider')->disableOriginalConstructor()->getMock();
		$this->iq = new IQ($this->userId, $this->host, $this->userManager, $this->config, $this->userProvider);
	}

	public function iqRosterProvider()
	{
		$user1 = $this->getMockBuilder(User::class)->disableOriginalConstructor()->getMock();
		$user1->expects($this->any())
			->method('getUID')
			->will($this->returnValue('john'));

		$user1->expects($this->any())
			->method('getFullName')
			->will($this->returnValue('John'));

		$user2 = $this->getMockBuilder(User::class)->disableOriginalConstructor()->getMock();
		$user2->expects($this->any())
			->method('getUID')
			->will($this->returnValue('richard'));

		$user2->expects($this->any())
			->method('getFullName')
			->will($this->returnValue('Richard'));

		$expected1 = new IQRoster();
		$expected1->setType('result');
		$expected1->setTo('john');
		$expected1->setQid('f9a26583-3c59-4f09-89be-964ce265fbfd:sendIQ');
		$expected1->addItem('richard@localhost', 'Richard');

		$expected2 = new IQRoster();
		$expected2->setType('result');
		$expected2->setTo('john');
		$expected2->setQid('f9a26583-3c59-4f09-89be-964ce265fbfa:sendIQ');

		return [
			[
				['name' => '{jabber:client}iq',
					'value' => [0 => [
						'name' => '{http://jabber.org/protocol/disco#info}query',
						'value' => null,
						'attributes' => [
							'node' => 'undefined#undefined',
						],
					]],
					'attributes' => [
						'from' => 'admin@own.dev',
						'to' => 'own.dev',
						'type' => 'get',
						'id' => 'e4e3e333-1b72-4014-a191-8c157326e037:sendIQ',
					],
				],
				[],
				$this->never(),
				null
			],
			[
				[
					'name' => '{jabber:client}iq',
					'value' =>
						[
							0 =>
								[
									'name' => '{jabber:iq:roster}query',
									'value' => null,
									'attributes' =>
										[
										],
								]
						],
					'attributes' =>
						[
							'type' => 'get',
							'id' => 'f9a26583-3c59-4f09-89be-964ce265fbfd:sendIQ',
						],
				],
				[$user1, $user2],
				$this->once(),
				$expected1
			],
			[
				[
					'name' => '{jabber:client}iq',
					'value' =>
						[
							0 =>
								[
									'name' => '{jabber:iq:roster}query',
									'value' => null,
									'attributes' =>
										[
										],
								]
						],
					'attributes' =>
						[
							'type' => 'get',
							'id' => 'f9a26583-3c59-4f09-89be-964ce265fbfa:sendIQ',
						],
				],
				[],
				$this->once(),
				$expected2
			]
		];
	}

	/**
	 * @dataProvider iqRosterProvider
	 */
	public function testIqRoster(array $stanza, array $users, $searchCount, $expected)
	{
		$this->config->expects($this->once())
			->method('getSystemValue')
			->with('debug')
			->will($this->returnValue(false));

		$this->userProvider->expects($searchCount)
			->method('getAllUsers')
			->will($this->returnValue($users));

		$result = $this->iq->handle($stanza);

		if ($expected instanceof IQRoster) {
			$this->assertEquals($expected->getUnSanitizedFrom(), $result->getUnSanitizedFrom());
			$this->assertEquals($expected->getId(), $result->getId());
			$this->assertEquals($expected->getItems(), $result->getItems());
			$this->assertEquals($expected->getQid(), $result->getQid());
			$this->assertEquals($expected->getUnSanitizedTo(), $result->getUnSanitizedTo());
			$this->assertEquals($expected->getType(), $result->getType());
			$this->assertEquals($expected->getStanza(), $result->getStanza());
		} else {
			$this->assertEquals($expected, $result);
		}
	}

	public function testTerminateExceptionDiscoItems()
	{
		$stanza = ['name' => '{jabber:client}iq',
			'value' => [0 => [
				'name' => '{http://jabber.org/protocol/disco#items}query',
				'value' => null,
				'attributes' => [
					'node' => 'undefined#undefined',
				],
			]],
		];

		$this->userProvider->expects($this->once())
			->method('isUserExcluded')
			->with('john')
			->will($this->returnValue(true));

		$this->expectException(TerminateException::class);
		$this->iq->handle($stanza);
	}

	public function testTerminateExceptionDiscoInfo()
	{
		$stanza = ['name' => '{jabber:client}iq',
			'value' => [0 => [
				'name' => '{http://jabber.org/protocol/disco#info}query',
				'value' => null,
				'attributes' => [
					'node' => 'undefined#undefined',
				],
			]],
		];


		$this->userProvider->expects($this->once())
			->method('isUserExcluded')
			->with('john')
			->will($this->returnValue(true));

		$this->expectException(TerminateException::class);
		$this->iq->handle($stanza);
	}
}
