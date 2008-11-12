<?php
# ------------------------------------------------------------------------------------
# MARIS XDS REPOSITORY
# Copyright (C) 2007 - 2010  MARiS Project
# Dpt. Medical and Diagnostic Sciences, University of Padova - csaccavini@rad.unipd.it
# This program is distributed under the terms and conditions of the GPL
# See the LICENSE files for details
# ------------------------------------------------------------------------------------

include_once('./config/config.php');
include_once("config/REP_configuration.php");
if($database=="MYSQL"){
include_once('./lib/functions_mysql.php');
}
else if($database=="ORACLE"){
include_once('./lib/functions_oracle.php');
}

$token=$_GET["token"];
$get_token="SELECT URI FROM DOCUMENTS WHERE KEY_PROG=$token";
$uri_token=query_select($get_token);

if($ATNA_active=='A'){
include_once("rep_atna.php");

$eventOutcomeIndicator="0"; //EventOutcomeIndicator 0 OK 12 ERROR
$ip_repository=$rep_host; 
$ip_consumer=$_SERVER['REMOTE_ADDR']; 


createExportEvent($eventOutcomeIndicator,$ip_repository,$ip_consumer);

$java_atna_export=($java_path."java -jar ".$path_to_ATNA_jar."syslog.jar -u ".$ATNA_host." -p ".$ATNA_port." -f ".$atna_path."DataExport.xml");
//echo $java_atna_export;
if($save_files){
$fp_ebxml_val = fopen($tmp_path.$idfile."-comando_java_atna_export-".$idfile,"w+");
	fwrite($fp_ebxml_val,$java_atna_export);
fclose($fp_ebxml_val);
}

//$java_call_result = exec("$java_atna_export");

$INSERT_atna_export = "INSERT INTO AUDITABLEEVENT (EVENTTYPE,REGISTRYOBJECT,TIME_STAMP,SOURCE) VALUES ('Export','".$uri_token[0][0]."',CURRENT_TIMESTAMP,'".$ip_consumer."')";

$fp_ebxml_val =
fopen($tmp_path.$idfile."-insert_java_atna_export-".$idfile,"w+");
	fwrite($fp_ebxml_val,$INSERT_atna_export);
fclose($fp_ebxml_val);

$ris_export = query_execute($INSERT_atna_export);


}



header("Location: ".$www_REP_path.$uri_token[0][0]);
?>
