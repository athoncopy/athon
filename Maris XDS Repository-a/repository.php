<?php

# MARIS XDS REPOSITORY
# Copyright (C) 2007 - 2010  MARiS Project
# Dpt. Medical and Diagnostic Sciences, University of Padova - csaccavini@rad.unipd.it
# This program is distributed under the terms and conditions of the GPL
# See the LICENSE files for details

//BLOCCO IL BUFFER DI USCITA
ob_start();//OKKIO FONADAMENTALE!!!!!!!!!!

##### CONFIGURAZIONE DEL REPOSITORY
include("config/REP_configuration.php");
#######################################
include_once($lib_path."domxml-php4-to-php5.php");
include_once($lib_path."utilities.php");
include_once($lib_path."log.php");
include_once($lib_path."dom_utils.php");
$system=PHP_OS;

$windows=substr_count(strtoupper($system),"WIN");

$idfile = idrandom_file();

#### CREO L'OGGETTO DI LOG ####
$log = new Log("REP");
$log->set_tmp_path($tmp_path);
$log->set_idfile($idfile);
$log->setLogActive($logActive);
//$log->setCleanCache($clean_cache);
//$log->setCurrentNumFile();
$log->setCurrentLogPath($log_path);
$log->setCurrentFileSLogPath($tmp_path);

$errorcode=array();
$advertise=array();

$_SESSION['tmp_path']=$tmp_path;
$_SESSION['idfile']=$idfile;
$_SESSION['logActive']=$logActive;
$_SESSION['log_path']=$log_path;
$_SESSION['www_REP_path']=$www_REP_path;
$_SESSION['save_files']=$save_files;
writeTimeFile($idfile."--Repository: Ho ricevuto la richiesta");
##########################

//RECUPERO GLI HEADERS RICEVUTI DA APACHE
$headers = apache_request_headers();

writeTimeFile($idfile."--Repository: RECUPERO GLI HEADERS RICEVUTI DA APACHE");


$log->writeLogFile("RECEIVED:",1);
$log->writeLogFile($headers,0);

if($save_files)
$log->writeLogFileS($headers,$idfile."-headers_received-".$idfile,"M");



writeTimeFile($idfile."--Repository: Scrivo headers_received");

$input = $HTTP_RAW_POST_DATA;
$log->writeLogFile("RECEIVED:",1);
$log->writeLogFile($input,0);

// File da scrivere
$log->writeLogFileS($input,$idfile."-pre_decode_received-".$idfile,"M");

writeTimeFile($idfile."--Repository: Scrivo pre_decode_received");

//PASSO A DECODARE IL FILE CREATO
// Ottengo il boundary
$giveboundary = giveboundary($headers);
$boundary = $giveboundary[0];
$MTOM = $giveboundary[1];


include('rep_validation.php');
## TEST 11721: CONTROLLO CHE NON SIA the PAYLOAD is not metadata
if($boundary == ''){//boundary non dichiarato --> no payload
$isPayloadNotEmpty = controllaPayload($input);
}


//COMPLETO IL BOUNDARY con due - davanti
//$boundary = "--".substr($pre_boundary,0,strlen($pre_boundary)-1);
$boundary = "--".$boundary;


//BOUNDARY
$log->writeLogFile("BOUNDARY:",1);
$log->writeLogFile($boundary,0);

###### CASO DI PRESENZA DI ATTACHMENTS
if($boundary != "--")
{
//////////////////nuovo/////////////////////////
#### PRIMA OCCORRENZA DELL'ENVELOPE SOAP
if (preg_match('([^\t\n\r\f\v";][:]*+ENVELOPE)',strtoupper($input))) {
writeTimeFile($idfile."--Repository: Ho trovato SOAPENV:ENVELOPE");

preg_match('(<([^\t\n\r\f\v";<]+:)?(ENVELOPE))',strtoupper($input),$matches);

$presoap=$matches[1];
writeTimeFile($idfile."--Repository: Ho trovato $presoap");
$body = substr($input,strpos(strtoupper($input),"<".$presoap."ENVELOPE"));
}

// Body contiene da <SOAP-ENV fino alla fine del file

$log->writeLogFile("RECEIVED:",1);
$log->writeLogFile($body,0);


$log->writeLogFileS($body,$idfile."-body-".$idfile,"N");
///////////////////////////////////////////

#### ebXML IMBUSTATO SOAP

// Qui prendo solo la busta SOAP No allegati
$ebxml_imbustato_soap = rtrim(rtrim(substr($body,0,strpos($body,$boundary)),"\n"),"\r");

$log->writeLogFile("RECEIVED:",1);
$log->writeLogFile($ebxml_imbustato_soap,0);

if($save_files){
$log->writeLogFileS($ebxml_imbustato_soap,$idfile."-ebxml_imbustato_soap-".$idfile,"N");}

#### ebXML
$dom_SOAP_ebXML = domxml_open_mem($ebxml_imbustato_soap);

$root_SOAP_ebXML = $dom_SOAP_ebXML->document_element();
$dom_SOAP_ebXML_node_array = $root_SOAP_ebXML->get_elements_by_tagname("SubmitObjectsRequest");
for($i = 0;$i<count($dom_SOAP_ebXML_node_array);$i++)
{
	$node = $dom_SOAP_ebXML_node_array[$i];
	$ebxml_STRING = $dom_SOAP_ebXML->dump_node($node);
}

## SCRIVO L'ebXML SBUSTATO
$log->writeLogFile("RECEIVED:",1);
$log->writeLogFile($ebxml_STRING,0);

if($save_files)
$log->writeLogFileS($ebxml_STRING,$idfile."-ebxml-".$idfile,"N");


### SCRIVO L'ebXML DA VALIDARE (urn:uuid: ---> urn-uuid-)
$ebxml_STRING_VALIDATION = adjustURN_UUIDs($ebxml_STRING);

//file da scrivere!!!!!
$fp_ebxml_val = fopen($tmp_path.$idfile."-ebxml_for_validation-".$idfile,"w+");
	fwrite($fp_ebxml_val,$ebxml_STRING_VALIDATION);
fclose($fp_ebxml_val);


$isValid = isValid($ebxml_STRING_VALIDATION);


if ($isValid){
writeTimeFile($idfile."--Repository: Ho superato la validazione dell'ebxml");}

$connessione=connectDB();

##################################################################
### QUI SONO SICURO CHE IL METADATA E' VALIDO RISPETTO ALLO SCHEMA
############################################################
### OTTENGO L'OGGETTO DOM RELATIVO ALL'ebXML
$dom_ebXML = domxml_open_mem($ebxml_STRING);
##################################################################

#### SECONDA COSA: DEVO VALIDARE XDSSubmissionSet.sourceId
$SourceId_valid = validate_XDSSubmissionSetSourceId($dom_ebXML,$connessione);

if(!$SourceId_valid) {
writeTimeFile($idfile."--Repository: XDSSubmissionSetSourceId valido");}



#### SE SONO QUI HO PASSATO IL VINCOLO DI VALIDAZIONE SU sourceId

$conta_boundary=substr_count($body,$boundary)-1;
$conta_allegati=$conta_boundary;
/*
allegato_array[1]=primo allegato con parte iniziale
allegato_array[2]=secondo allegato con parte iniziale
allegato_array[$i]=$i allegato con parte iniziale
*/

$allegato_array=array();
$busta_array=explode($boundary,$input);
$conta_da_explode=count($busta_array);
for($ce=2;$ce<$conta_da_explode-1;$ce++){
$allegato_array=array_merge($allegato_array,array($busta_array[$ce]));
}


$validAllegatiExtrinsicObject = verificaContentMimeExtrinsicObject($dom_ebXML,$allegato_array);

if($validAllegatiExtrinsicObject){

####SE SONO QUI HO PASSATO IL VINCOLO DI VALIDAZIONE SU DocumentEntryUniqueId
$log->writeLogFile('SUPERATO I VINCOLI DI VALIDAZIONE',1);

if($save_files){
$log->writeLogFileS('SUPERATO I VINCOLI DI VALIDAZIONE',$idfile."-post_validation-".$idfile,"N");}

writeTimeFile($idfile."--Repository: Ho superato la validazione del messaggio");
}

#### CONTROLLO CHE CI SIANO DOCUMENTI IN ALLEGATO
$ExtrinsicObject_array = $dom_ebXML->get_elements_by_tagname("ExtrinsicObject");


##### SOLO NEL CASO CHE CI SIANO DOCUMENTI IN ALLEGATO
if(!empty($ExtrinsicObject_array))#### IMPORTANTE!!
{
#### TERZA COSA: DEVO VALIDARE XDSDocumentEntry.uniqueId
$UniqueId_valid_array = validate_XDSDocumentEntryUniqueId($dom_ebXML,$connessione);

if($UniqueId_valid_array[0]){
	writeTimeFile($idfile."--Repository: XDSDocumentEntryUniqueId valido $UniqueId_valid_array[0]");
	}//FINE if(!$UniqueId_valid_array[0])

// Devo verificare che siano corretti i boundary
$conta_EO = count($ExtrinsicObject_array);




$submission_uniqueID = getSubmissionUniqueID($dom_ebXML);




############ !!! IL METADATA RICEVUTO E' VALIDO !!! ############

// $ExtrinsicObject_array = $dom_ebXML->get_elements_by_tagname("ExtrinsicObject");
#### CICLO SU TUTTI I FILE ALLEGATI

for($o = 0 ; $o < $conta_EO ; $o++)
{
 	#### SINGOLO NODO ExtrinsicObject
	$ExtrinsicObject_node = $ExtrinsicObject_array[$o];

	#### RICAVO ATTRIBUTO id DI ExtrinsicObject
	$ExtrinsicObject_id_attr = $ExtrinsicObject_node->get_attribute('id');

	#### RICAVO ATTRIBUTO mymeType
	$ExtrinsicObject_mimeType_attr = $ExtrinsicObject_node->get_attribute('mimeType');
	#### RICAVO LA RELATIVA ESTENSIONE PER IL FILE
	$get_extension = "SELECT EXTENSION FROM MIMETYPE WHERE CODE = '$ExtrinsicObject_mimeType_attr'";
		$res = query_select2($get_extension,$connessione);

	$file_extension = $res[0][0];


	##### COMPONGO IL NOME DEL FILE (nomefile.estensione)
	$file_name = idrandom().".".$file_extension;

	#### COMPONGO IL PATH RELATIVO DOVE SALVO IL FILE


	if(!is_dir($relative_docs_path)){
	mkdir('./Submitted_Documents/'.date("Y").'/', 0777);
	mkdir('./Submitted_Documents/'.date("Y").'/'.date("m").'/', 0777);
	mkdir('./Submitted_Documents/'.date("Y").'/'.date("m").'/'.date("d").'/', 0777);
	}

	$document_URI = $relative_docs_path.$file_name;
	$document_URI2 = $relative_docs_path_2.$file_name;
		
	################################################################
	### SALVATAGGIO DELL'ALLEGATO SU FILESYSTEM

	#### ORA DEVO ANDARE SUL FILE Body PER SALVARE L' ALLEGATO

	$contentID_UP=strtoupper("Content-ID: <".$ExtrinsicObject_id_attr.">");
	$allegato_STRING_2 = substr($body,(strpos(strtoupper($body),$contentID_UP)+strlen($contentID_UP)),(strpos($body,$boundary,(strpos(strtoupper($body),$contentID_UP)))-strpos(strtoupper($body),$contentID_UP)-strlen($contentID_UP)));
	### PULISCO LA STRINGA IN CAPO E IN CODA: ATTENZIONE NON MODIFICARE !!!
	//$allegato_STRING = trim($allegato_STRING_2,"\n\r");### QUI HO L'ALLEGATO
	$allegato_STRING = substr($allegato_STRING_2,4,strlen($allegato_STRING_2)-6);### QUI HO L'ALLEGATO
##################### NON MODIFICARE!!!!!!! #########

######### SALVO IL DOCUMENTO IN ALLEGATO: SCRIVO SUL FILESYSTEM
	##### NON MODIFICARE
	$fp_allegato = fopen($document_URI,"wb+");
		fwrite($fp_allegato,$allegato_STRING);
	fclose($fp_allegato);
##############################################À

### SALVATAGGIO DELL'ALLEGATO SU FILESYSTEM
##################################################################
	
#### MI ASSICURO CHE URI,SIZE ED HASH NON SIANO GIA' SPECIFICATE NEL METADATA
	$mod = modifiable($ExtrinsicObject_node);

	$datetime="CURRENT_TIMESTAMP";
	$insert_into_DOCUMENTS = "INSERT INTO DOCUMENTS (XDSDOCUMENTENTRY_UNIQUEID,DATA,URI) VALUES ('".$UniqueId_valid_array[1][$o]."',$datetime,'$document_URI2')";
	//writeTimeFile($idfile."--Repository: INSERT INTO DOCUMENTS".$insert_into_DOCUMENTS);

	$fp_insert_into_DOCUMENTS= fopen($tmp_path.$idfile."-insert_into_DOCUMENTS-".$idfile, "wb+");
    	fwrite($fp_insert_into_DOCUMENTS,$insert_into_DOCUMENTS);
	fclose($fp_insert_into_DOCUMENTS);


	$ris = query_execute2($insert_into_DOCUMENTS,$connessione); //FINO A QUA OK!!!


	$selectTOKEN="SELECT KEY_PROG FROM DOCUMENTS WHERE XDSDOCUMENTENTRY_UNIQUEID = '".$UniqueId_valid_array[1][$o]."'";
	//writeTimeFile($idfile."--Repository: uniqueid".$selectTOKEN);
	//$selectTOKEN= "SELECT MAX(TOKEN_ID) AS TOKEN_ID FROM TOKEN";
		$res_token = query_select2($selectTOKEN,$connessione);
		$next_token = $res_token[0][0];
	writeTimeFile($idfile."--Repository: $selectTOKEN");
	$document_token = $www_REP_path."getDocument.php?token=".$next_token;
	//writeTimeFile($idfile."--Repository: document_token".$document_token);
	writeTimeFile($idfile."--Repository: protocol $rep_protocol");
	###### Calcolo URI
	if($rep_protocol=="NORMAL")
	{
	   $Document_URI_token = $normal_protocol.$rep_host.":".$rep_port.$document_token;
		writeTimeFile($idfile."--Sono in NORMAL: $Document_URI_token");
	}
	else if($rep_protocol=="TLS")
	{
	   $Document_URI_token = $tls_protocol.$rep_host.":".$rep_port.$document_token;	
	}
	
	###### Calcolo Hash
	//$hash = md5(file_get_contents($document_URI));
	$hash = hash("sha1",file_get_contents($document_URI));

	###### Calcolo size
	$size = filesize($document_URI);

include_once("createMetadataToForward.php");
#### MODIFICO IL METADATA PER FORWARDARLO SUCCESSIVAMENTE AL REGISTRY
      if($mod) ### CASO HASH-SIZE-URI NON PRESENTI
      {
		#### INSERISCO NEL DB E OTTENGO L'ebXML MODIFICATO
		$dom_ebXML = modifyMetadata($dom_ebXML,$ExtrinsicObject_node,$Document_URI_token,$hash,$size,$namespacerim_path); 
      }//END OF if($mod)
	else if(!$mod)### CASO HASH-SIZE-URI GIA' PRESENTI
	{
		$dom_ebXML_vuoto = deleteMetadata($dom_ebXML,$ExtrinsicObject_node);
		#### INSERISCO NEL DB E MANTENGO L'ebXML INALTERATO
		$dom_ebXML = modifyMetadata($dom_ebXML_vuoto,$ExtrinsicObject_node,$Document_URI_token,$hash,$size,$namespacerim_path);

	}//END OF else if(!$mod)
 #########################################################


}//END OF for($o = 0 ; $o < (count($ExtrinsicObject_array)) ; $o++)


}//END OF if(!empty($ExtrinsicObject_array))




#### MI PREPARO A SCRIVERE L'ebXML DA FORWARDARE AL REGISTRY
$submissionToForward = $dom_ebXML->dump_mem();
//apro e scrivo il file
$log->writeLogFile("SENT:",1);
$log->writeLogFile($submissionToForward,0);

if($save_files)
$file_written3 = $log->writeLogFileS($submissionToForward,$idfile."-ebxmlToForward-".$idfile,"N");

## 1- elimino la stringa <?amp;xml version="1.0"?amp;>  dall'ebxmlToForward
$ebxmlToForward_string = substr($submissionToForward,21);

## 2- ottengo il contenuto da forwardare (BUSTA CON ebXML ebxmlToForward)
$post_data = makeSoapEnvelope($ebxmlToForward_string);

}//END OF if($boundary != "--")

else if($boundary == "--")
{
	## NO ALLEGATI PERCIO' FORWARDO DIRETTAMENTE AL REG
	## Devo verificare che non ci siano ExtrinsicObject
	$validExtrinsicObject = verificaExtrinsicObject(domxml_open_mem($input));
	## Se non ci sono ExtrinsicObject posso inoltrare al registry
	if($validExtrinsicObject){
		$post_data = $input;
	}

}//END OF else if($boundary == "--")

## 3- METTO SU FILE CIO' CHE FORWARDO AL REG
$log->writeLogFile("SENT:",1);
$log->writeLogFile($post_data,0);

//File da scrivere!!!!
$file_forwarded_written = $log->writeLogFileS($post_data,$idfile."-forwarded-".$idfile,"N");
## 4- SPEDISCO IL MESSAGGIO AL REGISTRY E RICAVO LA RESPONSE


writeTimeFile($idfile."--Repository: registry protocol $reg_http");
#### CREO IL CLIENT PER LA CONNESSIONE HTTP CON IL REGISTRY
if($reg_http=="NORMAL"){
	writeTimeFile($idfile."--Repository: Sono in connect NORMAL");
	include("./http_connections/http_client.php");
	$client = new HTTP_Client($reg_host,$reg_port,30);
}
else if($reg_http=="TLS")
{
	include("./http_connections/ssl_connect.php");
	writeTimeFile($idfile."--Repository: Sono in connect TLS");
	$client = new HTTP_Client_ssl($reg_host,$reg_port,30);
	$client->set_protocol($tls_protocol);

}
### SETTAGGI COMUNI
$client->set_post_data($post_data);



writeTimeFile($idfile."--Repository: La dimensione è ".filesize($file_forwarded_written));



$client->set_data_length(filesize($file_forwarded_written));
$client->set_path($reg_path);
$client->set_idfile($idfile);
$client->set_save_files($save_files);


######## INOLTRO AL REGISTRY E ATTENDO LA RISPOSTA ##########

writeTimeFile($idfile."--Repository: Inoltro al registry e attendo la risposta");

$registry_response_arr = $client->send_request();

#$headers_da_registry = apache_request_headers();

$registry_response_log = $registry_response_arr[1];

writeTimeFile($idfile."--Repository: Ho ottenuto la risposta dal registry");


#### CASO DI ERORE DI CERTIFICATO
if($registry_response_log!=""){	
makeErrorFromRegistry($registry_response_log);
}//END OF if($registry_response_log!="")
#############################################################

if($save_files){

#### SONO FUORI DAL CASO DI ERRORE
$registry_response = $registry_response_arr[0];

## 5- scrivo in locale la RISPOSTA DAL REGISTRY

$fp_da_registry = fopen($tmp_path.$idfile."-da_registry-".$idfile, "wb+");
		fwrite($fp_da_registry,$registry_response);
fclose($fp_da_registry);

//============= END OF FORWARDING AL REGISTRY del NIST ===============//

}

##### N.B. NELLA RISPOSTA DAL REGISTRY HO HEADERS + BODY
//$da_registry = file_get_contents($tmp_path.$idfile."-da_registry-".$idfile);//Stringa

$da_registry = $registry_response_arr[0];//Stringa


// Se la risposta del registry è errata cancello il documento creato nel repository
if(strpos(strtoupper($da_registry),"ERROR")||strpos(strtoupper($da_registry),"FAILURE")){
	if ($windows>0) {
		exec('del '.$document_URI2.' /q');	
		}
	else {	
		exec('rm -f '.$document_URI2);
		}

	$deleteDocument="DELETE FROM DOCUMENTS WHERE KEY_PROG = $next_token";
		$res_delete = query_execute2($deleteDocument,$connessione);
}

#### XML RICEVUTO IN RISPOSTA DAL REGISTRY

if (preg_match('([^\t\n\r\f\v";][:]*+ENVELOPE)',strtoupper($da_registry))) {
writeTimeFile($idfile."--Repository: Ho trovato SOAPENV:ENVELOPE");

preg_match('(<([^\t\n\r\f\v";<]+:)?(ENVELOPE))',strtoupper($da_registry),$matches_reg);

$presoap_reg=$matches_reg[1];
writeTimeFile($idfile."--Repository: Ho trovato $presoap");
$body = substr($da_registry,strpos(strtoupper($da_registry),"<".$presoap_reg."ENVELOPE"));






}



//$body = trim((substr($da_registry,strpos($da_registry,"<SOAP-ENV:Envelope"))));

//File da scrivere!!!!!
$fp_body_response = fopen($tmp_path.$idfile."-body_response-".$idfile, "wb+");
		fwrite($fp_body_response,$body);
fclose($fp_body_response);
/*
#### HEADERS RICEVUTI IN RISPOSTA DAL REGISTRY
$headers = trim(substr($da_registry,strpos($da_registry,"HTTP"),(strlen($da_registry)-strlen($body)-17)));

//File da scrivere!!!!
$fp_headers_response = fopen($tmp_path.$idfile."-headers_response-".$idfile, "wb+");
		fwrite($fp_headers_response,$headers);
fclose($fp_headers_response);

//=============================================================================//
//==============  ORA RISPONDO (ACK) AL DOCUMENT SOURCE  =====================//
$headers_vector = file($tmp_path.$idfile."-headers_response-".$idfile);//metto gli headers ricevuti in un vettore
*/

//$body_response_fileSize = filesize($tmp_path.$idfile."-body_response-".$idfile);

##### CALCOLO LA SIZE DELLA RISPOSTA RICEVUTA
$fp_dim = fopen($tmp_path.$idfile."-dim-".$idfile,'wb+');
fwrite($fp_dim,$body_response_fileSize);
fclose($fp_dim);

/*
// La parte degli headers non serve

##### RESTITUISCO GLI HEADERS al PRODUCER
if(!empty($headers_vector))
{
	## FILE DEGLI HEADERS RESTITUITI AL SOURCE
	$fp_vector = fopen($tmp_path.$idfile."-headers_to_source-".$idfile,'wb+');

	######## PROCESSO IL VETTORE DEGLI HEADERS RICEVUTI DAL REGISTRY
	for($t =0;$t<count($headers_vector);$t++)
	{
		$h = $headers_vector[$t];
		##### FORZO IL CONTENT LENGTH
		if((strcmp(substr($h,0,14),"Content-Length:")) == 0)
		{
			$con_len_header = "Content-Length: ".(string)$body_response_fileSize;
			header($con_len_header);
			fwrite($fp_vector,$con_len_header."\n");
		}
		else
		{
			header($h);
			fwrite($fp_vector,$h."\n");
		}
	}

### AGGIUNGO LA SOAPACTION VUOTA
//header("SOAPAction : \"\"");

    //fwrite($fp_vector,"SOAPAction : \"\"");
fclose($fp_vector);

}//END OF if(!empty($headers_vector))
*/

header("HTTP/1.1 200 OK");
header("Content-Type: text/xml;charset=UTF-8");
header("Content-Length: ".(string)filesize($tmp_path.$idfile."-body_response-".$idfile));


##### PULISCO IL BUFFER DI USCITA
ob_get_clean();//OKKIO FONDAMENTALE!!!!!

if($file = fopen($tmp_path.$idfile."-body_response-".$idfile,'rb'))
{
   while((!feof($file)) && (connection_status()==0))
   {
       print(fread($file, 1024*8));
       flush();//NOTA BENE!!!!!!!!!

   }//END OF while((!feof($file)) && (connection_status()==0))

   fclose($file);

}//END OF if($file = fopen($tmp_path."body_response",'rb'))

#### SPEDISCO E PULISCO IL BUFFER DI USCITA
//Da verificare se va o non va messo
//ob_end_flush();//OKKIO FONDAMENTALE!!!!!!!!


writeTimeFile($idfile."--Repository: Ho spedito l'ack al Source");

writeTimeFile($idfile."--Repository: Ho terminato");
//================  FINE RISPOSTA AL DOCUMENT SOURCE  =================//




if($ATNA_active=='A'){
require_once('./lib/syslog.php');
        $syslog = new Syslog();
// ATNA IMPORT per Provide And Register Document Set
$eventOutcomeIndicator="0"; //EventOutcomeIndicator 0 OK 12 ERROR
$repository_endpoint="http://".$rep_host.":".$rep_port.$www_REP_path."repository.php";
$ip_repository=$rep_host; 
$ip_source=$_SERVER['REMOTE_ADDR']; 

$today = date("Y-m-d");
$cur_hour = date("H:i:s");
$datetime = $today."T".$cur_hour;

$message_import="<AuditMessage>
	<EventIdentification EventDateTime=\"$datetime\" EventActionCode=\"R\" EventOutcomeIndicator=\"0\">
		<EventID code=\"110107\" codeSystemName=\"DCM\" displayName=\"Import\"/>
		<EventTypeCode code=\"ITI-15\" codeSystemName=\"IHE Transactions\" displayName=\"Provide and Register Document Set\"/>
	</EventIdentification>
	<AuditSourceIdentification AuditSourceID=\"MARIS Repository\">
		<AuditSourceTypeCode code=\"4\" />
	</AuditSourceIdentification>
	<ActiveParticipant UserID=\"$ip_source\" UserIsRequestor=\"true\">
		<RoleIDCode code=\"110153\" codeSystemName=\"DCM\" displayName=\"Source\"/>
	</ActiveParticipant>
	<ActiveParticipant UserID=\"$repository_endpoint\" UserIsRequestor=\"false\">
		<RoleIDCode code=\"110152\" codeSystemName=\"DCM\" displayName=\"Destination\"/>
	</ActiveParticipant>
	<ParticipantObjectIdentification ParticipantObjectID=\"$submission_uniqueID\" ParticipantObjectTypeCode=\"2\" ParticipantObjectTypeCodeRole=\"20\">
		<ParticipantObjectIDTypeCode code=\"urn:uuid:a54d6aa5-d40d-43f9-88c5-b4633d873bdd\"/>
	</ParticipantObjectIdentification>
	</AuditMessage>";

	$logSyslog=$syslog->Send($ATNA_host,$ATNA_port,$message_import);

// ATNA EXPORT per Register Document Set
$eventOutcomeIndicator="0"; //EventOutcomeIndicator 0 OK 12 ERROR

$message_export="<AuditMessage>
	<EventIdentification EventDateTime=\"$datetime\" EventActionCode=\"R\" EventOutcomeIndicator=\"$eventOutcomeIndicator\">
		<EventID code=\"110106\" codeSystemName=\"DCM\" displayName=\"Export\"/>
		<EventTypeCode code=\"ITI-14\" codeSystemName=\"IHE Transactions\" displayName=\"Register Document Set\"/>
	</EventIdentification>
	<AuditSourceIdentification AuditSourceID=\"MARIS Repository\">
		<AuditSourceTypeCode code=\"4\" />
	</AuditSourceIdentification>
	<ActiveParticipant UserID=\"$ip_repository\" UserIsRequestor=\"true\">
		<RoleIDCode code=\"110153\" codeSystemName=\"DCM\" displayName=\"Source\"/>
	</ActiveParticipant>
	<ActiveParticipant UserID=\"http://".$reg_host.":".$reg_port.$reg_path."\" UserIsRequestor=\"false\">
		<RoleIDCode code=\"110152\" codeSystemName=\"DCM\" displayName=\"Destination\"/>
	</ActiveParticipant>
	<ParticipantObjectIdentification ParticipantObjectID=\"$submission_uniqueID\" ParticipantObjectTypeCode=\"2\" ParticipantObjectTypeCodeRole=\"20\">
		<ParticipantObjectIDTypeCode code=\"urn:uuid:a54d6aa5-d40d-43f9-88c5-b4633d873bdd\"/>
	</ParticipantObjectIdentification>
	</AuditMessage>";



        $logSyslog=$syslog->Send($ATNA_host,$ATNA_port,$message_export);



writeTimeFile($idfile."--Repository: Ho spedito i messaggi di ATNA");

}





unset($_SESSION['tmp_path']);
unset($_SESSION['idfile']);
unset($_SESSION['logActive']);
unset($_SESSION['log_query_path']);

//PULISCO LA CACHE TEMPORANEA

disconnectDB($connessione);

if($clean_cache=="A")
{
	if ($windows>0){
	exec('del tmp\\'.$idfile."* /q");	
	}
	else{	
	exec('rm -f '.$tmp_path.$idfile."*");
	//exec('rm -f '.$tmpQuery_path."*");
	}

}


?>
