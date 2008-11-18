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

//BLOCCO IL BUFFER DI USCITA
ob_start();//OKKIO FONADAMENTALE!!!!!!!!!!


##### CONFIGURAZIONE DEL REPOSITORY
include("REGISTRY_CONFIGURATION/REG_configuration.php");
#######################################

if($statActive=="A") {
	//Parte per calcolare i tempi di esecuzione
	$mtime = microtime();
	$mtime = explode(" ",$mtime);
	$mtime = $mtime[1] + $mtime[0];
	$starttime = $mtime;
}



$idfile = idrandom_file();


$script = $_SERVER['PHP_SELF'];
$www_REG_path=str_replace('registry.php', '',$script);

$_SESSION['tmp_path']=$tmp_path;
$_SESSION['tmpQueryService_path']=$tmpQueryService_path;
$_SESSION['idfile']=$idfile;
$_SESSION['logActive']=$logActive;
$_SESSION['log_path']=$log_path;
$_SESSION['www_REG_path']=$www_REG_path;


// Creo la cartella per i file temporanei
if(!is_dir($tmp_path)){
	$createtmpdir=false;
	$ntmpdir=0;
	while(!$createtmpdir && $ntmpdir<10){
		$cmdtmpdir=mkdir($tmp_path, 0777,true);
		if($cmdtmpdir){
			// Caso OK Riesce a creare il folder correttamente
			writeTimeFile($idfile."--Ho creato il folder tmp correttamente");
			$createtmpdir=true;
			}
		else {
			sleep(1);
			$ntmpdir++;
		}
	} //Fine while


	// Se dopo 10 volte non sono riuscito a creare il folder riporto un errore
	if(!$createtmpdir){
		$errorcode[]="XDSRegistryError";
		$error_message[] = "Registry can't create tmp folder. ";
		$folder_response = makeSoapedFailureResponse($error_message,$errorcode);
		writeTimeFile($_SESSION['idfile']."--Registry: Folder error");
		
		$file_input=$idfile."-folder_failure_response-".$idfile;
		writeTmpFiles($folder_response,$file_input);
		//SendResponse($tmp_path.$file_input);
		SendResponse($folder_response);
		exit;
	
	}
	
}


writeTimeFile($idfile."--Registry: Rimuovo la cache temporanea");


//RECUPERO GLI HEADERS RICEVUTI DA APACHE
$headers = apache_request_headers();
$input=$HTTP_RAW_POST_DATA;

//COPIO IN LOCALE TUTTI GLI HEADERS RICEVUTI
if($clean_cache!="O"){
writeTmpFiles($headers,$idfile."-headers_received-".$idfile);
}



if(stripos($headers["Content-Type"],"boundary")){
	writeTimeFile($idfile."--Registry: Il boundary e' presente");
	$boundary = giveboundary($headers);

	//PASSO A DECODARE IL FILE CREATO
	$input = $HTTP_RAW_POST_DATA;

	if (preg_match('([^\t\n\r\f\v";][:]*+ENVELOPE)',strtoupper($input))) {
		writeTimeFile($idfile."--Registry: Ho trovato SOAPENV:ENVELOPE");

		preg_match('(<([^\t\n\r\f\v";<]+:)?(ENVELOPE))',strtoupper($input),$matches);

		$presoap=$matches[1];
		writeTimeFile($idfile."--Registry: Ho trovato $presoap");
		$body = substr($input,strpos(strtoupper($input),"<".$presoap."ENVELOPE"));
		$ebxml_imbustato_soap = rtrim(rtrim(substr($body,0,strpos($body,$boundary)-2),"\n"),"\r");

		writeTmpFiles($ebxml_imbustato_soap,$idfile."-ebxml_imbustato_soap-".$idfile.".xml");


	}
}


//Caso MTOM
else {
	writeTmpFiles($input,$idfile."-ebxml_imbustato_soap-".$idfile.".xml");
}

//ebXML IMBUSTATO
//SBUSTO

$errorcode=array();
$failure_response=array();

$ebxml_imbustato_soap_STRING = file_get_contents($tmp_path.$idfile."-ebxml_imbustato_soap-".$idfile.".xml");




$dom_XML_completo = domxml_open_mem($ebxml_imbustato_soap_STRING);


$root_completo = $dom_XML_completo->document_element();

//Ottengo Action
$Action_array = $root_completo->get_elements_by_tagname("Action");
$Action_node = $Action_array[0];
$Action=$Action_node->get_content();

//Ottengo MessageID
$MessageID_array = $root_completo->get_elements_by_tagname("MessageID");
$MessageID_node = $MessageID_array[0];
$MessageID=$MessageID_node->get_content();

## OGGETTO DOM SUL SOAP RICEVUTO: QUI HO IL SOAP CORRETTO!
$dom_ebxml_imbustato_soap = domxml_open_mem($ebxml_imbustato_soap_STRING);

include_once('reg_validation.php');
##### GESTISCO IL CASO DI DEL PAYLOAD VUOTO

$isPayloadNotEmpty = controllaPayload($ebxml_imbustato_soap_STRING);


if ($isPayloadNotEmpty){
writeTimeFile($idfile."--Registry: Controllo il payload");}



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
if($clean_cache!="O"){
writeTmpFiles($ebxml_STRING,$idfile."-ebxml-".$idfile.".xml");
}


//SCRIVO L'ebXML DA VALIDARE (urn:uuid: ---> urn-uuid-)
$ebxml_STRING_VALIDATION = adjustURN_UUIDs($ebxml_STRING);
if($clean_cache!="O"){
writeTmpFiles($ebxml_STRING_VALIDATION,$idfile."-ebxml_for_validation-".$idfile);}



$schema='schemas3/lcm.xsd';
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
if(!$DocumentEntryPatientId_valid_array[0]){
writeTimeFile($idfile."--Registry: Ho validato XDSDocumentEntryPatientId");}

############# SUPPORT DOCUMENT REPLACEMENT
$Replacement_valid_array = validate_Replacement($dom_ebXML,$DocumentEntryPatientId_valid_array[2],$connessione);

if($Replacement_valid_array[0]){

writeTimeFile($idfile."--Registry: Ho validato XDSDocumentEntryPatientId Replacement");}

############# SUPPORT DOCUMENT APPEND
$Append_valid_array = validate_Append($dom_ebXML,$DocumentEntryPatientId_valid_array[2],$connessione);

if($Append_valid_array[0]){
writeTimeFile($idfile."--Registry: Ho validato XDSDocumentEntryPatientId Append");}

############# CHECK OF XDSDocumentEntry.uniqueId
$DocumentEntryUniqueId_valid_array = validate_XDSDocumentEntryUniqueId($dom_ebXML,$connessione);

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

if(!$SubmissionSetPatientId_valid_array[0]){
writeTimeFile($idfile."--Registry: Ho validato XDSSubmissionSetPatientId");}

############ CHECK OF XDSSubmissionSet.uniqueID
$SubmissionSetUniqueId_valid_array = validate_XDSSubmissionSetUniqueId($dom_ebXML,$connessione);

if($SubmissionSetUniqueId_valid_array[0]){
writeTimeFile($idfile."--Registry: Ho validato XDSSubmissionSetUniqueId");}

############ CHECK OF XDSFolder.uniqueID
if($controlFolderUniqueId=="A") {
	$FolderUniqueId_valid_array = validate_XDSFolderUniqueId($dom_ebXML,$connessione);
	if($FolderUniqueId_valid_array[0]){
	writeTimeFile($idfile."--Registry: Ho validato XDSFolderUniqueId");}
}
else {
	$FolderUniqueId_valid_array = validate_XDSFolderUniqueIdInsert($dom_ebXML,$connessione);
	if($FolderUniqueId_valid_array[0]){
	writeTimeFile($idfile."--Registry: Ho validato XDSFolderUniqueIdInsert");}
}

########### CHECK OF HASH + SIZE + URI
$hsu = arePresent_HASH_SIZE_repositoryUniqueId($dom_ebXML);
if($hsu[0]){
writeTimeFile($idfile."--Registry: Ho validato la presenza di HASH_SIZE_repositoryUniqueId");}

#### RECUPERO TUTTE LE INFORMAZIONI DELLA VALIDAZIONE
$XDSSubmissionSetPatientId = $SubmissionSetPatientId_valid_array[2];
$XDSDocumentEntryPatientId_arr = $DocumentEntryPatientId_valid_array[2];
$ExtrinsicObject_node_id_attr_array=$DocumentEntryPatientId_valid_array[3];

############ CHECK OF XDSFolder.patientID
$FolderPatientId_valid_array = validate_XDSFolderPatientId($dom_ebXML,$XDSDocumentEntryPatientId_arr,$XDSSubmissionSetPatientId,$DocumentEntryPatientId_valid_array[3],$connessione);
if($FolderPatientId_valid_array[0]){
writeTimeFile($idfile."--Registry: Ho validato XDSFolder.patientID");}

#### CONFRONTO XDSDocumentEntry.patientId vs XDSSubmissionSet.patientId
$conf_PatientIds_arr=array();
if(!(empty($XDSDocumentEntryPatientId_arr) && empty($ExtrinsicObject_node_id_attr_array)))
{
	$conf_PatientIds_arr=confrontaPatientIds($XDSSubmissionSetPatientId,$XDSDocumentEntryPatientId_arr,$ExtrinsicObject_node_id_attr_array);

}//END OF IF

if(!$conf_PatientIds_arr[0]){
writeTimeFile($idfile."--Registry: CONFRONTO XDSDocumentEntry.patientId vs XDSSubmissionSet.patientId OK");}



## CONFRONTO XDSDocumentEntry.patientId vs XDSFolder.patientId CASO ADD DOCUMENT
$isAddAllowed_array=array();
$isAddAllowed_array = verifyAddDocToFolder($dom_ebXML,$XDSDocumentEntryPatientId_arr,$connessione);

$error_code_array=array_merge($ExtrinsicObject_mimeType_array[2],$DocumentEntryPatientId_valid_array[4],$DocumentEntryUniqueId_valid_array[4],$Replacement_valid_array[2],$Append_valid_array[2],$SubmissionSetPatientId_valid_array[3],$SubmissionSetUniqueId_valid_array[2],$FolderUniqueId_valid_array[3],$hsu[2],$FolderPatientId_valid_array[2],$conf_PatientIds_arr[2],$isAddAllowed_array[2]);


$failure_response_array=array_merge($ExtrinsicObject_mimeType_array[1],$DocumentEntryPatientId_valid_array[1],$DocumentEntryUniqueId_valid_array[1],$Replacement_valid_array[1],$Append_valid_array[1],$SubmissionSetPatientId_valid_array[1],$SubmissionSetUniqueId_valid_array[1],$FolderUniqueId_valid_array[1],$hsu[1],$FolderPatientId_valid_array[1],$conf_PatientIds_arr[1],$isAddAllowed_array[1]);

###### CASO DI VALIDAZIONE ===NON=== PASSATA
if($ExtrinsicObject_mimeType_array[0] || $DocumentEntryPatientId_valid_array[0] || !$Replacement_valid_array[0] || !$Append_valid_array[0] || !$DocumentEntryUniqueId_valid_array[0] || $SubmissionSetPatientId_valid_array[0] || !$SubmissionSetUniqueId_valid_array[0] || !$FolderUniqueId_valid_array[0] || !$hsu[0] || !$FolderPatientId_valid_array[0] || $conf_PatientIds_arr[0] || !$isAddAllowed_array[0]){

	writeTimeFile($idfile."--Registry: NON HO SUPERATO I VINCOLI DI VALIDAZIONE");

	### RESTITUISCE IL MESSAGGIO DI FAIL IN SOAP
    	$SOAPED_failure_response = makeSoapedFailureResponse($failure_response_array,$error_code_array,$Action,$MessageID);

	### SCRIVO LA RISPOSTA IN UN FILE
	$file_input=$idfile."-SOAPED_failure_response.xml";
	writeTmpFiles($SOAPED_failure_response,$file_input);

	SendResponse($SOAPED_failure_response);
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
		$simbolic_ExtrinsicObject_id_array=$RETURN_from_ExtrinsicObject_id_array[3];
		#### LANGUAGE CODE
		$language=$RETURN_from_ExtrinsicObject_id_array[1];
		

writeTimeFile($idfile."--Registry: Inserito nel Database ExtrinsicObject");

	### 2 - RegistryPackage
	include_once('RegistryPackage_2.php');
		$RegistryPackage_id_array2=fill_RegistryPackage_tables($dom_ebXML,$language,$connessione);
		$RegistryPackage_id_array=$RegistryPackage_id_array2[0];
		$simbolic_RegistryPackage_id_array=$RegistryPackage_id_array2[2];
		$simbolic_RegistryPackage_FOL_id_array=$RegistryPackage_id_array2[3];

writeTimeFile($idfile."--Registry: Inserito nel Database RegistryPackage");

	### 3 - Classification
	include_once('Classification_2.php');
		fill_Classification_tables($dom_ebXML,$RegistryPackage_id_array,$simbolic_RegistryPackage_FOL_id_array,$connessione);

writeTimeFile($idfile."--Registry: Inserito nel Database Classification");

	### 4 - Association
	include_once('Association_2.php');
		fill_Association_tables($dom_ebXML,$RegistryPackage_id_array,$ExtrinsicObject_id_array,$simbolic_RegistryPackage_FOL_id_array,$connessione);

writeTimeFile($idfile."--Registry: Inserito nel Database Association");


####### Se arrivo a questo punto cambio lo stato del documento da NotCompleted a Approved
//Aggiorno lo stato di ExtrinsicObject
for($e=0;$e<count($ExtrinsicObject_id_array);$e++){
	$simbolic_ExtrinsicObject_id=$simbolic_ExtrinsicObject_id_array[$e];
	$UPDATE_ExtrinsicObject = "UPDATE ExtrinsicObject SET status='Approved' where id='".$ExtrinsicObject_id_array[$simbolic_ExtrinsicObject_id]."' AND status = 'NotCompleted'";

	$ris = query_exec2($UPDATE_ExtrinsicObject,$connessione);
	writeSQLQuery($ris.": ".$UPDATE_ExtrinsicObject);
}

//Aggiorno lo stato di RegistryPackage
for($s=0;$s<count($RegistryPackage_id_array);$s++){
	$simbolic_RegistryPackage_id=$simbolic_RegistryPackage_id_array[$s];
	if($simbolic_RegistryPackage_id!=''){
		$UPDATE_RegistryPackage = "UPDATE RegistryPackage SET status='Approved' where id='".$RegistryPackage_id_array[$simbolic_RegistryPackage_id]."' AND status = 'NotCompleted'";

		$ris = query_exec2($UPDATE_RegistryPackage,$connessione);
		writeSQLQuery($ris.": ".$UPDATE_RegistryPackage);
	}
}


####### FINE RIEMPIMENTO DATABASE DEL REGISTRY ########
#######################################################

//==================================================//
//============ REGISTRY RESPONSE  ==============//

####### RISPOSTA POSITIVA
$registry_response = makeSoapedSuccessResponse($Action,$MessageID);

//SCRIVO LA RISPOSTA IN UN FILE
writeTmpFiles($registry_response,$idfile."-registry_response.xml",true);

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
//header($path_header);
//cambiato il content type da text/xml a application/soap+xml
header("Content-Type: application/soap+xml; charset=UTF-8");
//header("Content-Type: application/soap+xml; action=\"urn:ihe:iti:2007:$Action\"; charset=UTF-8");
header("Content-Length: ".(string)filesize($tmp_path.$idfile."-registry_response.xml"));


### FILE BODY
if($file = fopen($tmp_path.$idfile."-registry_response.xml",'rb'))
{
   while((!feof($file)) && (connection_status()==0))
   {
      	print(fread($file, 1024*8));
      	//flush();//NOTA BENE!!!!!!!!!
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
target=\"signatureID\">http://".$_SERVER['SERVER_NAME'].$www_REG_path."storedquery.php</SignatureProperty>
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

if($clean_cache!="O"){
	writeTmpFiles($msg_mail,$idfile."-mail-".$idfile);
}


}

// Da verificare se si possono usare funzioni php al posto di java
// ATNA Import
if($ATNA_active=='A'){
require_once('./lib/syslog.php');
        $syslog = new Syslog();


// ATNA IMPORT per Register Document Set
$eventOutcomeIndicator="0"; //EventOutcomeIndicator 0 OK 12 ERROR
$registry_endpoint=$_SERVER['PHP_SELF'];
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
	<ParticipantObjectIdentification ParticipantObjectID=\"patient_id\" ParticipantObjectTypeCode=\"1\" ParticipantObjectTypeCodeRole=\"1\">
		<ParticipantObjectIDTypeCode code=\"2\"/>
    	</ParticipantObjectIdentification>
	<ParticipantObjectIdentification ParticipantObjectID=\"".$SubmissionSetUniqueId_valid_array[3]."\" ParticipantObjectTypeCode=\"2\" ParticipantObjectTypeCodeRole=\"20\">
		<ParticipantObjectIDTypeCode code=\"urn:uuid:a54d6aa5-d40d-43f9-88c5-b4633d873bdd\"/>
	</ParticipantObjectIdentification>
	</AuditMessage>";



        $logSyslog=$syslog->Send($ATNA_host,$ATNA_port,$message_import);

if($clean_cache!="O"){
	writeTmpFiles($message_import,$idfile."-atna_import.xml");
}

writeTimeFile($idfile."--Registry: Ho spedito i messaggi di ATNA");


}

//Statistiche
if($statActive=="A") {

	//Parte per calcolare i tempi di esecuzione
	$mtime = microtime();
	$mtime = explode(" ",$mtime);
	$mtime = $mtime[1] + $mtime[0];
	$endtime = $mtime;
	$totaltime = number_format($endtime - $starttime,15);

	$STAT_SUBMISSION="INSERT INTO STATS (REPOSITORY,DATA,EXECUTION_TIME,OPERATION) VALUES ('".$_SERVER['REMOTE_ADDR']."',CURRENT_TIMESTAMP,'$totaltime','SUBMISSION-B')";
	$ris = query_exec2($STAT_SUBMISSION,$connessione);
	writeSQLQuery($ris.": ".$STAT_SUBMISSION);
}
disconnectDB($connessione);

unset($_SESSION['tmp_path']);
unset($_SESSION['idfile']);
unset($_SESSION['logActive']);
unset($_SESSION['log_path']);
unset($_SESSION['tmpQueryService_path']);
unset($_SESSION['www_REG_path']);

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
