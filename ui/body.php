<?php
$dbhost='localhost'; $dbuser='root'; $dbpass=''; $dbname='aveelora_db';
$conn = new mysqli($dbhost,$dbuser,$dbpass,$dbname);
if($conn->connect_error) die('DB connect error: '.$conn->connect_error);
$conn->set_charset('utf8mb4');
?>