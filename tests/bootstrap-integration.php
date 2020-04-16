<?php
$nc_root = __DIR__ . '/../../..';

@include 'bootstrap-config.development.php';

echo "Using ".realpath($nc_root)." as Nextcloud root.\n\n";

try {
	require_once $nc_root . '/tests/bootstrap.php';
} catch (Exception $ex) {
	require_once $nc_root . '/lib/base.php';
}
require_once __DIR__ . '/../vendor/autoload.php';
