<?php

header('Content-Type: application/json; charset=utf-8');

$id = $_GET['id'];
if (empty($id)) {
	echo  json_encode(array(
		'status' => 'error',
		'message' => 'no id received'
	));
	exit();
}

$view = \OC\Files\Filesystem::getView();

try {
	$path = $view->getPath($id);
} catch (\Exception $e) {
	echo  json_encode(array(
		'status' => 'error',
		'message' => 'file does not exist'
	));
	exit();
}

$path = $view->getPath($id);
if ($view->is_file($path) && $view->isReadable($path)) {
	$secret = \OC::$server->getConfig()->getSystemValue('secret');
	$instanceID = \OC::$server->getConfig()->getSystemValue('instanceid');
	$chatRoomPassword = hash('sha512', 'chatroom-password'.$instanceID.$secret.$id);
	$chatRoomName = hash('sha512','chatroom-name'.$instanceID.$secret.$id);
	echo  json_encode(array(
		'status' => 'success',
		'password' => $chatRoomPassword,
		'name' => $chatRoomName
	));
} else {
	echo  json_encode(array(
		'status' => 'error',
		'message' => 'user unauthorised to view file'
	));
}