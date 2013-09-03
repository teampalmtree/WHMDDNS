<?php

// require configuration
require_once('WHMDDNS.config.php');

////////////////////////
// VERIFY CREDENTIALS //
////////////////////////

if (!isset($_SERVER['PHP_AUTH_USER']))
{
    header('WWW-Authenticate: Basic realm="WHMDDNS"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'AUTHORIZATION_REQUIRED';
    exit;
}

////////////////////
// WHM PARAMETERS //
////////////////////

$WHM_username = $_SERVER['PHP_AUTH_USER'];
$WHM_password = $_SERVER['PHP_AUTH_PW'];

///////////////////////
// RECORD PARAMETERS //
///////////////////////

// get hostname, record zone, record name
$hostname = $_REQUEST["hostname"];
$hostname_dot_index = strpos($hostname, '.');
$record_zone = substr($hostname, $hostname_dot_index + 1);
$record_name = $hostname . '.';
// get ip address
$record_address = $_REQUEST["myip"];

//////////////////
// CURL REQUEST //
//////////////////

function curl_request($query)
{

    // setup curl and execute request
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $header[0] = 'Authorization: Basic ' . base64_encode($GLOBALS['WHM_username'] . ':' . $GLOBALS['WHM_password']) . "\n\r";
    curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
    curl_setopt($curl, CURLOPT_URL, $GLOBALS['WHM_URL'] . $query);
    $response = curl_exec($curl);
    // process response
    if (!$response)
    {
        echo "CURL_ERROR [" . $query . "] (" . curl_error($curl) . ")\r\n";
        curl_close($curl);
        return null;
    }

    // close curl
    curl_close($curl);
    // good response
    $response = json_decode($response, true);
    $results = $response['result'][0];
    return $results;

}

///////////////////////
// RECORD MANAGEMENT //
///////////////////////

function get_record_line()
{
    // setup dump zone query
    $query = "/json-api/dumpzone?domain=" . $GLOBALS['record_zone'];
    // execute curl request
    $result = curl_request($query);
    // verify result
    if (!$result)
        return null;

    // loop through all records for this domain
    // looking for one that matches the url (and type A)
    foreach ($result['record'] AS $record)
    {
        if (($record['name'] == $GLOBALS['record_name']) && ($record['type'] == "A"))
            return $record['Line'];
    }

    // failed to find record
    return -1;

}

function add_record()
{

    // create add record query
    $query = "/json-api/addzonerecord?zone=" . $GLOBALS['record_zone']
        . "&name=" . $GLOBALS['record_name']
        . "&address=" . $GLOBALS['record_address']
        . "&type=A&class=IN&ttl=" . $GLOBALS['record_TTL'];
    // process query
    if (curl_request($query))
        echo "RECORD_ADDED\n";

}

function update_record($record_line)
{

    // create update record query
    $query = "/json-api/editzonerecord?zone=" . $GLOBALS['record_zone']
        . "&Line=" . $record_line
        . "&address=" . $GLOBALS['record_address']
        . "&type=A&class=IN&ttl=" . $GLOBALS['record_TTL'];
    // process query
    if (curl_request($query))
        echo "RECORD_UPDATED\n";
}

////////////////
// INITIALIZE //
////////////////

// first see if we already have a record
$record_line = get_record_line();
// if we have it, update it, else create
if ($record_line > 0)
    update_record($record_line);
else if ($record_line === -1)
    add_record();