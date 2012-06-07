<?php

require_once('SyncAccountDAO.class.php');
$dao = new SyncAccountDAO();

$form = '<form>
            View exceptions for only one account: 
            <input type="text" name="account_key" placeholder="MerchantOS Account Key" /> 
            <input type="submit" name="submit" value="Submit" />
        </form>';

echo $form;



if ($acct_key = $_GET['account_key']) {
    $acct = $dao->getSyncAccountByMOSAccountKey($acct_key);
    $id = $acct->getID();
    echo '<p>Showing exceptions only for account key ' . $acct_key . ', ordered oldest to newest.</p>';
}
else {
    echo '<p>Showing exceptions for all accounts, ordered oldest to newest.';
}


$exceptions_html = $dao->getExceptionsInHTML($id);

echo $exceptions_html;

?>
