<?php
// Credenziali database
define('DB2_USER', 'JDESPYTEST');
define('DB2_PASS', 'JBALLS18');

$server="Driver={IBM i Access ODBC Driver};System=127.0.0.1;Uid=".DB2_USER.";Pwd=".DB2_PASS.";TRANSLATE=1;";
$user=DB2_USER;
$pass=DB2_PASS;
$db_connection=odbc_connect($server,$user,$pass);
if(!$db_connection) {
    $errMsg = "Errore connessione al database : ".odbc_errormsg();
    error_log($errMsg);
    exit;
}
?>