<?php

// Database credentials. Assuming you are running MySQL
// server with default setting (user 'root' with no password)
define('DB_SERVER', '127.0.0.1:3306');
define('DB_USERNAME', 'your_db_user');
define('DB_PASSWORD', 'your_db_pass');
define('DB_NAME', 'realmd');

// SOAP credentials and connection info (move here)
define('SOAP_REGNAME', 'your_soap_user');
define('SOAP_REGPASS', 'your_soap_pass');
define('SOAP_HOST', '127.0.0.1');
define('SOAP_PORT', 7878);

// Attempt to connect to MySQL database
$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if ($link === false) {
    die("ERROR: Could not connect. " . mysqli_connect_error());
}