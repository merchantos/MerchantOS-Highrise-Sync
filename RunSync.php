<?php

/*
 * RunSync, a script to call sync on SyncAccounts
 * if run on the command line, an optional argument
 * of a MerchantOS account key may be passed in,
 * and this script will attempt to sync only that account.
 * 
 * If run without an argument, this script will
 * attempt to sync every account in the database.
 * 
 * @author Erika Ellison
 */

require_once('SyncAccount.class.php');
require_once('SyncAccountDAO.class.php');

$dao = new SyncAccountDAO();

if ($argv[1]) {
    $account = $dao->getSyncAccountByMOSAccountKey($argv[1]);
    if ($account) {
        $was_synced = $account->sync();
    }
}
else {
    $all_accounts = $dao->getAllSyncAccounts();

    foreach($all_accounts as $account) {
            $was_synced = $account->sync();
    }
}






?>
