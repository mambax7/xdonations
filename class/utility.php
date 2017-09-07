<?php

/**
 * Class MyalbumUtil
 */
class XdonationsUtility extends XoopsObject
{
    /**
     * Function responsible for checking if a directory exists, we can also write in and create an index.html file
     *
     * @param string $folder The full path of the directory to check
     *
     * @return void
     */
    public static function createFolder($folder)
    {
        //        try {
        //            if (!mkdir($folder) && !is_dir($folder)) {
        //                throw new \RuntimeException(sprintf('Unable to create the %s directory', $folder));
        //            } else {
        //                file_put_contents($folder . '/index.html', '<script>history.go(-1);</script>');
        //            }
        //        }
        //        catch (Exception $e) {
        //            echo 'Caught exception: ', $e->getMessage(), "\n", '<br>';
        //        }
        try {
            if (!file_exists($folder)) {
                if (!mkdir($folder) && !is_dir($folder)) {
                    throw new \RuntimeException(sprintf('Unable to create the %s directory', $folder));
                }
                file_put_contents($folder . '/index.html', '<script>history.go(-1);</script>');
            }
        } catch (Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n", '<br>';
        }
    }

    /**
     * @param $file
     * @param $folder
     * @return bool
     */
    public static function copyFile($file, $folder)
    {
        return copy($file, $folder);
        //        try {
        //            if (!is_dir($folder)) {
        //                throw new \RuntimeException(sprintf('Unable to copy file as: %s ', $folder));
        //            } else {
        //                return copy($file, $folder);
        //            }
        //        } catch (Exception $e) {
        //            echo 'Caught exception: ', $e->getMessage(), "\n", "<br>";
        //        }
        //        return false;
    }

    /**
     * @param $src
     * @param $dst
     */
    public static function recurseCopy($src, $dst)
    {
        $dir = opendir($src);
        //    @mkdir($dst);
        while (false !== ($file = readdir($dir))) {
            if (($file !== '.') && ($file !== '..')) {
                if (is_dir($src . '/' . $file)) {
                    self::recurseCopy($src . '/' . $file, $dst . '/' . $file);
                } else {
                    copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }
        closedir($dir);
    }

    /**
     *
     * Verifies XOOPS version meets minimum requirements for this module
     * @static
     * @param XoopsModule $module
     *
     * @param null|string $requiredVer
     * @return bool true if meets requirements, false if not
     */
    public static function checkVerXoops(XoopsModule $module = null, $requiredVer = null)
    {
        $moduleDirName = basename(dirname(__DIR__));
        if (null === $module) {
            $module = XoopsModule::getByDirname($moduleDirName);
        }
        xoops_loadLanguage('admin', $moduleDirName);
        //check for minimum XOOPS version
        $currentVer = substr(XOOPS_VERSION, 6); // get the numeric part of string
        $currArray  = explode('.', $currentVer);
        if (null === $requiredVer) {
            $requiredVer = '' . $module->getInfo('min_xoops'); //making sure it's a string
        }
        $reqArray = explode('.', $requiredVer);
        $success  = true;
        foreach ($reqArray as $k => $v) {
            if (isset($currArray[$k])) {
                if ($currArray[$k] > $v) {
                    break;
                } elseif ($currArray[$k] == $v) {
                    continue;
                } else {
                    $success = false;
                    break;
                }
            } else {
                if ((int)$v > 0) { // handles versions like x.x.x.0_RC2
                    $success = false;
                    break;
                }
            }
        }

        if (false === $success) {
            $module->setErrors(sprintf(_AM_XDONATION_ERROR_BAD_XOOPS, $requiredVer, $currentVer));
        }

        return $success;
    }

    /**
     *
     * Verifies PHP version meets minimum requirements for this module
     * @static
     * @param XoopsModule $module
     *
     * @return bool true if meets requirements, false if not
     */
    public static function checkVerPhp(XoopsModule $module)
    {
        xoops_loadLanguage('admin', $module->dirname());
        // check for minimum PHP version
        $success = true;
        $verNum  = PHP_VERSION;
        $reqVer  = $module->getInfo('min_php');
        if (false !== $reqVer && '' !== $reqVer) {
            if (version_compare($verNum, $reqVer, '<')) {
                $module->setErrors(sprintf(_AM_XDONATION_ERROR_BAD_PHP, $reqVer, $verNum));
                $success = false;
            }
        }

        return $success;
    }

    /**
     * @param $curr
     * @return string
     */
    public static function defineCurrency($curr)
    {
        switch ($curr) {
            case 'AUD':
                $currencySign = _MD_XDONATION_CURR_AUD;
                break;
            case 'EUR':
                $currencySign = _MD_XDONATION_CURR_EUR;
                break;
            case 'GBP':
                $currencySign = _MD_XDONATION_CURR_GBP;
                break;
            case 'JPY':
                $currencySign = _MD_XDONATION_CURR_JPY;
                break;
            case 'CAD':
                $currencySign = _MD_XDONATION_CURR_CAD;
                break;
            case 'USD':
            default:
                $currencySign = _MD_XDONATION_CURR_USD;
                break;
        }

        return $currencySign;
    }

    /**
     * Get all Config fields from DB
     *
     * @return array
     */
    public static function getConfigInfo()
    {
        global $xoopsDB;

        $query_cfg = 'SELECT * FROM ' . $xoopsDB->prefix('donations_config') . " WHERE subtype = '' OR subtype = 'array'";
        $cfgset    = $xoopsDB->query($query_cfg);
        $tr_config = [];
        while ($cfgset && $row = $xoopsDB->fetchArray($cfgset)) {
            $tr_config[$row['name']] = $row['value'];
        }

        return $tr_config;
    }

    /**
     * Get XOOPS Member Object
     *
     * @param  int $muser_id
     * @return FALSE - no member info avail for this id, SUCCESS - member object
     */
    public static function getUserInfo($muser_id)
    {
        global $xoopsDB;
        $thisUser = false;
        if ((int)$muser_id > 0) {
            $memberHandler = xoops_getHandler('member');
            $thisUser      = $memberHandler->getUser($muser_id);
        }

        return $thisUser;
    }

    /**
     * Retrieve list of db table's field names
     *
     * EXAMPLE USAGE:
     *
     * $list=XdonationsUtility::runSimpleQuery($xoopsDB->prefix('donations_transactions'));
     *
     * @param  string $table_name DB table name
     * @param  string $key_col    (optional) table column name
     * @param  mixed  $key_val    (optional) table column value
     * @param  array  $ignore     (optional) list of values to ignore (clear)
     * @return mixed  FALSE - nothing found, SUCCESS - array() of values
     */
    public static function runSimpleQuery($table_name, $key_col = '', $key_val = '', $ignore = [])
    {
        global $xoopsDB;
        // open the db
        //    $db_link = mysqli_connect(XOOPS_DB_HOST, XOOPS_DB_USER, XOOPS_DB_PASS);
        $keys = '';
        if ($key_col != '' && $key_val != '') {
            $keys = "WHERE $key_col = $key_val";
        }
        // query table using key col/val
        $simple_q   = false;
        $db_rs      = $xoopsDB->query("SELECT * FROM $table_name $keys");
        $num_fields = $xoopsDB->getFieldsNum($db_rs);
        if ($num_fields) {
            // first (and only) row
            $simple_q = [];
            $row      = $xoopsDB->fetchArray($db_rs);
            // load up array
            if ($key_col != '' && $key_val != '') {
                for ($i = 0; $i < $num_fields; ++$i) {
                    $var            = '';
                    $var            = $xoopsDB->getFieldName($db_rs, $i);
                    $simple_q[$var] = $row[$var];
                }
            } else {
                for ($i = 0; $i < $num_fields; ++$i) {
                    $var = '';
                    $var = $xoopsDB->getFieldName($db_rs, $i);
                    if (!in_array($var, $ignore)) {
                        $simple_q[$var] = '';
                    }
                }
            }
        }
        $xoopsDB->freeRecordSet($db_rs);

        return $simple_q;
    }

    /*
     * Functions for Administration display
     */

    /**
     * Display a Config Option html Option Box in a 2 column table row
     *
     * @param string $name name of config variable in config DB table
     * @param string $desc description of option box
     */
    public static function showYNBox($name, $desc)
    {
        global $tr_config, $modversion, $xoopsDB;

        $query_cfg = 'SELECT * FROM ' . $xoopsDB->prefix('donations_config') . " WHERE name = '{$name}'";
        $cfgset    = $xoopsDB->query($query_cfg);
        if ($cfgset) {
            $cfg  = $xoopsDB->fetchArray($cfgset);
            $text = htmlentities($cfg['text']);
            echo "<tr>\n" . "  <td title=\"{$text}\" style=\"text-align: right;\">{$desc}</td>\n" . "  <td title=\"{$text}\" style=\"text-align: left;\">";
            echo "    <select size=\"1\" name=\"var_{$name}\">";
            if ($cfg['value']) {
                echo '      <option selected value="1">' . _YES . '</option>' . '      <option value="0">' . _NO . '</option>';
            } else {
                echo '      <option value="1">' . _YES . '</option>' . '      <option selected value="0">' . _NO . '</option>';
            }
            echo "    </select>\n";
            echo "  </td>\n";
            echo "</tr>\n";
        }
    }

    /**
     * Display a Config option HTML Select Box in 2 column table
     *
     * @param string $name name of config DB table column
     * @param string $desc description of select box to show
     */
    public static function showDropBox($name, $desc)
    {
        global $tr_config, $modversion, $xoopsDB;

        $query_cfg = 'SELECT * FROM ' . $xoopsDB->prefix('donations_config') . " WHERE name = '{$name}'";
        $cfgset    = $xoopsDB->query($query_cfg);
        if ($cfgset) {
            $cfg  = $xoopsDB->fetchArray($cfgset);
            $text = htmlentities($cfg['text']);
            echo "<tr style=\"text-align: center;\">\n" . "  <td title=\"{$text}\" style=\"text-align: right; width: 50%;\">{$desc}</td>\n" . "  <td title=\"{$text}\" style=\"text-align: left;\">\n";
            echo "    <select size=\"1\" name=\"var_{$name}-array\">\n";
            if (isset($cfg['value'])) {
                $splitArr = explode('|', $cfg['value']);
                $i        = 0;
                while ($i < count($splitArr)) {
                    $selected = (0 == $i) ? ' selected' : '';
                    echo "      <option{$selected} value=\"{$splitArr[$i]}\">{$splitArr[$i]}</option>\n";
                    ++$i;
                }
            }
            echo "    </select>\n";
            echo "  </td>\n";
            echo "</tr>\n";
        }
    }

    /**
     * Display Config Array Drop Box in HTML 2 column table row
     *
     * @param string $name    name of DB column in config table
     * @param string $desc    description to display for select box
     * @param array  $x_array array( array($value1, $attrib1), array(...) )
     */
    public static function showArrayDropBox($name, $desc, $x_array)
    {
        global $tr_config, $modversion, $xoopsDB;
        $query_cfg = 'SELECT * FROM ' . $xoopsDB->prefix('donations_config') . " WHERE name = '{$name}' LIMIT 1";
        $cfgset    = $xoopsDB->query($query_cfg);
        if ($cfgset) {
            $cfg  = $xoopsDB->fetchArray($cfgset);
            $text = htmlentities($cfg['text']);
            echo "<tr>\n" . "  <td title=\"{$text}\" style=\"text-align: right;\">{$desc}</td>\n" . "  <td title=\"{$text}\" style=\"text-align: left;\">\n";
            echo "    <select size=\"1\" name=\"var_{$name}\">\n";
            if (isset($cfg['value'])) {
                if (0 == $cfg['value']) {
                    echo "      <option selected value=\"0\">-------</option>\n";
                } else {
                    echo "      <option value=\"0\">-------</option>\n";
                }
                $i = 0;
                while ($i < count($x_array)) {
                    $mvar     = $x_array[$i];
                    $selected = '';
                    if ($mvar[0] == $cfg['value']) {
                        $selected = ' selected';
                    }
                    echo "      <option{$selected} value=\"{$mvar[0]}\">{$mvar[1]}</option>\n";
                    ++$i;
                }
            }
            echo "    </select>\n";
            echo "  </td>\n";
            echo "</tr>\n";
        }
    }

    /**
     * Display Config Option Text Box in a 2 column table row
     *
     * @param string $name    name of DB column in config table
     * @param string $desc    description of text box to display
     * @param int    $tdWidth width of description field
     * @param int    $inpSize width of text input box
     * @param string $extra   extra info included in input box 'string'
     */
    public static function showTextBox($name, $desc, $tdWidth, $inpSize, $extra)
    {
        global $tr_config, $modversion, $xoopsDB;

        $query_cfg = 'SELECT * FROM ' . $xoopsDB->prefix('donations_config') . " WHERE name = '{$name}'";
        $cfgset    = $xoopsDB->query($query_cfg);
        if ($cfgset) {
            $cfg  = $xoopsDB->fetchArray($cfgset);
            $text = htmlentities($cfg['text']);
            echo "<tr>\n"
                 . "  <td title=\"{$text}\" style=\"text-align: right; width: {$tdWidth};\">{$desc}</td>\n"
                 . "  <td title=\"{$text}\" style=\"text-align: left;\">\n"
                 . "    <input size=\"{$inpSize}\" name=\"var_{$name}\" type=\"text\" value=\"{$cfg['value']}\"  {$extra}>\n"
                 . "  </td>\n"
                 . "</tr>\n";
        }
    }

    /************************************************************************
     *
     ***********************************************************************
     * @param $xnm
     * @param $ynm
     * @param $desc
     * @param $inpSize
     * @param $extra
     */
    public static function showImgXYBox($xnm, $ynm, $desc, $inpSize, $extra)
    {
        global $tr_config, $modversion, $xoopsDB;

        $query_cfg = 'SELECT * FROM ' . $xoopsDB->prefix('donations_config') . " WHERE name = '$xnm'";
        $cfgset    = $xoopsDB->query($query_cfg);

        if ($cfgset) {
            $cfg = $xoopsDB->fetchArray($cfgset);

            $text = htmlentities($cfg['text']);
            echo "<tr>\n" . "  <td title=\"{$text}\" style=\"text-align: right;\">{$desc}</td>\n" . "  <td title=\"{$text}\" style=\"text-align: left;\">\n";
            echo '    &nbsp;' . _AD_XDONATION_WIDTH . "&nbsp;\n" . "    <input size=\"{$inpSize}\" name=\"var_{$cfg['name']}\" type=\"text\" value=\"{$cfg['value']}\" {$extra}>\n";

            $query_cfg = 'SELECT * FROM ' . $xoopsDB->prefix('donations_config') . " WHERE name = '$ynm'";
            $cfgset    = $xoopsDB->query($query_cfg);
            if ($cfgset) {
                $cfg = $xoopsDB->fetchArray($cfgset);
                echo '    &nbsp;&nbsp;' . _AD_XDONATION_HEIGHT . "&nbsp;\n" . "    <input size=\"{$inpSize}\" name=\"var_{$cfg['name']}\" type=\"text\" value=\"{$cfg['value']}\" {$extra}>\n";
            }
            echo "  </td>\n" . "</tr>\n";
        }
    }

    /*
     * Functions to save Administration settings
     */

    /**
     * Update the Config option in the database
     *
     * @param  string $name config var name in the database
     * @param  string $sub  config subtype in the database
     * @param  mixed  $val  config var value
     * @param  string $txt  configuration text for this var
     * @return bool   TRUE value updated, FALSE value not updated
     */
    public static function updateDb($name, $sub, $val, $txt)
    {
        global $tr_config, $ilog, $xoopsDB;
        $insertRecordset = 'UPDATE `' . $xoopsDB->prefix('donations_config') . '`' . " SET `value`='$val', `text`='{$txt}'" . " WHERE `name`='{$name}' AND `subtype`='{$sub}'";
        $ilog            .= "{$insertRecordset}<br><br>";
        echo "{$insertRecordset}<br><br>";
        echo '<span style="color: #FF0000; font-weight: bold;">';
        $rvalue = $xoopsDB->query($insertRecordset);
        echo '</span>';
        $retVal = $rvalue ? true : false;

        return $retVal;
    }

    /************************************************************************
     *
     ***********************************************************************
     * @param $name
     * @param $sub
     * @param $val
     * @param $txt
     */
    public static function updateDbShort($name, $sub, $val, $txt = '')
    {
        global $tr_config, $ilog, $xoopsDB;
        if ($sub === 'array') {
            $newArr    = '';
            $query_cfg = 'SELECT * FROM ' . $xoopsDB->prefix('donations_config') . " WHERE name = '{$name}'";
            $cfgset    = $xoopsDB->query($query_cfg);
            $cfg       = $xoopsDB->fetchArray($cfgset);
            if (isset($cfg['value'])) {
                $splitArr = explode('|', $cfg['value']);
                $newArr   = $val;
                $i        = 0;
                while (false != ($singleVar = $splitArr[$i])) {
                    if ($singleVar != $val) {
                        $newArr = $newArr . '|' . $singleVar;
                    }
                    ++$i;
                }
                $val = $newArr;
            }
        }
        $insertRecordset = 'UPDATE `' . $xoopsDB->prefix('donations_config') . '`' . " SET `value`='{$val}'" . " WHERE `name`='{$name}' AND `subtype`='{$sub}'";

        $ilog .= "{$insertRecordset}<br><br>\n";
        echo "{$insertRecordset}<br><br><span style=\"color: #FF0000; font-weight: bold;\">\n";
        $rvalue = $xoopsDB->query($insertRecordset);
        echo "</span>\n";
    }

    /**
     * Get Configuration Value
     *
     * @param  string $name name of configuration variable
     * @return mixed  value of config var on success, FALSE on failure
     *
     */
    public static function getLibConfig($name)
    {
        global $xoopsDB;

        $sql       = 'SELECT * FROM ' . $xoopsDB->prefix('donations_config') . " WHERE name = '{$name}'";
        $Recordset = $xoopsDB->query($sql);
        $row       = $xoopsDB->fetchArray($Recordset);
        //  $text = $b = html_entity_decode($row['text']);
        $text = html_entity_decode($row['text']);

        return $text;
    }

    /**
     *
     * Get All Configuration Values
     *
     * @return array SUCCESS - array of config values (name as key); FAIL - empty
     */
    public static function getAllLibConfig()
    {
        global $xoopsDB;

        $sql      = 'SELECT * FROM ' . $xoopsDB->prefix('donations_config') . ' ORDER BY name, subtype';
        $sqlQuery = $xoopsDB->query($sql);

        $t = [];
        while (false !== ($sqlfetch = $xoopsDB->fetchArray($sqlQuery))) {
            $text = html_entity_decode($sqlfetch['text']);
            $text = str_replace('<br>', "\r\n", $text);
            $text = str_replace('<br>', "\r\n", $text);

            if ($sqlfetch['subtype'] == '') {
                $t[$sqlfetch['name']] = $text;
            } else {
                $t[$sqlfetch['name']][$sqlfetch['subtype']] = $text;
            }
        }

        //displayArray($t,"------getAllLibConfig-----------");
        return $t;
    }

    /*******************************************************************
     *
     ******************************************************************
     * @param        $t
     * @param string $name
     * @param int    $ident
     */
    public static function displayDonorArray($t, $name = '', $ident = 0)
    {
        if (is_array($t)) {
            echo '------------------------------------------------<br>';
            echo 'displayArray: ' . $name . ' - count = ' . count($t);
            //echo "<table ".getTblStyle().">";
            echo "<table>\n";

            echo '  <tr><td>';
            //jjd_echo ("displayArray: ".$name." - count = ".count($t), 255, "-") ;
            echo "</td></tr>\n";

            echo "  <tr><td>\n";
            echo '    <pre>';
            echo print_r($t);
            echo "</pre>\n";
            echo "  </td></tr>\n";
            echo "</table>\n";
        } else {
            echo "The variable ---|{$t}|--- is not an array\n";
            //        echo "l'indice ---|{$t}|--- n'est pas un tableau\n";
        }
        //jjd_echo ("Fin - ".$name, 255, "-") ;
    }

    /**
     * Display main top header table
     *
     */
    public static function adminmain()
    {
        global $tr_config, $modversion, $xoopsDB;

        echo "<div style=\"text-align: center;\">\n";
        echo "<table style='text-align: center; border-width: 1px; padding: 2px; margin: 2px; width: 90%;'>\n";
        echo "  <tr>\n";
        echo "    <td style='text-align: center; width: 25%;'><a href='index.php?op=Treasury'><img src='../images/admin/business_sm.png' alt='" . _AD_XDONATION_TREASURY . "'>&nbsp;" . _AD_XDONATION_TREASURY . "</a></td>\n";
        echo "    <td style='text-align: center; width: 25%;'><a href='index.php?op=ShowLog'><img src='../images/admin/view_text_sm.png' alt='" . _AD_XDONATION_SHOW_LOG . "'>&nbsp;" . _AD_XDONATION_SHOW_LOG . "</a></td>\n";
        echo "    <td style='text-align: center; width: 25%;'><a href='transaction.php'><img src='../images/admin/view_detailed_sm.png' alt='" . _AD_XDONATION_SHOW_TXN . "'>&nbsp;" . _AD_XDONATION_SHOW_TXN . "</a></td>\n";
        echo "    <td style='text-align: center; width: 25%;'><a href='index.php?op=Config'><img src='../images/admin/configure_sm.png' alt='" . _AD_XDONATION_CONFIGURATION . "'>&nbsp;" . _AD_XDONATION_CONFIGURATION . "</a></td>\n";
        echo "  </tr>\n";
        echo "</table>\n";
        echo "<br></div>\n";
    }
}
