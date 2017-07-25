<?php

namespace OCA\OJSXC\Controller;

use OCP\IConfig;
use OCP\IRequest;
use OCP\IUserSession;
use OCP\IURLGenerator;
use OCP\ILogger;
use OCP\IUser;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http;
use OCA\OJSXC\Exceptions\Exception;
use OCA\OJSXC\IDataRetriever;
use PHPUnit\Framework\TestCase;

class ManagedServerControllerTest extends TestCase
{
   private $request;
   private $urlGenerator;
   private $config;
   private $userSession;
   private $logger;
   private $dataRetriever;
   private $registrationUrl;

   private $apiUrl;
   private $apiSecret;
   private $userId;

   private $managedServerController;

   public function setUp() {
      parent::setUp();

      $this->request = $this->createMock(IRequest::class);
      $this->urlGenerator = $this->createMock(IURLGenerator::class);
      $this->config = $this->createMock(IConfig::class);
      $this->userSession = $this->createMock(IUserSession::class);
      $this->logger = $this->createMock(ILogger::class);
      $this->dataRetriever = $this->createMock(IDataRetriever::class);
      $this->registrationUrl = '';

      $this->apiUrl = 'https://localhost/api';
      $this->apiSecret = 'dummySecret';
      $this->userId = 'dummyUser';

      $this->urlGenerator
         ->expects($this->once())
         ->method('linkToRouteAbsolute')
         ->with('ojsxc.externalApi.index')
         ->willReturn($this->apiUrl);
      $this->config
         ->expects($this->once())
         ->method('getAppValue')
         ->with('ojsxc', 'apiSecret')
         ->willReturn($this->apiSecret);
      $this->userSession
         ->expects($this->once())
         ->method('getUser')
         ->willReturn($this->createUserMock($this->userId));

      $this->managedServerController = new ManagedServerController(
         'ojsxc',
         $this->request,
         $this->urlGenerator,
         $this->config,
         $this->userSession,
         $this->logger,
         $this->dataRetriever,
         $this->registrationUrl
      );
   }

   public function testRegisterWithoutPromoCode() {
      $this->doSuccessfulRegister();
   }

   public function testRegisterWithPromoCode() {
      $this->doSuccessfulRegister('asdflkj3j9sdjkfj3bas', 'asdflkj3j9sdjkfj3bas');
   }

   public function testRegisterWithInvalidPromotionCode() {
      $this->doSuccessfulRegister('as3-xs<>#');
   }

   public function testRegisterWithUnavailableEndpoint() {
      $this->logger
         ->expects($this->once())
         ->method('warning')
         ->with('RMS: Abort with message: Couldn\'t reach the registration server');
      $this->dataRetriever
         ->expects($this->once())
         ->method('fetchUrl')
         ->willReturn(['body' => false]);

      $return = $this->managedServerController->register();

      $this->assertEquals('error', $return->getData()['result']);
      $this->assertEquals(500, $return->getStatus());
   }

   public function testRegisterWithInvalidJson() {
      $this->logger
         ->expects($this->once())
         ->method('warning')
         ->with('RMS: Abort with message: Couldn\'t parse the response. Response code: 123');
      $this->dataRetriever
         ->expects($this->once())
         ->method('fetchUrl')
         ->willReturn(['body' => '{"": "}', 'headers' => ['response_code' => 123]]);

      $return = $this->managedServerController->register();

      $this->assertEquals('error', $return->getData()['result']);
      $this->assertEquals(500, $return->getStatus());
   }

   public function testRegisterWithErrorResponse() {
      $this->logger
         ->expects($this->once())
         ->method('warning')
         ->with('RMS: Abort with message: foobar');
      $this->logger
         ->expects($this->once())
         ->method('info')
         ->with('RMS: Response code: 123');
      $this->dataRetriever
         ->expects($this->once())
         ->method('fetchUrl')
         ->willReturn(['body' => '{"message": "foobar"}', 'headers' => ['response_code' => 123]]);

      $return = $this->managedServerController->register();

      $this->assertEquals('error', $return->getData()['result']);
      $this->assertEquals(500, $return->getStatus());
   }

   public function testRegisterWithBadBoshUrl() {
      $this->doRegisterFailWithBoshUrl('http://localhost/http-bind');
      $this->setUp();
      $this->doRegisterFailWithBoshUrl('https://localhost/http-bind/foo');
      $this->setUp();
      $this->doRegisterFailWithBoshUrl('https://localhost/eval?/http-bind');
      $this->setUp();
      $this->doRegisterFailWithBoshUrl('https://localhost/eval#/http-bind');
      $this->setUp();
      $this->doRegisterFailWithBoshUrl('/localhost/http-bind');
   }

   public function testRegisterWithBadDomain() {
      $this->doRegisterFailWithDomain('--this-is-no.domain');
      $this->setUp();
      $this->doRegisterFailWithDomain('foo bar');
      $this->setUp();
      $this->doRegisterFailWithDomain('localhost/bad');
      $this->setUp();
      $this->doRegisterFailWithDomain('local?host');
      $this->setUp();
      $this->doRegisterFailWithDomain('foo.');
   }

   private function doSuccessfulRegister($promotionCode = '', $expectedPromotionCode = null) {
      $this->dataRetriever
         ->expects($this->once())
         ->method('fetchUrl')
         ->with($this->registrationUrl, [
              'apiUrl' => $this->apiUrl,
              'apiSecret' => $this->apiSecret,
              'apiVersion' => 1,
              'userId' => $this->userId,
              'promotionCode' => $expectedPromotionCode
         ])
         ->willReturn([
            'body' => '{"boshUrl":"https://localhost/http-bind","domain":"localhost.xyz","externalServices":["https://localhost"]}',
            'headers' => ['response_code' => 200]
         ]);

      $return = $this->managedServerController->register($promotionCode);

      $this->assertTrue(is_array($return), 'The return value is no array.');
      $this->assertEquals('success', $return['result']);
   }

   private function doRegisterFailWithBoshUrl($boshUrl) {
      $this->logger
         ->method('warning')
         ->with('RMS: Abort with message: Got a bad bosh URL');
      $this->dataRetriever
         ->expects($this->once())
         ->method('fetchUrl')
         ->willReturn([
            'body' => '{"boshUrl": "'.$boshUrl.'"}',
            'headers' => ['response_code' => 200]
         ]);

      $return = $this->managedServerController->register();

      $this->assertInstanceOf(JSONResponse::class, $return);
      $this->assertEquals('error', $return->getData()['result']);
      $this->assertEquals(500, $return->getStatus());
   }

   private function doRegisterFailWithDomain($domain) {
      $this->logger
         ->method('warning')
         ->with('RMS: Abort with message: Got a bad domain');
      $this->dataRetriever
         ->expects($this->once())
         ->method('fetchUrl')
         ->willReturn([
            'body' => '{"boshUrl": "https://localhost/http-bind", "domain": "'.$domain.'"}',
            'headers' => ['response_code' => 200]
         ]);

      $return = $this->managedServerController->register();

      $this->assertInstanceOf(JSONResponse::class, $return);
      $this->assertEquals('error', $return->getData()['result']);
      $this->assertEquals(500, $return->getStatus());
   }

   private function createUserMock($displayName) {
      $user = $this->createMock(IUser::class);

      $user
         ->method('getUID')
         ->willReturn(preg_replace('/ /', '-', $displayName));

      $user
         ->method('getDisplayName')
         ->willReturn($displayName);

      return $user;
   }
}
