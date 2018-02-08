<?php
namespace OCA\OJSXC\Controller;

use OCA\OJSXC\Db\Message;
use OCA\OJSXC\Db\Presence;
use OCA\OJSXC\Db\Stanza;
use OCA\OJSXC\Db\StanzaMapper;
use OCA\OJSXC\Http\XMPPResponse;
use OCA\OJSXC\StanzaHandlers\IQ;
use OCA\OJSXC\StanzaLogger;
use OCP\AppFramework\Db\DoesNotExistException;
use PHPUnit\Framework\TestCase;
use Sabre\Xml\Writer;
use PHPUnit_Framework_MockObject_MockObject;

class HttpBindControllerTest extends TestCase
{

	/**
	 * @var HttpBindController
	 */
	private $controller;

	/**
	 * @var PHPUnit_Framework_MockObject_MockObject
	 */
	private $stanzaMapper;

	/**
	 * @var PHPUnit_Framework_MockObject_MockObject
	 */
	private $iqHandler;

	/**
	 * @var PHPUnit_Framework_MockObject_MockObject
	 */
	private $messageHandler;

	/**
	 * @var PHPUnit_Framework_MockObject_MockObject
	 */
	private $lock;

	/**
	 * @var PHPUnit_Framework_MockObject_MockObject
	 */
	private $presenceHandler;

	/**
	 * @var PHPUnit_Framework_MockObject_MockObject
	 */
	private $presenceMapper;

	private $userId = 'john';

	/**
	 * @var PHPUnit_Framework_MockObject_MockObject
	 */
	private $newContentContainer;

	/**
	 * @var StanzaLogger
	 */
	private $stanzaLogger;

	public function setUp()
	{
		$this->stanzaLogger = $this->getMockBuilder('OCA\OJSXC\StanzaLogger')->disableOriginalConstructor()->getMock();
	}

	private function mockLock()
	{
		$this->lock->expects($this->any())
			->method('setLock')
			->will($this->returnValue(null));

		$this->lock->expects($this->any())
			->method('stillLocked')
			->will($this->returnValue(true));
	}

	/**
	 * Helper function to set up the controller. This can't be done in the setUp,
	 * since the requestBody is different for every test.
	 * @param $requestBody
	 */
	private function setUpController($requestBody)
	{
		$request = $this->getMockBuilder('OCP\IRequest')->disableOriginalConstructor()->getMock();
		$this->stanzaMapper = $this->getMockBuilder('OCA\OJSXC\Db\StanzaMapper')->disableOriginalConstructor()->getMock();
		$this->presenceMapper = $this->getMockBuilder('OCA\OJSXC\Db\PresenceMapper')->disableOriginalConstructor()->getMock();

		$this->iqHandler = $this->getMockBuilder('OCA\OJSXC\StanzaHandlers\IQ')->disableOriginalConstructor()->getMock();
		$this->messageHandler = $this->getMockBuilder('OCA\OJSXC\StanzaHandlers\Message')->disableOriginalConstructor()->getMock();
		$this->presenceHandler = $this->getMockBuilder('OCA\OJSXC\StanzaHandlers\Presence')->disableOriginalConstructor()->getMock();
		$this->lock = $this->getMockBuilder('OCA\OJSXC\ILock')->disableOriginalConstructor()->getMock();
		$this->newContentContainer = $this->getMockBuilder('OCA\OJSXC\NewContentContainer')->disableOriginalConstructor()->getMock();

		$logger = \OC::$server->getLogger();

		$this->controller = new HttpBindController(
			'ojsxc',
			$request,
			$this->userId,
			$this->stanzaMapper,
			$this->iqHandler,
			$this->messageHandler,
			'localhost',
			$this->lock,
			$logger,
			$this->presenceHandler,
			$this->presenceMapper,
			$requestBody,
			0,
			10,
			$this->newContentContainer,
			$this->stanzaLogger
		);
	}

	public function testNewContentContainerNoNew()
	{
		$this->setUpController('<body xmlns=\'http://jabber.org/protocol/httpbind\'/>');
		$this->mockLock();
		$ex = new DoesNotExistException('');
		$expResponse = new XMPPResponse($this->stanzaLogger);

		$this->newContentContainer->expects($this->once())
			->method('getCount')
			->will($this->returnValue(0));

		$this->newContentContainer->expects($this->never())
			->method('getStanzas');

		$this->stanzaMapper->expects($this->exactly(10))
				->method('findByTo')
				->with('john')
				->will($this->throwException($ex));

		$this->presenceMapper->expects($this->once())
				->method('setActive')
				->with('john');

		$response = $this->controller->index();
		$this->assertEquals($expResponse, $response);
	}

	public function testNewContentContainerNoNewWithDbResults()
	{
		$result = new Stanza('test');
		$this->setUpController('<body rid=\'897878797\' xmlns=\'http://jabber.org/protocol/httpbind\' sid=\'7862\'/>');
		$this->mockLock();

		$expResponse = new XMPPResponse($this->stanzaLogger, $result);

		$this->iqHandler->expects($this->never())
				->method('handle')
				->will($this->returnValue($result));

		$r1 = $this->getMockBuilder('OCA\OJSXC\Db\Stanza')->disableOriginalConstructor()->getMock();
		$r1->expects($this->once())
				->method('xmlSerialize')
				->will($this->returnCallback(function (Writer $writer) {
					$writer->write('test');
				}));

		$this->stanzaMapper->expects($this->once())
				->method('findByTo')
				->with('john')
				->will($this->returnValue([$r1]));

		$this->presenceMapper->expects($this->once())
				->method('setActive')
				->with('john');

		$this->newContentContainer->expects($this->once())
				->method('getCount')
				->will($this->returnValue(0));

		$this->newContentContainer->expects($this->never())
				->method('getStanzas');

		$response = $this->controller->index();
		$this->assertEquals($expResponse, $response);
		$this->assertEquals($expResponse->render(), $response->render());
	}


	public function testNewContentContainerWithNewWithDbResults()
	{
		$result = new Stanza('test');
		$this->setUpController('<body rid=\'897878797\' xmlns=\'http://jabber.org/protocol/httpbind\' sid=\'7862\'/>');
		$this->mockLock();

		$expResponse = new XMPPResponse($this->stanzaLogger, $result);

		$this->iqHandler->expects($this->never())
				->method('handle')
				->will($this->returnValue($result));

		$r1 = $this->getMockBuilder('OCA\OJSXC\Db\Stanza')->disableOriginalConstructor()->getMock();
		$r1->expects($this->once())
				->method('xmlSerialize')
				->will($this->returnCallback(function (Writer $writer) {
					$writer->write('test');
				}));

		$this->stanzaMapper->expects($this->once())
				->method('findByTo')
				->with('john')
				->will($this->returnValue([$r1]));

		$this->presenceMapper->expects($this->once())
				->method('setActive')
				->with('john');

		$this->newContentContainer->expects($this->once())
				->method('getCount')
				->will($this->returnValue(5));

		$testStanza = new Stanza();
		$testStanza->setFrom('derp@own.dev');
		$testStanza->setTo('admin@own.dev');

		$this->newContentContainer->expects($this->once())
				->method('getStanzas')
				->will($this->returnValue([$testStanza,$testStanza, $testStanza, $testStanza, $testStanza ]));

		$expResponse->write($testStanza);
		$expResponse->write($testStanza);
		$expResponse->write($testStanza);
		$expResponse->write($testStanza);
		$expResponse->write($testStanza);

		$response = $this->controller->index();
		$this->assertEquals($expResponse, $response);
		$this->assertEquals($expResponse->render(), $response->render());
	}


	/**
	 * When invalid XML, just start long polling.
	 * Note: this test will cause some errors in the owncloud.log:
	 * {"reqId":"HmbEV6qTWF68ii1G\/kz1","remoteAddr":"","app":"PHP","message":"XMLReader::read(): An Error Occured while reading at \/var\/www\/owncloud\/apps\/ojsxc\/vendor\/sabre\/xml\/lib\/Reader.php#66","level":0,"time":"2016-01-30T14:52:44+00:00","method":"--","url":"--"}
	 * {"reqId":"HmbEV6qTWF68ii1G\/kz1","remoteAddr":"","app":"PHP","message":"XMLReader::read(): An Error Occured while reading at \/var\/www\/owncloud\/apps\/ojsxc\/vendor\/sabre\/xml\/lib\/Reader.php#145","level":0,"time":"2016-01-30T14:52:44+00:00","method":"--","url":"--"}
	 */
	public function testInvalidXML()
	{
		$ex = new DoesNotExistException('');
		$expResponse = new XMPPResponse($this->stanzaLogger);

		$this->setUpController('<x>');
		$this->mockLock();
		$this->stanzaMapper->expects($this->exactly(10))
			->method('findByTo')
			->with('john')
			->will($this->throwException($ex));

		$this->presenceMapper->expects($this->once())
			->method('setActive')
			->with('john');

		$response = $this->controller->index();
		$this->assertEquals($expResponse, $response);
	}

	public function IQProvider()
	{
		$expStanza1 = new Stanza();
		$expStanza1->setStanza('<iq to="admin@localhost" type="result" id="2:sendIQ"><query xmlns="jabber:iq:roster"><item jid="derp@localhost" name="derp"></item></query></iq><iq to="admin@localhost" type="result" id="2:sendIQ"><query xmlns="jabber:iq:roster"><item jid="derp@localhost" name="derp"></item></query></iq><iq to="admin@localhost" type="result" id="2:sendIQ"><query xmlns="jabber:iq:roster"><item jid="derp@localhost" name="derp"></item></query></iq>');

		$result1 = new Stanza();
		$result1->setStanza('<iq to="admin@localhost" type="result" id="2:sendIQ"><query xmlns="jabber:iq:roster"><item jid="derp@localhost" name="derp"></item></query></iq>');

		$result2 = new Stanza();
		$result2->setStanza(null);
		$expStanza2 = new Stanza();
		$expStanza2->setStanza(null);
		return [
			[
				'<body rid=\'897878733\' xmlns=\'http://jabber.org/protocol/httpbind\' sid=\'7862\'><iq from=\'admin@localhost\' to=\'localhost\' type=\'get\' xmlns=\'jabber:client\' id=\'1:sendIQ\'><query xmlns=\'http://jabber.org/protocol/disco#info\' node=\'undefined#undefined\'/></iq><iq type=\'get\' xmlns=\'jabber:client\' id=\'2:sendIQ\'><query xmlns=\'jabber:iq:roster\'/></iq><iq type=\'get\' to=\'admin@localhost\' xmlns=\'jabber:client\' id=\'3:sendIQ\'><vCard xmlns=\'vcard-temp\'/></iq></body>',
				$result1,
				$expStanza1, // we ask for 3 IQ's thus return 3 values
				$this->once(),
				$this->exactly(3)
			],
			[
				'<body rid=\'897878734\' xmlns=\'http://jabber.org/protocol/httpbind\' sid=\'7862\'><iq from=\'admin@localhost\' to=\'localhost\' type=\'get\' xmlns=\'jabber:client\' id=\'1:sendIQ\'><query xmlns=\'http://jabber.org/protocol/disco#info\' node=\'undefined#undefined\'/></iq><iq type=\'get\' xmlns=\'jabber:client\' id=\'2:sendIQ\'><query xmlns=\'jabber:iq:roster\'/></iq><iq type=\'get\' to=\'admin@localhost\' xmlns=\'jabber:client\' id=\'3:sendIQ\'><vCard xmlns=\'vcard-temp\'/></iq></body>',
				$result2,
				$expStanza2,
				$this->once(),
				$this->exactly(3)
			]
		];
	}

	/**
	 * @dataProvider IQProvider
	 */
	public function testIQHandlerWhenNoDbResults($body, $result, $expected, $pollCount, $handlerCount)
	{
		$ex = new DoesNotExistException('');
		$this->setUpController($body);
		$this->mockLock();
		$expResponse = new XMPPResponse($this->stanzaLogger);
		$expResponse->write($expected);

		$this->iqHandler->expects($handlerCount)
			->method('handle')
			->will($this->returnValue($result));

		$this->stanzaMapper->expects($pollCount)
			->method('findByTo')
			->with('john')
			->will($this->throwException($ex));

		$this->presenceMapper->expects($this->once())
			->method('setActive')
			->with('john');

		$response = $this->controller->index();
		$this->assertEquals($expResponse, $response);
		$this->assertEquals($expResponse->render(), $response->render());
	}

	public function testDbResults()
	{
		$result = new Stanza('test');
		$this->setUpController('<body rid=\'897878797\' xmlns=\'http://jabber.org/protocol/httpbind\' sid=\'7862\'/>');
		$this->mockLock();

		$expResponse = new XMPPResponse($this->stanzaLogger, $result);

		$this->iqHandler->expects($this->never())
			->method('handle')
			->will($this->returnValue($result));

		$r1 = $this->getMockBuilder('OCA\OJSXC\Db\Stanza')->disableOriginalConstructor()->getMock();
		$r1->expects($this->once())
			->method('xmlSerialize')
			->will($this->returnCallback(function (Writer $writer) {
				$writer->write('test');
			}));

		$this->stanzaMapper->expects($this->once())
			->method('findByTo')
			->with('john')
			->will($this->returnValue([$r1]));

		$this->presenceMapper->expects($this->once())
			->method('setActive')
			->with('john');

		$response = $this->controller->index();
		$this->assertEquals($expResponse, $response);
		$this->assertEquals($expResponse->render(), $response->render());
	}

	public function testMessageNoDbHandler()
	{
		$body = '<body rid=\'897878959\' xmlns=\'http://jabber.org/protocol/httpbind\' sid=\'7862\'><message to=\'derp@own.dev\' type=\'chat\' id=\'1452960296859-msg\' xmlns=\'jabber:client\'><body>abc</body><request xmlns=\'urn:xmpp:receipts\'/></message></body>';
		$ex = new DoesNotExistException('');
		$this->setUpController($body);
		$this->mockLock();

		$expResponse = new XMPPResponse($this->stanzaLogger);

		$this->messageHandler->expects($this->once())
			->method('handle');

		$this->stanzaMapper->expects($this->exactly(10))
			->method('findByTo')
			->with('john')
			->will($this->throwException($ex));

		$this->presenceMapper->expects($this->once())
			->method('setActive')
			->with('john');

		$response = $this->controller->index();
		$this->assertEquals($expResponse, $response);
		$this->assertEquals($expResponse->render(), $response->render());
	}


	public function testMultipleMessageNoDbHandler()
	{
		$body = <<<XML
		<body rid='897878959' xmlns='http://jabber.org/protocol/httpbind' sid='7862'>
			<message to='derp@own.dev' type='chat' id='1452960296859-msg' xmlns='jabber:client'><body>abc</body></message>
			<message to='derp@own.dev' type='chat' id='1452960296860-msg' xmlns='jabber:client'><body>abc2</body></message>
			<message to='derp@own.dev' type='chat' id='1452960296861-msg' xmlns='jabber:client'><body>abc3</body></message>
		</body>
XML;
		$ex = new DoesNotExistException('');
		$this->setUpController($body);
		$this->mockLock();

		$expResponse = new XMPPResponse($this->stanzaLogger);
		$this->messageHandler->expects($this->any())
			->method('handle');

		$this->messageHandler->expects($this->exactly(3))
			->method('handle')
			->withConsecutive(
				$this->equalTo(
				[	'name' => '{jabber:client}message',
				'	value' => [
					'{jabber:client}body' => 'abc',
				],
				'attributes' => [
					'to' => 'derp@own.dev',
					'type' => 'chat',
					'id' => '1452960296859-msg',
				]]
				),
				$this->equalTo([	'name' => '{jabber:client}message',
					'value' => [
					'{jabber:client}body' => 'abc2',
				],	'attributes' => [
					'to' => 'derp@own.dev',
					'type' => 'chat',
					'id' => '1452960296860-msg',
				]]),
				$this->equalTo([	'name' => '{jabber:client}message',
					'value' => [
						'{jabber:client}body' => 'abc3',
					],	'attributes' => [
					'to' => 'derp@own.dev',
					'type' => 'chat',
					'id' => '1452960296861-msg',
				]])

			);

		$this->stanzaMapper->expects($this->exactly(10))
			->method('findByTo')
			->with('john')
			->will($this->throwException($ex));

		$this->presenceMapper->expects($this->once())
			->method('setActive')
			->with('john');

		$response = $this->controller->index();
		$this->assertEquals($expResponse, $response);
		$this->assertEquals($expResponse->render(), $response->render());
	}

	public function testMessageDbHandler()
	{
		$body = '<body rid=\'897878959\' xmlns=\'http://jabber.org/protocol/httpbind\' sid=\'7862\'><message to=\'derp@own.dev\' type=\'chat\' id=\'1452960296859-msg\' xmlns=\'jabber:client\'><body>abc</body><request xmlns=\'urn:xmpp:receipts\'/></message></body>';
		$this->setUpController($body);
		$this->mockLock();

		$expResponse = new XMPPResponse($this->stanzaLogger, new Stanza('test'));

		$this->messageHandler->expects($this->once())
			->method('handle');

		$r1 = $this->getMockBuilder('OCA\OJSXC\Db\Stanza')->disableOriginalConstructor()->getMock();
		$r1->expects($this->once())
			->method('xmlSerialize')
			->will($this->returnCallback(function (Writer $writer) {
				$writer->write('test');
			}));

		$this->stanzaMapper->expects($this->once())
			->method('findByTo')
			->with('john')
			->will($this->returnValue([$r1]));

		$this->presenceMapper->expects($this->once())
			->method('setActive')
			->with('john');

		$response = $this->controller->index();
		$this->assertEquals($expResponse, $response);
		$this->assertEquals($expResponse->render(), $response->render());
	}

	public function testPresenceReturnNothingHandler()
	{
		$body = "<body xmlns='http://jabber.org/protocol/httpbind'><presence xmlns='jabber:client'><show>chat</show></presence></body>";
		$ex = new DoesNotExistException('');
		$expResponse = new XMPPResponse($this->stanzaLogger);

		$this->setUpController($body);
		$this->mockLock();

		$this->presenceHandler->expects($this->once())
			->method('handle')
			->will($this->returnValue(null));

		$this->stanzaMapper->expects($this->exactly(10))
			->method('findByTo')
			->with('john')
			->will($this->throwException($ex));

		$this->presenceMapper->expects($this->once())
			->method('setActive')
			->with('john');

		$response = $this->controller->index();
		$this->assertEquals($expResponse, $response);
		$this->assertEquals($expResponse->render(), $response->render());
	}

	public function testPresenceHandler()
	{
		$body = "<body xmlns='http://jabber.org/protocol/httpbind'><presence xmlns='jabber:client'><show>chat</show></presence></body>";
		$ex = new DoesNotExistException('');
		$expResponse = new XMPPResponse($this->stanzaLogger);

		$pres1 = new Presence();
		$pres1->setPresence('online');
		$pres1->setUserid('admin');
		$pres1->setTo('admin@localhost');
		$pres1->setFrom('derp@localhot');

		$pres2 = new Presence();
		$pres2->setPresence('unavailable');
		$pres2->setUserid('herp');
		$pres2->setTo('admin@localhost');
		$pres2->setFrom('herp@localhot');

		$expResponse->write($pres1);
		$expResponse->write($pres2);

		$this->setUpController($body);
		$this->mockLock();

		$this->presenceHandler->expects($this->once())
			->method('handle')
			->will($this->returnValue([$pres1, $pres2]));

		$this->stanzaMapper->expects($this->never())
			->method('findByTo')
			->with('john')
			->will($this->throwException($ex));

		$this->presenceMapper->expects($this->once())
			->method('setActive')
			->with('john');

		$response = $this->controller->index();
		$this->assertEquals($expResponse, $response);
		$this->assertEquals($expResponse->render(), $response->render());
	}

	public function testBodyHandler()
	{
		$ex = new DoesNotExistException('');
		$body = '<body rid=\'897878985\' xmlns=\'http://jabber.org/protocol/httpbind\' sid=\'7862\'/>';
		$this->setUpController($body);
		$this->mockLock();
		$expResponse = new XMPPResponse($this->stanzaLogger);

		$this->stanzaMapper->expects($this->exactly(10))
			->method('findByTo')
			->with('john')
			->will($this->throwException($ex));

		$this->presenceMapper->expects($this->once())
			->method('setActive')
			->with('john');

		$response = $this->controller->index();
		$this->assertEquals($expResponse, $response);
		$this->assertEquals($expResponse->render(), $response->render());
	}
}
