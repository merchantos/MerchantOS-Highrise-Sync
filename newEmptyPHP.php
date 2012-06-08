<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

require_once('SyncAccount.class.php');

$acct =  new SyncAccount(null, 'TestAcct', 
        'd95cf22bca845c8444715cc8e1840145e148bfaac16bd8eeaf7de66131e13eb4', 39184, 
        '0f5b609203e0f9b3af5d4325215876d2', 'merchantosintern');

$valid = $acct->hasValidCredentialsHighrise();

var_dump($valid);


?>
