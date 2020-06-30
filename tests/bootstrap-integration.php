<?php
define('PHPUNIT_RUN', 1);

$nc_root = __DIR__ . '/../../..';

@include 'bootstrap-config.development.php';

require_once $nc_root . '/lib/base.php';
require_once __DIR__ . '/../vendor/autoload.php';

\OC::$composerAutoloader->addPsr4('OCA\\OJSXC\\Tests\\', __DIR__, true);
