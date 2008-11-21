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
function error_schema(){
	$error_message = "Schema Validation Failed\n"; 
	$errorCode = "XDSRepositoryMetadataError";
	$errors = libxml_get_errors();
	print_r($errors);
    	foreach ($errors as $error) {
        	$error_message .= $error->message."\n";
   	 }
	//$error_message.= $errors->message;
	### RESTITUISCE IL MESSAGGIO DI FAIL IN SOAP
    	$SOAPED_failure_response = makeSoapedFailureResponse($error_message,$logentry,$errorCode);

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



?>