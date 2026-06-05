<php

$url = 'https://developer.fingerspot.io/api/get_attlog';
$data = '{"trans_id":"1", "cloud_id":"S118001290", "start_date":"2026-05-30", "end_date":"2026-06-30"}';
$authorization = "Authorization: Bearer FLDYHON2Z9S53DQ9";

$postdata = $data;

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , $authorization));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
$result = curl_exec($ch);
curl_close($ch);
print_r ($result);

?>