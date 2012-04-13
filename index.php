<?php 
/**
 * 
 *
 * @author Erika Ellison
 * 
 * 
 * requirements: php5-xsl package (not installed by default, I used "sudo apt-get install php5-xsl")
 */

require_once('MOSAPICall.class.php');
require_once('HighriseAPICall.class.php');
?>

<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/plain; charset=UTF-8">
        <title>Get Customers Test</title>
    </head>
    <body>
        <?php
        libxml_use_internal_errors(true);

        $mos_api_key = 'd95cf22bca845c8444715cc8e1840145e148bfaac16bd8eeaf7de66131e13eb4';
        $mos_acct_id = 39184;
        $mos_api = new MOSAPICall($mos_api_key, $mos_acct_id);
        
        $highrise_api_key = '0f5b609203e0f9b3af5d4325215876d2';
        $highrise_username = 'merchantosintern';
        $highrise_api = new HighriseAPICall($highrise_api_key, $highrise_username);
 
        $customers = $mos_api->makeAPICall('Account.Customer', 'Read');
        
        $xsltProc = new XSLTProcessor();        
        $stylesheet = simplexml_load_file('customerToPerson.xsl');        
        $xsltProc->importStylesheet($stylesheet);
        
        foreach($customers->Customer as $customer) {
            $customerXML = new SimpleXMLElement($customer->asXML());
            echo htmlentities($customerXML->asXML());
            echo '<br />';
            
            $personXML = new SimpleXMLElement($xsltProc->transformToXML($customerXML));
            echo htmlentities($personXML->asXML());
            echo '<br />';
            //$response = $highrise_api->makeAPICall('POST', 'people.xml', $person_xml);
            //echo htmlentities($response->asXML());
        }

        
        xslt_free($xsltProc);
        ?>
        
    </body>
</html>
