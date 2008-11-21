<?php
# ------------------------------------------------------------------------------------
# MARIS XDS REGISTRY
# Copyright (C) 2007 - 2010  MARiS Project
# Dpt. Medical and Diagnostic Sciences, University of Padova - csaccavini@rad.unipd.it
# This program is distributed under the terms and conditions of the GPL

# Contributor(s):
# A-thon srl <info@a-thon.it>
# Alberto Castellini

# See the LICENSE files for details
# ------------------------------------------------------------------------------------

require('./config/config.php');
require('./lib/functions_'.$database.'.php');
$connessione=connectDB();
############## HTTP ################

$REG_http_post = $_POST['registry_http'];

$deleteREG_HTTP = "DELETE FROM HTTP";
$REG_HTTP_delete = query_exec2($deleteREG_HTTP,$connessione);

$insertREG_HTTP = "INSERT INTO HTTP (httpd,active) VALUES ('$REG_http_post','A')";
$REG_HTTP_insert = query_exec2($insertREG_HTTP,$connessione);


################## REGISTRY CACHE ###################

$REG_cache = $_POST['registry_cache'];
$REG_patient = $_POST['registry_patient'];
$REG_log = $_POST['registry_log'];
$REG_stat = $_POST['registry_stat'];
$REG_folder = $_POST['folder'];

$deleteREG_config = "DELETE FROM CONFIG";
$REG_delete_config = query_exec2($deleteREG_config,$connessione);
$insertREG_config = "INSERT INTO CONFIG (CACHE,PATIENTID,LOG,STAT,FOLDER) VALUES ('$REG_cache','$REG_patient','$REG_log','$REG_stat','$REG_folder')";
$REG_insert_config = query_exec2($insertREG_config,$connessione);


############### ATNA ###############
$REG_host_post_ATNA = $_POST['registry_host_atna'];
$REG_port_post_ATNA = $_POST['registry_port_atna'];
$REG_status_post_ATNA = $_POST['registry_atna_status'];



$deleteREG_ATNA = "DELETE FROM ATNA";
$REG_delete_ATNA = query_exec2($deleteREG_ATNA,$connessione);


$insertATNA = "INSERT INTO ATNA (id,host,port,active,description) VALUES ('1','$REG_host_post_ATNA','$REG_port_post_ATNA','$REG_status_post_ATNA','ATNA REGISTRY')";
$REG_ATNA_insert = query_exec2($insertATNA,$connessione);

################## REGISTRY NAV ###################

$REG_NAV = $_POST['registry_nav'];
$REG_NAV_from = $_POST['registry_nav_from'];
$REG_NAV_to = $_POST['registry_nav_to'];

$deleteREG_NAV = "DELETE FROM NAV";
$REG_delete_NAV = query_exec2($deleteREG_NAV,$connessione);
$insertREG_nav = "INSERT INTO NAV (nav,nav_from,nav_to) VALUES ('$REG_NAV','$REG_NAV_from','$REG_NAV_to')";
$REG_insert_nav = query_exec2($insertREG_nav,$connessione);


disconnectDB($connessione);

header('location: setup.php');

?>