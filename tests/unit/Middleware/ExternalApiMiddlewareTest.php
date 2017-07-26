<?php

namespace OCA\OJSXC\Middleware;

use OCA\OJSXC\Controller\SignatureProtectedApiController;
use OCA\OJSXC\Middleware\ExternalApiMiddleware;
use OCA\OJSXC\Exceptions\SecurityException;
use OCA\OJSXC\RawRequest;
use OCP\IRequest;
use OCP\IConfig;
use PHPUnit\Framework\TestCase;

class ExternalApiMiddlewareTest extends TestCase
{
	private $request;
	private $config;
	private $rawRequest;
	private $externalApiMiddleware;

	public function setUp()
	{
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->config = $this->createMock(IConfig::class);
		$this->rawRequest = $this->createMock(RawRequest::class);

		$this->externalApiMiddleware = new ExternalApiMiddleware(
		 $this->request,
		 $this->config,
		 $this->rawRequest
	  );
	}

	public function testBeforeControllerWithoutHeader()
	{
		$this->request
			   ->expects($this->once())
			   ->method('getHeader')
			   ->with('X-JSXC-SIGNATURE')
			   ->willReturn(null);

		$this->expectException(SecurityException::class);
		$this->expectExceptionMessage('HTTP header "X-JSXC-Signature" is missing.');

		$controller = $this->createMock(SignatureProtectedApiController::class);
		$this->externalApiMiddleware->beforeController($controller, 'someMethod');
	}

	public function testBeforeControllerWithoutSecret()
	{
		$this->request
			   ->expects($this->once())
			   ->method('getHeader')
			   ->with('X-JSXC-SIGNATURE')
			   ->willReturn('foo=bar');
		$this->config
			   ->expects($this->once())
			   ->method('getAppValue')
			   ->with('ojsxc', 'apiSecret')
			   ->willReturn(null);

		$this->expectException(SecurityException::class);
		$this->expectExceptionMessage('Missing secret.');

		$controller = $this->createMock(SignatureProtectedApiController::class);
		$this->externalApiMiddleware->beforeController($controller, 'someMethod');
	}

	public function testBeforeControllerWithUnsupportedAlgo()
	{
		$this->request
			   ->expects($this->once())
			   ->method('getHeader')
			   ->with('X-JSXC-SIGNATURE')
			   ->willReturn('foo=bar');
		$this->config
			   ->expects($this->once())
			   ->method('getAppValue')
			   ->with('ojsxc', 'apiSecret')
			   ->willReturn('secret');

		$this->expectException(SecurityException::class);
		$this->expectExceptionMessage('Hash algorithm \'foo\' is not supported.');

		$controller = $this->createMock(SignatureProtectedApiController::class);
		$this->externalApiMiddleware->beforeController($controller, 'someMethod');
	}

	public function testBeforeControllerWithInvalidHeaderFormat()
	{
		$this->request
			   ->expects($this->once())
			   ->method('getHeader')
			   ->with('X-JSXC-SIGNATURE')
			   ->willReturn('foobar');
		$this->config
			   ->expects($this->once())
			   ->method('getAppValue')
			   ->with('ojsxc', 'apiSecret')
			   ->willReturn('secret');

		$this->expectException(SecurityException::class);
		$this->expectExceptionMessage('Hash algorithm \'foobar\' is not supported.');

		$controller = $this->createMock(SignatureProtectedApiController::class);
		$this->externalApiMiddleware->beforeController($controller, 'someMethod');
	}

	public function testBeforeControllerWithInvalidHeader()
	{
		$this->request
			   ->expects($this->once())
			   ->method('getHeader')
			   ->with('X-JSXC-SIGNATURE')
			   ->willReturn('sha1=foobar');
		$this->config
			   ->expects($this->once())
			   ->method('getAppValue')
			   ->with('ojsxc', 'apiSecret')
			   ->willReturn('secret');
		$this->rawRequest
			   ->expects($this->once())
			   ->method('get')
			   ->willReturn('asdf');

		$this->expectException(SecurityException::class);
		$this->expectExceptionMessage('Signature does not match.');

		$controller = $this->createMock(SignatureProtectedApiController::class);
		$this->externalApiMiddleware->beforeController($controller, 'someMethod');
	}

	public function testBeforeControllerWithValidHeader()
	{
		$algo = 'sha1';
		$apiSecret = 'secret';
		$rawRequestBody = 'rawRequestBody';
		$hash = hash_hmac($algo, $rawRequestBody, $apiSecret);

		$this->request
			   ->expects($this->once())
			   ->method('getHeader')
			   ->with('X-JSXC-SIGNATURE')
			   ->willReturn($algo.'='.$hash);
		$this->config
			   ->expects($this->once())
			   ->method('getAppValue')
			   ->with('ojsxc', 'apiSecret')
			   ->willReturn($apiSecret);
		$this->rawRequest
			   ->expects($this->once())
			   ->method('get')
			   ->willReturn($rawRequestBody);

		$controller = $this->createMock(SignatureProtectedApiController::class);
		$this->externalApiMiddleware->beforeController($controller, 'someMethod');
	}
}
