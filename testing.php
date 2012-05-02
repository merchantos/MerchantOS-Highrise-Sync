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
        
        $merchantos_api_key = 'd95cf22bca845c8444715cc8e1840145e148bfaac16bd8eeaf7de66131e13eb4';
        $merchantos_acct_id = '39184';
        $highrise_api_key = '0f5b609203e0f9b3af5d4325215876d2';
        $highrise_username = 'merchantosintern';
        
        require_once('APIInterface.class.php');
        $api_interface = new APIInterface($merchantos_api_key, $merchantos_acct_id, $highrise_api_key, $highrise_username);
        
        $customers = simplexml_load_file('testCustomers.xml');
        $people = simplexml_load_file('testPeople.xml');
        
        /* $uncreated_customers = $api_interface->createCustomers($customers);
        if ($uncreated_customers.count() > 0) {
            echo htmlentities($uncreated_customers->asXML());
        }
        else {
            echo 'all customers created <br />';
        } */
        
        /*
        $uncreated_people = $api_interface->createPeople($people);
        if ($uncreated_people.count() > 0) {
            echo htmlentities($uncreated_people->asXML());
        }
        else {
            echo 'all people created <br />';
        }
         */
        
        
        $highrise_api = new HighriseAPICall($highrise_api_key, $highrise_username);
        foreach ($people->person as $person) {
            $result = $highrise_api->makeAPICall('people.xml', 'Create', $person->asXML());
            echo htmlentities($result->asXML());
        }
        
        $result = $api_interface->readAllPeople();
        echo htmlentities($result->asXML());
        
        
        
        
        
        
        
        ?>
    </body>
</html>
