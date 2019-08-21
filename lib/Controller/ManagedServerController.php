<?php

namespace OCA\OJSXC\Controller;

use OCP\AppFramework\Controller;
use OCP\IRequest;
use OCP\IUserSession;
use OCP\IURLGenerator;
use OCP\ILogger;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http;
use OCA\OJSXC\Exceptions\Exception;
use OCA\OJSXC\IDataRetriever;
use OCA\OJSXC\AppInfo\Application;
use OCA\OJSXC\Config;
use OCP\Security\ISecureRandom;
use OCP\App\IAppManager;

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
		Config $config,
		IUserSession $userSession,
		ILogger $logger,
		IDataRetriever $dataRetriever,
		ISecureRandom $random,
		IAppManager $appManager,
		$registrationUrl
   ) {
		parent::__construct($appName, $request);

		$this->urlGenerator = $urlGenerator;
		$this->config = $config;
		$this->userSession = $userSession;
		$this->logger = $logger;
		$this->dataRetriever = $dataRetriever;
		$this->random = $random;
		$this->appManager = $appManager;
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
		$apiSecret = $this->config->getAppValue(Config::API_SECRET);
		$userId = $this->userSession->getUser()->getUID();
		$appVersion = $this->appManager->getAppVersion('ojsxc');

		$data = [
		  'apiUrl' => $apiUrl,
		  'apiSecret' => $apiSecret,
		  'apiVersion' => 1,
		  'userId' => $userId,
		  'appVersion' => $appVersion,
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

		$this->config->setAppValue(Config::XMPP_SERVER_TYPE, Application::MANAGED);
		$this->config->setAppValue(Config::XMPP_URL, $responseJSON->boshUrl);
		$this->config->setAppValue(Config::XMPP_DOMAIN, $responseJSON->domain);
		$this->config->setAppValue(Config::XMPP_USE_TIME_LIMITED_TOKEN, 1);
		$this->config->setAppValue(Config::XMPP_PREFER_MAIL, 0);
		$this->config->setAppValue(Config::MANAGED_SERVER_STATUS, 'registered');
		$this->config->setAppValue(Config::EXTERNAL_SERVICES, implode('|', $responseJSON->externalServices));

		$this->config->setAppValue(Config::ICE_URL, implode(', ', $responseJSON->iceServers->urls));
		$this->config->setAppValue(Config::ICE_USERNAME, $responseJSON->iceServers->username);
		$this->config->setAppValue(Config::ICE_SECRET, $responseJSON->iceServers->credential);
		$this->config->setAppValue(Config::ICE_CREDENTIAL, '');
		$this->config->setAppValue(Config::ICE_TTL, 3600 * 24);

		return true;
	}
}
