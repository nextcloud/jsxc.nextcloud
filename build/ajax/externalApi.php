<?php

header('Content-Type: application/json; charset=utf-8');

$config = \OC::$server->getConfig();
$apiSecret = $config->getAppValue('ojsxc', 'apiSecret');

function abort($msg) {
   http_response_code(500);

   \OCP\Util::writeLog('ojsxc', 'ExAPI: Abort with message: '.$msg, \OCP\Util::WARN );

   die(json_encode(array(
      'result' => 'error',
      'data' => array(
         'msg' => $msg
      ),
   )));
}

function checkPassword() {
   $currentUser = null;

   \OCP\Util::writeLog('ojsxc', 'ExAPI: Check password for user: '.$_POST['username'], \OCP\Util::INFO );

   if(!empty($_POST['password']) && !empty($_POST['username'])) {
      $currentUser = \OC::$server->getUserManager()->checkPassword($_POST['username'], $_POST['password']);
   }

   if (!$currentUser) {
       echo json_encode(array(
               'result' => 'noauth',
       ));
       exit();
   }

   $data = array();
   $data ['uid'] = $currentUser->getUID();

   echo json_encode(array(
      'result' => 'success',
      'data' => $data,
   ));
}

function isUser() {
   \OCP\Util::writeLog('ojsxc', 'ExAPI: Check if "'.$_POST['username'].'" exists', \OCP\Util::INFO );

   $isUser = false;

   if(!empty($_POST['username'])) {
      $isUser = \OC::$server->getUserManager()->userExists($_POST['username']);
   }

   echo json_encode(array(
      'result' => 'success',
      'data' => array(
         'isUser' => $isUser
      )
   ));
}

// check if we have a signature
if ( ! isset( $_SERVER[ 'HTTP_X_JSXC_SIGNATURE' ] ) )
        abort( 'HTTP header "X-JSXC-Signature" is missing.' );
else if ( ! extension_loaded( 'hash' ) )
        abort( 'Missing "hash" extension to check the secret code validity.' );
else if ( ! $apiSecret)
        abort( 'Missing secret.' );

// check if the algo is supported
list( $algo, $hash ) = explode( '=', $_SERVER[ 'HTTP_X_JSXC_SIGNATURE' ], 2 ) + array( '', '' );
if ( ! in_array( $algo, hash_algos(), TRUE ) )
        abort( "Hash algorithm '$algo' is not supported." );

// check if the key is valid
$rawPost = file_get_contents( 'php://input' );
if ( $hash !== hash_hmac( $algo, $rawPost, $apiSecret ) )
        abort( 'Signature does not match.' );

switch($_POST['operation']) {
   case 'auth':
      checkPassword();
      break;
   case 'isuser':
      isUser();
      break;
   default:
      abort( "Unsupported operation." );
}
