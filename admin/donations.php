<?php
/************************************************************************/
/* Donations - Paypal financial management module for Xoops 2           */
/* Copyright (c) 2016 XOOPS Project                                     */
/* http://dev.xoops.org/modules/xfmod/project/?group_id=1060            */
/*
/************************************************************************/
/*                                                                      */
/* Based on NukeTreasury for PHP-Nuke - by Dave Lawrence AKA Thrash     */
/* NukeTreasury - Financial management for PHP-Nuke                     */
/* Copyright (c) 2004 by Dave Lawrence AKA Thrash                       */
/*                       thrash@fragnastika.com                         */
/*                       thrashn8r@hotmail.com                          */
/*                                                                      */
/************************************************************************/
/*                                                                      */
/* This program is free software; you can redistribute it and/or modify */
/* it under the terms of the GNU General Public License as published by */
/* the Free Software Foundation; either version 2 of the License.       */
/*                                                                      */
/* This program is distributed in the hope that it will be useful, but  */
/* WITHOUT ANY WARRANTY; without even the implied warranty of           */
/* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU     */
/* General Public License for more details.                             */
/*                                                                      */
/* You should have received a copy of the GNU General Public License    */
/* along with this program; if not, write to the Free Software          */
/* Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307  */
/* USA                                                                  */
/************************************************************************/

use XoopsModules\Xdonations;

require_once dirname(dirname(dirname(__DIR__))) . '/include/cp_header.php';

xoops_loadLanguage('main', $xoopsModule->getVar('dirname'));
// require_once dirname(__DIR__) . '/class/Utility.php';
require_once __DIR__ . '/admin_header.php';
xoops_cp_header();

$tr_config = $utility::getConfigInfo();
//determine the currency
$PP_CURR_CODE = explode('|', $tr_config['pp_curr_code']); // [USD,GBP,JPY,CAD,EUR,AUD]
$PP_CURR_CODE = $PP_CURR_CODE[0];
$currencySign = $utility::defineCurrency($PP_CURR_CODE);

/***************************************************************************
 *
 ***************************************************************************/
function treasury()
{
    global $tr_config, $xoopsDB, $xoopsModule, $modversion, $currencySign, $pathIcon16;
    require_once XOOPS_ROOT_PATH . '/class/xoopsformloader.php';
    $adminObject = \Xmf\Module\Admin::getInstance();
    $adminObject->displayNavigation('donations.php?op=Treasury');

    // Register paging
    $maxRows_Recordset1  = 10;
    $pageNum_Recordset1  = \Xmf\Request::getInt('pageNum_Recordset1', 0, 'POST');
    $startRow_Recordset1 = $pageNum_Recordset1 * $maxRows_Recordset1;

    //  $query_Recordset1 = "SELECT id, date, DATE_FORMAT(date, '%d-%b-%Y') as fdate, DATE_FORMAT(date, '%d') as day, DATE_FORMAT(date, '%m') as mon, DATE_FORMAT(date, '%Y') as year, num, name, descr, amount FROM ".$xoopsDB->prefix("donations_financial")." order by date DESC";
    $query_Recordset1       = "SELECT id, date, DATE_FORMAT(date, '%d-%b-%Y') AS fdate, num, name, descr, amount FROM " . $xoopsDB->prefix('donations_financial') . ' ORDER BY date DESC';
    $query_limit_Recordset1 = "$query_Recordset1 LIMIT $startRow_Recordset1, $maxRows_Recordset1";
    $Recordset1             = $xoopsDB->query($query_limit_Recordset1);
    $row_Recordset1         = $xoopsDB->fetchArray($Recordset1);

    if (\Xmf\Request::hasVar('totalRows_Recordset1', 'POST')) {
        $totalRows_Recordset1 = $_POST['totalRows_Recordset1'];
    } else {
        $all_Recordset1       = $xoopsDB->query($query_Recordset1);
        $totalRows_Recordset1 = $xoopsDB->getRowsNum($all_Recordset1);
    }
    $totalPages_Recordset1  = ceil($totalRows_Recordset1 / $maxRows_Recordset1) - 1;
    $queryString_Recordset1 = '&totalRows_Recordset1=' . $totalRows_Recordset1 . '#AdminTop';

    // Collect IPN reconcile data
    // First, get the date of the last time we reconciled
    $query_Recordset2 = 'SELECT `date` AS recdate FROM ' . $xoopsDB->prefix('donations_financial') . " WHERE name = 'PayPal IPN' ORDER BY date DESC LIMIT 1";
    $Recordset2       = $xoopsDB->query($query_Recordset2);
    $row_Recordset2   = $xoopsDB->fetchArray($Recordset2);
    $recdate          = $row_Recordset2['recdate'];

    // Get the date of the last donation
    $query_Recordset2 = 'SELECT `payment_date` AS curdate FROM ' . $xoopsDB->prefix('donations_transactions') . " WHERE payment_status = 'Completed' AND (txn_type = 'send_money' OR txn_type = 'web_accept' ) ORDER BY payment_date DESC LIMIT 1";
    $Recordset2       = $xoopsDB->query($query_Recordset2);
    $row_Recordset2   = $xoopsDB->fetchArray($Recordset2);
    $curdate          = $row_Recordset2['curdate'];

    // Collect the IPN transactions between recdate and curdate
    $query_Recordset2 = 'SELECT custom, SUM(mc_gross) AS gross, SUM(mc_gross - mc_fee) AS net FROM ' . $xoopsDB->prefix('donations_transactions') . " WHERE (payment_date > '{$recdate}' AND payment_date <= '{$curdate}') GROUP BY txn_id";
    $Recordset2       = $xoopsDB->query($query_Recordset2);

    // Iterate over the records skipping the ones that total out to zero(refunds)
    $ipn_tot = 0;
    $num_ipn = 0;
    while (false !== ($row_Recordset2 = $xoopsDB->fetchArray($Recordset2))) {
        if ($row_Recordset2['gross'] > 0) {
            $ipn_tot += $row_Recordset2['net'];
            ++$num_ipn;
        }
    }

    // Get the register balance & total number of records
    $query_Recordset4 = 'SELECT SUM(amount) AS total, COUNT(*) AS numRec FROM ' . $xoopsDB->prefix('donations_financial') . ' ';
    $Recordset4       = $xoopsDB->query($query_Recordset4);
    list($total, $numRec) = $xoopsDB->fetchRow($Recordset4);
    /*
     $row_Recordset4 = $xoopsDB->fetchArray($Recordset4);
     $total = $row_Recordset4['total'];

     // Query to remove the Edit/Delete buttons if no results will be listed.
     $queryRec = "SELECT COUNT(*) FROM ".$xoopsDB->prefix("donations_financial")."";
     list($numRec) = $xoopsDB->fetchRow($queryRec);
     */
    // Output the page
    echo "<table style=\"border-width: 1px; width: 100%; text-align: center;\">\n" . "<tr><td>\n";
    echo "<table style=\"border-width: 0px; padding: 0px; margin: 0px; text-align: center;\">\n";
    echo '  <tr><td style="width: 100%; text-align: center; font-weight: bold;">';
    echo '<span class="option"><h3>' . _AD_XDONATION_TREASURY_F_REGISTER . "</h3></span></td></tr>\n";
    echo '  <tr><td style="width: 100%;">' . _AD_XDONATION_NEW_IPN_COUNT . " {$num_ipn} - " . _AD_XDONATION_TOTALING . " {$currencySign}{$ipn_tot}";
    echo "</td></tr>\n";
    echo "<tr><td style=\"width: 100%; text-align: center;\">\n";
    echo "  <form action=\"donations.php?op=IpnRec#AdminTop\" method=\"post\">\n";
    echo "    <input type=\"hidden\" name=\"op\" value=\"IpnRec\">\n" . '    <input type="submit" value="' . _AD_XDONATION_SYNCHRONISE_IPN . "\" onClick=\"return confirm('" . _AD_XDONATION_CONFIRM_TOTAL_UP . "')\">\n" . "  </form>\n";
    echo "</td></tr></table>\n";

    if ($pageNum_Recordset1 > 0) {
        echo "<table style=\"border-width: 0px; text-align: center;\">\n" . "  <tr>\n";
        echo "    <td><form action=\"donations.php#AdminTop\" method=\"post\">\n"
             . "<input type=\"hidden\" name=\"op\" value=\"Treasury\">\n"
             . "<input type=\"hidden\" name=\"pageNum_Recordset1\" value=\"0\">\n"
             . "<input type=\"hidden\" name=\"totalRows_Recordset1\" value=\"{$totalRows_Recordset1}\">\n"
             . '<input type="submit" name="navig" value="|&lt;" title="'
             . _AD_XDONATION_CURRENT
             . "\"></form></td>\n";
        echo "<td><form action=\"donations.php#AdminTop\" method=\"post\">\n"
             . "<input type=\"hidden\" name=\"op\" value=\"Treasury\">\n"
             . '<input type="hidden" name="pageNum_Recordset1" value="'
             . max(0, $pageNum_Recordset1 - 1)
             . "\">\n"
             . "<input type=\"hidden\" name=\"totalRows_Recordset1\" value=\"{$totalRows_Recordset1}\">\n"
             . '<input type="submit" name="navig" value="&lt;" title="'
             . _AD_XDONATION_NEXT_NEWEST
             . "\"></form></td>\n";
        if ($pageNum_Recordset1 < $totalPages_Recordset1) {
            echo "<td><form action=\"donations.php#AdminTop\" method=\"post\">\n"
                 . "<input type=\"hidden\" name=\"op\" value=\"Treasury\">\n"
                 . '<input type="hidden" name="pageNum_Recordset1" value="'
                 . min($totalPages_Recordset1, $pageNum_Recordset1 + 1)
                 . "\">\n"
                 . "<input type=\"hidden\" name=\"totalRows_Recordset1\" value=\"{$totalRows_Recordset1}\">\n"
                 . '<input type="submit" name="navig" value=">" title="'
                 . _AD_XDONATION_NEXT_OLDEST
                 . "\"></form></td>\n";
            echo "<td><form action=\"donations.php#AdminTop\" method=\"post\">\n"
                 . "<input type=\"hidden\" name=\"op\" value=\"Treasury\">\n"
                 . "<input type=\"hidden\" name=\"pageNum_Recordset1\" value=\"{$totalPages_Recordset1}\">\n"
                 . "<input type=\"hidden\" name=\"totalRows_Recordset1\" value=\"{$totalRows_Recordset1}\">\n"
                 . '<input type="submit" name="navig" value=">|" title="'
                 . _AD_XDONATION_OLDEST
                 . "\"></form></td>\n";
        }
        echo "</tr></table>\n";
    }

    echo "<table class='outer' width='100%' border='0' cellpadding='0' cellspacing='0'>"
         . "<th align='center'>"
         . _AD_XDONATION_DATE
         . "</th><th align='center'>"
         . _AD_XDONATION_NUM
         . "</th><th align='center'>"
         . _AD_XDONATION_NAME
         . "</th><th align='center'>"
         . _AD_XDONATION_DESCRIPTION
         . "</th><th align='center'>"
         . _AD_XDONATION_AMOUNT
         . "</th><th align='center'>"
         . _AD_XDONATION_ACTION
         . "</th></tr>\n";
    //      $class = 'even';

    $row = 0;
    do {
        ++$row;
        echo "<tr>\n";
        echo "</td>\n"
             . "<td style=\"text-align: center;\">$row_Recordset1[fdate]</td>\n"
             . "<td style=\"text-align: center; width: 8px;\">$row_Recordset1[num]</td>\n"
             . "<td style=\"text-align: center;\">$row_Recordset1[name]</td>\n"
             . "<td style=\"text-align: center;\">$row_Recordset1[descr]</td>\n"
             . '<td style="text-align: right;"><span ';
        $amt = sprintf('%10.2f', $row_Recordset1['amount']);
        if ($amt < 0) {
            echo 'style="color: #FF0000;"';
        }
        echo ">{$currencySign}{$amt}</span></td>\n";

        if (0 != $numRec) {
            echo '<td style="text-align: center;">';
            $jscriptCmd = '<a href="javascript: void 0" onclick="' . "document.recedit.id.value = '$row_Recordset1[id]'; " . "document.recedit.StartDate.value = '$row_Recordset1[fdate]'; ";
            $jscriptCmd .= "document.recedit.Num.value = '$row_Recordset1[num]'; "
                           . "document.recedit.Name.value = '$row_Recordset1[name]'; "
                           . "document.recedit.Descr.value = '$row_Recordset1[descr]'; "
                           . "document.recedit.Amount.value = '$row_Recordset1[amount]'; "
                           . "document.recedit.Submit.value = 'Modify'; "
                           . "document.recedit.op.value = 'FinRegEdit'; "
                           . 'return false;">'
                           . '<img style="border-width: 0px; width: 16px; height: 16px;" src='
                           . $pathIcon16
                           . '/edit.png'
                           . " alt='"
                           . _EDIT
                           . "' title='"
                           . _EDIT
                           . "'></a>&nbsp;"
                           . "<a href=\"donations.php?op=FinRegDel&id=$row_Recordset1[id]\">"
                           . '<img style="border-width: 0px; width: 16px; height: 16px;" src='
                           . $pathIcon16
                           . '/delete.png'
                           . " alt='"
                           . _DELETE
                           . "' title='"
                           . _DELETE
                           . "'\" onClick=\"return confirm('"
                           . _AD_XDONATION_CONFIRM_DELETE
                           . '\n\n'
                           . _AD_XDONATION_CONFIRM_ACTION
                           . "')\""
                           . '></a>'
                           . "</td>\n";
            echo $jscriptCmd;
        }
    } while (false !== ($row_Recordset1 = $xoopsDB->fetchArray($Recordset1)));

    echo "</table>\n" . "<table style=\"width: 100%; text-align: center;\"><br>\n";
    echo '<tr><td style="text-align: right; font-weight: bold;" colspan="5"><h4>' . _AD_XDONATION_NETBAL . ":&nbsp;&nbsp;{$currencySign}";
    echo sprintf('%0.2f', $total) . "&nbsp;</h4></td></tr>\n";
    echo "</table><br>\n";

    echo "<table style=\"text-align: center;\">\n"
         . '<tr><td style="text-align: center; font-weight: bold;">'
         . _AD_XDONATION_DATE
         . "</td>\n"
         . '<td style="text-align: center; font-weight: bold;">'
         . _AD_XDONATION_NUM
         . "</td>\n"
         . '<td style="text-align: center; font-weight: bold;">'
         . _AD_XDONATION_NAME
         . "</td>\n"
         . '<td style="text-align: center; font-weight: bold;">'
         . _AD_XDONATION_DESCRIPTION
         . "</td>\n"
         . '<td style="text-align: right; font-weight: bold;">'
         . _AD_XDONATION_AMOUNT
         . "</td></tr>\n"
         . "<tr>\n"
         . "<td style=\"text-align: center;\">\n"
         . "<form action=\"donations.php\" method=\"post\" name=\"recedit\">\n"
         . "<input name=\"id\" type=\"hidden\">\n";
    $newDate  = new \XoopsFormTextDateSelect('Date', 'StartDate', $size = 15, null);
    $showDate = $newDate->render();
    echo $showDate . "</td>\n";
    echo "<td style=\"text-align: center; width: 8px;\"><input name=\"Num\" type=\"text\" size=\"8\"></td>\n"
         . "<td style=\"text-align: center;\"><input name=\"Name\" type=\"text\"></td>\n"
         . "<td style=\"text-align: center;\"><input name=\"Descr\" type=\"text\"></td>\n"
         . "<td style=\"text-align: right;\"><input name=\"Amount\" type=\"text\" size=\"8\"></td>\n";
    echo "</tr>\n";
    echo "<tr><td style=\"text-align: right;\" colspan=\"5\">\n"
         . '<input name="" type="reset" value="'
         . _RESET
         . '" onclick="'
         . "document.recedit.Submit.value = '"
         . _ADD
         . "'; "
         . "document.recedit.op.value = 'FinRegAdd'; "
         . "return true;\">&nbsp;\n"
         . '<input type="hidden" name="op" value="FinRegAdd"><input name="Submit" type="submit" value="'
         . _AD_XDONATION_ADD
         . "\">\n"
         . "</form>\n";
    echo "</td></tr>\n";
    echo "</table>\n";
    echo "</td></tr></table>\n";
}

function addFinancialReg()
{
    global $tr_config, $modversion, $xoopsDB;

    $time = date('h:i:s');
    //  $nTime = $_POST['StartYear'].'-'.$_POST['StartMonth'].'-'.$_POST['StartDay'].' '.$time;
    //  $nTime = strtotime($nTime);
    $nTime = strtotime("{$_POST['StartDate']} {$time}");
    if (-1 == $nTime) {
        echo _AD_XDONATION_ERR_BAD_DATE_FORMAT . "<br>\n";
    } else {
        if ('' === $_POST['Name']) {
            echo _AD_XDONATION_ERR_BAD_NAME_FORMAT . "<br>\n";
        } else {
            if (!is_numeric($_POST['Amount'])) {
                echo _AD_XDONATION_INVALID_AMOUNT . '<br>';
            } else {
                echo _AD_XDONATION_FIELD_PASSED . '<br>';
                echo strftime('%Y-%m-%d', $nTime) . " $_POST[Num] $_POST[Name] $_POST[Descr] $_POST[Amount]<br><br>";

                $insertRecordset = 'INSERT INTO `'
                                   . $xoopsDB->prefix('donations_financial')
                                   . '` VALUES '
                                   . "(NULL, '"
                                   . strftime('%Y-%m-%d %H:%M:%S', $nTime)
                                   . "','"
                                   . addslashes($_POST['Num'])
                                   . "','"
                                   . addslashes($_POST['Name'])
                                   . "','"
                                   . addslashes($_POST['Descr'])
                                   . "','"
                                   . addslashes($_POST['Amount'])
                                   . "')";

                $rvalue = $xoopsDB->query($insertRecordset);
                echo $insertRecordset;
                echo strftime('%Y-%m-%d', $nTime) . " $_POST[Num] $_POST[Name] $_POST[Descr] $_POST[Amount]<br><br>$insertRecordset";
                header('Location: donations.php?op=Treasury#AdminTop');
            }
        }
    }
}

function deleteFinancialReg()
{
    global $tr_config, $modversion, $xoopsDB;

    echo _AD_XDONATION_FIELD_PASSED . "<br>\n";

    if (is_numeric($_GET['id']) && ($_GET['id'] > 0)) {
        $del_Recordset = 'DELETE FROM `' . $xoopsDB->prefix('donations_financial') . '`' . " WHERE `id`='" . \Xmf\Request::getInt('id', 0, 'GET') . "' LIMIT 1";
        $rvalue        = $xoopsDB->queryF($del_Recordset);
        header('Location: donations.php?op=Treasury#AdminTop');
    } else {
        echo '<br>' . _AD_XDONATION_ERR_INVALID_RECORD_ID . "<br>\n";
    }
}

function editFinancialReg()
{
    global $tr_config, $modversion, $xoopsDB;

    $time = date('h:i:s');
    //    $nTime = $_POST['StartYear'].'-'.$_POST['StartMonth'].'-'.$_POST['StartDay'].' '.$time;
    $nTime = $_POST['StartDate'] . ' ' . $time;
    $nTime = strtotime($nTime);

    if (-1 == $nTime) {
        echo _AD_XDONATION_ERR_BAD_DATE_FORMAT . "<br>\n";
    } else {
        if ('' === $_POST['Name']) {
            echo _AD_XDONATION_ERR_BAD_NAME_FORMAT . "<br>\n";
        } else {
            if (!is_numeric($_POST['Amount'])) {
                echo _AD_XDONATION_INVALID_AMOUNT2 . '<br>\n';
            } else {
                echo _AD_XDONATION_FIELD_PASSED . "<br>\n";

                echo strftime('%Y-%m-%d', $nTime) . " $_POST[Num] $_POST[Name] $_POST[Descr] $_POST[Amount]<br><br>\n";

                $insertRecordset = 'UPDATE `' . $xoopsDB->prefix('donations_financial') . "` SET date='" . strftime('%Y-%m-%d %H:%M:%S', $nTime) . "', num='$_POST[Num]', Name='$_POST[Name]', " . "descr='$_POST[Descr]', amount='$_POST[Amount]' WHERE id='$_POST[id]' LIMIT 1";

                echo $insertRecordset;
                $rvalue = $xoopsDB->query($insertRecordset);

                echo (string)$_POST['id'] . strftime('%Y-%m-%d', $nTime) . " $_POST[Num] $_POST[Name] $_POST[Descr] $_POST[Amount]<br><br>$insertRecordset";

                header('Location: donations.php?op=Treasury#AdminTop');
            }
        }
    }
}

/*********************************************************************
 *
 *********************************************************************/
function setConfig()
{
    global $tr_config, $xoopsModule, $modversion, $xoopsDB;


    /** @var Xdonations\Utility $utility */
    $utility = new Xdonations\Utility();

    //------------------------------------------------------------------------
    $adminObject = \Xmf\Module\Admin::getInstance();
    $adminObject->displayNavigation('donations.php?op=Config'); ?>
    <script Language="JavaScript">
        function isEmailAddr(email) {
            var result = false;
            var theStr = String(email);
            var index = theStr.indexOf("@");
            if (index > 0) {
                var pindex = theStr.indexOf(".", index);
                if ((pindex > index + 1) && (theStr.length > pindex + 1))
                    result = true;
            }
            return result;
        }

        function validRequired(formField, fieldLabel, message) {
            var result = true;

            if (formField.value === "") {
                alert(message.replace("%1\$s", field));

                formField.focus();
                result = false;
            }

            return result;
        }

        function allDigits(str) {
            return inValidCharSet(str, "0123456789");
        }

        function inValidCharSet(str, charset) {
            var result = true;

            // Note: doesn't use regular expressions to avoid early Mac browser bugs
            for (var i = 0; i < str.length; i++)
                if (charset.indexOf(str.substr(i, 1)) < 0) {
                    result = false;
                    break;
                }

            return result;
        }

        function validInt(formField, fieldLabel, required, message) {
            var result = true;

            if (required && !validRequired(formField, fieldLabel, message))
                result = false;

            if (result) {
//      var num = parseInt(formField.value,10);
                if (!allDigits(formField.value)) {
                    if (required) {
                        //alert('Please enter a number for the "' + fieldLabel +'" field.');
                        alert(message.replace("%1\$s", fieldLabel));
                        formField.focus();
                        result = false;
                    }
                    elseif(formField.value == "")
                    {
                        return true;
                    }
                else
                    {
                        //alert('Please enter a number or a blank for the "' + fieldLabel +'" field.');
                        alert(message.replace("%1\$s", fieldLabel));
                        formField.focus();
                        result = false;
                    }
                }
            }

            return result;
        }


        function validateURL(formField, value, secure) {

            var match = /https/i.test(value);

            if (value !== "" && !/^http/i.test(value)) {
                alert('The URL must start with http://');
                formField.focus();

                return false;
            }

            if (secure && value !== "" && !/^https/i.test(value)) {
//      alert('This should reside on a HTTPS server.  Users will be warned about viewing secure and non-secure data on the same page');
                return confirm('This URL does not begin with https://\nThis image should reside on an HTTPS server.\nIf you use this URL, users will receive a warning\nabout viewing secure and non-secure data on the same page.\n\n  Are you sure you want to continue?');
            }

            return true;
        }


        function checkCancelledURL() {
            if (document.tr_configs.var_pp_image_url.value === "")
                alert('There is no URL for a Cancelled payment.  If you do not enter\na URL for cancelled payments PayPal will also use\nthis URL for cancelled payments.');

            return true;
        }
    </script>
    <?php
    //-------------------------------------------------------------------------------
    echo "<form name=\"tr_configs\" action=\"donations.php\" method=\"post\">\n" . "<input type=\"hidden\" name=\"op\" value=\"updateConfig\">\n";
    echo "<table style=\"border-width: 1px; width: 90%; text-align: center;\"><tr>\n";
    echo "<td style=\"text-align: center; font-weight: bold;\" class=\"title\">\n";
    echo '<h3>' . _AD_XDONATION_CONFIG_MODULE . "</h3>\n";
    echo "<table style=\"border-width: 1px; text-align: center;\">\n";

    $utility::showTextBox('don_button_top', '<span style="font-weight: bold;">' . _AD_XDONATION_IMG_BUTTON_TOP . '</span>', '', '70', 'onChange="return validateURL(this,this.value);"');
    $utility::showImgXYBox('don_top_img_width', 'don_top_img_height', '<span style="font-weight: bold;">' . _AD_XDONATION_IMAGE_SIZE . '</span>', '4', "onChange='return validInt(this,\"" . _AD_XDONATION_IMAGE_SIZE . '",0,"' . _AD_XDONATION_ALERTE_INPUT_NUMBER . "\");'");
    $utility::showTextBox('don_button_submit', '<span style="font-weight: bold;">' . _AD_XDONATION_IMG_BUTTON_URL . '</span>', '', '70', 'onChange="return validateURL(this,this.value);"');
    $utility::showImgXYBox('don_sub_img_width', 'don_sub_img_height', '<span style="font-weight: bold;">' . _AD_XDONATION_IMAGE_SIZE . '</span>', '4', "onChange='return validInt(this,\"" . _AD_XDONATION_IMAGE_SIZE . '",0,"' . _AD_XDONATION_ALERTE_INPUT_NUMBER . "\");'");
    //"onChange='return validInt(this,"._AD_XDONATION_IMAGE_SIZE.")'"
    $utility::showTextBox('don_name_prompt', '<span style="font-weight: bold;">' . _AD_XDONATION_USERNAME_REQUEST . '</span>', '', '70', '');
    $utility::showTextBox('don_name_yes', '<span style="font-weight: bold;">' . _AD_XDONATION_USERNAME_REQUEST_YES . '</span>', '', '50', '');
    $utility::showTextBox('don_name_no', '<span style="font-weight: bold;">' . _AD_XDONATION_USERNAME_REQUEST_NO . '</span>', '', '50', '');

    $desc = 'This is where you can appeal to your' . 'users and your community for donations.' . 'Suggestion: Explain why you need donations,' . 'what you do with the money and how you' . 'manage it. Make them comfortable that' . 'they are not throwing their money away.';

    $sql       = 'SELECT * FROM ' . $xoopsDB->prefix('donations_config') . " WHERE name = 'don_text'";
    $Recordset = $xoopsDB->query($sql);
    $row       = $xoopsDB->fetchArray($Recordset);
    $donText   = $row['text'];
    echo "<tr>\n"
         . "  <td title=\"{$desc}\" style=\"text-align: right; font-weight: bold;\">"
         . _AD_XDONATION_INTRODUCE_TEXT
         . "</td>\n"
         . "  <td title=\"{$desc}\" style=\"text-align: left;\">"
         . "<textarea name=\"var_xdonation_text-rawtext-txt\" cols=\"100\" rows=\"20\">{$donText}</textarea></td>\n";
    echo "</tr>\n";

    //    $utility::showTextBox('don_amt_checked', '<span style=\'font-weight: bold;\'>'._AD_XDONATION_AMOUNT_DEFAULT.'</span>', '', '4', "onChange=\"return validInt(this,'"._AD_XDONATION_AMOUNT_DEFAULT."',1,'"._AD_XDONATION_ALERTE_INPUT_NUMBER."');\"");

    echo "</table>\n";
    echo "<br>\n";

    $query_Recordset1     = 'SELECT * FROM ' . $xoopsDB->prefix('donations_config') . " WHERE name = 'don_amount' ORDER BY subtype";
    $Recordset1           = $xoopsDB->query($query_Recordset1);
    $row_Recordset1       = $xoopsDB->fetchArray($Recordset1);
    $totalRows_Recordset1 = $xoopsDB->getRowsNum($Recordset1);
    $desc                 = htmlentities($row_Recordset1['text'], ENT_QUOTES | ENT_HTML5);

    echo "<table style=\"border-width: 1px; width: 100px; text-align: center;\">\n";
    echo '  <tr><td style="text-align: center; width: 100%; font-weight: bold;" colspan="8">' . _AD_XDONATION_SUGGESTED_AMOUNT . "<br></td></tr>\n";
    $row1 = "  <tr><td title=\"{$desc}\" style=\"text-align: center;\"></td>\n";
    $row2 = "  <tr><td title=\"{$desc}\" style=\"text-align: center; font-weight: bold;\">" . _AD_XDONATION_AMOUNT . "</td>\n";
    do {
        $row1 .= "    <td title=\"{$desc}\" style=\"text-align: center;\">{$row_Recordset1['subtype']}</td>\n";
        $row2 .= "    <td title=\"{$desc}\" style=\"text-align: center;\"><input size=\"4\" name=\"var_xdonation_amount-{$row_Recordset1['subtype']}\" type=\"text\" value=\"{$row_Recordset1['value']}\" onChange=\"return validInt(this,'"
                 . _AD_XDONATION_SUGGESTED_AMOUNT
                 . " #{$row_Recordset1['subtype']}',1,'"
                 . _AD_XDONATION_ALERTE_INPUT_NUMBER
                 . "');\"></td>\n";
    } while (false !== ($row_Recordset1 = $xoopsDB->fetchArray($Recordset1)));

    $row1 .= "</tr>\n";
    $row2 .= "</tr>\n";
    echo "{$row1} {$row2}\n";

    // display default option
    $query_cfg   = 'SELECT * FROM ' . $xoopsDB->prefix('donations_config') . " WHERE name = 'don_amt_checked' LIMIT 1";
    $cfgResult   = $xoopsDB->query($query_cfg);
    $amt         = $xoopsDB->fetchArray($cfgResult);
    $amt_checked = (int)$amt['value'];
    echo '<tr><td>' . _AD_XDONATION_DEFAULT . "</td>\n";
    for ($i = 1; $i < 8; ++$i) {
        $checked = ($i == $amt_checked) ? ' checked' : '';
        echo "<td><input type=\"radio\" name=\"var_xdonation_amt_checked\"{$checked} value=\"{$i}\"></td>\n";
    }
    echo "</tr>\n";
    echo "</table>\n";

    echo "</td></tr>\n";
    echo '<tr><td style="text-align: center; width: 100%;"><br><input type="submit" value="' . _AD_XDONATION_SUBMIT . '"></td></tr>';
    echo "</table><br><br>\n";
    $adminObject->displayNavigation('donations.php?op=Config');
    echo "<table style=\"border-width: 1px; width: 90%; text-align: center;\"><tr>\n";
    echo '<td class="title" style="font-weight: bold; text-align: center;"><h3>' . _AD_XDONATION_CONFIG_PAYPAL_HEADER . "</h3><br>\n";
    echo "<table style=\"border-width: 1px; text-align: center;\">\n";

    $rsql    = 'SELECT rank_id, rank_title FROM ' . $xoopsDB->prefix('ranks') . ' ';
    $rresult = $xoopsDB->query($rsql);
    $r_array = [];
    while (false !== ($r_row = $xoopsDB->fetchRow($rresult))) {
        $r_array[] = $r_row;
    }
    $utility::showDropBox('paypal_url', '<span style=\'font-weight: bold;\'>' . _AD_XDONATION_IPN_URL . '</span>');
    $utility::showTextBox('receiver_email', '<span style=\'font-weight: bold;\'>' . _AD_XDONATION_IPN_EMAIL_RECEIVER . '</span>', '', '40', '');
    $utility::showTextBox('ty_url', '<span style=\'font-weight: bold;\'>' . _AD_XDONATION_IPN_URL_SUCCESS . '</span>', '', '80', 'onChange="checkCancelledURL(); return validateURL(this,this.value);"');
    $utility::showTextBox('pp_cancel_url', '<span style=\'font-weight: bold;\'>' . _AD_XDONATION_IPN_URL_CANCELED . '</span>', '', '80', 'onChange="return validateURL(this,this.value);"');
    $utility::showTextBox('pp_itemname', '<span style=\'font-weight: bold;\'>' . _AD_XDONATION_PP_ITEM_NAME . '</span>', '', '20', '');
    $utility::showTextBox('pp_item_num', '<span style=\'font-weight: bold;\'>' . _AD_XDONATION_PP_ITEM_NUMBER . '</span>', '', '20', '');
    $utility::showTextBox('pp_image_url', '<span style=\'font-weight: bold;\'>' . _AD_XDONATION_PP_IMG . '</span>', '', '60', '');
    $utility::showYNBox('pp_get_addr', '<span style=\'font-weight: bold;\'>' . _AD_XDONATION_PP_ASK_CP_ADRESS . '</span>');
    $utility::showDropBox('pp_curr_code', '<span style=\'font-weight: bold;\'>' . _AD_XDONATION_PP_MONEY . '</span>');
    $gsql    = 'SELECT groupid, name FROM ' . $xoopsDB->prefix('groups') . ' WHERE groupid>3';
    $gresult = $xoopsDB->query($gsql);
    $g_array = [];
    while (false !== ($g_row = $xoopsDB->fetchRow($gresult))) {
        $g_array[] = $g_row;
    }
    $utility::showArrayDropBox('assign_group', '<span style=\'font-weight: bold;\'>' . _AD_XDONATION_PP_GROUP . '</span>', $g_array);
    $rsql    = 'SELECT rank_id, rank_title FROM ' . $xoopsDB->prefix('ranks') . ' ';
    $rresult = $xoopsDB->query($rsql);
    $r_array = [];
    while (false !== ($r_row = $xoopsDB->fetchRow($rresult))) {
        $r_array[] = $r_row;
    }
    $utility::showArrayDropBox('assign_rank', '<span style=\'font-weight: bold;\'>' . _AD_XDONATION_PP_RANK . '</span>', $r_array);
    $utility::showYNBox('don_forceadd', '<span style=\'font-weight: bold;\'>' . _AD_XDONATION_ADD_ANYWAY . '</span>');

    echo "</table><br>\n";

    echo "<table style=\"border-width: 1px; width: 100px; text-align: center;\">\n";
    echo '  <tr><td style="text-align: center; width: 100%; font-weight: bold;" colspan="2">' . _AD_XDONATION_IPN_LOGGING . "<br></td></tr>\n";
    echo "  <tr>\n" . '    <td style="text-align: right; font-weight: bold;">' . _AD_XDONATION_IPN_LOGGING_LEVEL . "</td>\n" . "    <td style=\"text-align: left;\">\n" . "      <select size=\"1\" name=\"var_ipn_dbg_lvl\">\n";
    echo '        <option ';
    if (0 == $tr_config['ipn_dbg_lvl']) {
        echo 'selected ';
    }
    echo 'value="0">' . _AD_XDONATION_LOG_OFF . "</option>\n";
    echo '        <option ';
    if (1 == $tr_config['ipn_dbg_lvl']) {
        echo 'selected ';
    }
    echo 'value="1">' . _AD_XDONATION_LOG_ONLY_ERRORS . "</option>\n";
    echo '        <option ';
    if (2 == $tr_config['ipn_dbg_lvl']) {
        echo 'selected ';
    }
    echo 'value="2">' . _AD_XDONATION_LOG_EVERYTHING . "</option>\n";
    echo "      </select>\n" . "    </td>\n" . "  </tr>\n";

    $utility::showTextBox('ipn_log_entries', '<nobr><span style=\'font-weight: bold;\'>' . _AD_XDONATION_LOG_ENTRY . '</span></nobr>', '', '4', '');

    $desc = 'This box shows the link to the IPN recorder.
    This link must be pasted EXACTLY as it is
    into your PayPal IPN profile.  You can click
    on the "test" link to the right to verify
    that the IPN recorder is functioning correctly.';
    $desc = htmlentities($desc, ENT_QUOTES | ENT_HTML5);
    echo "<tr>\n"
         . "  <td title =\"$desc\" style=\"text-align: right; font-weight: bold;\">"
         . _AD_XDONATION_IPN_LINK
         . "</td>\n"
         . "  <td title =\"$desc\" style=\"text-align: center;\">&nbsp;"
         . XOOPS_URL
         . '/modules/'
         . $xoopsModule->getVar('dirname')
         . "/ipnppd.php&nbsp;&nbsp;\n"
         . '    <br><a href="'
         . XOOPS_URL
         . '/modules/'
         . $xoopsModule->getVar('dirname')
         . '/ipnppd.php?dbg=1" target="_blank"><span style="font-weight: bold; font-style: italic;"><img src="../assets/images/admin/info.png" style="height: 16px; width: 16px;" alt="">&nbsp;'
         . _AD_XDONATION_TEST_IPN
         . "</span></a>\n"
         . "  </td>\n"
         . "</tr>\n";
    echo "</table><br>\n";
    echo "</td></tr>\n";
    echo '<tr><td style="text-align: center; width: 100%;"><input type="submit" value="' . _AD_XDONATION_SUBMIT . "\">\n";
    echo '</td></tr></table><br><br>';

    //Goal Preferences
    //===============================
    $adminObject->displayNavigation('donations.php?op=Config');
    echo "<table style=\"border-width: 1px; width: 90%; text-align: center;\">\n" . "  <tr>\n";
    echo "    <td style=\"text-align: center; font-weight: bold;\" class=\"title\">\n" . '      <h3>' . _AD_XDONATION_GOAL_PREFERENCES . "</h3>\n";
    echo "      <table style=\"border-width: 1px; text-align: center;\">\n" . "        <tr><td style=\"text-align: center;\">\n";
    echo "          <table style=\"border-width: 1px; text-align: center;\">\n";
    $utility::showDropBox('use_goal', '<span style=\'font-weight: bold;\'>' . _AD_XDONATION_GOAL_TYPE . '.</span>');
    echo "          </table>\n";

    $query_Recordset1     = 'SELECT * FROM ' . $xoopsDB->prefix('donations_config') . " WHERE name = 'week_goal' AND subtype<>'Default'";
    $Recordset1           = $xoopsDB->query($query_Recordset1);
    $row_Recordset1       = $xoopsDB->fetchArray($Recordset1);
    $totalRows_Recordset1 = $xoopsDB->getRowsNum($Recordset1);
    $desc                 = htmlentities($row_Recordset1['text'], ENT_QUOTES | ENT_HTML5);

    echo "          <table style=\"border-width: 1px; width: 100px; text-align: center;\">\n" . '            <tr><td style="text-align: center; width: 100%; font-weight: bold;" colspan="5">' . _AD_XDONATION_GOAL_HEBDO . "<br></td></tr>\n";
    $row1 = "  <tr>\n" . '    <td style="text-align: center; font-weight: bold;">' . _AD_XDONATION_WEEK . "</td>\n";
    $row2 = "  <tr>\n" . '    <td style="text-align: center; font-weight: bold;">' . _AD_XDONATION_GOAL . "</td>\n";
    //-------------------------------------------------------------
    $shortMonth = explode('|', _AD_XDONATION_SHORT_MONTH);
    $ordinaux   = explode('|', _AD_XDONATION_NUMBER_ORDINAUX);
    //-------------------------------------------------------------
    $h = 0;
    do {
        $ord  = $ordinaux[$h++];
        $row1 .= "    <td title=\"{$desc}\" style=\"text-align: center;\">{$ord}</td>\n";
        $row2 .= "    <td title=\"{$desc}\" style=\"text-align: center;\"><input size=\"4\" name=\"var_week_goal-$row_Recordset1[subtype]\" type=\"text\" value=\"$row_Recordset1[value]\" onChange=\"return validInt(this,'$row_Recordset1[subtype] "
                 . _AD_XDONATION_GOAL_DONATION
                 . "',1,'"
                 . _AD_XDONATION_ALERTE_INPUT_NUMBER
                 . "');\"></td>\n";
    } while (false !== ($row_Recordset1 = $xoopsDB->fetchArray($Recordset1)));
    $row1 .= "  </tr>\n";
    $row2 .= "  </tr>\n";
    echo "{$row1} {$row2}";

    echo "</table>\n";

    $query_Recordset1     = 'SELECT * FROM ' . $xoopsDB->prefix('donations_config') . " WHERE name = 'month_goal' AND subtype<>'Default'";
    $Recordset1           = $xoopsDB->query($query_Recordset1);
    $row_Recordset1       = $xoopsDB->fetchArray($Recordset1);
    $totalRows_Recordset1 = $xoopsDB->getRowsNum($Recordset1);
    $desc                 = htmlentities($row_Recordset1['text'], ENT_QUOTES | ENT_HTML5);

    $h = 0;
    echo "<table style=\"border-width: 1px; width: 100px; text-align: center;\">\n";
    echo '  <tr><td style="text-align: center; width: 100%; font-weight: bold;" colspan="13">' . _AD_XDONATION_GOAL_MENSUEL . "</td></tr><br>\n";
    $row1 = "  <tr>\n" . '    <td style="text-align: center; font-weight: bold;">' . _AD_XDONATION_MONTH . "</td>\n";
    $row2 = "  <tr>\n" . '    <td style="text-align: center; font-weight: bold;">' . _AD_XDONATION_GOAL . "</td>\n";
    do {
        $month = $shortMonth[$h++];
        $row1  .= "    <td title=\"{$desc}\" style=\"text-align: center;\">{$month}</td>\n";
        $row2  .= "    <td title=\"{$desc}\" style=\"text-align: center;\"><input size=\"4\" name=\"var_month_goal-$row_Recordset1[subtype]\" type=\"text\" value=\"$row_Recordset1[value]\" onChange=\"return validInt(this,'$row_Recordset1[subtype] "
                  . _AD_XDONATION_GOAL_DONATION
                  . "',1,'"
                  . _AD_XDONATION_ALERTE_INPUT_NUMBER
                  . "');\"></td>\n";
    } while (false !== ($row_Recordset1 = $xoopsDB->fetchArray($Recordset1)));
    $row1 .= "  </tr>\n";
    $row2 .= "  </tr>\n";
    echo "{$row1}{$row2}";

    echo "</table>\n";
    echo "<table style=\"border-width: 1px; width: 100px; text-align: center;\">\n";
    $utility::showTextBox('swing_day', '<span style=\'font-weight: bold;\'>' . _AD_XDONATION_SWING_DAY . '</span>', '175', '4', "onChange='return validInt(this,\"" . _AD_XDONATION_SWING_DAY . '",1,"' . _AD_XDONATION_ALERTE_INPUT_NUMBER . "\");'");
    echo "</table>\n";

    $query_Recordset1     = 'SELECT * FROM ' . $xoopsDB->prefix('donations_config') . " WHERE name = 'quarter_goal' AND subtype<>'Default'";
    $Recordset1           = $xoopsDB->query($query_Recordset1);
    $row_Recordset1       = $xoopsDB->fetchArray($Recordset1);
    $totalRows_Recordset1 = $xoopsDB->getRowsNum($Recordset1);
    $desc                 = htmlentities($row_Recordset1['text'], ENT_QUOTES | ENT_HTML5);

    echo "<table style=\"border-width: 1px; width: 100px; text-align: center;\">\n";
    echo '  <tr><td style="text-align: center; width: 100%; font-weight: bold;" colspan="5">' . _AD_XDONATION_QUARTER . "<br></td></tr>\n";
    $row1 = '  <tr><td style="text-align: center; font-weight: bold;">' . _AD_XDONATION_QUARTER . "</td>\n";
    $row2 = '  <tr><td style="text-align: center; font-weight: bold;">' . _AD_XDONATION_GOAL . "</td>\n";
    $h    = 0;
    do {
        $ord  = $ordinaux[$h++];
        $row1 .= "    <td title='{$desc}' class='center;'>{$ord}</td>\n";
        $row2 .= "    <td title='{$desc}' class='center;'><input size=\"4\" name=\"var_quarter_goal-$row_Recordset1[subtype]\" type=\"text\" value=\"$row_Recordset1[value]\" onChange=\"return validInt(this,'$row_Recordset1[subtype] "
                 . _AD_XDONATION_GOAL_DONATION
                 . "',1,'"
                 . _AD_XDONATION_ALERTE_INPUT_NUMBER
                 . "');\"></td>\n";
    } while (false !== ($row_Recordset1 = $xoopsDB->fetchArray($Recordset1)));
    $row1 .= "  </tr>\n";
    $row2 .= "  </tr>\n";
    echo "{$row1} {$row2}";

    echo "</table><br>\n";
    echo "</td></tr>\n";
    echo '<tr><td style="text-align: center; width: 100%;"><input type="submit" value="' . _AD_XDONATION_SUBMIT . "\"></td></tr>\n";
    echo "</table><br>\n";
    echo "</td></tr></table>\n";
    echo "</form>\n";
}

/**
 *
 * Update Configuration Settings in the database
 */
function updateConfig()
{
    global $tr_config, $modversion, $xoopsDB;

    echo '<br>' . _AD_XDONATION_ERR_SQL_FAILURE . "<br><br>\n";

    $error = 1;
    $ilog  = "<br>\n";

    foreach ($_POST as $option => $value) {
        /// Look for form variables

        if (false !== strpos($option, 'var_')) {
            $varnm = str_replace('var_', '', $option);
            // Check for subtype field

            if (preg_match('/-(.*)/', $varnm, $subtype)) {
                echo "<br>subtype = $subtype[1] <br>\n";
                $temp  = $varnm;
                $varnm = preg_replace('/-.*/', '', $temp);
                // Is this is a text field?
                if (preg_match('/([^-]*)-txt/', $subtype[1], $subtype2)) {
                    $textarea = addslashes($value);
                    echo "$varnm $subtype2[1] text=> " . nl2br(htmlspecialchars($textarea, ENT_QUOTES | ENT_HTML5)) . "<br>\n";
                    $error &= $utility::updateDb($varnm, $subtype2[1], '0', $textarea);
                } else {
                    echo "$varnm $subtype[1] => $value<br>\n";
                    $error &= $utility::updateDbShort($varnm, $subtype[1], $value);
                }
            } else {
                echo "$varnm => $value<br>\n";
                $error &= $utility::updateDbShort($varnm, '', $value);
            }
        }
    }

    // If there were no errors
    if (0 == $error) {
        header('Location: donations.php?op=Config#AdminTop');
    }
}

/**
 *
 * Reconcile the IPN Log
 */
function reconcileIpn()
{
    global $tr_config, $modversion, $xoopsDB, $currencySign;
    $recdate          = '';
    $query_Recordset1 = 'SELECT `date` AS recdate FROM ' . $xoopsDB->prefix('donations_financial') . " WHERE name='PayPal IPN' ORDER BY date DESC LIMIT 1";
    $Recordset1       = $xoopsDB->query($query_Recordset1);
    if ($Recordset1) {
        $row_Recordset1 = $xoopsDB->fetchArray($Recordset1);
        if ($row_Recordset1) {
            $recdate = "payment_date > '" . $row_Recordset1['recdate'] . "' AND";
        }
    }

    $query_Recordset1 = 'SELECT `payment_date` AS curdate FROM ' . $xoopsDB->prefix('donations_transactions') . " WHERE payment_status='Completed' AND (txn_type='send_money' OR txn_type='web_accept')" . ' ORDER BY payment_date DESC LIMIT 1';
    $Recordset1       = $xoopsDB->query($query_Recordset1);
    $row_Recordset1   = $xoopsDB->fetchArray($Recordset1);
    $curdate          = $row_Recordset1['curdate'];
    $query_Recordset1 = 'SELECT SUM(mc_gross - mc_fee) AS ipn_total, COUNT(*) AS numrecs' . ' FROM ' . $xoopsDB->prefix('donations_transactions') . " WHERE ({$recdate} payment_date <= '{$curdate}')" . " AND payment_status = 'Completed' AND (txn_type='send_money' OR txn_type='web_accept')";
    $Recordset1       = $xoopsDB->query($query_Recordset1);
    $row_Recordset1   = $xoopsDB->fetchArray($Recordset1);

    echo "<span style='text-align: center; font-weight: bold;' class='title'>" . _AD_XDONATION_UPDATE_REGISTER_IPN . '</span><br><br>';
    if (0 == $row_Recordset1['numrecs']) {
        echo _AD_XDONATION_NO_NEW_IPNS;
    } else {
        $insert_set = 'INSERT INTO `' . $xoopsDB->prefix('donations_financial') . "` (`date`,`num`,`name`,`descr`,`amount`) VALUES ('{$curdate}','','PayPal IPN','Auto-Reconcile','{$row_Recordset1['ipn_total']}')";

        if ($xoopsDB->query($insert_set)) {
            echo sprintf(_AD_XDONATION_RECORDS_INSERTED, $row_Recordset1['numrecs'], $currencySign, $row_Recordset1['ipn_total']);
        } else {
            echo sprintf(_AD_XDONATION_ERR_DB_INSERTION, $row_Recordset1['numrecs']);
        }
    }

    echo '<br><br><form action="donations.php?op=Treasury#AdminTop" method="post">';
    echo '<input type="hidden" name="op" value="Treasury">' . '<input type="submit" value="' . _AD_XDONATION_RETURN . '">' . '</form>';
}

/**
 *
 * Display the IPN Log
 *
 */
function showLog()
{
    global $tr_config, $modversion, $xoopsDB, $currencySign;
    require_once XOOPS_ROOT_PATH . '/class/xoopsformloader.php';
    $adminObject = \Xmf\Module\Admin::getInstance();
    $adminObject->displayNavigation('donations.php?op=ShowLog');

    $query_Recordset1 = 'SELECT id, log_date, payment_date, logentry FROM ' . $xoopsDB->prefix('donations_translog') . ' ORDER BY log_date DESC';
    $transRecords     = $xoopsDB->query($query_Recordset1);
    $numRows          = $xoopsDB->getRowsNum($transRecords);
    $logForm          = new \XoopsThemeForm(_AD_XDONATION_SHOW_LOG, 'logform', $_SERVER['PHP_SELF'], 'post', true);

    if ($numRows) {
        while (false !== (list($rId, $rLdate, $rPdate, $rLentry) = $xoopsDB->fetchRow($transRecords))) {
            $thisTray  = 'logTray_' . $rId;
            ${$thisTray} = new \XoopsFormElementTray($rId, '<br>');
            ${$thisTray}->addElement(new \XoopsFormLabel(_AD_XDONATION_LOG_DATE, $rLdate));
            ${$thisTray}->addElement(new \XoopsFormLabel(_AD_XDONATION_PMNT_DATE, $rPdate));
            $rLentrySplit = '';
            $rLentry      = htmlspecialchars($rLentry, ENT_QUOTES | ENT_HTML5);
            $dispWidth    = 110;
            do {
                //                echo '[' . strlen($rLentry) . ']<br>';
                $pos = strrpos($rLentry, ' ', $dispWidth - strlen($rLentry));
                if (!$pos) {
                    $pos = strrpos($rLentry, ',', $dispWidth - strlen($rLentry));
                    if (!$pos) {
                        $rLentrySplit .= '<br>' . substr($rLentry, 0, $dispWidth);
                        $rLentry      = substr($rLentry, $dispWidth);
                    } else {
                        $rLentrySplit .= '<br>' . substr($rLentry, 0, $pos + 1);
                        $rLentry      = substr($rLentry, $pos);
                    }
                } else {
                    $rLentrySplit .= '<br>' . substr($rLentry, 0, $pos + 1);
                    $rLentry      = substr($rLentry, $pos);
                }
            } while (strlen($rLentry) > $dispWidth);
            ${$thisTray}->addElement(new \XoopsFormLabel(_AD_XDONATION_LOG_ENTRY_TXT, $rLentrySplit . $rLentry));

            //            ${$thisTray}->addElement(new \XoopsFormLabel( _AD_XDONATION_LOGENTRY, $rLentry));
            $logForm->addElement(${$thisTray});
        }
        $buttonTray = new \XoopsFormElementTray('');
        $cButton    = new \XoopsFormButton('', 'op', _AD_XDONATION_CLEAR_LOG, 'submit');
        $cButton->setExtra("onclick=\"this.form.elements.op.value='ClearLog'\"", true);
        $buttonTray->addElement($cButton);
        $logForm->addElement($buttonTray);
    } else {
        //FIXME: replace this with 'full width' cell
        $logForm->addElement(new \XoopsFormLabel('', _AD_XDONATION_LOG_EMPTY));
    }
    $logForm->display();
}

/**
 *
 * Clear the IPN log
 * @param int $ok =0 ask to verify, !=0 clear the log
 */
function clearLog($ok = 0)
{
    global $xoopsDB;
    if ($ok > 0) {
        $sql     = 'DELETE FROM ' . $xoopsDB->prefix('donations_translog');
        $success = $xoopsDB->query($sql);
        $retMsg  = $success ? _AD_XDONATION_LOG_CLEARED : _AD_XDONATION_LOG_NOT_CLEARED;
        echo '<form name="ipnlog" action="donations.php" method="get">';
        echo "<table style=\"text-align: center; border-width: 0px; margin: 4px;\"><tr><td>{$retMsg}</td></tr>";
        echo '<tr><td><input type="submit" value="' . _AD_XDONATION_CONTINUE . '"></td></tr></table>';
        echo '</form>';
    //        redirect_header('./index.php', 2, $retMsg);
        //
    } else {
        xoops_confirm(['op' => 'ClearLog', 'ok' => 1], 'index.php', _AD_XDONATION_CLEAR_THIS_LOG, _DELETE);
    }
}

/**
 *
 * Process incoming operand
 *
 */

$op = isset($_GET['op']) ? $_GET['op'] : 'Treasury';
$op = isset($_POST['op']) ? $_POST['op'] : $op;

switch ($op) {
    case 'FinRegAdd':
        addFinancialReg();
        break;

    case 'FinRegEdit':
        editFinancialReg();
        break;

    case 'FinRegDel':
        deleteFinancialReg();
        break;

    case 'Config':
        setConfig();
        break;

    case 'updateConfig':
        updateConfig();
        break;

    case 'IpnRec':
        reconcileIpn();
        break;
    case 'ShowLog':
        showLog();
        break;

    case 'ClearLog':
        $ok = \Xmf\Request::getInt('ok', 0, 'GET');
        $ok = \Xmf\Request::getInt('ok', $ok, 'POST');
        clearLog($ok);
        break;

    default:
    case 'Treasury':
        treasury();
        break;
}
require_once __DIR__ . '/admin_footer.php';
