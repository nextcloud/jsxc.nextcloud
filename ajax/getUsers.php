<?php

OCP\User::checkLoggedIn();
OCP\JSON::callCheck();

header('Content-Type: application/json; charset=utf-8');

$limit = 10;
$offset = 0;

$config = \OC::$server->getConfig();
$preferMail = $config->getAppValue('ojsxc', 'xmppPreferMail');
$preferMail = $preferMail === true || $preferMail === 'true';

$userManager = \OC::$server->getUserManager();
$users = $userManager->searchDisplayName((string) $_GET['search'], $limit, $offset);
$response = array();

foreach($users as $user) {
   $uid = $user->getUID();
   $index = $uid;

   if ($preferMail) {
      $mail = OCP\Config::getUserValue($uid, 'settings', 'email');

      if (!empty($mail)) {
         $index = $mail;
      }
   }

   $response[$index] = $user->getDisplayName();
}

echo json_encode($response);
