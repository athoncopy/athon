<?php
# ------------------------------------------------------------------------------------
# MARIS XDS REPOSITORY
# Copyright (C) 2007 - 2010  MARiS Project
# Dpt. Medical and Diagnostic Sciences, University of Padova - csaccavini@rad.unipd.it
# This program is distributed under the terms and conditions of the GPL
# See the LICENSE files for details
# ------------------------------------------------------------------------------------


include_once('./config/config.php');
require_once('./lib/functions_'.$database.'.php');

$Login = $_POST['login'];
$Password = crypt($_POST['password'],'xds');



$deleteUSER = "DELETE FROM USERS";
$USER_delete = query_execute($deleteUSER);

$insertUSER = "INSERT INTO USERS (LOGIN,PASSWORD) VALUES ('$Login','$Password')";
$USER_insert = query_execute($insertUSER);


header('location: setup.php');

?>