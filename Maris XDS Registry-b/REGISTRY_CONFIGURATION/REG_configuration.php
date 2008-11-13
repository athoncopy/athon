<?php
# ------------------------------------------------------------------------------------
# MARIS XDS REGISTRY
# Copyright (C) 2007 - 2010  MARiS Project
# Dpt. Medical and Diagnostic Sciences, University of Padova - csaccavini@rad.unipd.it
# This program is distributed under the terms and conditions of the GPL
# See the LICENSE files for details
# ------------------------------------------------------------------------------------

##### FILE DI CONFIGURAZIONE DEL REGISTRY

//------------------- LOCAL REGISTRY HOST INFOS ------------------//
require('./config/config.php');
require_once('./lib/functions_'.$database.'.php');

$connessione=connectDB();

### QUERY FOR HTTP kind of CONNECTION WITH REGISTRY (NORMAL or TLS)
$http_con = "SELECT HTTPD FROM HTTP WHERE HTTP.ACTIVE = 'A'";



$res_http = query_select2($http_con,$connessione);

##### OTTENGO LE INFORMAZIONI SUL PROTOCOLLO
$http = $res_http[0][0];

###### PARAMETRO PROTOCOLLO HTTPS
$tls_protocol = "https://";
$normal_protocol = "http://";

############### SERVIZIO DI SUBMISSION
$get_reg_info="SELECT * FROM REGISTRY WHERE REGISTRY.SERVICE = 'SUBMISSION' AND REGISTRY.ACTIVE = 'A' AND REGISTRY.HTTP IN ($http_con)";

//include_once('./lib/functions_QUERY_mysql.php');
	$res_reg_info = query_select2($get_reg_info,$connessione);

###### OTTENGO LE INFORMAZIONI DI QUESTO REGISTRY (SUBMISSION)
$reg_host = $res_reg_info[0][1];
$reg_port = $res_reg_info[0][2];

################ SERVIZIO DI QUERY
$get_QUERY_info="SELECT * FROM REGISTRY WHERE REGISTRY.SERVICE = 'QUERY' AND REGISTRY.ACTIVE = 'A' AND REGISTRY.HTTP IN ($http_con)";

//include_once('./lib/functions_QUERY_mysql.php');
	$res_QUERY_info = query_select2($get_QUERY_info,$connessione);

###### OTTENGO LE INFORMAZIONI DI QUESTO REGISTRY (QUERY)
$reg_QUERY_host = $res_QUERY_info[0][1];
$reg_QUERY_port = $res_QUERY_info[0][2];

//------------------ LOCAL REGISTRY HOST INFOS ------------------//

//------------------ LOCAL FILE SYSTEM PATHS --------------------//

$tmp_path = "./tmp/";
$tmpQuery_path = "./tmpQuery/";
$tmpQueryService_path = "./tmpQueryService/";
$lib_path = "./lib/";      //nota: sempre con lo / finale!!!

$select_config = "SELECT * FROM CONFIG";
$res_config = query_select2($select_config,$connessione);

$www_REG_path=$res_config[0][0];
//$www_REG_path  = "/MARIS_xds3/xdsServices2/registry/";
//------------------ LOCAL FILE SYSTEM PATHS --------------------//

####################### PARAMETRI DI SERVIZIO

### PER I MESSAGGI DI FAILURE
$logentry = "\"http://$reg_host:$reg_port"."$www_REG_path\"";
$logentry_query = "\"http://$reg_QUERY_host:$reg_QUERY_port"."$www_REG_path\"";

##### NOME DEI SERVIZI
$service = "registry.php";
$service_query = "query.php";

###### PER LA CHIAMATA AL JAR FILE (VALIDAZIONE ebXML CON SCHEMA)
$path_to_VALIDATION_jar = "./XSD_VALIDATION_JAR/";
$path_to_XSD_file = "./schemas/rs.xsd";
$path_to_XSD_file_sq = "./schemas3/query.xsd";

###### PER COSTRUIRE L'ebXML DI RISPOSTA ALLE QUERY (NAMESPACES)
$ns_rim = "rim";
$ns_rim_path = "urn:oasis:names:tc:ebxml-regrep:rim:xsd:2.1";
$ns_q = "q";
$ns_q_path = "urn:oasis:names:tc:ebxml-regrep:query:xsd:2.1";

$ns_rim3 = "rim";
$ns_rim3_path = "urn:oasis:names:tc:ebxml-regrep:xsd:rim:3.0";
$ns_q3 = "q";
$ns_q3_path = "urn:oasis:names:tc:ebxml-regrep:xsd:query:3.0";

###### PER COSTRUIRE I MESSAGGI DI AUDIT ATNA
$path_to_IMPORT = "./atna/message/DataImport.xml";
$path_to_EXPORT = "./atna/message/DataExport.xml";
$path_to_QUERY = "./atna/message/Query.xml";

###### PER LA CHIAMATA AL JAR FILE (SENDING ATNA MESSAGES)
$path_to_ATNA_jar = "./atna/java/";

###### A CHI SPEDIRE I MESSAGGI ATNA
$get_ATNA_node = "SELECT * FROM ATNA";
//include_once('./lib/functions_QUERY_mysql.php');
	$res_ATNA_info = query_select2($get_ATNA_node,$connessione);

$ATNA_host = $res_ATNA_info[0][1];
$ATNA_port = $res_ATNA_info[0][2];
$ATNA_active = $res_ATNA_info[0][3];

##### LOGS ATNA
$atna_path = "./atna_logs/";

//------------------ LOCAL FILE SYSTEM PATHS ------------------//

##### PULIZIA CACHE TEMPORANEA

$clean_cache = $res_config[0][1];	### A=attiva O=non attivo


##### CONTROL PATIENT ID
### A=controlla il PatientID e se non presente nel database ritorna un errore 
## O=controlla il PatientID e se non presente lo inserisce nel database
$control_PatientID = $res_config[0][2];	


##### LOG

$logActive = $res_config[0][3];
$log_path = "./log/";



$java_path = $res_config[0][4];

####### NAV

$get_NAV="SELECT * FROM NAV";

$res_NAV = query_select2($get_NAV,$connessione);



$NAV = $res_NAV[0][0];    ### A=attiva O=non attivo
$NAV_from = $res_NAV[0][1];
$NAV_to = $res_NAV[0][2];




?>