<?php

include_once('./lib/functions_mysql.php');


$Login = $_POST['login'];
$Password = crypt($_POST['password'],'xds');



$deleteUSER = "DELETE FROM USERS";
$USER_delete = query_execute($deleteUSER);

$insertUSER = "INSERT INTO USERS (login,password) VALUES ('$Login','$Password')";
$USER_insert = query_execute($insertUSER);


header('location: setup.php');

?>