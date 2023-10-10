<?php
require_once 'defines.php';

require_once 'database.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $data = json_decode(file_get_contents("php://input"));

    $instaData = getInstagramData($data);

    echo json_encode($instaData);
} else {
    http_response_code(405);
    echo "Method not allowed.";
}

function getInstagramData($data)
{
    global $conn;

    $pageAccessEndpoint = ENDPOINT_BASE . $data->pageId;

    // endpoint params
    $igParams = array(
        'fields' => 'instagram_business_account',
        'access_token' => $data->accessToken
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
    if (isset($responseArray['instagram_business_account']) && $responseArray['instagram_business_account']['id']) {

        $checkQuery = "select * from pages where user_id = '" . $data->userId . "'";
        if ($conn->query($checkQuery)->num_rows > 0) {
            $sql = "update pages set page_id = '" . $data->pageId . "',page_access_token = '" . $data->accessToken . "',insta_id = '" . $responseArray['instagram_business_account']['id'] . "' where user_id = '" . $data->userId . "'";
        } else {
            $sql = "insert into pages(user_id,page_id,page_access_token,insta_id) values('" . $data->userId . "','" . $data->pageId . "','" . $data->accessToken . "','" . $responseArray['instagram_business_account']['id'] . "')";
        }

        if ($conn->query($sql)) {
            $result = getInstagramAccountData($data, $responseArray);
            return $result;
        } else {
            return false;
        }
    } else {
        return array(
            'message' => 'No Instagram Business Account Connected To The Page',
            'status' => 0,
        );
    }
}

function getInstagramAccountData($data, $instaData)
{
    $pageAccessEndpoint = ENDPOINT_BASE . $instaData['instagram_business_account']['id'];

    // endpoint params
    $igParams = array(
        'fields' => 'id,username,biography,profile_picture_url',
        'access_token' => $data->accessToken
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
    $responseArray['status'] = 1;
    return $responseArray;
}

$conn->close();
