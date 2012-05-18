<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once('SyncAccount.class.php');
require_once('SyncAccountDAO.class.php');

$dao = new SyncAccountDAO();

$all_accounts = $dao->getAllSyncAccounts();

foreach($all_accounts as $account) {
    echo $account->toString();
    if ($account->getID() != 0) {
        echo 'attempting to sync ' , $account->getName(), '<br />';
        echo time();
        $was_synced = $account->sync();
        echo time();
        $report = $account->getName() . '\'s account was ';
        if (!$was_synced) {
            $report .= 'NOT ';
        }
        $report .= 'synced.<br />';
        echo $report;
    }
    echo '<br /><br />';
}

?>
