<form action="https://<{$block.paypal_url}>/cgi-bin/webscr" target="paypal" method="post">
    <{securityToken}><{*//mb*}>
    <{$block.lang_select}><br>
    <select name="amount">
        <{$block.amounts}>
    </select> <br>
    <br>
    <table>
        <tr>
            <td colspan="2"><{$block.prompt}></td>
        </tr>
        <tr>
            <td><input type="radio" name="os0" checked value="Yes"></td>
            <td><{$block.nm_yes}></td>
        </tr>
        <tr>
            <td><input type="radio" name="os0" value="No"></td>
            <td><{$block.nm_no}></td>
        </tr>
    </table>
    <input type="hidden" name="cmd" value="_xclick"> <input
            type="hidden" name="business" value="<{$block.email}>"> <input
            type="hidden" name="item_name" value="<{$block.item}>"> <input
            type="hidden" name="item_number" value="110"> <input
            type="hidden" name="rm" value="2"> <input type="hidden"
                                                      name="notify_url" value="<{$xoops_url}>/modules/<{$block.xdon_dir}>/ipnppd.php">
    <input type="hidden" name="on0" value="List your name? "> <input
            type="hidden" name="no_shipping" value="<{$block.pp_noship}>">
    <input type="hidden" name="currency_code" value="<{$block.pp_curr_code}>">
    <input type="hidden" name="cn" value="Comments"> <input
            type="hidden" name="custom" value="<{$block.custom}>"> <input
            type="hidden" name="cancel_return" value="<{$block.pp_cancel}>">
    <input type="hidden" name="return" value="<{$block.pp_thanks}>">
    <input type="hidden" name="image_url" value="<{$block.pp_image}>"><br>
    <br>
    <input type="submit" value="<{$block.submit_button}>" border="0"
           name="I1"></form>
