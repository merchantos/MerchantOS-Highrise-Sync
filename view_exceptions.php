<?php

require_once('SyncAccountDAO.class.php');
$dao = new SyncAccountDAO();

$form = '<form>
            View exceptions for only one account: <br />
            <input type="text" name="account_key" placeholder="MerchantOS Account Key" />
            <input type="submit" name="submit" value="Submit" />
        </form>';

echo $form;



if ($acct_key = $_GET['account_key']) {
    $acct = $dao->getSyncAccountByMOSAccountKey($acct_key);
    $id = $acct->getID();
    echo '<p>Showing exceptions only for account key ' . $acct_key . '.</p>';
}
else {
    echo '<p>Showing all exceptions.';
}


$exceptions_html = $dao->getExceptionsInHTML($id);

echo $exceptions_html;

?>
