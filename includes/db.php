<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "university_db";

mysqli_report(MYSQLI_REPORT_STRICT | MYSQLI_REPORT_ERROR);

try{
    $conn = new mysqli($servername, $username, $password, $dbname);
}catch(mysqli_sql_exception $e){
    error_log("[DB CONNECTION ERROR] " . $e->getMessage(), 0);
}


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?>
