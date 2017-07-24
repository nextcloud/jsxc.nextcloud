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

function stringOrEmpty($s) {
   if(empty($s)) {
      return "<empty>";
   } else {
      return $s;
   }
}

function getUsername() {
   if(!empty($_POST['username'])) {
      if(!empty($_POST['domain'])) {
         return $_POST['username'] . "@" . $_POST['domain'];
      } else {
         return $_POST['username'];
      }
   } else {
      abort('No username provided');
   }
}

function checkPassword() {
   $currentUser = null;

   \OCP\Util::writeLog('ojsxc', 'ExAPI: Check password for user: '.stringOrEmpty($_POST['username'])."@".stringOrEmpty($_POST['domain']), \OCP\Util::INFO );

   if(!empty($_POST['password']) && !empty($_POST['username'])) {
      if(!empty($_POST['domain'])) {
         $loggedIn = \OC::$server->getUserSession()->login($_POST['username'] . "@" . $_POST['domain'], $_POST['password']);
      }
      if(!$loggedIn) {
         $loggedIn = \OC::$server->getUserSession()->login($_POST['username'], $_POST['password']);
      }
   }
   if ($loggedIn) {
      $currentUser = \OC::$server->getUserSession()->getUser();
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
   \OCP\Util::writeLog('ojsxc', 'ExAPI: Check if "'.stringOrEmpty($_POST['username'])."@".stringOrEmpty($_POST['domain']).'" exists', \OCP\Util::INFO );

   $isUser = false;

   if(!empty($_POST['username'])) {
      if(!empty($_POST['domain'])) {
         $isUser = \OC::$server->getUserManager()->userExists($_POST['username'] . "@" . $_POST['domain']);
      }
      if(!$isUser) {
         $isUser = \OC::$server->getUserManager()->userExists($_POST['username']);
      }
   }

   echo json_encode(array(
      'result' => 'success',
      'data' => array(
         'isUser' => $isUser
      )
   ));
}

function sharedRoster() {
   $username = getUsername();
   $roster = [];

   $userGroups = \OC::$server->getGroupManager()->getUserIdGroups($username);

   foreach($userGroups as $userGroup) {
      foreach($userGroup->getUsers() as $user) {
         $uid = $user->getUID();

         if(!$roster[$uid]) {
            $roster[$uid] = [
               'name' => $user->getDisplayName(),
               'groups' => []
            ];
         }

         $roster[$uid]['groups'][] = $userGroup->getDisplayName();
      }
   }

   echo json_encode(array(
      'result' => 'success',
      'data' => array(
         'sharedRoster' => $roster
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
   case 'sharedroster':
      sharedRoster();
      break;
   default:
      abort( "Unsupported operation.");
}
