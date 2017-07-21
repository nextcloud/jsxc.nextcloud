<?php

namespace OCA\OJSXC\Middleware;

use OCP\AppFramework\Middleware;
use OCP\IConfig;
use OCP\IRequest;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCA\OJSXC\Exceptions\SecurityException;

class ExternalApiMiddleware extends Middleware
{
   private $request;

   private $config;

   private $rawPost;

   public function __construct(IRequest $request, IConfig $config, $rawPost)
   {
      $this->request = $request;
      $this->config = $config;
      $this->rawPost = $rawPost;
   }

   public function beforeController($controller, $methodName)
   {
      $controllerName = get_class($controller);

      if ($controllerName !== 'OCA\OJSXC\Controller\ExternalApiController') {
         return;
      }

      $apiSecret = $this->config->getAppValue('ojsxc', 'apiSecret');
      $jsxcSignatureHeader = $this->request->getHeader('X-JSXC-SIGNATURE');

      // check if we have a signature
      if (! isset($jsxcSignatureHeader)) {
         throw new SecurityException('HTTP header "X-JSXC-Signature" is missing.');
      } elseif (! extension_loaded('hash')) {
         throw new SecurityException('Missing "hash" extension to check the secret code validity.');
      } elseif (! $apiSecret) {
         throw new SecurityException('Missing secret.');
      }

      // check if the algo is supported
      list($algo, $hash) = explode('=', $jsxcSignatureHeader, 2) + array( '', '' );
      if (! in_array($algo, hash_algos(), true)) {
         throw new SecurityException("Hash algorithm '$algo' is not supported.");
      }

      // check if the key is valid
      if ($hash !== hash_hmac($algo, $this->rawPost, $apiSecret)) {
         throw new SecurityException('Signature does not match.');
      }
   }

   public function afterException($controller, $methodName, \Exception $exception)
   {
      //@TODO filter exception, because this will fetch all exceptions from all controllers in ojsxc
      return new JSONResponse(array(
         'result' => 'error',
         'data' => array(
            'msg' => $exception->getMessage()
         )
      ), Http::STATUS_INTERNAL_SERVER_ERROR);
   }
}
