<?php

require_once 'defines.php';
require_once 'database.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $data = json_decode(file_get_contents("php://input"));
    $response = array();
    $searchSql = "select * from post_links where page_id = '" . $data->pageId . "' and media_id = '" . $data->mediaId . "'";
    $result = $conn->query($searchSql);
    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();
        $response['message'] = $data['text_to_send'];
        $response['status'] = true;
        echo json_encode($response);
    } else {
        echo array('status' => false);
    }
} else {
    http_response_code(405);
    echo "Method not allowed.";
}
