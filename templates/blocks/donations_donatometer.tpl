<table
    style="border-width: 0; padding: 0; margin: 0; width: 100%; font-size: smaller;">
    <tr>
        <td style="width: 100%; text-align: center;" colspan="2"><a
            href="<{$block.DON_URL}>"> <img src="<{$block.DM_BUTTON}>"
        style="border-width; 0px; text-align: center;"
        alt="<{$block.DM_MAKEDON}>" <{$block.DM_BUTT_DIMS}> /> </a></td>
    </tr>
    <tr>
        <td
            style="width: 100%; text-align: center; font-weight: bold; text-decoration: underline;"
            colspan="2"><{$block.DM_STAT}></td>
    </tr>
    <tr>
        <td style="width: 55%; text-align: right;"><{$block.DM_MONGOAL}>:</td>
        <td style="text-align: left;"><{$block.DM_GOAL}></td>
    </tr>
    <tr>
        <td style="width: 55%; text-align: right;"><{$block.DM_DUEDATE}>:</td>
        <td style="text-align: left;"><{$block.DM_DUE}></td>
    </tr>
    <tr>
        <td style="width: 55%; text-align: right;"><{$block.DM_GROSSAMT}>:</td>
        <td style="text-align: left;"><{$block.DM_GROSS}></td>
    </tr>
    <tr>
        <td style="width: 55%; text-align: right;"><{$block.DM_NETBAL}>:</td>
        <td style="text-align: left;"><{$block.DM_NET}></td>
    </tr>
    <tr>
        <td style="width: 55%; text-align: right; font-weight: bold;"><{$block.DM_REMAIN}>:</td>
        <td style="text-align: left; font-weight: bold;"><{$block.DM_BALANCE}></td>
    </tr>
</table>
<{ if ($block.show_don == 1) && ($block.content != '')}>
<table
    style="border-width: 0; padding: 0; margin: 0; width: 100%;">
    <tr>
        <td style="width: 100%;">
        <hr />
        </td>
    </tr>
    <tr>
        <td
            style="text-align: center; font-weight: bold; text-decoration: underline;"
            colspan="2"><{$block.DM_DONATIONS}></td>
    </tr>
    <{$block.content}>
</table>
<{ /if }>
