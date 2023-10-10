<?php
// file_put_contents( 'request_data.log', print_r( $_GET, true ), FILE_APPEND );
// echo $_GET['hub_challenge'];
// die();
require_once 'defines.php';
require_once 'database.php';

$json = file_get_contents('php://input');
$data = json_decode($json);

$sql = "insert into webhook_comments(comment_id,comment_text,media_id) values('" . $data->value->id . "','" . $data->value->text . "','" . $data->value->media->id . "')";

if ($conn->query($sql)) {
    header('Content-Type: application/json; charset=utf-8');
    $response = sendComment($data->value->id, $data->value->text, $data->value->media->id);
    echo json_encode($response);
} else {
    return false;
}

function sendComment($commentId, $commentText, $mediaId)
{
    global $conn;

    $searchSql = "select post_links.* , pages.*
                from post_links
                left join pages on post_links.page_id = pages.page_id
                where media_id = " . $mediaId;
    $result = $conn->query($searchSql);
    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();
        if (strpos(strtolower($commentText), $data['key_word']) !== false) {
            $sendReplyEndpoint = ENDPOINT_BASE . $data['page_id'] . '/messages';
            // endpoint params
            $igParams = array(
                'recipient' => '{ "comment_id": ' . $commentId . '}',
                'message' => '{ "text": "' . $data['text_to_send'] . '" }',
                'access_token' => $data['page_access_token']
            );

            // setup curl
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $sendReplyEndpoint);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($igParams));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, 1);

            // make call and get response
            $response = curl_exec($ch);
            curl_close($ch);
            $responseArray = json_decode($response, true);
            updateWebhook($responseArray, $data, $commentId);
            return $responseArray;
        } else {
            echo json_encode(array(
                'message_save' => 'success',
                'status' => 200
            ));
        }
    }
}

function updateWebhook($responseArray, $data, $commentId)
{
    global $conn;

    $recipient_id = (isset($responseArray['recipient_id']) && !empty(($responseArray['recipient_id']))) ? $responseArray['recipient_id'] : '';

    $sql = "update webhook_comments set is_send = '" . ((!empty($recipient_id)) ? 1 : 0) . "', recipient_id = '" . $recipient_id . "', text_sent = '" . $data['text_to_send'] . "' where comment_id = " . $commentId;

    if ($conn->query($sql)) {
        return true;
    } else {
        return false;
    }
}
