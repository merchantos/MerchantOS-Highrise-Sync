<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once('SyncAccountDAO.class.php');
require_once('SyncDateTime.class.php');

define('BR', '<br />');

$dao = new SyncAccountDAO();

testGetExceptions();


function testLogException() {
    global $dao;
    $now = new SyncDateTime();
    $when = $now->getDatabaseFormat();
    $dao->logException('6666', $when, 'this is an exception with only a message and no data involved');
}


function testGetExceptions() {
    global $dao;
    
    $html = $dao->getExceptionsInHTML(4);
    
    echo $html;   
}


function testSaveAndUpdate() {
    global $dao;
    $sync_acct = new SyncAccount(null, 'ToBeCreated',
            'mosapikey', 'mosacctid',
            'highriseapikey', 'highgirseusername',
            null, null);
    echo $sync_acct->toString();
    $sync_acct->save();
    echo $sync_acct->toString();
}



// run successfully 2012-06-05
function testGetSyncAccountByMOSAccountKey() {
    global $dao;
    $existing_account_key = 'RealTestAccount';
    $nonexisting_account_key = 'NewAccount';
    $result = $dao->getSyncAccountByMOSAccountKey($existing_account_key);
    echo $result->toString();    
    $result = $dao->getSyncAccountByMOSAccountKey($nonexisting_account_key);
    var_dump($result);
}

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
