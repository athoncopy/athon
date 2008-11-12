<?php
# ------------------------------------------------------------------------------------
# MARIS XDS REGISTRY
# Copyright (C) 2007 - 2010  MARiS Project
# Dpt. Medical and Diagnostic Sciences, University of Padova - csaccavini@rad.unipd.it
# This program is distributed under the terms and conditions of the GPL
# See the LICENSE files for details
# ------------------------------------------------------------------------------------

include_once('./config/config.php');
if($database=="MYSQL"){
include_once('./lib/functions_QUERY_mysql.php');
}
else if($database=="ORACLE"){
include_once('./lib/functions_oracle.php');
}


$Login = $_POST['login'];
$Password = crypt($_POST['password'],'xds');



$deleteUSER = "DELETE FROM USERS";
$USER_delete = query_exec($deleteUSER);

$insertUSER = "INSERT INTO USERS (login,password) VALUES ('$Login','$Password')";
$USER_insert = query_exec($insertUSER);


header('location: setup.php');

?>