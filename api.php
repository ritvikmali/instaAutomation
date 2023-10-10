<?php

require_once 'defines.php';
require_once 'database.php';


if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $data = json_decode(file_get_contents("php://input"));

    $longLiveToken = getLongLiveToken($data);
    $pageData = getPages($data, $longLiveToken);

    echo json_encode($pageData);
} else {
    http_response_code(405);
    echo "Method not allowed.";
}


function getLongLiveToken($data)
{
    global $conn;

    $accessTokenEndpoint = ENDPOINT_BASE . 'oauth/access_token';

    // endpoint params
    $igParams = array(
        'grant_type' => 'fb_exchange_token',
        'client_id' => FACEBOOK_APP_ID,
        'client_secret' => FACEBOOK_APP_SECRET,
        'fb_exchange_token' => $data->accessToken
    );

    // add params to endpoint
    $accessTokenEndpoint .= '?' . http_build_query($igParams);

    // setup curl
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $accessTokenEndpoint);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // make call and get response
    $response = curl_exec($ch);
    curl_close($ch);
    $responseArray = json_decode($response, true);

    $checkQuery = "select * from users where user_id = '" . $data->userId . "'";
    if ($conn->query($checkQuery)->num_rows > 0) {
        $sql = "update users set access_token = '" . $responseArray['access_token'] . "' where user_id = '" . $data->userId . "'";
    } else {
        $sql = "insert into users(user_id,access_token) values('" . $data->userId . "','" . $responseArray['access_token'] . "')";
    }

    if ($conn->query($sql)) {
        return $responseArray['access_token'];
    } else {
        return false;
    }
}

function getPages($data, $longLiveToken)
{
    $pageAccessEndpoint = ENDPOINT_BASE . $data->userId . '/accounts';

    // endpoint params
    $igParams = array(
        'access_token' => $longLiveToken
    );

    // add params to endpoint
    $pageAccessEndpoint .= '?' . http_build_query($igParams);

    // setup curl
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $pageAccessEndpoint);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // make call and get response
    $response = curl_exec($ch);
    curl_close($ch);
    $responseArray = json_decode($response, true);
    $responseArray['userId'] = $data->userId;
    return $responseArray;
}

$conn->close();
