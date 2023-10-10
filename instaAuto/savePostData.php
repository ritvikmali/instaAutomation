<?php

require_once 'defines.php';
require_once 'database.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $data = json_decode(file_get_contents("php://input"));

    $response = savePostData($data);

    echo json_encode($response);
} else {
    http_response_code(405);
    echo "Method not allowed.";
}

function savePostData($data)
{
    global $conn;
    $checkQuery = "select * from post_links where page_id = '" . $data->pageId . "' and media_id = '" . $data->mediaId . "'";
    if ($conn->query($checkQuery)->num_rows > 0) {
        $sql = "update post_links set text_to_send = '" . $data->textToSend . "' where page_id = '" . $data->pageId . "' and media_id = '" . $data->mediaId . "'";
    } else {
        $sql = "insert into post_links(page_id,media_id,key_word,text_to_send) values('" . $data->pageId . "','" . $data->mediaId . "','link','" . $data->textToSend . "')";
    }

    if ($conn->query($sql)) {
        return array('status' => true);
    } else {
        return false;
    }
}
