<?php

##### FILE DI CONFIGURAZIONE DEL REPOSITORY

### QUERY FOR HTTP kind of CONNECTION WITH REGISTRY (NORMAL or TLS)
$http_con = "SELECT HTTPD FROM HTTP WHERE HTTP.ACTIVE = 'A'";
include_once('./lib/functions_mysql.php');
$res_http = query_select($http_con);

##### OTTENGO LE INFORMAZIONI SUL PROTOCOLLO
$http = $res_http[0][0];

###### PARAMETRO PROTOCOLLO HTTPS
$tls_protocol = "https://";
$normal_protocol = "http://";

//------------------------- LOCAL REPOSITORY HOST INFOS -------------------------//

$get_repo_info="SELECT * FROM REPOSITORY WHERE REPOSITORY.SERVICE = 'SUBMISSION' AND REPOSITORY.ACTIVE = 'A' AND REPOSITORY.HTTP IN ($http_con)";
$res = query_select($get_repo_info);

###### OTTENGO LE INFORMAZIONI DI QUESTO REPOSITORY
$rep_host = $res[0][1];
$rep_port = $res[0][2];

//------------------------- LOCAL REPOSITORY HOST INFOS -------------------------//

//------------------------- LOCAL FILE SYSTEM PATHS -------------------------//
$tmp_path = "./tmp/";
$tmpQuery_path = "./tmpQuery/";
$lib_path = "./lib/";
   $tmp_path_2 = "tmp/";                         //nota: sempre con lo / finale!!!


$relative_docs_path = "./Submitted_Documents/".date("Y")."/".date("m")."/".date("d")."/";   // come sopra


$relative_docs_path_2 = "Submitted_Documents/".date("Y")."/".date("m")."/".date("d")."/";//PER COMPORRE L'URI


$select_config = "SELECT * FROM CONFIG";
$res_config = query_select($select_config);

//$www_REP_path = "/MARIS_xds3/xdsServices2/repository/";
$www_REP_path=$res_config[0][0];


$www_docs_path = $www_REP_path.$relative_docs_path_2;//PER COMPORRE L'URI

#### VARIABILI DI SERVIZIO

#### LOGS
$log_path = "./log/log.out";
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

#### MESSAGGI
$service = "repository.php";
$logentry = "\"http://$rep_host:$rep_port"."$www_REP_path\"";

### PER LA CHIAMATA AL JAR FILE
$path_to_VALIDATION_jar = "./XSD_VALIDATION_JAR/";
$path_to_XSD_file = "./schemas/rs.xsd";


//------------------- LOCAL FILE SYSTEM PATHS ---------------------//

//------------------- TO REGISTRY CONNECTION INFOS -------------------//

####  LEGGO LE INFORMAZIONI DA DB: NODO ATTIVO
$select_registry = "SELECT * FROM REGISTRY WHERE REGISTRY.ACTIVE = 'A' AND REGISTRY.SERVICE = 'SUBMISSION' AND REGISTRY.HTTP IN ($http_con)";
$ris = query_select($select_registry);

#### OTTENGO LE INFORMAZIONI DEL NODO REGISTRY
$reg_host = $ris[0][1];
$reg_port = $ris[0][2];
$reg_path = $ris[0][3];
$reg_description = $ris[0][7];

###### PER COSTRUIRE I MESSAGGI DI AUDIT ATNA
$path_to_IMPORT = "./atna/message/DataImport.xml";
$path_to_EXPORT = "./atna/message/DataExport.xml";
$path_to_QUERY = "./atna/message/Query.xml";

###### PER LA CHIAMATA AL JAR FILE (SENDING ATNA MESSAGES)
$path_to_ATNA_jar = "./atna/java/";

###### A CHI SPEDIRE I MESSAGGI ATNA
$get_ATNA_node = "SELECT * FROM ATNA";
include_once('./lib/functions_mysql.php');
	$res_ATNA = query_select($get_ATNA_node);

$ATNA_host = $res_ATNA[0][1];
$ATNA_port = $res_ATNA[0][2];
$ATNA_active = $res_ATNA[0][3];

##### LOGS ATNA
$atna_path = "./atna_logs/";


//-------------------------- TO REGISTRY CONNECTION INFOS --------------------------//

?>