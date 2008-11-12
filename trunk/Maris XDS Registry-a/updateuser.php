<?php
# ------------------------------------------------------------------------------------
# MARIS XDS REGISTRY
# Copyright (C) 2007 - 2010  MARiS Project
# Dpt. Medical and Diagnostic Sciences, University of Padova - csaccavini@rad.unipd.it
# This program is distributed under the terms and conditions of the GPL
# See the LICENSE files for details
# ------------------------------------------------------------------------------------

require('./config/config.php');
require('./lib/functions_'.$database.'.php');

$connessione=connectDB();

$Login = $_POST['login'];
$Password = crypt($_POST['password'],'xds');



$deleteUSER = "DELETE FROM USERS";
$USER_delete = query_exec2($deleteUSER,$connessione);

$insertUSER = "INSERT INTO USERS (login,password) VALUES ('$Login','$Password')";
$USER_insert = query_exec2($insertUSER,$connessione);

disconnectDB($connessione);
header('location: setup.php');

?>