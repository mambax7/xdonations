<?php

defined('XOOPS_ROOT_PATH') || exit('Restricted access.');
echo '' . XOOPS_ROOT_PATH . '<br>';
// referer check
$ref = xoops_getenv('HTTP_REFERER');
if ('' == $ref || 0 === strpos($ref, XOOPS_URL . '/modules/system/admin.php')) {
    $moduleDirName = basename(dirname(__DIR__));
    require_once __DIR__ . '/installscript.php';

    eval('xoops_module_install_' . $moduleDirName . '();
        ');
}
