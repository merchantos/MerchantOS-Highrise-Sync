<?php
$highrise_api_key = '0f5b609203e0f9b3af5d4325215876d2';
$user_pw = $highrise_api_key . ':X';
$highrise_username = 'merchantosintern';

$url = 'https://' . $highrise_username . '.highrisehq.com';

$url = $url . '/people.xml';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_HTTPGET, 1);
curl_setopt($ch, CURLOPT_USERPWD, $user_pw);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

$result = curl_exec($ch);


?>
<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title></title>
    </head>
    <body>
        <?php
        try
        {
                $result_simplexml = new simpleXMLElement($result);
        }
        catch (Exception $e)
        {
                echo 'uh-oh';
        }

        if (!is_object($result_simplexml))
        {
                echo 'Could not parse XML, Response: ' . $result;
        }
        
        echo htmlentities($result_simplexml->asXML());
        
        ?>
    </body>
</html>
