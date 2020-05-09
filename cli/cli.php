<?php

$getopt = getopt('vtd:');
define("PATH", '/home/ramtv.ru/parser');
define("PATH_WWW", '/home/ramtv.ru/www');
define("DEFAULT_DOMAIN", 'https://ramtv.ru.pronskiy.ru');
define("CLASS_PATH", PATH . '/class/');
define("CONFIG", PATH . '/config.json');
define('DRY_RUN', isset($getopt['t']) ? true : false);
define('VERBOSE', isset($getopt['v']) ? true : false);
define('ARCHIVE_DATE_RANGE', isset($getopt['d']) ? $getopt['d'] : false);


require_once PATH . '/vendor/autoload.php';

// use \Carbon\Carbon;

foreach (['config', 'parser', 'exporter'] as $class) {
	require_once CLASS_PATH . $class . '.class.php';
}

new \Parser\Controller();
