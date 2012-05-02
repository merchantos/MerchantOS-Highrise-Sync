<!--
To change this template, choose Tools | Templates
and open the template in the editor.
-->
<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title></title>
    </head>
    <body>
        <?php
        error_reporting(E_ALL);
        ini_set('display_errors', '1');
        
        require_once('APIInterface.class.php');
        $api_interface = new APIInterface('d95cf22bca845c8444715cc8e1840145e148bfaac16bd8eeaf7de66131e13eb4', '39184', '0f5b609203e0f9b3af5d4325215876d2', 'merchantosintern');
        
        
        $api_interface->archiveAllCustomers();
        
        
        ?>
    </body>
</html>
