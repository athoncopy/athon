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

############## HTTP ################

$REG_http_post = $_POST['registry_http'];

$deleteREG_HTTP = "DELETE FROM HTTP";
$REG_HTTP_delete = query_exec($deleteREG_HTTP);

$insertREG_HTTP = "INSERT INTO HTTP (httpd,active) VALUES ('$REG_http_post','A')";
$REG_HTTP_insert = query_exec($insertREG_HTTP);

################## REGISTRY SUBMISSION ###################

$REG_host_submission = $_POST['registry_host_submission'];
$REG_port_submission = $_POST['registry_port_submission'];
//$REG_http_submission = $_POST['registry_http_submission'];
$REG_http_submission = "NORMAL";


$deleteREG_HTTP = "DELETE FROM REGISTRY";
$REG_delete_HTTP = query_exec($deleteREG_HTTP);

$insertREG_submission = "INSERT INTO REGISTRY (id,host,port,service,active,http) VALUES ('1','$REG_host_submission','$REG_port_submission','SUBMISSION','A','$REG_http_submission')";
$REG_insert_submission = query_exec($insertREG_submission);


################## REGISTRY QUERY ###################

$REG_host_query = $_POST['registry_host_query'];
$REG_port_query = $_POST['registry_port_query'];
//$REG_http_query = $_POST['registry_http_query'];
$REG_http_query = "NORMAL";


$insertREG_query = "INSERT INTO REGISTRY (id,host,port,service,active,http) VALUES ('2','$REG_host_query','$REG_port_query','QUERY','A','$REG_http_query')";
$REG_insert_query = query_exec($insertREG_query);


################## REGISTRY CACHE ###################

$REG_www = $_POST['registry_www'];
$REG_cache = $_POST['registry_cache'];
$REG_patient = $_POST['registry_patient'];
$REG_log = $_POST['registry_log'];

$deleteREG_config = "DELETE FROM CONFIG";
$REG_delete_config = query_exec($deleteREG_config);
$insertREG_config = "INSERT INTO CONFIG (www,cache,patientid,log) VALUES ('$REG_www','$REG_cache','$REG_patient','$REG_log')";
$REG_insert_config = query_exec($insertREG_config);


############### ATNA ###############
$REG_host_post_ATNA = $_POST['registry_host_atna'];
$REG_port_post_ATNA = $_POST['registry_port_atna'];
$REG_status_post_ATNA = $_POST['registry_atna_status'];



$deleteREG_ATNA = "DELETE FROM ATNA";
$REG_delete_ATNA = query_exec($deleteREG_ATNA);


$insertATNA = "INSERT INTO ATNA (id,host,port,active,description) VALUES ('1','$REG_host_post_ATNA','$REG_port_post_ATNA','$REG_status_post_ATNA','ATNA REGISTRY')";
$REG_ATNA_insert = query_exec($insertATNA);

################## REGISTRY NAV ###################

$REG_NAV = $_POST['registry_nav'];
$REG_NAV_from = $_POST['registry_nav_from'];
$REG_NAV_to = $_POST['registry_nav_to'];

$deleteREG_NAV = "DELETE FROM NAV";
$REG_delete_NAV = query_exec($deleteREG_NAV);
$insertREG_nav = "INSERT INTO NAV (nav,nav_from,nav_to) VALUES ('$REG_NAV','$REG_NAV_from','$REG_NAV_to')";
$REG_insert_nav = query_exec($insertREG_nav);

header('location: setup.php');

?>