<?php

include 'database.php';

function aa()
{
    global $conn;
    $sql = "select * from users";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $data[] = array($row);
        }
    } else {
        echo "No records found";
    }

    var_dump($data);
}

aa();
die;
