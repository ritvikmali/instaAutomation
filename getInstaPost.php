<?php

require_once 'defines.php';
require_once 'database.php';


if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $data = json_decode(file_get_contents("php://input"));

    $pageData = getInstaPost($data);

    echo json_encode($pageData);
} else {
    http_response_code(405);
    echo "Method not allowed.";
}


function getInstaPost($data)
{
    global $conn;
    $sql = "select * from pages where insta_id = " . $data->instaId;
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $pageData = array($row);
        }
    }

    $pageAccessEndpoint = ENDPOINT_BASE . $data->instaId . '/media';

    // endpoint params
    $igParams = array(
        'fields' => 'id,ig_id,caption,media_url',
        'access_token' => $pageData[0]['page_access_token']
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
    $responseArray['pageId'] = $pageData[0]['page_id'];
    return $responseArray;
}


$conn->close();
