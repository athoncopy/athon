<?php
# ------------------------------------------------------------------------------------
# MARIS XDS REPOSITORY
# Copyright (C) 2007 - 2010  MARiS Project
# Dpt. Medical and Diagnostic Sciences, University of Padova - csaccavini@rad.unipd.it
# This program is distributed under the terms and conditions of the GPL
# See the LICENSE files for details
# ------------------------------------------------------------------------------------

##### FILE DI CONFIGURAZIONE DEL REPOSITORY
require('config/config.php');
### QUERY FOR HTTP kind of CONNECTION WITH REGISTRY (NORMAL or TLS)
//$http_con = "SELECT HTTPD FROM HTTP WHERE HTTP.ACTIVE = 'A'";
require_once('./lib/functions_'.$database.'.php');
$connessione=connectDB();
//$res_http = query_select2($http_con,$connessione);

##### OTTENGO LE INFORMAZIONI SUL PROTOCOLLO
//$http = $res_http[0][0];

###### PARAMETRO PROTOCOLLO HTTPS
$tls_protocol = "https://";
$normal_protocol = "http://";

//------------------------- LOCAL REPOSITORY HOST INFOS -------------------------//

$get_repo_info="SELECT HOST,PORT,HTTP FROM REPOSITORY WHERE REPOSITORY.SERVICE = 'SUBMISSION' AND REPOSITORY.ACTIVE = 'A'";
$res = query_select2($get_repo_info,$connessione);

###### OTTENGO LE INFORMAZIONI DI QUESTO REPOSITORY
$rep_host = $res[0][0];
$rep_port = $res[0][1];
$rep_protocol = $res[0][2];

//------------------------- LOCAL REPOSITORY HOST INFOS -------------------------//

//------------------------- LOCAL FILE SYSTEM PATHS -------------------------//
$tmp_path = "./tmp/";
//$tmpQuery_path = "./tmpQuery/";
$lib_path = "./lib/";
   $tmp_path_2 = "tmp/";                         //nota: sempre con lo / finale!!!


$relative_docs_path = "./Submitted_Documents/".date("Y")."/".date("m")."/".date("d")."/";   // come sopra


$relative_docs_path_2 = "Submitted_Documents/".date("Y")."/".date("m")."/".date("d")."/";//PER COMPORRE L'URI


$select_config = "SELECT WWW,LOG,CACHE,FILES,JAVA_PATH FROM CONFIG";
$res_config = query_select2($select_config,$connessione);

//$www_REP_path = "/MARIS_xds3/xdsServices2/repository/";
$www_REP_path=$res_config[0][0];


$www_docs_path = $www_REP_path.$relative_docs_path_2;//PER COMPORRE L'URI

#### VARIABILI DI SERVIZIO
//============ SECURITY OPTIONS ====================
define ("IS_SECURE", FALSE);
define("MY_CERT", "/var/www/MARIS_XDS/repository-a/certificates/athon1.ihe-europe.org.p12");
//define("MY_CERT", "/srv/www/htdocs/Consumer/consumer/ssl/ssl/o3-c1.ihe-europe.org.cer");
//define("MY_CERT", "/home/eso/Desktop/TLS/usati/o3_1.p12");
define("MY_KEY", "/var/www/MARIS_XDS/repository-a/certificates/athon1.ihe-europe.org.pem");
//define("MY_KEY", "/srv/www/htdocs/Consumer/consumer/ssl/ssl/o3c1.pem");
define ("MY_CAPATH","/var/www/MARIS_XDS/repository-a/certificates/");
//define ("MY_CAINFO", "/srv/www/htdocs/Consumer/consumer/ssl/ssl/tiani-spirit3.ihe-europe.org.cer");
//define ("MY_CAINFO", "/var/www/MARIS_XDS/repository-a/certificates/ac_gipcps1.cer");
define ("MY_CAINFO", "/var/www/MARIS_XDS/repository-a/certificates/cacerts.jks");
#### LOGS
//$log_path = "./log/log.out";
$log_path = "./log/";


//$logActive = "A";
$logActive = $res_config[0][1];

$res_config[0][1];
##### PULIZIA CACHE TEMPORANEA
//$clean_cache = "O";	### A=attiva O=non attivo
$clean_cache = $res_config[0][2];

##### True=Salva tutti i file False=Salva solo i file necessari

if($res_config[0][3]=="A"){
	$save_files = true;
	}
#### JAVA PATH
$java_path = $res_config[0][4];


#### MESSAGGI
$service = "repository.php";
$logentry = "\"http://$rep_host:$rep_port"."$www_REP_path\"";

### PER LA CHIAMATA AL JAR FILE
$path_to_VALIDATION_jar = "./XSD_VALIDATION_JAR/";
$path_to_XSD_file = "./schemas/rs.xsd";


//------------------- LOCAL FILE SYSTEM PATHS ---------------------//

//------------------- TO REGISTRY CONNECTION INFOS -------------------//

####  LEGGO LE INFORMAZIONI DA DB: NODO ATTIVO
$select_registry = "SELECT HOST,PORT,PATH,HTTP FROM REGISTRY WHERE REGISTRY.ACTIVE = 'A' AND REGISTRY.SERVICE = 'SUBMISSION'";
$ris = query_select($select_registry);

#### OTTENGO LE INFORMAZIONI DEL NODO REGISTRY
$reg_host = $ris[0][0];
$reg_port = $ris[0][1];
$reg_path = $ris[0][2];
$reg_http = $ris[0][3];

###### PER COSTRUIRE I MESSAGGI DI AUDIT ATNA
$path_to_IMPORT = "./atna/message/DataImport.xml";
$path_to_EXPORT = "./atna/message/DataExport.xml";
$path_to_QUERY = "./atna/message/Query.xml";

###### PER LA CHIAMATA AL JAR FILE (SENDING ATNA MESSAGES)
$path_to_ATNA_jar = "./atna/java/";

###### A CHI SPEDIRE I MESSAGGI ATNA
$get_ATNA_node = "SELECT * FROM ATNA";
	$res_ATNA = query_select2($get_ATNA_node,$connessione);

$ATNA_host = $res_ATNA[0][1];
$ATNA_port = $res_ATNA[0][2];
$ATNA_active = $res_ATNA[0][3];

##### LOGS ATNA
$atna_path = "./atna_logs/";



$namespacerim_path="urn:oasis:names:tc:ebxml-regrep:rim:xsd:2.1";


//-------------------------- TO REGISTRY CONNECTION INFOS --------------------------//

?>