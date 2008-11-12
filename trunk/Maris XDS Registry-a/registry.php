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

##### CONFIGURAZIONE DEL REPOSITORY
include("REGISTRY_CONFIGURATION/REG_configuration.php");
#######################################
include('./config/registry_QUERY_mysql_db.php');
include($lib_path."utilities.php");

$idfile = idrandom_file();

$_SESSION['tmp_path']=$tmp_path;
$_SESSION['idfile']=$idfile;
$_SESSION['logActive']=$logActive;
$_SESSION['log_path']=$log_path;

include_once("./lib/log.php");
//$log = new Log_REG("REG");
/*$log = new Log_REG();
$log->set_tmp_path($tmp_path);
$log->set_idfile($idfile);
$log->setLogActive($logActive);
$log->setCurrentLogPath($log_path);*/



//PULISCO LA CACHE TEMPORANEA
/*if($clean_cache=="A"){
	exec('rm -f '.$tmp_path."*");
	exec('rm -f '.$tmpQuery_path."*");
	}*/

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

writeTimeFile($idfile."--Registry: Analizzo caso in cui ci sia solo boundary");

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
	include($lib_path.'mimeDecode.php');

	$filename = $tmp_path.$idfile."-ebxml_imbustato_soap-".$idfile;
   		$input  = fread(fopen($filename,'rb'),filesize($filename));

// 	$params['include_bodies'] = true;
// 	$params['decode_bodies'] = true;
// 	$params['decode_headers'] = true;
//
// 	$decode = new Mail_mimeDecode($input);
// 	$structure = $decode->decode($params); //struttura: oggetto
//
// 	$body = $structure->{'body'};

	#### PRIMA OCCORRENZA DELL'ENVELOPE SOAP
	if(strstr(strtoupper($input),"SOAP-ENV")){
	$body = substr($input,strpos(strtoupper($input),"<SOAP-ENV:ENVELOPE"));
	}
	else if(strstr(strtoupper($input),"SOAPENV")){
	$body = substr($input,strpos(strtoupper($input),"<SOAPENV:ENVELOPE"));
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

include_once('payload.php');
$isPayloadNotEmpty = controllaPayload($dom_ebxml_imbustato_soap);


writeTimeFile($idfile."--Registry: Controllo il payload");

##### GESTISCO IL CASO DI DEL PAYLOAD VUOTO
if(!$isPayloadNotEmpty)
{
	$fp = fopen($tmp_path.$idfile."-isPayloadNotEmpty-".$idfile, "w+");
    		fwrite($fp,"**** ATTENTION: RECEIVED PAYLOAD IS EMPTY !! ****");
	fclose($fp);

	$advertise = "\n$service: No metadata\n";
	$empty_payload_response = makeSoapedFailureResponse($advertise,$logentry);

	//SCRIVO LA RISPOSTA IN UN FILE
	$fp_empty_payload_response = fopen($tmp_path.$idfile."-empty_PAYLOAD_response-".$idfile,"wb+");
		fwrite($fp_empty_payload_response,$empty_payload_response);
	fclose($fp_empty_payload_response);

//SPEDISCO : PULISCO IL BUFFER DI USCITA
	ob_get_clean();//OKKIO FONDAMENTALE!!!!!

	//HEADERS
	header("HTTP/1.1 200 OK");
	header("Path: $www_REG_path");
	header("Content-Type: text/xml;charset=UTF-8");
	header("Content-Length: ".(string)filesize($tmp_path.$idfile."-empty_PAYLOAD_response-".$idfile));
		//CONTENUTO DEL FILE DI RISPOSTA
	if($file = fopen($tmp_path.$idfile."-empty_PAYLOAD_response-".$idfile,'rb'))
	{
   		while((!feof($file)) && (connection_status()==0))
   		{
     			print(fread($file, 1024*8));
      			flush();//NOTA BENE!!!!!!!!!
   		}

   		fclose($file);
	}

	//SPEDISCO E PULISCO IL BUFFER DI USCITA
	ob_end_flush();
	//BLOCCO L'ESECUZIONE DELLO SCRIPT
	exit;

}//END OF if(!$isPayloadNotEmpty)

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

####### VALIDAZIONE DELL'ebXML SECONDO LO SCHEMA
//$comando_java_validation=("/usr/lib/jvm/java-1.5.0-sun-1.5.0_03/jre/bin/java -jar ".$path_to_VALIDATION_jar."valid.jar -xsd ".$path_to_XSD_file." -xml ".$tmp_path.$idfile."-ebxml_for_validation-".$idfile);
$comando_java_validation=("java -jar ".$path_to_VALIDATION_jar."valid.jar -xsd ".$path_to_XSD_file." -xml ".$tmp_path.$idfile."-ebxml_for_validation-".$idfile);


$fp_ebxml_val = fopen($tmp_path.$idfile."-comando_java_validation-".$idfile,"w+");
	fwrite($fp_ebxml_val,$comando_java_validation);
fclose($fp_ebxml_val);

#### ESEGUO IL COMANDO
$java_call_result = exec("$comando_java_validation",$output,$error);
$error_message = "";
### SE NON SI SONO VERIFICATI ERRORI NELLA CHIAMATA AL METODO


writeTimeFile($idfile."--Registry: Valido il documento");

if($error==0)
{
	##### CASO DI VALIDAZIONE NON RIUSCITA
	#### true=""
	$isValid = ($output[0]=="");
	for($jj=0;$jj <= count($output)-1;$jj++)
	{
		if(!$isValid)
		{
		  $error_message=$error_message."\n".$output[$jj]."\n";

		}//END OF if(!($output[$jj]==""))

	}//END OF for($jj=0;$jj <= count($output)-1;$jj++)

}//END OF if($error==0)

#### NEL CASO DI MANCATA VALIDAZIONE RESTITUISCE
#### IL MESSAGGIO DI FAIL IN SOAP ED ESCE
if(!$isValid)
{
	### RESTITUISCE IL MESSAGGIO DI FAIL IN SOAP
    	$SOAPED_failure_response = makeSoapedFailureResponse($error_message,$logentry);

	### SCRIVO LA RISPOSTA IN UN FILE
	 $fp = fopen($tmp_path.$idfile."-SOAPED_failure_VALIDATION_response-".$idfile,"w+");
           fwrite($fp,$SOAPED_failure_response);
         fclose($fp);

	### PULISCO IL BUFFER DI USCITA
	ob_get_clean();//OKKIO FONDAMENTALE!!!!!

	### HEADERS
	header("HTTP/1.1 200 OK");
	header("Path: $www_REG_path");
	header("Content-Type: text/xml;charset=UTF-8");
	header("Content-Length: ".(string)filesize($tmp_path.$idfile."-SOAPED_failure_VALIDATION_response-".$idfile));
	### CONTENUTO DEL FILE DI RISPOSTA
	if($file = fopen($tmp_path.$idfile."-SOAPED_failure_VALIDATION_response-".$idfile,'rb'))
	{
   		while((!feof($file)) && (connection_status()==0))
   		{
     			print(fread($file, 1024*8));
      			flush();//NOTA BENE!!!!!!!!!
   		}

   		fclose($file);
	}
	### SPEDISCO E PULISCO IL BUFFER DI USCITA
	ob_end_flush();
	### BLOCCO L'ESECUZIONE DELLO SCRIPT

writeTimeFile($idfile."--Registry: Documento non valido");

	exit;


}//END OF if(!$isValid)

$fp_SCHEMA_val = fopen($tmp_path.$idfile."-SCHEMA_validation-".$idfile,"w+");
	fwrite($fp_SCHEMA_val,"VALIDAZIONE DA SCHEMA ==> OK <==");
fclose($fp_SCHEMA_val);








##################################################################
### QUI SONO SICURO CHE IL METADATA E' VALIDO RISPETTO ALLO SCHEMA
##################################
### OTTENGO L'OGGETTO DOM DALL'ebXML
$dom_ebXML = domxml_open_mem($ebxml_STRING);
####################################################################

include_once('reg_validation.php');
##### ATTENZIONE!!! CONTA L'ORDINE DI CHIAMATA ALLE FUNZIONI!!!!
##### !!!!! NON CAMBIARE L'ORDINE !!!!!!  ######
############# CHECK OF ExtrinsicObject mimeType
$ExtrinsicObject_mimeType_array = validate_ExtrinsicObject_mimeType($dom_ebXML);

writeTimeFile($idfile."--Registry: Ho validato mimetype");

############# CHECK OF XDSDocumentEntry.patientId
if($control_PatientID=="A"){
	$DocumentEntryPatientId_valid_array = validate_XDSDocumentEntryPatientIdError($dom_ebXML);
	}
else {
	$DocumentEntryPatientId_valid_array = validate_XDSDocumentEntryPatientIdInsert($dom_ebXML);
	}

writeTimeFile($idfile."--Registry: Ho validato XDSDocumentEntryPatientId");

############# SUPPORT DOCUMENT REPLACEMENT
$Replacement_valid_array = validate_Replacement($dom_ebXML,$DocumentEntryPatientId_valid_array[2]);

writeTimeFile($idfile."--Registry: Ho validato XDSDocumentEntryPatientId Replacement");

############# CHECK OF XDSDocumentEntry.uniqueId
$DocumentEntryUniqueId_valid_array = validate_XDSDocumentEntryUniqueId($dom_ebXML);

writeTimeFile($idfile."--Registry: Ho validato XDSDocumentEntry.uniqueId");


//$SubmissionSetPatientId_valid_array = validate_XDSSubmissionSetPatientId($dom_ebXML,$idfile);

############# CHECK OF XDSSubmissionSet.patientId
if($control_PatientID=="A"){
	$SubmissionSetPatientId_valid_array = validate_XDSSubmissionSetPatientIdError($dom_ebXML);
	}
else {
	$SubmissionSetPatientId_valid_array = validate_XDSSubmissionSetPatientIdInsert($dom_ebXML);
	}

writeTimeFile($idfile."--Registry: Ho validato XDSSubmissionSetPatientId");

############ CHECK OF XDSSubmissionSet.uniqueID
$SubmissionSetUniqueId_valid_array = validate_XDSSubmissionSetUniqueId($dom_ebXML,$idfile);

writeTimeFile($idfile."--Registry: Ho validato XDSSubmissionSetUniqueId");

############ CHECK OF XDSFolder.uniqueID
$FolderUniqueId_valid_array = validate_XDSFolderUniqueId($dom_ebXML,$idfile);

writeTimeFile($idfile."--Registry: Ho validato XDSFolderUniqueId");

########### CHECK OF HASH + SIZE + URI
$hsu = arePresent_HASH_SIZE_URI($dom_ebXML);

writeTimeFile($idfile."--Registry: Verifico se presente HASH_SIZE_URI");

writeTimeFile($idfile."--Registry: Verifico se presente HASH_SIZE_URI".$XDSSubmissionSetPatientId);

writeTimeFile($idfile."--Registry: Verifico se presente HASH_SIZE_URI".$XDSSubmissionSetPatientId);
#### RECUPERO TUTTE LE INFORMAZIONI DELLA VALIDAZIONE
$XDSSubmissionSetPatientId = $SubmissionSetPatientId_valid_array[2];
$XDSDocumentEntryPatientId_arr = $DocumentEntryPatientId_valid_array[2];
$ExtrinsicObject_node_id_attr_array=$DocumentEntryPatientId_valid_array[3];

############ CHECK OF XDSFolder.patientID
$FolderPatientId_valid_array = validate_XDSFolderPatientId($dom_ebXML,$XDSDocumentEntryPatientId_arr,$XDSSubmissionSetPatientId,$DocumentEntryPatientId_valid_array[3],$idfile);

writeTimeFile($idfile."--Registry: Ho validato XDSFolder.patientID");

#### CONFRONTO XDSDocumentEntry.patientId vs XDSSubmissionSet.patientId
$conf_PatientIds_arr=array();
if(!(empty($XDSDocumentEntryPatientId_arr) && empty($ExtrinsicObject_node_id_attr_array)))
{
	$conf_PatientIds_arr=confrontaPatientIds($XDSSubmissionSetPatientId,$XDSDocumentEntryPatientId_arr,$ExtrinsicObject_node_id_attr_array);

}//END OF IF

writeTimeFile($idfile."--Registry: CONFRONTO XDSDocumentEntry.patientId vs XDSSubmissionSet.patientId");



## CONFRONTO XDSDocumentEntry.patientId vs XDSFolder.patientId CASO ADD DOCUMENT
$isAddAllowed_array=array();
$isAddAllowed_array = verifyAddDocToFolder($dom_ebXML,$XDSDocumentEntryPatientId_arr);

$bool_array=array(!$Replacement_valid_array[0],!$Replacement_valid_array[2],!$Replacement_valid_array[4],$conf_PatientIds_arr[0],$DocumentEntryPatientId_valid_array[0],!$DocumentEntryUniqueId_valid_array[0],$SubmissionSetPatientId_valid_array[0],!$SubmissionSetUniqueId_valid_array[0],!$FolderUniqueId_valid_array[0],$ExtrinsicObject_mimeType_array[0],$FolderPatientId_valid_array[0],!$FolderPatientId_valid_array[2],!$FolderPatientId_valid_array[4],!$isAddAllowed_array[0],!$isAddAllowed_array[2],!$hsu[0],!$hsu[2],!$hsu[4]);

/*$fp = fopen($tmp_path.$idfile."-BOOLEANS-".$idfile,"w+");
$ii=0;
while($ii<count($bool_array))
{
	$bool = $bool_array[$ii];
	if($bool)
	{
		fwrite($fp,"ELEMENTO IN POSIZIONE $ii = ".$bool);
	}
	$ii=$ii+1;

}//END OF while($ii<count($bool_array))
fclose($fp);*/

###### CASO DI VALIDAZIONE ===NON=== PASSATA
if(!$Replacement_valid_array[0] || !$Replacement_valid_array[2] || !$Replacement_valid_array[4] || $conf_PatientIds_arr[0] || $DocumentEntryPatientId_valid_array[0] || !$DocumentEntryUniqueId_valid_array[0] || $SubmissionSetPatientId_valid_array[0] || !$SubmissionSetUniqueId_valid_array[0] || !$FolderUniqueId_valid_array[0]|| $ExtrinsicObject_mimeType_array[0] || $FolderPatientId_valid_array[0] || !$FolderPatientId_valid_array[2] || !$FolderPatientId_valid_array[4] || !$isAddAllowed_array[0] || !$isAddAllowed_array[2] || !$hsu[0] || !$hsu[2] || !$hsu[4])
{
	### COMPONGO IL CORE DEL MESSAGGIO DI FAIL
	$failure_response = $Replacement_valid_array[1].$Replacement_valid_array[3].$Replacement_valid_array[5].$conf_PatientIds_arr[1].$DocumentEntryPatientId_valid_array[1].$DocumentEntryUniqueId_valid_array[1].$SubmissionSetPatientId_valid_array[1].$SubmissionSetUniqueId_valid_array[1].$FolderUniqueId_valid_array[1].$FolderPatientId_valid_array[1].$FolderPatientId_valid_array[3].$FolderPatientId_valid_array[5].$ExtrinsicObject_mimeType_array[1].$isAddAllowed_array[1].$isAddAllowed_array[3].$hsu[1].$hsu[3].$hsu[5];

	##### AGGIUNGO INFO SULL'ESITO DELL'INTERA SUBMISSION
	$failure_response=$failure_response."\n\t *** [STATUS OF YOUR SUBMISSION] - ENTIRE SUBMISSION WAS REJECTED BY THIS REGISTRY *** \n";

	### RESTITUISCE IL MESSAGGIO DI FAIL IN SOAP
    	$SOAPED_failure_response = makeSoapedFailureResponse($failure_response,$logentry);

	### SCRIVO LA RISPOSTA IN UN FILE
	 $fp = fopen($tmp_path.$idfile."-SOAPED_failure_response-".$idfile,"w+");
           fwrite($fp,$SOAPED_failure_response);
         fclose($fp);

	### PULISCO IL BUFFER DI USCITA
	ob_get_clean();//OKKIO FONDAMENTALE!!!!!

	### HEADERS
	header("HTTP/1.1 200 OK");
	header("Path: $www_REG_path");
	header("Content-Type: text/xml;charset=UTF-8");
	header("Content-Length: ".(string)filesize($tmp_path.$idfile."-SOAPED_failure_response-".$idfile));
	### CONTENUTO DEL FILE DI RISPOSTA
	if($file = fopen($tmp_path.$idfile."-SOAPED_failure_response-".$idfile,'rb'))
	{
   		while((!feof($file)) && (connection_status()==0))
   		{
     			print(fread($file, 1024*8));
      			flush();//NOTA BENE!!!!!!!!!
   		}

   		fclose($file);
	}
	### SPEDISCO E PULISCO IL BUFFER DI USCITA
	ob_end_flush();
	### BLOCCO L'ESECUZIONE DELLO SCRIPT
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
		$RETURN_from_ExtrinsicObject_id_array=fill_ExtrinsicObject_tables($dom_ebXML);
		#### ARRAY DEGLI EXTRINSICOBJECTS ID
		$ExtrinsicObject_id_array=$RETURN_from_ExtrinsicObject_id_array[0];
		#### LANGUAGE CODE
		$language=$RETURN_from_ExtrinsicObject_id_array[1];
		

writeTimeFile($idfile."--Registry: Inserito nel Database ExtrinsicObject");

	### 2 - RegistryPackage
	include_once('RegistryPackage_2.php');
		$RegistryPackage_id_array2=fill_RegistryPackage_tables($dom_ebXML,$language);
		$RegistryPackage_id_array=$RegistryPackage_id_array2[0];

writeTimeFile($idfile."--Registry: Inserito nel Database RegistryPackage");

	### 3 - Classification
	include_once('Classification_2.php');
		fill_Classification_tables($dom_ebXML,$RegistryPackage_id_array);

writeTimeFile($idfile."--Registry: Inserito nel Database Classification");

	### 4 - Association
	include_once('Association_2.php');
		fill_Association_tables($dom_ebXML,$RegistryPackage_id_array,$ExtrinsicObject_id_array,$idfile);

writeTimeFile($idfile."--Registry: Inserito nel Database Association");

####### FINE RIEMPIMENTO DATABASE DEL REGISTRY ########
#######################################################

//==================================================//
//============ REGISTRY RESPONSE  ==============//

####### RISPOSTA POSITIVA
$registry_response = makeSoapedSuccessResponse($logentry);

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
}


// ATNA Import
if($ATNA_active=='A'){
include_once("reg_atna.php");
	
	// DocumentEntryUniqueId
	for($index_doc=0;$index_doc<count($DocumentEntryUniqueId_valid_array[3]);$index_doc++){
	$atna_value=$RegistryPackage_id_array2[1];
	$eventOutcomeIndicator="0"; //EventOutcomeIndicator 0 OK 12 ERROR
	$ip_registry=$reg_host; 

	$ip_source=$atna_value['urn:uuid:554ac39e-e3fe-47fe-b233-965d2a147832']; 
	//AE Title source

	//$SUID=$atna_value['urn:uuid:96fdda7c-d067-4183-912e-bf5ee74998a8']; //Study Instance UID
	$SUID=$DocumentEntryUniqueId_valid_array[3][$index_doc]; //Study Instance UID
	
	//$pid=$atna_value['urn:uuid:6b5aea1a-874d-4603-a4bc-96a0a7b38446']; //Patient ID
	$pid=$DocumentEntryPatientId_valid_array[2][$index_doc]; //Patient ID

	//Patient Name
	$pna=$RETURN_from_ExtrinsicObject_id_array[2];

	$idName="Study Instance UID";

	createImportEvent($eventOutcomeIndicator,$ip_registry,$ip_source,$SUID,$pid,$pna,$idName);

	$java_atna_import=("java -jar ".$path_to_ATNA_jar."syslog.jar -u ".$ATNA_host." -p ".$ATNA_port." -f ".$atna_path."DataImport.xml");
//echo $java_atna_export;

	$fp_ebxml_val = fopen($tmp_path.$idfile."-comando_java_atna_import_eo-".$idfile,"w+");
	fwrite($fp_ebxml_val,$java_atna_import);
	fclose($fp_ebxml_val);


	$java_call_result = exec("$java_atna_import");

	//$INSERT_atna_import = "INSERT INTO AuditableEvent (eventType,registryObject,timeStamp,Source) VALUES ('Import','".$SUID."',CURRENT_TIMESTAMP,'".$ip_source."')";

	$ris_import = query_exec($INSERT_atna_import);

	} // Fine for($index_doc=0;$index_doc<count($DocumentEntryUniqueId_valid_array[3]);$index_doc++)


	// Folder
	for($index_doc_f=0;$index_doc_f<count($FolderUniqueId_valid_array[2]);$index_doc_f++){
	$atna_value=$RegistryPackage_id_array2[1];
	$eventOutcomeIndicator="0"; //EventOutcomeIndicator 0 OK 12 ERROR
	$ip_registry=$reg_host; 

	$ip_source=$atna_value['urn:uuid:554ac39e-e3fe-47fe-b233-965d2a147832']; 
	//AE Title source

	$SUID=$FolderUniqueId_valid_array[2][$index_doc_f]; //Folder UID
	
	$pid=$FolderPatientId_valid_array[7][$index_doc_f]; //Patient ID

	$pna=$RETURN_from_ExtrinsicObject_id_array[2]; //Patient Name

	$idName="Folder UID";

	createImportEvent($eventOutcomeIndicator,$ip_registry,$ip_source,$SUID,$pid,$pna,$idName);

	$java_atna_import=("java -jar ".$path_to_ATNA_jar."syslog.jar -u ".$ATNA_host." -p ".$ATNA_port." -f ".$atna_path."DataImport.xml");
//echo $java_atna_export;

	$fp_ebxml_val = fopen($tmp_path.$idfile."-comando_java_atna_import_folder-".$idfile,"w+");
	fwrite($fp_ebxml_val,$java_atna_import);
	fclose($fp_ebxml_val);


	$java_call_result = exec("$java_atna_import");

	//$INSERT_atna_import_folder = "INSERT INTO AuditableEvent (eventType,registryObject,time_Stamp,Source) VALUES ('Import','".$SUID."',CURRENT_TIMESTAMP,'".$ip_source."')";

	$ris_import_folder = query_exec($INSERT_atna_import_folder);
	} // Fine for($index_doc=0;$index_doc<count($FolderUniqueId_valid_array[2]);$index_doc++)



} // Fine if($ATNA_active=='A')

unset($_SESSION['tmp_path']);
unset($_SESSION['idfile']);
unset($_SESSION['logActive']);
unset($_SESSION['log_path']);

// Clean tmp folder
$system=PHP_OS;

$windows=substr_count(strtoupper($system),"WIN");


if($clean_cache=="A")
{
	if ($windows>0){
	exec('del tmp\\'.$idfile."* /q");	
	}
	else{	
	exec('rm -f '.$tmp_path.$idfile."*");
	exec('rm -f '.$tmpQuery_path."*");
	}

}

?>
