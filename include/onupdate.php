<?php

defined('XOOPS_ROOT_PATH') || exit('Restricted access.');
echo '' . XOOPS_ROOT_PATH . '<br>';
// referer check
$ref = xoops_getenv('HTTP_REFERER');
if ($ref == '' || strpos($ref, XOOPS_URL . '/modules/system/admin.php') === 0) {
    $moduleDirName = basename(dirname(__DIR__));
    require_once __DIR__ . '/installscript.php';

    eval('xoops_module_install_' . $moduleDirName . '();
        ');
}
