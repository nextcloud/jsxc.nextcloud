<?php

namespace OCA\OJSXC\Db;

use OCA\OJSXC\AppInfo\Application;
use OCA\OJSXC\Db\Presence as PresenceEntity;
use OCA\OJSXC\NewContentContainer;
use OCA\OJSXC\Utility\MapperTestUtility;
use OCA\DAV\AppInfo\Application as DavApp;

$time = 0;

function time()
{
	global $time;
	return $time;
}


/**
 * @group DB
 */
class PresenceMapperTest extends MapperTestUtility
{

	/**
	 * @var PresenceMapper
	 */
	protected $mapper;

	/**
	 * @var NewContentContainer $newContentContainer
	 */
	protected $newContentContainer;

	protected function setUp()
	{
		$this->entityName = 'OCA\OJSXC\Db\Presence';
		$this->mapperName = 'PresenceMapper';
		parent::setUp();
		$this->setValueOfPrivateProperty($this->mapper, 'updatedPresence', false);
		$this->setValueOfPrivateProperty($this->mapper, 'fetchedConnectedUsers', false);
		$this->setValueOfPrivateProperty($this->mapper, 'connectedUsers', []);
		$this->newContentContainer = $this->container->query('NewContentContainer');
		$this->setValueOfPrivateProperty($this->newContentContainer, 'stanzas', []);
		foreach (\OC::$server->getUserManager()->search('') as $user) {
			$user->delete();
		}
	}

	protected function tearDown()
	{
		foreach (\OC::$server->getUserManager()->search('') as $user) {
			$user->delete();
		}
	}

	public function setupContactsStoreAPI()
	{
		foreach (\OC::$server->getUserManager()->search('') as $user) {
			$user->delete();
		}

		$users[] = \OC::$server->getUserManager()->createUser('admin', 'admin');
		$users[] = \OC::$server->getUserManager()->createUser('derp', 'derp');
		$users[] = \OC::$server->getUserManager()->createUser('derpina', 'derpina');
		$users[] = \OC::$server->getUserManager()->createUser('herp', 'herp');
		$users[] = \OC::$server->getUserManager()->createUser('foo', 'foo');

		$currentUser = \OC::$server->getUserManager()->createUser('autotest', 'autotest');
		\OC::$server->getUserSession()->setUser($currentUser);

		if (Application::contactsStoreApiSupported()) {
			/** @var \OCA\DAV\CardDAV\SyncService $syncService */
			$syncService = \OC::$server->query('CardDAVSyncService');
			$syncService->getLocalSystemAddressBook();
			$syncService->updateUser($currentUser);

			foreach ($users as $user) {
				$syncService->updateUser($user);
			}

			$cm = \OC::$server->getContactsManager();
			$davApp = new DavApp();
			$davApp->setupSystemContactsProvider($cm);
		}
		\OC_User::setIncognitoMode(false);
		\OC::$server->getDatabaseConnection()->executeQuery("DELETE FROM *PREFIX*ojsxc_stanzas");
	}

	/**
	 * @return array
	 */
	public function presenceIfNotExitsProvider()
	{
		$input1 = new PresenceEntity();
		$input1->setPresence('online');
		$input1->setUserid('admin');
		$input1->setLastActive(23434);

		$input2 = new PresenceEntity();
		$input2->setPresence('unavailable');
		$input2->setUserid('derp');
		$input2->setLastActive(23434475);


		$input3 = new PresenceEntity();
		$input3->setPresence('chat');
		$input3->setUserid('derpina');
		$input3->setLastActive(23445645634);

		return [
			[
				[$input1, $input2, $input3],
				[
					[
						'userid' => 'admin',
						'presence' => 'online',
						'last_active' => 23434,
					],
					[
						'userid' => 'derp',
						'presence' => 'unavailable',
						'last_active' => 23434475,
					],
					[
						'userid' => 'derpina',
						'presence' => 'chat',
						'last_active' => 23445645634
					]
				]
			]
		];
	}

	/**
	 * @dataProvider presenceIfNotExitsProvider
	 * Test setting the presence if it doesn't exits.
	 * @param PresenceEntity[] $inputs
	 * @param array $expected
	 */
	public function testSetPresenceIfNotExists($inputs, $expected)
	{
		foreach ($inputs as $input) {
			$this->mapper->setPresence($input);
		}
		$result = $this->fetchAllAsArray();

		$this->assertArrayDbResultsEqual($expected, $result, ['userid', 'presence', 'last_active']);
	}

	/**
	 * @return array
	 */
	public function presenceIfExitsProvider()
	{
		$input1 = new PresenceEntity();
		$input1->setPresence('online');
		$input1->setUserid('admin');
		$input1->setLastActive(23434);

		$input2 = new PresenceEntity();
		$input2->setPresence('unavailable');
		$input2->setUserid('derp');
		$input2->setLastActive(23434475);

		$input3 = new PresenceEntity();
		$input3->setPresence('chat');
		$input3->setUserid('derpina');
		$input3->setLastActive(23445645634);

		$input4 = new PresenceEntity();
		$input4->setPresence('chat');
		$input4->setUserid('admin');
		$input4->setLastActive(3234343424);

		$input5 = new PresenceEntity();
		$input5->setPresence('online');
		$input5->setUserid('derp');
		$input5->setLastActive(23434353);

		return [
			[
				[$input1, $input2, $input3, $input4, $input5],
				[
					[
						'userid' => 'admin',
						'presence' => 'chat',
						'last_active' => 3234343424,
					],
					[
						'userid' => 'derp',
						'presence' => 'online',
						'last_active' => 23434353,
					],
					[
						'userid' => 'derpina',
						'presence' => 'chat',
						'last_active' => 23445645634,
					]
				]
			]
		];
	}

	/**
	 * @dataProvider presenceIfExitsProvider
	 * Test setting the presence if it doesn't exits.
	 * @param PresenceEntity[] $inputs
	 * @param array $expected
	 */
	public function testSetPresenceIfExists($inputs, $expected)
	{
		foreach ($inputs as $input) {
			$this->mapper->setPresence($input);
		}
		$result = $this->fetchAllAsArray();


		$this->assertArrayDbResultsEqual($expected, $result, ['userid', 'presence', 'last_active']);
	}

	public function getPresenceProvider()
	{
		$input1 = new PresenceEntity();
		$input1->setPresence('online');
		$input1->setUserid('admin');
		$input1->setLastActive(23434);

		$input2 = new PresenceEntity();
		$input2->setPresence('unavailable');
		$input2->setUserid('derp');
		$input2->setLastActive(23434475);


		$input3 = new PresenceEntity();
		$input3->setPresence('chat');
		$input3->setUserid('derpina');
		$input3->setLastActive(23445645634);

		$input4 = new PresenceEntity();
		$input4->setPresence('chat');
		$input4->setUserid('admin');
		$input4->setLastActive(3234343424);

		$input5 = new PresenceEntity();
		$input5->setPresence('online');
		$input5->setUserid('derp');
		$input5->setLastActive(23434353);

		$expected1 = new PresenceEntity();
		$expected1->setUserid('derp');
		$expected1->setPresence('online');
		$expected1->setLastActive(23434353);
		$expected1->setTo('admin', 'localhost/internal');
		$expected1->setFrom('derp', 'localhost/internal');

		$expected2 = new PresenceEntity();
		$expected2->setUserid('derpina');
		$expected2->setPresence('chat');
		$expected2->setLastActive(23445645634);
		$expected2->setTo('admin', 'localhost/internal');
		$expected2->setFrom('derpina', 'localhost/internal');
		return [
			[
				[$input1, $input2, $input3, $input4, $input5],
				[$expected1, $expected2]
			]
		];
	}

	/**
	 * @dataProvider getPresenceProvider
	 * @param $inputs
	 * @param $expected
	 */
	public function testGetPresence($inputs, $expected)
	{
		foreach ($inputs as $input) {
			$this->mapper->setPresence($input);
		}

		$result = $this->mapper->getPresences();

		$this->assertObjectDbResultsEqual($expected, $result, ['userid', 'presence', 'lastActive', 'to', 'from']);
	}

	public function getConnectedUsersProvider()
	{
		$input1 = new PresenceEntity();
		$input1->setPresence('online');
		$input1->setUserid('admin');
		$input1->setLastActive(23434);

		$input2 = new PresenceEntity();
		$input2->setPresence('unavailable');
		$input2->setUserid('derp');
		$input2->setLastActive(23434475);

		$input3 = new PresenceEntity();
		$input3->setPresence('chat');
		$input3->setUserid('derpina');
		$input3->setLastActive(23445645634);

		$input4 = new PresenceEntity();
		$input4->setPresence('chat');
		$input4->setUserid('admin');
		$input4->setLastActive(3234343424);

		$input5 = new PresenceEntity();
		$input5->setPresence('online');
		$input5->setUserid('derp');
		$input5->setLastActive(23434353);

		$input6 = new PresenceEntity();
		$input6->setPresence('unavailable');
		$input6->setUserid('herp');
		$input6->setLastActive(123);

		return [
			[
				[$input1, $input2, $input3, $input4, $input5, $input6],
				['derp', 'derpina']
			]
		];
	}

	/**
	 * @dataProvider getConnectedUsersProvider
	 */
	public function testGetConnectedUsers($inputs, $expected)
	{
		$this->setupContactsStoreAPI();
		foreach ($inputs as $input) {
			$this->mapper->setPresence($input);
		}

		$result = $this->mapper->getConnectedUsers();

		$this->assertCount(count($expected), $result);
		sort($expected);
		sort($result);
		$this->assertEquals($expected, $result);
	}

	public function testGetConnectedUsersIfUserHasNot()
	{
		$this->setValueOfPrivateProperty($this->mapper, 'connectedUsers', []);
		$this->setupContactsStoreAPI();
		if (!Application::contactsStoreApiSupported()) {
			$this->markTestSkipped();
		}

		$group = \OC::$server->getGroupManager()->createGroup('group1');
		$group->addUser(\OC::$server->getUserManager()->get('derp'));

		$group2 = \OC::$server->getGroupManager()->createGroup('group2');
		$group2->addUser(\OC::$server->getUserManager()->get('autotest'));
		$group2->addUser(\OC::$server->getUserManager()->get('foo'));
		$group2->addUser(\OC::$server->getUserManager()->get('admin'));

		\OC::$server->getConfig()->setAppValue('core', 'shareapi_only_share_with_group_members', 'yes');

		$input1 = new PresenceEntity();
		$input1->setPresence('online');
		$input1->setUserid('foo');
		$input1->setLastActive(23434475);

		$input2 = new PresenceEntity();
		$input2->setPresence('online');
		$input2->setUserid('derp');
		$input2->setLastActive(23434475);

		$this->mapper->setPresence($input1);
		$this->mapper->setPresence($input2);

		$expected = ['foo'];

		$result = $this->mapper->getConnectedUsers();

		$this->assertCount(count($expected), $result);
		sort($expected);
		sort($result);
		$this->assertEquals($expected, $result);

		\OC::$server->getConfig()->setAppValue('core', 'shareapi_only_share_with_group_members', 'no');

		$group->removeUser(\OC::$server->getUserManager()->get('derp'));
		$group->delete();
		$group2->removeUser(\OC::$server->getUserManager()->get('autotest'));
		$group2->removeUser(\OC::$server->getUserManager()->get('admin'));
		$group2->delete();
	}

	public function updatePresenceProvider()
	{
		$input1 = new PresenceEntity();
		$input1->setPresence('online');
		$input1->setUserid('admin');
		$input1->setLastActive(1000);

		$input2 = new PresenceEntity();
		$input2->setPresence('online');
		$input2->setUserid('foo');
		$input2->setLastActive(1000);

		$input3 = new PresenceEntity(); // will go offline
		$input3->setPresence('xa');
		$input3->setUserid('derp');
		$input3->setLastActive(600);

		$input4 = new PresenceEntity(); // will go offline
		$input4->setPresence('chat');
		$input4->setUserid('derpina');
		$input4->setLastActive(400);

		$expStanza1 = new PresenceEntity();
		$expStanza1->setPresence('unavailable');
		$expStanza1->setFrom('derp', 'localhost/internal');
		$expStanza1->setTo('admin', 'localhost/internal');

		$expStanza2 = new PresenceEntity();
		$expStanza2->setPresence('unavailable');
		$expStanza2->setFrom('derpina', 'localhost/internal');
		$expStanza2->setTo('admin', 'localhost/internal');

		return [
			[
				[$input1, $input2, $input3, $input4],
				[
					[
						'userid' => 'admin',
						'presence' => 'online',
						'last_active' => '1000'
					],
					[
						'userid' => 'foo',
						'presence' => 'online',
						'last_active' => '1000'
					],
					[
						'userid' => 'derp',
						'presence' => 'xa',
						'last_active' => '600'
					],
					[
						'userid' => 'derpina',
						'presence' => 'chat',
						'last_active' => '400'
					]
				],
				['foo', 'derp', 'derpina'],
				[$expStanza1, $expStanza2],
				2,
				[
					[
						"from" => 'derp',
						"to" => "foo",
						"stanza" => '<presence type="unavailable" from="derp" to="foo" xmlns="jabber:client"/>'
					],
					[
						"from" => 'derpina',
						"to" => "foo",
						"stanza" => '<presence type="unavailable" from="derpina" to="foo" xmlns="jabber:client"/>'
					]
				]
			]
		];
	}

	/**
	 * @dataProvider updatePresenceProvider
	 * @param PresenceEntity[] $inputs
	 * @param array $expInput
	 * @param array $expConnectedUsers
	 * @param PresenceEntity[] $expNewContent
	 * @param int $expNewContentCount
	 * @param array $expStanzasToSend
	 */
	public function testUpdatePresence($inputs, $expInput, $expConnectedUsers, $expNewContent, $expNewContentCount, $expStanzasToSend)
	{
		$this->setupContactsStoreAPI();
		global $time;
		$time = 1000;
		foreach ($inputs as $input) {
			$this->mapper->setPresence($input);
		}
		$this->assertArrayDbResultsEqual($expInput, $this->fetchAllAsArray(), ['userid', 'presence', 'last_active']);

		$connectedUsers = $this->mapper->getConnectedUsers();
		sort($expConnectedUsers);
		sort($connectedUsers);
		$this->assertEquals($expConnectedUsers, $connectedUsers); // before cleaning

		$this->mapper->updatePresence();

		$this->assertEquals($expNewContentCount, $this->newContentContainer->getCount());
		$newContent = $this->newContentContainer->getStanzas();
		sort($expNewContent);
		sort($newContent);
		$this->assertObjectDbResultsEqual($expNewContent, $newContent, ['userid', 'presence', 'lastActive', 'to', 'from']);
		$this->assertEquals(0, $this->newContentContainer->getCount()); // stanzas will be removed once fetched

		$stanzasToSend = $this->fetchAllAsArray('*PREFIX*ojsxc_stanzas');

		$this->assertArrayDbResultsEqual($expStanzasToSend, $stanzasToSend, ['to', 'from', 'stanza']);
	}

	public function setActiveProvider()
	{
		$input1 = new PresenceEntity();
		$input1->setPresence('online');
		$input1->setUserid('admin');
		$input1->setLastActive(1000);

		$input2 = new PresenceEntity();
		$input2->setPresence('unavailable');
		$input2->setUserid('foo');
		$input2->setLastActive(1000);

		$input3 = new PresenceEntity(); // will go offline
		$input3->setPresence('xa');
		$input3->setUserid('derp');
		$input3->setLastActive(600);

		$input4 = new PresenceEntity(); // will go offline
		$input4->setPresence('chat');
		$input4->setUserid('derpina');
		$input4->setLastActive(400);

		return [
			[
				[$input1, $input2, $input3, $input4],
				[
					[
						'userid' => 'admin',
						'presence' => 'online',
						'last_active' => '1010',
					],
					[
						'userid' => 'foo',
						'presence' => 'unavailable',
						'last_active' => '1000',
					],
					[
						'userid' => 'derp',
						'presence' => 'xa',
						'last_active' => '1020',
					],
					[
						'userid' => 'derpina',
						'presence' => 'chat',
						'last_active' => '400',
					],
				]
			]
		];
	}

	/**
	 * @dataProvider  setActiveProvider
	 */
	public function testSetActive($inputs, $expected)
	{
		global $time;

		foreach ($inputs as $input) {
			$this->mapper->setPresence($input);
		}
		$time = 1010;
		$this->mapper->setActive('admin');
		$time = 1020;
		$this->mapper->setActive('derp');

		$result = $this->fetchAllAsArray();

		$this->assertArrayDbResultsEqual($expected, $result, ['userid', 'last_active', 'presence']);
	}

	public function deletePresenceProvider()
	{
		$input1 = new PresenceEntity();
		$input1->setPresence('online');
		$input1->setUserid('admin');
		$input1->setLastActive(1000);

		$input2 = new PresenceEntity();
		$input2->setPresence('unavailable');
		$input2->setUserid('foo');
		$input2->setLastActive(1000);

		$input3 = new PresenceEntity();
		$input3->setPresence('xa');
		$input3->setUserid('derp');
		$input3->setLastActive(600);

		$input4 = new PresenceEntity();
		$input4->setPresence('chat');
		$input4->setUserid('derpina');
		$input4->setLastActive(400);

		return [
			[
				[$input1, $input2, $input3, $input4],
				[
					[
						'userid' => 'foo',
						'presence' => 'unavailable',
						'last_active' => '1000',
					],
					[
						'userid' => 'derp',
						'presence' => 'xa',
						'last_active' => '600',
					],
					[
						'userid' => 'derpina',
						'presence' => 'chat',
						'last_active' => '400',
					],
				]
			]
		];
	}

	/**
	 * @dataProvider  deletePresenceProvider
	 */

	public function testDeletePresence($inputs, $expected)
	{
		foreach ($inputs as $input) {
			$this->mapper->setPresence($input);
		}

		$this->mapper->deletePresence('admin');

		$result = $this->fetchAllAsArray();

		$this->assertArrayDbResultsEqual($expected, $result, ['userid', 'last_active', 'presence']);
	}
}
