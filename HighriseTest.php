<?php
$highrise_api_key = '0f5b609203e0f9b3af5d4325215876d2';
$highrise_username = 'merchantosintern';

$highrise_api = new HighriseAPICall($highrise_api_key, $highrise_username);
$result_xml = $higrise_api->makeAPICall("GET", "people.xml");

?>
<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title></title>
    </head>
    <body>
        <?php
        echo htmlentities($result_xml->asXML());
        ?>
    </body>
</html>
