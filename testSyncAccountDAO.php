<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once('SyncAccountDAO.class.php');
define('BR', '<br />');

$dao = new SyncAccountDAO();



// run successfully 2012-05-18
function testUpdateLastSyncedOn() {
    global $dao;
        $existing_acct = new SyncAccount('fjkdlf', 39, 
            'eruirue', 'herpsrus', 
            'derp.h@gmail.com', 'strongpass', 'Herp McDerp', 
            42, 12, 'now');
    $was_updated = $dao->updateLastSyncedOn($existing_acct);    
    echo 'was updated=';
    var_dump($was_updated);
    echo BR;
}


// run successfully 2012-05-18
function testUpdateCustomFieldID() {
    global $dao;
    $existing_acct = new SyncAccount('fjkdlf', 39, 
            'eruirue', 'herpsrus', 
            'derp.h@gmail.com', 'strongpass', 'Herp McDerp', 
            42, 12, time());
    $was_updated = $dao->updateCustomFieldID($existing_acct);    
    echo 'was updated=';
    var_dump($was_updated);
    echo BR;
}


// run successfully 2012-05-18
function testDeleteSyncAccount() {
    global $dao;
    $existing_acct = new SyncAccount('fjkdlf', 39, 
            'eruirue', 'herpsrus', 
            'derp.h@gmail.com', 'strongpass', 'Herp McDerp', 
            42, 12, time());
    $was_deleted = $dao->deleteSyncAccount($existing_acct);
    echo 'was deleted=';
    var_dump($was_deleted);
    echo BR;
}

// run successfully 2012-05-18
function testGetAllSyncAccounts() {
    global $dao;
    $all_accounts = $dao->getAllSyncAccounts();
    foreach($all_accounts as $acct) {
        echo $acct->toString(), BR;
    }
}


// run successfully 2012-05-18
function testSaveSyncAccountWithNew() {
    global $dao;
    $new_acct = new SyncAccount('fjkdlf', 39, 
            'eruirue', 'herpsrus', 
            'derp.h@gmail.com', 'weakpass', 'Herp McDerp');
    echo $new_acct->toString(), BR;
    $was_saved = $dao->saveSyncAccount($new_acct);
    echo 'was saved=';
    var_dump($was_saved);
    echo BR;
}


// run successfully 2012-05-18
function testSaveSyncAccountWithOld() {
    global $dao;
    $existing_acct = new SyncAccount('fjkdlf', 39, 
            'eruirue', 'herpsrus', 
            'derp.h@gmail.com', 'strongpass', 'Herp McDerp', 
            NULL, 12, time());
    $was_saved = $dao->saveSyncAccount($existing_acct);
    echo 'was_saved=';
    var_dump($was_saved);
    echo BR;   
}





?>
