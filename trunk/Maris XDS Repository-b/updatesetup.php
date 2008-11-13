<?php
# ------------------------------------------------------------------------------------
# MARIS XDS REPOSITORY
# Copyright (C) 2007 - 2010  MARiS Project
# Dpt. Medical and Diagnostic Sciences, University of Padova - csaccavini@rad.unipd.it
# This program is distributed under the terms and conditions of the GPL
# See the LICENSE files for details
# ------------------------------------------------------------------------------------

include_once('./config/config.php');
include_once('./lib/functions_'.$database.'.php');

$REG_host_post = $_POST['registry_host'];
$REG_port_post = $_POST['registry_port'];
$REG_path_post = $_POST['registry_path'];
$REG_http_post = $_POST['registry_http'];


$deleteREG = "DELETE FROM REGISTRY";
$REG_delete = query_execute($deleteREG);

$insertREG = "INSERT INTO REGISTRY (ID,HOST,PORT,PATH,ACTIVE,HTTP,SERVICE,DESCRIPTION) VALUES ('1','$REG_host_post','$REG_port_post','$REG_path_post','A','$REG_http_post','SUBMISSION','REGISTRY')";
//echo $insertREG;
$REG_insert = query_execute($insertREG);


$REP_host_post = $_POST['repository_host'];
$REP_port_post = $_POST['repository_port'];
$REP_http_post = $_POST['repository_http'];


$deleteREP = "DELETE FROM REPOSITORY";
$REP_delete = query_execute($deleteREP);

$insertREP = "INSERT INTO REPOSITORY (ID,HOST,PORT,SERVICE,ACTIVE,HTTP) VALUES ('1','$REP_host_post','$REP_port_post','SUBMISSION','A','$REP_http_post')";
//echo $insertREP;
$REP_insert = query_execute($insertREP);

$REP_www_post = $_POST['repository_www'];
$REP_log_post = $_POST['repository_log'];
$REP_cache_post = $_POST['repository_cache'];
$REP_files_post = $_POST['repository_files'];
$REP_java_home = $_POST['repository_java_home'];
$REP_uniqueID = $_POST['repository_uniqueid'];

$deleteREP_config = "DELETE FROM CONFIG";
$REP_delete_config = query_execute($deleteREP_config);

$insertREP_config = "INSERT INTO CONFIG (WWW,LOG,CACHE,FILES,JAVA_PATH,UNIQUEID) VALUES ('$REP_www_post','$REP_log_post','$REP_cache_post','$REP_files_post','$REP_java_home','$REP_uniqueID')";
//echo $insertREP_config;
$REP_insert_config = query_execute($insertREP_config);




$ATNA_status = $_POST['repository_atna_status'];
$ATNA_host = $_POST['repository_atna_host'];
$ATNA_port = $_POST['repository_atna_port'];


$deleteATNA = "DELETE FROM ATNA";
$ATNA_delete = query_execute($deleteATNA);

$insertATNA = "INSERT INTO ATNA (ID,HOST,PORT,ACTIVE,DESCRIPTION) VALUES ('1','$ATNA_host','$ATNA_port','$ATNA_status','ATNA NODE')";
//echo $insertREP;
$ATNA_insert = query_execute($insertATNA);



header('location: setup.php');

?>