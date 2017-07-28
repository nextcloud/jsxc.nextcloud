<?php

namespace OCA\OJSXC\Controller;

use OCP\AppFramework\Controller;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IUserSession;
use OCP\IURLGenerator;
use OCP\ILogger;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http;
use OCA\OJSXC\Exceptions\Exception;
use OCA\OJSXC\IDataRetriever;
use OCP\Security\ISecureRandom;

class ManagedServerController extends Controller
{
	private $urlGenerator;
	private $config;
	private $userSession;
	private $logger;
	private $dataRetriever;
	private $registrationUrl;
	private $random;

	public function __construct(
	$appName,
								 IRequest $request,
								 IURLGenerator $urlGenerator,
								 IConfig $config,
								 IUserSession $userSession,
								 ILogger $logger,
								 IDataRetriever $dataRetriever,
								 ISecureRandom $random,
								 $registrationUrl
   ) {
		parent::__construct($appName, $request);

		$this->urlGenerator = $urlGenerator;
		$this->config = $config;
		$this->userSession = $userSession;
		$this->logger = $logger;
		$this->dataRetriever = $dataRetriever;
		$this->random = $random;
		$this->registrationUrl = $registrationUrl;
	}

	public function register($promotionCode = null)
	{
		$requestId = $this->random->generate(
			20,
			ISecureRandom::CHAR_LOWER.ISecureRandom::CHAR_UPPER.ISecureRandom::CHAR_DIGITS
		);
		$promotionCode = (preg_match('/^[0-9a-z]+$/i', $promotionCode)) ? $promotionCode : null;
		$registrationResult = false;

		try {
			$registrationResult = $this->doRegistration($promotionCode, $requestId);
		} catch (\Exception $exception) {
			$this->logger->warning('RMS: Abort with message: '.$exception->getMessage());

			return new JSONResponse([
			'result' => 'error',
			'data' => [
			   'msg' => $exception->getMessage(),
				'requestId' => $requestId
			]
		 ], Http::STATUS_INTERNAL_SERVER_ERROR);
		}

		if ($registrationResult) {
			return [
			'result' => 'success',
			'data' => []
		 ];
		}
	}

	private function doRegistration($promotionCode, $requestId)
	{
		$apiUrl = $this->urlGenerator->linkToRouteAbsolute('ojsxc.externalApi.index');
		$apiSecret = $this->config->getAppValue('ojsxc', 'apiSecret');
		$userId = $this->userSession->getUser()->getUID();

		$data = [
		  'apiUrl' => $apiUrl,
		  'apiSecret' => $apiSecret,
		  'apiVersion' => 1,
		  'userId' => $userId,
		  'promotionCode' => $promotionCode
	  ];

		$response = $this->dataRetriever->fetchUrl($this->registrationUrl.'?rid='.$requestId, $data);

		if ($response['body'] === false) {
			throw new Exception('Couldn\'t reach the registration server');
		}

		$responseJSON = json_decode($response['body']);

		if ($responseJSON === null) {
			throw new Exception('Couldn\'t parse the response. Response code: '.$response['headers']['response_code']);
		}

		if ($response['headers']['response_code'] !== 200) {
			$this->logger->info('RMS: Response code: '.$response['headers']['response_code']);

			throw new Exception(htmlspecialchars($responseJSON->message));
		}

		if (!preg_match('#^https://#', $responseJSON->boshUrl) ||
		  !preg_match('#/http-bind/?$#', $responseJSON->boshUrl) ||
		  preg_match('/\?|#/', $responseJSON->boshUrl)) {
			throw new Exception('Got a bad bosh URL');
		}

		if (!preg_match('/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i', $responseJSON->domain) ||
		  !preg_match('/^.{1,253}$/', $responseJSON->domain) ||
		  !preg_match('/^[^\.]{1,63}(\.[^\.]{1,63})*$/', $responseJSON->domain)) {
			throw new Exception('Got a bad domain');
		}

		$this->config->setAppValue('ojsxc', 'serverType', 'managed');
		$this->config->setAppValue('ojsxc', 'boshUrl', $responseJSON->boshUrl);
		$this->config->setAppValue('ojsxc', 'xmppDomain', $responseJSON->domain);
		$this->config->setAppValue('ojsxc', 'timeLimitedToken', 'true');
		$this->config->setAppValue('ojsxc', 'xmppPreferMail', 'false');
		$this->config->setAppValue('ojsxc', 'managedServer', 'registered');
		$this->config->setAppValue('ojsxc', 'externalServices', implode('|', $responseJSON->externalServices));

		return true;
	}
}
