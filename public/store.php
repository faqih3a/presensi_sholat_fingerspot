<?php
    include 'include/global.php';
    include 'include/function.php';

    $original_data  = file_get_contents('php://input');
    $decoded_data   = json_decode($original_data, true);
    $encoded_data   = json_encode($decoded_data);

    if (isset($decoded_data['type']) AND isset($decoded_data['cloud_id']))
    {

        $type       = $decoded_data['type'];
        $cloud_id   = $decoded_data['cloud_id'];
        $created_at = date('Y-m-d H:i:s');

        $sql    = "INSERT INTO t_log (cloud_id,type,created_at,original_data) VALUES ('".$cloud_id."', '".$type."', '".$created_at."', '".$encoded_data."')";
        $result = mysqli_query($conn, $sql);

    }
?>