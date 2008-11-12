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
/*
//PULISCO LA CACHE TEMPORANEA
if($clean_cache=="A")
{
	exec('rm -f '.$tmp_path."*");
	exec('rm -f '.$tmpQuery_path."*");
}
// */
#### CREO L'OGGETTO DI LOG ####
$log = new Log("REP");
$log->set_tmp_path($tmp_path);
$log->set_idfile($idfile);
$log->setLogActive($logActive);
//$log->setCleanCache($clean_cache);
//$log->setCurrentNumFile();
$log->setCurrentLogPath($log_path);
$log->setCurrentFileSLogPath($tmp_path);



$_SESSION['tmp_path']=$tmp_path;
$_SESSION['idfile']=$idfile;
$_SESSION['logActive']=$logActive;
$_SESSION['log_path']=$log_path;

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

//COPIO IN LOCALE TUTTI GLI HEADERS RICEVUTI
// $fp_headers_received = fopen($tmp_path."headers_received","wb+");
// foreach ($headers as $header => $value)
// {
//    fwrite ($fp_headers_received, "$header = $value  \n");
// }
// fclose($fp_headers_received);

$input = $HTTP_RAW_POST_DATA;
$log->writeLogFile("RECEIVED:",1);
$log->writeLogFile($input,0);

// File da scrivere
$log->writeLogFileS($input,$idfile."-pre_decode_received-".$idfile,"M");
// //COPIO IN LOCALE IL FILE RICEVUTO PRIMA DI DECODARLO
// $fp_pre_decode_received = fopen($tmp_path."pre_decode_received","wb+");
//     fwrite($fp_pre_decode_received,$input);// $HTTP_RAW_POST_DATA
// fclose($fp_pre_decode_received);


writeTimeFile($idfile."--Repository: Scrivo pre_decode_received");


//PASSO A DECODARE IL FILE CREATO
include($lib_path.'mimeDecode.php');

//$filename = $tmp_path.'pre_decode_received';
  // $input  = fread(fopen($filename,'rb'),filesize($filename));
//echo filesize($filename);

// $params['include_bodies'] = true;
// $params['decode_bodies'] = true;
// $params['decode_headers'] = true;
//
// $decode = new Mail_mimeDecode($input);
// $structure = $decode->decode($params); //struttura: oggetto
//
// $body = $structure->{'body'};
/*
#### PRIMA OCCORRENZA DELL'ENVELOPE SOAP
if(strstr(strtoupper($input),"SOAP-ENV")){
$body = substr($input,strpos(strtoupper($input),"<SOAP-ENV:ENVELOPE"));
writeTimeFile($idfile."--Repository: trovato SOAP-ENV:ENVELOPE");
}
else if(strstr(strtoupper($input),"SOAPENV")){
$body = substr($input,strpos(strtoupper($input),"<SOAPENV:ENVELOPE"));
writeTimeFile($idfile."--Repository: trovato SOAPENV:ENVELOPE");
}

$log->writeLogFile("RECEIVED:",1);
$log->writeLogFile($body,0);


$log->writeLogFileS($body,$idfile."-body-".$idfile,"N");*/

if(stripos($headers["Content-Type"],"boundary")){
writeTimeFile($idfile."--Repository: Il boundary e' presente");
//if (preg_match('(boundary="[A-Za-z0-9_]+")',$headers["Content-Type"])) {
if (preg_match('(boundary="[^\t\n\r\f\v";]+")',$headers["Content-Type"])) {
writeTimeFile($idfile."--Repository: Ho trovato il boundary di tipo boundary=\"bvdwetrct637crtv\"");

$content_type = stristr($headers["Content-Type"],'boundary');
$pre_boundary = substr($content_type,strpos($content_type,'"')+1);

$fine_boundary = strpos($pre_boundary,'"')+1;
//BOUNDARY ESATTO
$boundary = '';
$boundary = substr($pre_boundary,0,$fine_boundary-1);

writeTimeFile($idfile."--Repository: Il boundary ".$boundary);
}

else if (preg_match('(boundary=[^\t\n\r\f\v";]+[;])',$headers["Content-Type"])) {
writeTimeFile($idfile."--Repository: Ho trovato il boundary di tipo boundary=bvdwetrct637crtv;");
$content_type = stristr($headers["Content-Type"],'boundary');
$pre_boundary = substr($content_type,strpos($content_type,'=')+1);
$fine_boundary = strpos($pre_boundary,';');
//BOUNDARY ESATTO
$boundary = '';
$boundary = substr($pre_boundary,0,$fine_boundary);

writeTimeFile($idfile."--Repository: Il boundary ".$boundary);

}

else {
writeTimeFile($idfile."--Repository: Il boundary non e' del tipo boundary=\"bvdwetrct637crtv\" o boundary=bvdwetrct637crtv;");

 }

$MTOM=false;
}
//Caso MTOM
else {
writeTimeFile($idfile."--Repository: non e' dichiarato il boundary");
$MTOM=true;
//$boundary = "--boundary_per_MTOM";
}


## TEST 11721: CONTROLLO CHE NON SIA the PAYLOAD is not metadata
if($boundary == '')//boundary non dichiarato --> no payload
{ 
writeTimeFile($idfile."--Repository: Non e presente il boundary");
	include_once('payload.php');
	$dom_pre_decode_received = domxml_open_mem(file_get_contents($tmp_path.$idfile."-pre_decode_received-".$idfile));
	$isPayloadNotEmpty = controllaPayload($dom_pre_decode_received);
	### CASO DI ASSENZA DEL PAYLOAD
	if(!$isPayloadNotEmpty)
	{ 
		$advertise = "\n$service: No metadata\n";
		$empty_payload_response = makeSoapedFailureResponse($advertise,$logentry);

//SCRIVO LA RISPOSTA IN UN FILE
// $fp_empty_payload_response = fopen($tmp_path."empty_payload_response","wb+");
// 	fwrite($fp_empty_payload_response,$empty_payload_response);
// fclose($fp_empty_payload_response);

	$log->writeLogFile("SENT:",1);
	$log->writeLogFile($empty_payload_response,0);

	$log->writeLogFileS($empty_payload_response,$idfile."-empty_payload_response-".$idfile,"M");


writeTimeFile($idfile."--Repository: Scrivo empty_payload_response");

//SPEDISCO : PULISCO IL BUFFER DI USCITA
	ob_get_clean();//OKKIO FONDAMENTALE!!!!!

	//HEADERS
	header("HTTP/1.1 200 OK");
	header("Path: $www_REP_path");
	header("Content-Type: text/xml;charset=UTF-8");
	header("Content-Length: ".(string)filesize($idfile."-empty_payload_response-".$idfile));
		//CONTENUTO DEL FILE DI RISPOSTA
	if($file = fopen($file_written,'rb'))
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




}//END OF if($boundary == '')

//COMPLETO IL BOUNDARY con due - davanti
//$boundary = "--".substr($pre_boundary,0,strlen($pre_boundary)-1);
$boundary = "--".$boundary;


//BOUNDARY
$log->writeLogFile("BOUNDARY:",1);
$log->writeLogFile($boundary,0);


if($save_files)
$log->writeLogFileS($boundary,$idfile."-boundary-".$idfile,"N");
// $fp_bo = fopen($tmp_path."boundary","wb+");
//     fwrite($fp_bo,$boundary);
// fclose($fp_bo);


writeTimeFile($idfile."--Repository: Scrivo boundary");


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


/*if(strstr(strtoupper($input),"SOAP-ENV")){
$body = substr($input,strpos(strtoupper($input),"<SOAP-ENV:ENVELOPE"));

writeTimeFile($idfile."--Repository: trovato SOAP-ENV:ENVELOPE");
}
else if(strstr(strtoupper($input),"SOAPENV")){
$body = substr($input,strpos(strtoupper($input),"<SOAPENV:ENVELOPE"));
writeTimeFile($idfile."--Repository: trovato SOAPENV:ENVELOPE");
}*/

$log->writeLogFile("RECEIVED:",1);
$log->writeLogFile($body,0);


$log->writeLogFileS($body,$idfile."-body-".$idfile,"N");
///////////////////////////////////////////

#### ebXML IMBUSTATO SOAP
$ebxml_imbustato_soap = rtrim(rtrim(substr($body,0,strpos($body,$boundary)),"\n"),"\r");

$log->writeLogFile("RECEIVED:",1);
$log->writeLogFile($ebxml_imbustato_soap,0);

if($save_files)
$log->writeLogFileS($ebxml_imbustato_soap,$idfile."-ebxml_imbustato_soap-".$idfile,"N");

// $fp_ebxml_imbustato_soap = fopen($tmp_path."ebxml_imbustato_soap","wb+");
// 	fwrite($fp_ebxml_imbustato_soap,$ebxml_imbustato_soap);
// fclose($fp_ebxml_imbustato_soap);

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


libxml_use_internal_errors(true);
$domEbxml = DOMDocument::loadXML($ebxml_STRING_VALIDATION);

// Valido il messaggio da uno schema
if (!$domEbxml->schemaValidate('schemas/rs.xsd')) {
	$error_message = "Schema Validation Failed\n"; 
	$errors = libxml_get_errors();
	print_r($errors);
    	foreach ($errors as $error) {
        	$error_message .= $error->message."\n";
   	 }
	//$error_message.= $errors->message;
	### RESTITUISCE IL MESSAGGIO DI FAIL IN SOAP
    	$SOAPED_failure_response = makeSoapedFailureResponse($error_message,$logentry);

	### SCRIVO LA RISPOSTA IN UN FILE
	// File da scrivere
	 $fp = fopen($tmp_path.$idfile."-SOAPED_failure_VALIDATION_response-".$idfile,"w+");
           fwrite($fp,$SOAPED_failure_response);
         fclose($fp);

	### PULISCO IL BUFFER DI USCITA
	ob_get_clean();//OKKIO FONDAMENTALE!!!!!

	### HEADERS
	header("HTTP/1.1 200 OK");
	header("Path: $www_REP_path");
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
	exit;
	}


####### VALIDAZIONE DELL'ebXML SECONDO LO SCHEMA
/*
$comando_java_validation=($java_path."java -jar ".$path_to_VALIDATION_jar."valid.jar -xsd ".$path_to_XSD_file." -xml ".$tmp_path.$idfile."-ebxml_for_validation-".$idfile);


if($save_files){
$fp_ebxml_val = fopen($tmp_path.$idfile."-comando_java_validation-".$idfile,"w+");
	fwrite($fp_ebxml_val,$comando_java_validation);
fclose($fp_ebxml_val);
}

#### ESEGUO IL COMANDO
$java_call_result = exec("$comando_java_validation",$output,$error);
$error_message = "";






### SE NON SI SONO VERIFICATI ERRORI NELLA CHIAMATA AL METODO
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

}//END OF if($error==0)*/





writeTimeFile($idfile."--Repository: Ho validato il messaggio");

if($save_files){
$fp_SCHEMA_val = fopen($tmp_path.$idfile."-SCHEMA_validation-".$idfile,"w+");
	fwrite($fp_SCHEMA_val,"VALIDAZIONE DA SCHEMA ==> OK <==");
fclose($fp_SCHEMA_val);
}





##################################################################
### QUI SONO SICURO CHE IL METADATA E' VALIDO RISPETTO ALLO SCHEMA
############################################################
### OTTENGO L'OGGETTO DOM RELATIVO ALL'ebXML
$dom_ebXML = domxml_open_mem($ebxml_STRING);
##################################################################

include_once('validation.php');
#### SECONDA COSA: DEVO VALIDARE XDSSubmissionSet.sourceId
$SourceId_valid_array = validate_XDSSubmissionSetSourceId($dom_ebXML,$idfile,$save_files);

writeTimeFile($idfile."--Repository: Verifico se il XDSSubmissionSetSourceId valido");


#########Ho tolto la validazione del sourceID#######
if($SourceId_valid_array[0])
#####################################à


//if(false)
{
     //RESTITUISCE IL MESSAGGIO DI ERRORE
	$advertise = "\nXDSSubmissionSet.SourceId '".$SourceId_valid_array[1]."' has not permission for submissions to this Repository\n";
	$failure_response = makeSoapedFailureResponse($advertise,$logentry);

	  //SCRIVO LA RISPOSTA IN UN FILE
 	 $fp = fopen($tmp_path."sourceId_failure_response", "wb+");
            fwrite($fp,$failure_response);
         fclose($fp);

	$log->writeLogFile("SENT:",1);
	$log->writeLogFile($failure_response,0);

	//File da scrivere
	$file_written1=$log->writeLogFileS($failure_response,$idfile."-sourceId_failure_response-".$idfile,"M");

	      //PULISCO IL BUFFER DI USCITA
	      ob_get_clean();//OKKIO FONDAMENTALE!!!!!

		//HEADERS
	      header("HTTP/1.1 200 OK");
			header("Path: $www_REP_path");
			header("Content-Type: text/xml;charset=UTF-8");
			header("Content-Length: ".(string)filesize($file_written1));
		//CONTENUTO DEL FILE DI RISPOSTA
			if($file1 = fopen($file_written1, 'rb'))
			{
   			while((!feof($file1)) && (connection_status()==0))
   			{
     				print(fread($file1, 1024*8));
      			flush();//NOTA BENE!!!!!!!!!
   			}

   			fclose($file1);
			}

	  //SPEDISCO E PULISCO IL BUFFER DI USCITA
	  ob_end_flush();
	  //BLOCCO L'ESECUZIONE DELLO SCRIPT
	  exit;
}//FINE if(!$SourceId_valid_array[0])
#### SE SONO QUI HO PASSATO IL VINCOLO DI VALIDAZIONE SU sourceId

#### CONTROLLO CHE CI SIANO DOCUMENTI IN ALLEGATO
$ExtrinsicObject_array = $dom_ebXML->get_elements_by_tagname("ExtrinsicObject");
##### SOLO NEL CASO CHE CI SIANO DOCUMENTI IN ALLEGATO
if(!empty($ExtrinsicObject_array))#### IMPORTANTE!!
{
#### TERZA COSA: DEVO VALIDARE XDSDocumentEntry.uniqueId
$UniqueId_valid_array = validate_XDSDocumentEntryUniqueId($dom_ebXML);

writeTimeFile($idfile."--Repository: Verifico XDSDocumentEntryUniqueId");

if(!$UniqueId_valid_array[0])
{
         //RESTITUISCE IL MESSAGGIO DI ERRORE
	$advertise = $UniqueId_valid_array[1];
	$failure_response = makeSoapedFailureResponse($advertise,$logentry);

//SCRIVO LA RISPOSTA IN UN FILE
// 	$fp = fopen($tmp_path."uniqueId_failure_response", "wb+");
//            fwrite($fp,$failure_response);
//         fclose($fp);

	$log->writeLogFile("SENT:",1);
	$log->writeLogFile($failure_response,0);
	$file_written2 = $log->writeLogFileS($failure_response,$idfile."-uniqueId_failure_response-".$idfile,"M");

	//PULISCO IL BUFFER DI USCITA
	ob_get_clean();//OKKIO FONDAMENTALE!!!!!

	//HEADERS
	header("HTTP/1.1 200 OK");
	header("Path: $www_REP_path");
	header("Content-Type: text/xml;charset=UTF-8");
	header("Content-Length: ".(string)filesize($file_written2));
	//CONTENUTO DEL FILE DI RISPOSTA
	if($file2 = fopen($file_written2, 'rb'))
	{
   		while((!feof($file2)) && (connection_status()==0))
   		{
     			print(fread($file2, 1024*8));
      			flush();//NOTA BENE!!!!!!!!!
   		}

   		fclose($file2);
	}

	//SPEDISCO E PULISCO IL BUFFER DI USCITA
	ob_end_flush();
	//BLOCCO L'ESECUZIONE DELLO SCRIPT
	exit;
}//FINE if(!$UniqueId_valid_array[0])
####SE SONO QUI HO PASSATO IL VINCOLO DI VALIDAZIONE SU DocumentEntryUniqueId
$log->writeLogFile('SUPERATO I VINCOLI DI VALIDAZIONE',1);

if($save_files)
$log->writeLogFileS('SUPERATO I VINCOLI DI VALIDAZIONE',$idfile."-post_validation-".$idfile,"N");

// $fp = fopen($tmp_path."POST_VALIDATION", "w+");
//       fwrite($fp,'SUPERATO I VINCOLI DI VALIDAZIONE');
// fclose($fp);
############ !!! IL METADATA RICEVUTO E' VALIDO !!! ############



// Devo verificare che siano corretti i boundary
$conta_EO = count($ExtrinsicObject_array);
$conta_boundary=substr_count($body,$boundary)-1;


if($conta_boundary!=$conta_EO)#### IMPORTANTE!!
{
#### QUARTA COSA: DEVO VERIFICARE CHE CI SIANO I BOUNDARY
writeTimeFile($idfile."--Repository: Non ci sono abbastanza boundary");

         //RESTITUISCE IL MESSAGGIO DI ERRORE
	$advertise = "\nThe boundary is not correctly set or there are less boundary than ExtrinsicObject\n";
	$failure_response = makeSoapedFailureResponse($advertise,$logentry);

//SCRIVO LA RISPOSTA IN UN FILE
// 	$fp = fopen($tmp_path."uniqueId_failure_response", "wb+");
//            fwrite($fp,$failure_response);
//         fclose($fp);

	$log->writeLogFile("SENT:",1);
	$log->writeLogFile($failure_response,0);
	$file_written3 = $log->writeLogFileS($failure_response,$idfile."-boundary_failure_response-".$idfile,"M");

	//PULISCO IL BUFFER DI USCITA
	ob_get_clean();//OKKIO FONDAMENTALE!!!!!

	//HEADERS
	header("HTTP/1.1 200 OK");
	header("Path: $www_REP_path");
	header("Content-Type: text/xml;charset=UTF-8");
	header("Content-Length: ".(string)filesize($file_written3));
	//CONTENUTO DEL FILE DI RISPOSTA
	if($file3 = fopen($file_written3, 'rb'))
	{
   		while((!feof($file3)) && (connection_status()==0))
   		{
     			print(fread($file3, 1024*8));
      			flush();//NOTA BENE!!!!!!!!!
   		}

   		fclose($file3);
	}

	//SPEDISCO E PULISCO IL BUFFER DI USCITA
	ob_end_flush();
	//BLOCCO L'ESECUZIONE DELLO SCRIPT
	exit;
}//FINE if($conta_boundary==$conta_EO)









// $ExtrinsicObject_array = $dom_ebXML->get_elements_by_tagname("ExtrinsicObject");
#### CICLO SU TUTTI I FILE ALLEGATI
for($o = 0 ; $o < (count($ExtrinsicObject_array)) ; $o++)
{
 	#### SINGOLO NODO ExtrinsicObject
	$ExtrinsicObject_node = $ExtrinsicObject_array[$o];

	#### RICAVO ATTRIBUTO id DI ExtrinsicObject
	$ExtrinsicObject_id_attr = $ExtrinsicObject_node->get_attribute('id');

	#### RICAVO ATTRIBUTO mymeType
	$ExtrinsicObject_mimeType_attr = $ExtrinsicObject_node->get_attribute('mimeType');
	#### RICAVO LA RELATIVA ESTENSIONE PER IL FILE
	$get_extension = "SELECT EXTENSION FROM MIMETYPE WHERE CODE = '$ExtrinsicObject_mimeType_attr'";
		$res = query_select($get_extension);

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
		
	/*$selectTOKEN="SELECT KEY_PROG FROM DOCUMENTS WHERE XDSDOCUMENTENTRY_UNIQUEID = '".$UniqueId."'";
	writeTimeFile($idfile."--Repository: uniqueid".$selectTOKEN);
	//$selectTOKEN= "SELECT MAX(TOKEN_ID) AS TOKEN_ID FROM TOKEN";
		$res_token = query_select($selectTOKEN);
		$next_token = $res_token[0][0];

	$document_token = $www_REP_path."getDocument.php?token=".$next_token;
	writeTimeFile($idfile."--Repository: document_token".$document_token);

	$insertTOKEN= "INSERT INTO TOKEN (TOKEN_ID,URI) VALUES ('$next_token','$document_URI2')";
	writeTimeFile($idfile."--Repository: token".$insertTOKEN);

		$ris_token = query_execute($insertTOKEN);*/
################################################################
### SALVATAGGIO DELL'ALLEGATO SU FILESYSTEM

	#### ORA DEVO ANDARE SUL FILE Body PER SALVARE L' ALLEGATO

	// Qui va gestito un errore (potrebbe essere Content-ID: <$ExtrinsicObject_id_attr)

$contentID_UP=strtoupper("Content-ID: <$ExtrinsicObject_id_attr>");
if(!strpos(strtoupper($body),$contentID_UP))#### IMPORTANTE!!
{
#### QUINTA COSA: DEVO VERIFICARE CHE Content-ID sia corretto
writeTimeFile($idfile."--Repository: In Content-ID non e formattato bene");

         //RESTITUISCE IL MESSAGGIO DI ERRORE
	$advertise = "\nThe Content-ID is not correctly set\n";
	$failure_response = makeSoapedFailureResponse($advertise,$logentry);

	$log->writeLogFile("SENT:",1);
	$log->writeLogFile($failure_response,0);
	$file_written4 = $log->writeLogFileS($failure_response,$idfile."-content-id_failure_response-".$idfile,"M");

	//PULISCO IL BUFFER DI USCITA
	ob_get_clean();//OKKIO FONDAMENTALE!!!!!

	//HEADERS
	header("HTTP/1.1 200 OK");
	header("Path: $www_REP_path");
	header("Content-Type: text/xml;charset=UTF-8");
	header("Content-Length: ".(string)filesize($file_written3));
	//CONTENUTO DEL FILE DI RISPOSTA
	if($file4 = fopen($file_written4, 'rb'))
	{
   		while((!feof($file4)) && (connection_status()==0))
   		{
     			print(fread($file4, 1024*8));
      			flush();//NOTA BENE!!!!!!!!!
   		}

   		fclose($file4);
	}

	//SPEDISCO E PULISCO IL BUFFER DI USCITA
	ob_end_flush();
	//BLOCCO L'ESECUZIONE DELLO SCRIPT
	exit;
}//FINE if($conta_boundary==$conta_EO)



	
	$allegato_STRING_2 = substr($body,(strpos(strtoupper($body),$contentID_UP)+strlen($contentID_UP)),(strpos($body,$boundary,(strpos(strtoupper($body),$contentID_UP)))-strpos(strtoupper($body),$contentID_UP)-strlen($contentID_UP)));
	/*if(strpos($body,"Content-ID: <$ExtrinsicObject_id_attr>")){
	$content_id = "Content-ID: <$ExtrinsicObject_id_attr>";
	$allegato_STRING_2 = substr($body,(strpos($body,$content_id)+strlen($content_id)),(strpos($body,$boundary,(strpos($body,$content_id)))-strpos($body,$content_id)-strlen($content_id)));
	}

	
	if(strpos($body,"Content-Id: <$ExtrinsicObject_id_attr>")){
	$content_id = "Content-Id: <$ExtrinsicObject_id_attr>";
	$allegato_STRING_2 = substr($body,(strpos($body,$content_id)+strlen($content_id)),(strpos($body,$boundary,(strpos($body,$content_id)))-strpos($body,$content_id)-strlen($content_id)));
	}*/
	### PULISCO LA STRINGA IN CAPO E IN CODA: ATTENZIONE NON MODIFICARE !!!
	$allegato_STRING = trim($allegato_STRING_2,"\n\r");### QUI HO L'ALLEGATO
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


	if($save_files){
	$fp = fopen($tmp_path.$idfile."-MOD-".$idfile, "w+");
		fwrite($fp,$mod);
	fclose($fp);

}


	$datetime="CURRENT_TIMESTAMP";
	$insert_into_DOCUMENTS = "INSERT INTO DOCUMENTS (XDSDOCUMENTENTRY_UNIQUEID,DATA,URI) VALUES ('$UniqueId_valid_array[2]',$datetime,'$document_URI2')";
	//writeTimeFile($idfile."--Repository: INSERT INTO DOCUMENTS".$insert_into_DOCUMENTS);

	$fp_insert_into_DOCUMENTS= fopen($tmp_path.$idfile."-insert_into_DOCUMENTS-".$idfile, "wb+");
    	fwrite($fp_insert_into_DOCUMENTS,$insert_into_DOCUMENTS);
	fclose($fp_insert_into_DOCUMENTS);


	$ris = query_execute($insert_into_DOCUMENTS); //FINO A QUA OK!!!


	$selectTOKEN="SELECT KEY_PROG FROM DOCUMENTS WHERE XDSDOCUMENTENTRY_UNIQUEID = '".$UniqueId_valid_array[2]."'";
	//writeTimeFile($idfile."--Repository: uniqueid".$selectTOKEN);
	//$selectTOKEN= "SELECT MAX(TOKEN_ID) AS TOKEN_ID FROM TOKEN";
		$res_token = query_select($selectTOKEN);
		$next_token = $res_token[0][0];

	$document_token = $www_REP_path."getDocument.php?token=".$next_token;
	writeTimeFile($idfile."--Repository: document_token".$document_token);

#### MODIFICO IL METADATA PER FORWARDARLO SUCCESSIVAMENTE AL REGISTRY
      if($mod) ### CASO HASH-SIZE-URI NON PRESENTI
      {
		include_once("createMetadataToForward.php");

		#### INSERISCO NEL DB E OTTENGO L'ebXML MODIFICATO
		//$dom_ebXML = modifyMetadata($dom_ebXML,$ExtrinsicObject_node,$file_name,$document_URI,$allegato_STRING,$idfile);
		//$dom_ebXML = modifyMetadata($dom_ebXML,$ExtrinsicObject_node,$file_name,$document_URI,$allegato_STRING,$idfile,$document_token);
		$dom_ebXML = modifyMetadata($dom_ebXML,$ExtrinsicObject_node,$file_name,$idfile,$document_URI,$document_token); 
      }//END OF if($mod)
	else if(!$mod)### CASO HASH-SIZE-URI GIA' PRESENTI
	{
		include_once("createMetadataToForward.php");

		#### INSERISCO NEL DB E MANTENGO L'ebXML INALTERATO
		//mantainMetadata($ExtrinsicObject_node,$file_name,$document_URI,$allegato_STRING);
		//mantainMetadata($ExtrinsicObject_node,$file_name,$document_URI,$allegato_STRING,$document_token);
		mantainMetadata($ExtrinsicObject_node,$file_name,$document_URI);

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

// ## 3- METTO SU FILE CIO' CHE FORWARDO AL REG
// $log->writeLogFile("SENT:",1);
// $log->writeLogFile($post_data,0);
// $file_forwarded_written = $log->writeLogFileS($post_data,"forwarded","N");

}//END OF if($boundary != "--")

else if($boundary == "--")
{
	## NO ALLEGATI PERCIO' FORWARDO DIRETTAMENTE AL REG
	$post_data = file_get_contents($tmp_path.$idfile."-body-".$idfile);

}//END OF else if($boundary == "--")

## 3- METTO SU FILE CIO' CHE FORWARDO AL REG
$log->writeLogFile("SENT:",1);
$log->writeLogFile($post_data,0);

//File da scrivere!!!!
$file_forwarded_written = $log->writeLogFileS($post_data,$idfile."-forwarded-".$idfile,"N");
## 4- SPEDISCO IL MESSAGGIO AL REGISTRY E RICAVO LA RESPONSE



#### CREO IL CLIENT PER LA CONNESSIONE HTTP CON IL REGISTRY
if($http=="NORMAL")
{
	include("./http_connections/http_client.php");
	$client = new HTTP_Client($reg_host,$reg_port,30);
}
else if($http=="TLS")
{
	include("./http_connections/http_client_TLS.php");
	$client = new HTTP_Client_TLS();
}
### SETTAGGI COMUNI
$client->set_post_data($post_data);



writeTimeFile($idfile."--Repository: La dimensione e ".filesize($file_forwarded_written));



$client->set_data_length(filesize($file_forwarded_written));
$client->set_path($reg_path);
$client->set_idfile($idfile);
$client->set_save_files($save_files);


#### SETTAGGI DEL TLS
$client->set_protocol($tls_protocol);
$client->set_host($reg_host);
$client->set_port($reg_port);

######## INOLTRO AL REGISTRY E ATTENDO LA RISPOSTA ##########

writeTimeFile($idfile."--Repository: Inoltro al registry e attendo la risposta");

$registry_response_arr = $client->send_request();

#$headers_da_registry = apache_request_headers();

$registry_response_log = $registry_response_arr[1];

writeTimeFile($idfile."--Repository: Ho ottenuto la risposta dal registry");


#### CASO DI ERORE DI CERTIFICATO
if($registry_response_log!="")
{
	$failure_response = makeSoapedFailureResponse($registry_response_log,$logentry);

	$fp_TLS_Client_POST_ERROR = fopen($tmp_path."Client_CONNECTION_ERROR","w+");
       	    fwrite($fp_TLS_Client_POST_ERROR,$failure_response);
	fclose($fp_TLS_Client_POST_ERROR);

	//PULISCO IL BUFFER DI USCITA
	ob_get_clean();//OKKIO FONDAMENTALE!!!!!

	//HEADERS
	header("HTTP/1.1 200 OK");
	header("Path: $www_REP_path");
	header("Content-Type: text/xml;charset=UTF-8");
	header("Content-Length: ".(string)filesize($tmp_path."Client_CONNECTION_ERROR"));
	//CONTENUTO DEL FILE DI RISPOSTA
	if($file = fopen($tmp_path."Client_CONNECTION_ERROR",'rb'))
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
		$res_delete = query_execute($deleteDocument);
}

#### XML RICEVUTO IN RISPOSTA DAL REGISTRY
$body = trim((substr($da_registry,strpos($da_registry,"<SOAP-ENV:Envelope"))));

//File da scrivere!!!!!
$fp_body_response = fopen($tmp_path.$idfile."-body_response-".$idfile, "wb+");
		fwrite($fp_body_response,$body);
fclose($fp_body_response);

#### HEADERS RICEVUTI IN RISPOSTA DAL REGISTRY
$headers = trim(substr($da_registry,strpos($da_registry,"HTTP"),(strlen($da_registry)-strlen($body)-17)));

//File da scrivere!!!!
$fp_headers_response = fopen($tmp_path.$idfile."-headers_response-".$idfile, "wb+");
		fwrite($fp_headers_response,$headers);
fclose($fp_headers_response);

//=============================================================================//
//==============  ORA RISPONDO (ACK) AL DOCUMENT SOURCE  =====================//
$headers_vector = file($tmp_path.$idfile."-headers_response-".$idfile);//metto gli headers ricevuti in un vettore


$body_response_fileSize = filesize($tmp_path.$idfile."-body_response-".$idfile);

if($save_files){
$fp_dim = fopen($tmp_path.$idfile."-dim-".$idfile,'wb+');
##### CALCOLO LA SIZE DELLA RISPOSTA RICEVUTA
fwrite($fp_dim,$body_response_fileSize);
fclose($fp_dim);
}

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
header("SOAPAction : \"\"");

    fwrite($fp_vector,"SOAPAction : \"\"");
fclose($fp_vector);

}//END OF if(!empty($headers_vector))

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


unset($_SESSION['tmp_path']);
unset($_SESSION['idfile']);
unset($_SESSION['logActive']);
unset($_SESSION['log_query_path']);

//PULISCO LA CACHE TEMPORANEA



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
