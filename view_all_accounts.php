<?php

require_once('SyncAccountDAO.class.php');
$dao = new SyncAccountDAO();

$all_accounts = $dao->getAllSyncAccounts();

foreach($all_accounts as $account) {
    echo $account->toString();
}

?>
