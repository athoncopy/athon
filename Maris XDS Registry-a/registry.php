<?php

# ------------------------------------------------------------------------------------
# MARIS XDS REGISTRY
# Copyright (C) 2007 - 2010  MARiS Project
# Dpt. Medical and Diagnostic Sciences, University of Padova - csaccavini@rad.unipd.it
# This program is distributed under the terms and conditions of the GPL
# See the LICENSE files for details
# ------------------------------------------------------------------------------------

//BLOCCO IL BUFFER DI USCITA
ob_start();//OKKIO FONADAMENTALE!!!!!!!!!!

//Parte per calcolare i tempi di esecuzione
$mtime = microtime();
$mtime = explode(" ",$mtime);
$mtime = $mtime[1] + $mtime[0];
$starttime = $mtime;



##### CONFIGURAZIONE DEL REPOSITORY
include("REGISTRY_CONFIGURATION/REG_configuration.php");
#######################################

//include('./config/registry_QUERY_mysql_db.php');
include_once($lib_path."domxml-php4-to-php5.php");
include($lib_path."utilities.php");

$idfile = idrandom_file();

$_SESSION['tmp_path']=$tmp_path;
$_SESSION['tmpQueryService_path']=$tmpQueryService_path;
$_SESSION['idfile']=$idfile;
$_SESSION['logActive']=$logActive;
$_SESSION['log_path']=$log_path;
$_SESSION['www_REG_path']=$www_REG_path;
include_once("./lib/log.php");


if(!is_dir($tmp_path)){
mkdir($tmp_path, 0777,true);
}



writeTimeFile($idfile."--Registry: Rimuovo la cache temporanea");


//RECUPERO GLI HEADERS RICEVUTI DA APACHE
$headers = apache_request_headers();


//COPIO IN LOCALE TUTTI GLI HEADERS RICEVUTI
$fp_headers_received = fopen($tmp_path.$idfile."-headers_received-".$idfile, "w+");
foreach ($headers as $header => $value)
{
   fwrite ($fp_headers_received, "$header = $value  \n");
}
fclose($fp_headers_received);

//ebXML IMBUSTATO
$fp_ebxml_imbustato_soap = fopen($tmp_path.$idfile."-ebxml_imbustato_soap-".$idfile, "w+");
    fwrite($fp_ebxml_imbustato_soap,$HTTP_RAW_POST_DATA);
fclose($fp_ebxml_imbustato_soap);

//SBUSTO
$ebxml_imbustato_soap_STRING = file_get_contents($tmp_path.$idfile."-ebxml_imbustato_soap-".$idfile);

### GESTISCO IL CASO DI ATTACHMENT
$content_type = stristr($headers["Content-Type"],'boundary');

writeTimeFile($idfile."--Registry: Analizzo caso in cui ci sia il boundary");

#### SOLO CASO DI DICHIARAZ. BOUNDARY
if($content_type)
{
	$ebxml_imbustato_soap_STRING = '';


	$pre_boundary = substr($content_type,strpos($content_type,'"')+1);

	$fine_boundary = strpos($pre_boundary,'"')+1;
	//BOUNDARY ESATTO
	$boundary = '';
	$boundary = substr($pre_boundary,0,$fine_boundary-1);

/*
	//RESETTO LA VARIABILE
	## IN TAL CASO NON MI VA BENE $ebxml_imbustato_soap_STRING
	$pre_boundary = substr($content_type,strpos($content_type,'=')+2);
	#### RICAVO: BOUNDARY ESATTO
	$boundary = '';
*/
	#### OCCHIO: AGGIUNGO DUE - !!!!!
	$boundary = "--".substr($pre_boundary,0,strlen($pre_boundary)-1);
	###################################################################

	writeTimeFile($idfile."--Registry: Scrivo il boundary");

	//BOUNDARY
	$fp_bo = fopen($tmp_path."boundary","wb+");
    		fwrite($fp_bo,$boundary);
	fclose($fp_bo);

	//PASSO A DECODARE IL FILE CREATO

	$filename = $tmp_path.$idfile."-ebxml_imbustato_soap-".$idfile;
   		$input  = fread(fopen($filename,'rb'),filesize($filename));


	if (preg_match('([^\t\n\r\f\v";][:]*+ENVELOPE)',strtoupper($input))) {
	writeTimeFile($idfile."--Repository: Ho trovato SOAPENV:ENVELOPE");

	preg_match('(<([^\t\n\r\f\v";<]+:)?(ENVELOPE))',strtoupper($input),$matches);

	$presoap=$matches[1];
	writeTimeFile($idfile."--Repository: Ho trovato $presoap");
	$body = substr($input,strpos(strtoupper($input),"<".$presoap."ENVELOPE"));
	}

	//CONTENUTO
	$fp = fopen($tmp_path.$idfile."-body-".$idfile,"wb+");
    		fwrite($fp,$body);
	fclose($fp);

	### RIOTTENGO LA STRINGA SOAP
	$ebxml_imbustato_soap_STRING = rtrim(rtrim(substr($body,0,strpos($body,$boundary)),"\n"),"\r");
}
//ebXML IMBUSTATO
$fp_ebxml_imbustato_soap = fopen($tmp_path.$idfile."-ebxml_imbustato_soap_2-".$idfile, "w+");
    fwrite($fp_ebxml_imbustato_soap,$ebxml_imbustato_soap_STRING);
fclose($fp_ebxml_imbustato_soap);

## OGGETTO DOM SUL SOAP RICEVUTO: QUI HO IL SOAP CORRETTO!
$dom_ebxml_imbustato_soap = domxml_open_mem($ebxml_imbustato_soap_STRING);

##### GESTISCO IL CASO DI DEL PAYLOAD VUOTO

$errorcode=array();

include_once('reg_validation.php');

$isPayloadNotEmpty = controllaPayload($ebxml_imbustato_soap_STRING);


if ($isPayloadNotEmpty){
writeTimeFile($idfile."--Registry: Ho validato il payload");}



### SE SONO QUA SIGNIFICA CHE IL PAYLOAD RICEVUTO NON E' VUOTO
### ====>>>> POSSO RECUPERARE L'ebXML


writeTimeFile($idfile."--Registry: Inizio ad analizzare il documento");

$root_SOAP_ebXML = $dom_ebxml_imbustato_soap->document_element();
$dom_SOAP_ebXML_node_array = $root_SOAP_ebXML->get_elements_by_tagname("SubmitObjectsRequest");
for($i = 0;$i<count($dom_SOAP_ebXML_node_array);$i++)
{
	$node = $dom_SOAP_ebXML_node_array[$i];
	$ebxml_STRING = $dom_ebxml_imbustato_soap->dump_node($node);
}
//SCRIVO L'ebXML SBUSTATO
$fp_ebxml = fopen($tmp_path.$idfile."-ebxml-".$idfile,"w+");
	fwrite($fp_ebxml,$ebxml_STRING);
fclose($fp_ebxml);








//SCRIVO L'ebXML DA VALIDARE (urn:uuid: ---> urn-uuid-)
$ebxml_STRING_VALIDATION = adjustURN_UUIDs($ebxml_STRING);
$fp_ebxml_val = fopen($tmp_path.$idfile."-ebxml_for_validation-".$idfile,"w+");
	fwrite($fp_ebxml_val,$ebxml_STRING_VALIDATION);
fclose($fp_ebxml_val);

$schema='schemas/rs.xsd';
$isValid = isValid($ebxml_STRING_VALIDATION,$schema);

if ($isValid){
writeTimeFile($idfile."--Registry: Il metadata e' valido");}



##################################################################
### QUI SONO SICURO CHE IL METADATA E' VALIDO RISPETTO ALLO SCHEMA
##################################
### OTTENGO L'OGGETTO DOM DALL'ebXML
$dom_ebXML = domxml_open_mem($ebxml_STRING);
####################################################################






##### ATTENZIONE!!! CONTA L'ORDINE DI CHIAMATA ALLE FUNZIONI!!!!
##### !!!!! NON CAMBIARE L'ORDINE !!!!!!  ######
############# CHECK OF ExtrinsicObject mimeType
// se $ExtrinsicObject_mimeType_array[0] è vero dà errore
$ExtrinsicObject_mimeType_array = validate_ExtrinsicObject_mimeType($dom_ebXML,$connessione);

if(!$ExtrinsicObject_mimeType_array[0]){
writeTimeFile($idfile."--Registry: Ho validato mimetype");}

############# CHECK OF XDSDocumentEntry.patientId
if($control_PatientID=="A"){
	$DocumentEntryPatientId_valid_array = validate_XDSDocumentEntryPatientIdError($dom_ebXML,$connessione);

	}
else {
	$DocumentEntryPatientId_valid_array = validate_XDSDocumentEntryPatientIdInsert($dom_ebXML,$connessione);
	}

// se $DocumentEntryPatientId_valid_array[0] è vero dà errore
if(!$DocumentEntryPatientId_valid_array[0]){
writeTimeFile($idfile."--Registry: Ho validato XDSDocumentEntryPatientId");}

############# SUPPORT DOCUMENT REPLACEMENT
$Replacement_valid_array = validate_Replacement($dom_ebXML,$DocumentEntryPatientId_valid_array[2],$connessione);

// se $DocumentEntryPatientId_valid_array[0] è falso dà errore
if($Replacement_valid_array[0]){
writeTimeFile($idfile."--Registry: Ho validato XDSDocumentEntryPatientId Replacement");}


############# SUPPORT DOCUMENT APPEND
$Append_valid_array = validate_Append($dom_ebXML,$DocumentEntryPatientId_valid_array[2],$connessione);

// se $Append_valid_array[0] è falso dà errore
if($Append_valid_array[0]){
writeTimeFile($idfile."--Registry: Ho validato XDSDocumentEntryPatientId Append");}


############# CHECK OF XDSDocumentEntry.uniqueId
$DocumentEntryUniqueId_valid_array = validate_XDSDocumentEntryUniqueId($dom_ebXML,$connessione);

// se $DocumentEntryUniqueId_valid_array[0] è falso dà errore
if($DocumentEntryUniqueId_valid_array[0]){
writeTimeFile($idfile."--Registry: Ho validato XDSDocumentEntry.uniqueId");}


//$SubmissionSetPatientId_valid_array = validate_XDSSubmissionSetPatientId($dom_ebXML,$idfile);

############# CHECK OF XDSSubmissionSet.patientId
if($control_PatientID=="A"){
	$SubmissionSetPatientId_valid_array = validate_XDSSubmissionSetPatientIdError($dom_ebXML,$connessione);
	}
else {
	$SubmissionSetPatientId_valid_array = validate_XDSSubmissionSetPatientIdInsert($dom_ebXML,$connessione);
	}

// se $SubmissionSetPatientId_valid_array[0] è vero dà errore
if(!$SubmissionSetPatientId_valid_array[0]){
writeTimeFile($idfile."--Registry: Ho validato XDSSubmissionSetPatientId");}

############ CHECK OF XDSSubmissionSet.uniqueID

// se $SubmissionSetUniqueId_valid_array[0] è falso dà errore

$SubmissionSetUniqueId_valid_array = validate_XDSSubmissionSetUniqueId($dom_ebXML,$connessione);
if($SubmissionSetUniqueId_valid_array[0]){
writeTimeFile($idfile."--Registry: Ho validato XDSSubmissionSetUniqueId");}

############ CHECK OF XDSFolder.uniqueID
// se $FolderUniqueId_valid_array[0] è falso dà errore
$FolderUniqueId_valid_array = validate_XDSFolderUniqueId($dom_ebXML,$connessione);
if($FolderUniqueId_valid_array[0]){
writeTimeFile($idfile."--Registry: Ho validato XDSFolderUniqueId");}

########### CHECK OF HASH + SIZE + URI
// se $hsu[0] è falso dà errore
$hsu = arePresent_HASH_SIZE_URI($dom_ebXML);
if($hsu[0]){
writeTimeFile($idfile."--Registry: Ho validato HASH_SIZE_URI");}

#### RECUPERO TUTTE LE INFORMAZIONI DELLA VALIDAZIONE
$XDSSubmissionSetPatientId = $SubmissionSetPatientId_valid_array[2];
$XDSDocumentEntryPatientId_arr = $DocumentEntryPatientId_valid_array[2];
$ExtrinsicObject_node_id_attr_array=$DocumentEntryPatientId_valid_array[3];

############ CHECK OF XDSFolder.patientID
// se $FolderPatientId_valid_array[0] è falso dà errore
$FolderPatientId_valid_array = validate_XDSFolderPatientId($dom_ebXML,$XDSDocumentEntryPatientId_arr,$XDSSubmissionSetPatientId,$DocumentEntryPatientId_valid_array[3],$connessione);

if($FolderPatientId_valid_array[0]){
writeTimeFile($idfile."--Registry: Ho validato XDSFolder.patientID");}

#### CONFRONTO XDSDocumentEntry.patientId vs XDSSubmissionSet.patientId
// se $conf_PatientIds_arr[0] è vero dà errore
$conf_PatientIds_arr=array();
if(!(empty($XDSDocumentEntryPatientId_arr) && empty($ExtrinsicObject_node_id_attr_array)))
{
	$conf_PatientIds_arr=confrontaPatientIds($XDSSubmissionSetPatientId,$XDSDocumentEntryPatientId_arr,$ExtrinsicObject_node_id_attr_array);

}//END OF IF

if(!$conf_PatientIds_arr[0]){
writeTimeFile($idfile."--Registry: CONFRONTO XDSDocumentEntry.patientId vs XDSSubmissionSet.patientId OK");
}


## CONFRONTO XDSDocumentEntry.patientId vs XDSFolder.patientId CASO ADD DOCUMENT
// se $isAddAllowed_array[0] è falso dà errore
$isAddAllowed_array = verifyAddDocToFolder($dom_ebXML,$XDSDocumentEntryPatientId_arr,$connessione);

/*$bool_array=array(!$Replacement_valid_array[0],!$Replacement_valid_array[2],!$Replacement_valid_array[4],$conf_PatientIds_arr[0],$DocumentEntryPatientId_valid_array[0],!$DocumentEntryUniqueId_valid_array[0],$SubmissionSetPatientId_valid_array[0],!$SubmissionSetUniqueId_valid_array[0],!$FolderUniqueId_valid_array[0],$ExtrinsicObject_mimeType_array[0],$FolderPatientId_valid_array[0],!$FolderPatientId_valid_array[2],!$FolderPatientId_valid_array[4],!$isAddAllowed_array[0],!$isAddAllowed_array[2],!$hsu[0],!$hsu[2],!$hsu[4]);*/

$error_code_array=array_merge($ExtrinsicObject_mimeType_array[2],$DocumentEntryPatientId_valid_array[4],$DocumentEntryUniqueId_valid_array[4],$Replacement_valid_array[2],$Append_valid_array[2],$SubmissionSetPatientId_valid_array[3],$SubmissionSetUniqueId_valid_array[2],$FolderUniqueId_valid_array[3],$hsu[2],$FolderPatientId_valid_array[2],$conf_PatientIds_arr[2],$isAddAllowed_array[2]);


$failure_response_array=array_merge($ExtrinsicObject_mimeType_array[1],$DocumentEntryPatientId_valid_array[1],$DocumentEntryUniqueId_valid_array[1],$Replacement_valid_array[1],$Append_valid_array[1],$SubmissionSetPatientId_valid_array[1],$SubmissionSetUniqueId_valid_array[1],$FolderUniqueId_valid_array[1],$hsu[1],$FolderPatientId_valid_array[1],$conf_PatientIds_arr[1],$isAddAllowed_array[1]);



###### CASO DI VALIDAZIONE ===NON=== PASSATA


if($ExtrinsicObject_mimeType_array[0] || $DocumentEntryPatientId_valid_array[0] || !$Replacement_valid_array[0] || !$Append_valid_array[0] || !$DocumentEntryUniqueId_valid_array[0] || $SubmissionSetPatientId_valid_array[0] || !$SubmissionSetUniqueId_valid_array[0] || !$FolderUniqueId_valid_array[0] || !$hsu[0] || !$FolderPatientId_valid_array[0] || $conf_PatientIds_arr[0] || !$isAddAllowed_array[0]){

	### COMPONGO IL CORE DEL MESSAGGIO DI FAIL

	writeTimeFile($idfile."--Registry: NON HO SUPERATO I VINCOLI DI VALIDAZIONE");
	### RESTITUISCE IL MESSAGGIO DI FAIL IN SOAP
    	$SOAPED_failure_response = makeSoapedFailureResponse($failure_response_array,$error_code_array);

	### SCRIVO LA RISPOSTA IN UN FILE
	$file_input=$tmp_path.$idfile."-SOAPED_failure_response-".$idfile;
	 $fp = fopen($file_input,"w+");
           fwrite($fp,$SOAPED_failure_response);
         fclose($fp);

	SendResponse($file_input);
	exit;

	
}######## END OF VALIDAZIONE ===NON=== PASSATA

#### SE SONO QUI HO SUPERATO TUTTI I VINCOLI DI VALIDAZIONE

$fp = fopen($tmp_path.$idfile."-POST_VALIDATION-".$idfile, "w+");
      fwrite($fp,'SUPERATO IL VINCOLO DI VALIDAZIONE SU SCHEMAS + UNIQUEID + PATIENTID + FOLDER');
fclose($fp);

######################################################
###### POSSO RIEMPIRE IL DATABASE DEL REGISTRY #######
writeTimeFile($idfile."--Registry: Inizio a riempire il Database");


	### 1 - ExtrinsicObject
	include_once('ExtrinsicObject_2.php');
		$RETURN_from_ExtrinsicObject_id_array=fill_ExtrinsicObject_tables($dom_ebXML,$connessione);
		#### ARRAY DEGLI EXTRINSICOBJECTS ID
		$ExtrinsicObject_id_array=$RETURN_from_ExtrinsicObject_id_array[0];
		#### LANGUAGE CODE
		$language=$RETURN_from_ExtrinsicObject_id_array[1];
		

writeTimeFile($idfile."--Registry: Inserito nel Database ExtrinsicObject");

	### 2 - RegistryPackage
	include_once('RegistryPackage_2.php');
		$RegistryPackage_id_array2=fill_RegistryPackage_tables($dom_ebXML,$language,$connessione);
		$RegistryPackage_id_array=$RegistryPackage_id_array2[0];

writeTimeFile($idfile."--Registry: Inserito nel Database RegistryPackage");

	### 3 - Classification
	include_once('Classification_2.php');
		fill_Classification_tables($dom_ebXML,$RegistryPackage_id_array,$connessione);

writeTimeFile($idfile."--Registry: Inserito nel Database Classification");

	### 4 - Association
	include_once('Association_2.php');
		fill_Association_tables($dom_ebXML,$RegistryPackage_id_array,$ExtrinsicObject_id_array,$connessione);

writeTimeFile($idfile."--Registry: Inserito nel Database Association");

####### FINE RIEMPIMENTO DATABASE DEL REGISTRY ########
#######################################################

//==================================================//
//============ REGISTRY RESPONSE  ==============//

####### RISPOSTA POSITIVA
$registry_response = makeSoapedSuccessResponse();

//SCRIVO LA RISPOSTA IN UN FILE
$fp_registry_response = fopen($tmp_path.$idfile."-registry_response.xml","wb+");
	fwrite($fp_registry_response,$registry_response);
fclose($fp_registry_response);

writeTimeFile($idfile."--Registry: Scrivo la risposta positiva in un file");

//PULISCO IL BUFFER DI USCITA
ob_get_clean();//OKKIO FONDAMENTALE!!!!!

########### QUI CI VA IL RESPONSE

### HEADERS
header("HTTP/1.1 200 OK");
$path_header = "Path: $www_REG_path";
if($http=="TLS")
{
	##### NEL CASO TLS AGGIUNGO LA DICITURA SECURE
	$path_header = $path_header."; Secure";
}
header($path_header);
header("Content-Type: text/xml; charset=UTF-8");
header("Content-Length: ".(string)filesize($tmp_path.$idfile."-registry_response.xml"));

### FILE BODY
if($file = fopen($tmp_path.$idfile."-registry_response.xml",'rb'))
{
   while((!feof($file)) && (connection_status()==0))
   {
      	print(fread($file, 1024*8));
      	flush();//NOTA BENE!!!!!!!!!
   }

   fclose($file);
}



//=============  END OF REGISTRY RESPONSE  =============//
writeTimeFile($idfile."--Registry: Spedisco la risposta");

//=======================================//
#### SPEDISCO E PULISCO IL BUFFER DI USCITA
ob_end_flush();//OKKIO FONDAMENTALE!!!


// NAV
if ($NAV=="A") {

$bound_mail="--".md5(time());
$eol="\r\n";

$headers_mail = "From: ".$NAV_from.$eol;
$headers_mail.= "Message-ID: <".time()."@".$_SERVER['SERVER_NAME'].">".$eol;
$headers_mail.= "MIME-Version: 1.0".$eol;
$headers_mail.= "Content-Type: multipart/mixed; boundary=".$bound_mail.$eol;



$msg_mail = "--".$bound_mail.$eol;
$msg_mail .= "Content-Type: text/plain; charset=us-ascii".$eol;
$msg_mail .= "Instructions to the user as to the use of this e-mail message.".$eol;
//$msg_mail .= print_r($DocumentEntryUniqueId_valid_array).$eol;

$msg_mail .= "--".$bound_mail.$eol;

$msg_mail .= "Content-Type: application/xml; charset=UTF-8".$eol;
$msg_mail .= "Content-Disposition: attachment; filename=\"IHEXDSNAV-".idrandom().".xml\"".$eol;

$msg_mail .= "<Signature Id=\"signatureID\" xmlns=\"http://www.w3.org/2000/09/xmldsig#\">
<SignedInfo>
<CanonicalizationMethod Algorithm=\"http://www.w3.org/TR/2001/REC-xml-c14n-20010315#WithComments\"/>
<SignatureMethod Algorithm=\"urn:ihe:iti:dsg:nosig\"/>
<Reference URI=\"#IHEManifest\" Type=\"http://www.w3.org/2000/09/xmldsig#Manifest\">
<DigestMethod Algorithm=\"http://www.w3.org/2000/09/xmldsig#sha1\"/>
<DigestValue>00</DigestValue>
</Reference>
</SignedInfo>
<SignatureValue>base64SignatureValue</SignatureValue>
<Object>
<SignatureProperties>
<SignatureProperty Id=\"recommendedRegistry\"
target=\"signatureID\">http://".$_SERVER['SERVER_NAME'].$www_REG_path.$service_query."</SignatureProperty>
<SignatureProperty Id=\"sendAcknowledgementTo\"
target=\"signatureID\">".$NAV_to."</SignatureProperty>
</SignatureProperties>
<Manifest Id=\"IHEManifest\">";

for($index_doc=0;$index_doc<count($DocumentEntryUniqueId_valid_array[3]);$index_doc++){
$msg_mail .= "<Reference URI=\"".$DocumentEntryUniqueId_valid_array[3][$index_doc]."\">
<DigestMethod Algorithm=\"http://www.w3.org/2000/09/xmldsig#sha1\"/>
<DigestValue>base64DigestValue</DigestValue>
<!--this is document ".$index_doc.", read it first-->
</Reference>";
}

$msg_mail .= "
</Manifest>
</Object>
</Signature>".$eol;

$msg_mail .= "--".$bound_mail."--";

	$fp_ebxml_nav = fopen($tmp_path.$idfile."-nav_msg-".$idfile,"w+");
	fwrite($fp_ebxml_nav,$msg_mail);
	fclose($fp_ebxml_nav);



mail($NAV_to, "Notification of Document Availability",$msg_mail, $headers_mail);

$fp_mail = fopen($tmp_path.$idfile."-mail-".$idfile,"w+");
	fwrite($fp_mail,$NAV_to."Notification of Document Availability".$msg_mail.$headers_mail);
fclose($fp_mail);

writeTimeFile($idfile."--Registry: Ho spedito i messaggi di NAV");


}

if($ATNA_active=='A'){
require_once('./lib/syslog.php');
        $syslog = new Syslog();


// ATNA IMPORT per Register Document Set
$eventOutcomeIndicator="0"; //EventOutcomeIndicator 0 OK 12 ERROR
$registry_endpoint=$http_protocol.$ip_server.":".$port_server.$www_REG_path."registry.php";
	
$ip_repository=$_SERVER['REMOTE_ADDR'];

$today = date("Y-m-d");
$cur_hour = date("H:i:s");
$datetime = $today."T".$cur_hour;

$message_import="<AuditMessage>
	<EventIdentification EventDateTime=\"$datetime\" EventActionCode=\"R\" EventOutcomeIndicator=\"0\">
		<EventID code=\"110106\" codeSystemName=\"DCM\" displayName=\"Import\"/>
		<EventTypeCode code=\"ITI-14\" codeSystemName=\"IHE Transactions\" displayName=\"Register Document Set\"/>
	</EventIdentification>
	<AuditSourceIdentification AuditSourceID=\"MARIS Registry\">
		<AuditSourceTypeCode code=\"4\" />
	</AuditSourceIdentification>
	<ActiveParticipant UserID=\"$ip_repository\" UserIsRequestor=\"true\">
		<RoleIDCode code=\"110153\" codeSystemName=\"DCM\" displayName=\"Source\"/>
	</ActiveParticipant>
	<ActiveParticipant UserID=\"$registry_endpoint\" UserIsRequestor=\"false\">
		<RoleIDCode code=\"110152\" codeSystemName=\"DCM\" displayName=\"Destination\"/>
	</ActiveParticipant>
	<ParticipantObjectIdentification ParticipantObjectID=\"".htmlentities($XDSSubmissionSetPatientId)."\" ParticipantObjectTypeCode=\"1\" ParticipantObjectTypeCodeRole=\"1\">
		<ParticipantObjectIDTypeCode code=\"2\"/>
    	</ParticipantObjectIdentification>
	<ParticipantObjectIdentification ParticipantObjectID=\"".$SubmissionSetUniqueId_valid_array[3]."\" ParticipantObjectTypeCode=\"2\" ParticipantObjectTypeCodeRole=\"20\">
		<ParticipantObjectIDTypeCode code=\"urn:uuid:a54d6aa5-d40d-43f9-88c5-b4633d873bdd\"/>
	</ParticipantObjectIdentification>
	</AuditMessage>";



        $logSyslog=$syslog->Send($ATNA_host,$ATNA_port,$message_import);



writeTimeFile($idfile."--Registry: Ho spedito i messaggi di ATNA");


}

//Parte per calcolare i tempi di esecuzione
$mtime = microtime();
$mtime = explode(" ",$mtime);
$mtime = $mtime[1] + $mtime[0];
$endtime = $mtime;
$totaltime = number_format($endtime - $starttime,15);

$STAT_SUBMISSION="INSERT INTO STATS (REPOSITORY,DATA,EXECUTION_TIME,OPERATION) VALUES ('".$_SERVER['REMOTE_ADDR']."',CURRENT_TIMESTAMP,'$totaltime','QUERY')";
$ris = query_exec2($STAT_SUBMISSION,$connessione);
writeSQLQuery($ris.": ".$STAT_SUBMISSION);

disconnectDB($connessione);

unset($_SESSION['tmp_path']);
unset($_SESSION['idfile']);
unset($_SESSION['logActive']);
unset($_SESSION['log_path']);

// Clean tmp folder
$system=PHP_OS;

$windows=substr_count(strtoupper($system),"WIN");


if($clean_cache=="O")
{
	if ($windows>0){
	exec('del tmp\\'.$idfile."* /q");	
	}
	else{	
	exec('rm -f '.$tmp_path.$idfile."*");
	}

}

?>
