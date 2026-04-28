<?php

$publicIndex = __DIR__.'/public/index.php';

$_SERVER['SCRIPT_FILENAME'] = $publicIndex;
$_SERVER['SCRIPT_NAME'] = '/billing/index.php';
$_SERVER['PHP_SELF'] = '/billing/index.php';

require $publicIndex;
