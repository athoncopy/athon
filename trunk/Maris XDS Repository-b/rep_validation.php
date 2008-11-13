<?php
# ------------------------------------------------------------------------------------
# MARIS XDS REGISTRY
# Copyright (C) 2007 - 2010  MARiS Project
# Dpt. Medical and Diagnostic Sciences, University of Padova - csaccavini@rad.unipd.it
# This program is distributed under the terms and conditions of the GPL
# See the LICENSE files for details
# ------------------------------------------------------------------------------------
#### XDSSubmissionSet.sourceId
function validate_XDSSubmissionSetSourceId($dom,$connessione)
{
    	//$ebxml_value = searchForIds($dom,'RegistryPackage','uniqueId');
    
	$ebxml_value = '';

##### RADICE DEL DOCUMENTO ebXML
	$root_ebXML = $dom->document_element();
	
	##### ARRAY DEI NODI REGISTRYPACKAGE
	$dom_ebXML_RegistryPackage_node_array=$root_ebXML->get_elements_by_tagname("RegistryPackage");

	#### CICLO SU OGNI RegistryPackage ####
	for($index=0;$index<(count($dom_ebXML_RegistryPackage_node_array));$index++)
	{
	##### SINGOLO NODO REGISTRYPACKAGE
	$RegistryPackage_node = $dom_ebXML_RegistryPackage_node_array[$index];
	
	#### ARRAY DEI FIGLI DEL NODO REGISTRYPACKAGE ##############	
	$RegistryPackage_child_nodes = $RegistryPackage_node->child_nodes();
	#################################################################

################# PROCESSO TUTTI I NODI FIGLI DI REGISTRYPACKAGE
	for($k=0;$k<count($RegistryPackage_child_nodes);$k++)
	{
		#### SINGOLO NODO FIGLIO DI REGISTRYPACKAGE
		$RegistryPackage_child_node=$RegistryPackage_child_nodes[$k];
		#### NOME DEL NODO
		$RegistryPackage_child_node_tagname = $RegistryPackage_child_node->node_name();

		if($RegistryPackage_child_node_tagname=='ExternalIdentifier')
		{
			$externalidentifier_node = $RegistryPackage_child_node;
			$value_value= $externalidentifier_node->get_attribute('value');
			
			#### NODI FIGLI DI EXTERNALIDENTIFIER
			$externalidentifier_child_nodes = $externalidentifier_node->child_nodes();
		//print_r($name_node);
			for($q = 0;$q < count($externalidentifier_child_nodes);$q++)
			{
				$externalidentifier_child_node = $externalidentifier_child_nodes[$q];
				$externalidentifier_child_node_tagname = $externalidentifier_child_node->node_name();
				if($externalidentifier_child_node_tagname=='Name')
				{
					$name_node=$externalidentifier_child_node;

					$LocalizedString_nodes = $name_node->child_nodes();
		//print_r($LocalizedString_nodes);
			for($p = 0;$p < count($LocalizedString_nodes);$p++)
			{
				$LocalizedString_node = $LocalizedString_nodes[$p];//->node_name();
				$LocalizedString_node_tagname = $LocalizedString_node->node_name();

				if($LocalizedString_node_tagname == 'LocalizedString')
				{
					$LocalizedString_value =$LocalizedString_node->get_attribute('value');
					if(strpos(strtolower(trim($LocalizedString_value)),strtolower('SubmissionSet.sourceId')))
					{
						$ebxml_value = $value_value;
					}
				}

			}

				}
			}
		}

	}

	}//END OF for($index=0;$index<(count($dom_ebXML_RegistryPackage_node_array));$index++)

    	//QUERY AL DB
    	//$query = "SELECT * FROM SUBMISSIONS WHERE XDSSubmissionSet_uniqueId = '$ebxml_value'";
	$query = "SELECT * FROM KNOWN_SOUCES_IDS WHERE XDSSUBMISSIONSET_SOURCEID = '$ebxml_value'";
	 
  	if($_SESSION['save_files']){
    	$fp_sourceIdQuery = fopen($_SESSION['tmp_path'].$_SESSION['idfile']."-SubmissionSetSourceIdQuery-".$_SESSION['idfile'],"w+");
		fwrite($fp_sourceIdQuery,$query);
	fclose($fp_sourceIdQuery);
	}
	#### ESEGUO LA QUERY
    	$res = query_select2($query,$connessione); //array bidimensionale

    	$isEmptySource = (empty($res));

		if($isEmptySource){
		$errorcode[]="XDSRepositoryMetadataError";
		$error_message[] = "XDSSubmissionSet.SourceId '".$ebxml_value."' has not permission for submissions to this Repository";
		$SourceId_response = makeSoapedFailureResponse($error_message,$errorcode);
		writeTimeFile($_SESSION['idfile']."--Repository: sourceId_failure_response");
			
		$file_input=$_SESSION['tmp_path'].$_SESSION['idfile']."-sourceId_failure_response-".$_SESSION['idfile'];
		$fp = fopen($file_input, "wb+");
            	  fwrite($fp,$SourceId_response);
         	fclose($fp);

		SendError($file_input);
		exit;
	
		}
		else {
    			return $isEmptySource;
		}
}//end of validate_XDSSubmissionSetSourceId($dom_ebXML)

##############################################################################

##### XDSDocumentEntry.uniqueId
function validate_XDSDocumentEntryUniqueId($dom,$connessione)
{
//      $fp_uniqueIdQuery = fopen("tmp/DocumentEntryUniqueIdQuery","w+");

	$ebxml_value = array();

##### RADICE DEL DOCUMENTO ebXML
	$root_ebXML = $dom->document_element();
	
	##### ARRAY DEI NODI ExtrinsicObject
	$dom_ebXML_ExtrinsicObject_node_array=$root_ebXML->get_elements_by_tagname("ExtrinsicObject");

	#### CICLO SU OGNI ExtrinsicObject ####
	$isEmpty = false;
	$failure = "";
	for($index=0;$index<(count($dom_ebXML_ExtrinsicObject_node_array));$index++)
	{
	##### NODO ExtrinsicObject RELATIVO AL DOCUMENTO NUMERO $index
	$ExtrinsicObject_node = $dom_ebXML_ExtrinsicObject_node_array[$index];
	
	#### ARRAY DEI FIGLI DEL NODO ExtrinsicObject ##############	
	$ExtrinsicObject_child_nodes = $ExtrinsicObject_node->child_nodes();
	#################################################################

################# PROCESSO TUTTI I NODI FIGLI DI ExtrinsicObject
	for($k=0;$k<count($ExtrinsicObject_child_nodes);$k++)
	{
		#### SINGOLO NODO FIGLIO DI ExtrinsicObject
		$ExtrinsicObject_child_node=$ExtrinsicObject_child_nodes[$k];
		#### NOME DEL NODO
		$ExtrinsicObject_child_node_tagname = $ExtrinsicObject_child_node->node_name();

		if($ExtrinsicObject_child_node_tagname=='ExternalIdentifier')
		{
			$externalidentifier_node = $ExtrinsicObject_child_node;
			$value_value= avoidHtmlEntitiesInterpretation($externalidentifier_node->get_attribute('value'));
			
			#### NODI FIGLI DI EXTERNALIDENTIFIER
			$externalidentifier_child_nodes = $externalidentifier_node->child_nodes();
		//print_r($name_node);
			for($q = 0;$q < count($externalidentifier_child_nodes);$q++)
			{
				$externalidentifier_child_node = $externalidentifier_child_nodes[$q];
				$externalidentifier_child_node_tagname = $externalidentifier_child_node->node_name();
				if($externalidentifier_child_node_tagname=='Name')
				{
					$name_node=$externalidentifier_child_node;

					$LocalizedString_nodes = $name_node->child_nodes();
		//print_r($LocalizedString_nodes);
			for($p = 0;$p < count($LocalizedString_nodes);$p++)
			{
				$LocalizedString_node = $LocalizedString_nodes[$p];//->node_name();
				$LocalizedString_node_tagname = $LocalizedString_node->node_name();

				if($LocalizedString_node_tagname == 'LocalizedString')
				{
					$LocalizedString_value =$LocalizedString_node->get_attribute('value');
					if(strpos(strtolower(trim($LocalizedString_value)),strtolower('DocumentEntry.uniqueId')))
					{
						$ebxml_value[$index] = $value_value;
					}
					
				}

			}

				}
			}
		}//END OF if($ExtrinsicObject_child_node_tagname=='ExternalIdentifier')

	
	}


	//QUERY AL DB
    	$query = "SELECT XDSDOCUMENTENTRY_UNIQUEID FROM DOCUMENTS WHERE XDSDOCUMENTENTRY_UNIQUEID = '".$ebxml_value[$index]."'";
    	
	### EFFETTUO LA QUERY ED OTTENGO IL RISULTATO
	$res = query_select2($query,$connessione); //array bidimensionale
	 
    	$isEmptyUniqueId = ((empty($res)) || $isEmpty);

		if(!$isEmptyUniqueId){###---> uniqueId già presente --> eccezione
			$errorcode[]="XDSNonIdenticalHash";
			$error_message[] = "ExternalIdentifier XDSDocumentEntry.uniqueId '".$ebxml_value[$index]."' (urn:uuid:2e82c1f6-a085-4c72-9da3-8640a32e42ab) already exists in registry";
			$uniqueId_response = makeSoapedFailureResponse($error_message,$errorcode);
			writeTimeFile($_SESSION['idfile']."--Repository: uniqueId_failure_response");
			
			$file_input=$_SESSION['tmp_path'].$_SESSION['idfile']."-uniqueId_failure_response-".$_SESSION['idfile'];
			$fp = fopen($file_input, "wb+");
            	  	  fwrite($fp,$uniqueId_response);
         		fclose($fp);

			SendError($file_input);
			exit;
		}
	}//END OF for($index=0;$index<(count($dom_ebXML_ExtrinsicObject_node_array));$index++)
    	$ret = array($isEmptyUniqueId,$ebxml_value);
	return $ret;

  
}//end of validate_XDSDocumentEntryUniqueId($dom)

function controllaPayload($input){
	$errorcode=array();
	$error_message=array();
	writeTimeFile($_SESSION['idfile']."--Repository: Non e presente il boundary");
	$dom_ebXML = domxml_open_mem($input);
	$root_ebXML = $dom_ebXML->document_element();
	$dom_ebXML_node_array=$root_ebXML->get_elements_by_tagname("RegistryObjectList");
	
	$node = $dom_ebXML_node_array[0];
	$payload = $node->child_nodes();

	$isNotEmpty = (count($payload)-1);
		
		if(!$isNotEmpty){
			$errorcode[]="XDSMissingMetadata";
			$error_message[] = "$service: No metadata\n";
			$empty_payload_response = makeSoapedFailureResponse($error_message,$errorcode);
			writeTimeFile($_SESSION['idfile']."--Repository: empty_payload_response");
			
			$file_input=$_SESSION['tmp_path'].$_SESSION['idfile']."-empty_payload_response-".$_SESSION['idfile'];
			$fp = fopen($file_input, "wb+");
            			fwrite($fp,$empty_payload_response);
         		fclose($fp);

			SendError($file_input);
			exit;
	
		}
	return $isNotEmpty;

}


function isValid($ebxml_STRING_VALIDATION){

	####### VALIDAZIONE DELL'ebXML SECONDO LO SCHEMA
	libxml_use_internal_errors(true);
	$domEbxml = DOMDocument::loadXML($ebxml_STRING_VALIDATION);

	// Valido il messaggio da uno schema
	if (!$domEbxml->schemaValidate('schemas3/lcm.xsd')) {
		$errors = libxml_get_errors();
    		foreach ($errors as $error) {
			$errorcode[] = "XDSRepositoryMetadataError"; 
        		$error_message[] = $error->message;
   	 	}
		### RESTITUISCE IL MESSAGGIO DI FAIL IN SOAP
    		$failure_response = makeSoapedFailureResponse($error_message,$errorcode);

		### SCRIVO LA RISPOSTA IN UN FILE
		// File da scrivere
		$file_input=$_SESSION['tmp_path'].$_SESSION['idfile']."-SOAPED_failure_VALIDATION_response-".$_SESSION['idfile'];
	 	$fp = fopen($file_input,"w+");
           	   fwrite($fp,$failure_response);
        	fclose($fp);

		SendError($file_input);
		exit;
	
	}

	else {
		return true;
	}
}


function verificaAllegatiExtrinsicObject($conta_EO,$conta_allegati,$conta_Document_id){

	### Caso in cui ci siano meno allegati che ExtrinsicObject
	### Devo dare un errore XDSMissingDocument
	if($conta_allegati<$conta_EO || $conta_allegati<$conta_Document_id)#### IMPORTANTE!!
	{
		writeTimeFile($idfile."--Repository: Non ci sono abbastanza allegati");

         	//RESTITUISCE IL MESSAGGIO DI ERRORE
		$errorcode[] = "XDSMissingDocument";
		$error_message[] = "XDSDocumentEntry exists in metadata with no corresponding attached document";
		$failure_response = makeSoapedFailureResponse($error_message,$errorcode);

		$file_input=$_SESSION['tmp_path'].$_SESSION['idfile']."-Document_missing-".$_SESSION['idfile'];
	 	$fp = fopen($file_input,"w+");
           	   fwrite($fp,$failure_response);
        	fclose($fp);

		SendError($file_input);
		exit;
		//PULISCO IL BUFFER DI USCITA
		ob_get_clean();//OKKIO FONDAMENTALE!!!!!
	
	}//FINE if($conta_boundary<$conta_EO)


	### Caso in cui ci siano più allegati che ExtrinsicObject
	### Devo dare un errore XDSMissingDocumentMetadata
	else if ($conta_allegati>$conta_EO || $conta_allegati>$conta_Document_id){

         	//RESTITUISCE IL MESSAGGIO DI ERRORE
		$errorcode[] = "XDSMissingDocumentMetadata";
		$error_message[] = "There are more attached file than ExtrinsicObject";
		$failure_response = makeSoapedFailureResponse($error_message,$errorcode);

		$file_input=$_SESSION['tmp_path'].$_SESSION['idfile']."-ExtrinsicObject_missing-".$_SESSION['idfile'];
	 	$fp = fopen($file_input,"w+");
           	   fwrite($fp,$failure_response);
        	fclose($fp);

		SendError($file_input);
		exit;
		//PULISCO IL BUFFER DI USCITA
		ob_get_clean();//OKKIO FONDAMENTALE!!!!!
	}

	else if ($conta_EO != $conta_Document_id) {

	         	//RESTITUISCE IL MESSAGGIO DI ERRORE
		$errorcode[] = "XDSMetadataError";
		$error_message[] = "Dodcument ID and ExtrinsicObject Mismatch ";
		$failure_response = makeSoapedFailureResponse($error_message,$errorcode);

		$file_input=$_SESSION['tmp_path'].$_SESSION['idfile']."-ContentID_missing-".$_SESSION['idfile'];
	 	$fp = fopen($file_input,"w+");
           	   fwrite($fp,$failure_response);
        	fclose($fp);

		SendError($file_input);
		exit;
		//PULISCO IL BUFFER DI USCITA
		ob_get_clean();//OKKIO FONDAMENTALE!!!!!
	}

	

	else {
 		writeTimeFile($_SESSION['idfile']."--Repository: Ci sono $conta_EO ExtrinsicObject, $conta_Document_id Content-ID e $conta_boundary allegati");
		return true;
	}


}

function verificaContentMimeExtrinsicObject($dom_ebXML,$allegato_array){

	$valid=true;
	$ExtrinsicObject_array = $dom_ebXML->get_elements_by_tagname("ExtrinsicObject");
	$Document_array = $dom_ebXML->get_elements_by_tagname("Document");
	//$Document_id_attr = $Document_node->get_attribute('id');
	$conta_Document_id = count($Document_array);
	$conta_EO = count($ExtrinsicObject_array);
	$ContaAllegati = count($allegato_array);
	$Document_href_attr=array();
	$Document_href_attr_nocid=array();
	if($ContaAllegati != $conta_EO || $ContaAllegati != $conta_Document_id || $conta_EO != $conta_Document_id){
		$valid=false;
		writeTimeFile($_SESSION['idfile']."--Repository: Gli Allegati o i Content-ID sono diversi dagli ExtrinsicObject");
		verificaAllegatiExtrinsicObject($conta_EO,$ContaAllegati,$conta_Document_id);
		exit;
	}

	else {
		//Ciclo su ExtrinsicObject
		for($index = 0 ; $index < $conta_EO ; $index++)
		{
			
 			#### SINGOLO NODO ExtrinsicObject
			$ExtrinsicObject_node = $ExtrinsicObject_array[$index];

			#### RICAVO ATTRIBUTO id DI ExtrinsicObject
			$ExtrinsicObject_id_attr = $ExtrinsicObject_node->get_attribute('id');
			$contenID_arr[$index]=$ExtrinsicObject_id_attr;
		
			#### RICAVO ATTRIBUTO mymeType
			$ExtrinsicObject_mimeType_attr = $ExtrinsicObject_node->get_attribute('mimeType');
			$mimeType_arr[$index]=$ExtrinsicObject_mimeType_attr;

			

			$trovato_id=false;
			//Ciclo su Document Content ID
			for($s=0;$s<$conta_Document_id && !$trovato_id;$s++){
				$Document_node = $Document_array[$s];
				$Document_id_attr = $Document_node->get_attribute('id');
				if($ExtrinsicObject_id_attr[$index]==$Document_id_attr[$s]){
					$Document_child_nodes = $Document_node->child_nodes();
					### SINGOLO NODO FIGLIO

					for($i=0;$i<count($Document_child_nodes);$i++){
					$Document_child_node=$Document_child_nodes[$i];

					### TAGNAME DEL SINGOLO NODO FIGLIO
					$Document_child_node_tagname=$Document_child_node->node_name();
					if($Document_child_node_tagname=='Include')
					{
						$Document_href_attr[$index] = $Document_child_node->get_attribute('href');
						$Document_href_attr_nocid[$index] = substr($Document_href_attr[$index],strpos($Document_href_attr[$index],'cid:')+4);
						$trovato_id=true;
					}//END OF if($ExtrinsicObject_child_node_tagname=='Classification')
					}
				}
				
			}



			// Non c'è corrispondenza tra ExtrinsicObject e Include
			if(!$trovato_id) {
				writeTimeFile($_SESSION['idfile']."--Repository: Non ho trovato corrispondenza tra : ".$ExtrinsicObject_id_attr[$index]." e ".$Document_id_attr[$s]);
				$valid=false;
				$conta_Document_id--;
				verificaAllegatiExtrinsicObject($conta_EO,$ContaAllegati,$conta_Document_id);
				exit;
				}

		}

		for($k=0;$k<$conta_EO && !$trovato;$k++){
			$contentID_UP=strtoupper("Content-ID: <".$Document_href_attr_nocid[$k].">");
			$mimeType_UP=strtoupper("Content-Type: ".$mimeType_arr[$k]);
			$trovato=false;
			for($i=0;$i<$ContaAllegati && !$trovato;$i++){
				//Se voglio controllare anche il mime-type
				//if((strpos(strtoupper($allegato_array[$i]),$contentID_UP)) && (strpos(strtoupper($allegato_array[$i]),$mimeType_UP))){
				if(strpos(strtoupper($allegato_array[$i]),$contentID_UP)){
				$trovato=true;
				}
			}
		}

		// Se non trova il Content-ID o MimeType--> Errore XDSMissingDocument
		if(!$trovato) {
			writeTimeFile($_SESSION['idfile']."--Repository: In Content-ID o Content-Type non corrisponde Content-ID: ".$contentID_UP." Mime-Type: ".$mimeType_UP);
			$valid=false;
			$ContaAllegati--;
			verificaAllegatiExtrinsicObject($conta_EO,$ContaAllegati,$conta_Document_id);
			exit;
		}

	}

$ret=array($valid,$Document_href_attr_nocid,$mimeType_arr);
return $ret;
}


function verificaAllegatiExtrinsicObjectMTOM($conta_EO,$conta_Document_id){

	### Caso in cui ci siano meno allegati che ExtrinsicObject
	### Devo dare un errore XDSMissingDocument
	if($conta_Document_id<$conta_EO)#### IMPORTANTE!!
	{
		writeTimeFile($idfile."--Repository: Non ci sono abbastanza allegati");

         	//RESTITUISCE IL MESSAGGIO DI ERRORE
		$errorcode[] = "XDSMissingDocument";
		$error_message[] = "XDSDocumentEntry exists in metadata with no corresponding attached document";
		$failure_response = makeSoapedFailureResponse($error_message,$errorcode);

		$file_input=$_SESSION['tmp_path'].$_SESSION['idfile']."-Document_missing-".$_SESSION['idfile'];
	 	$fp = fopen($file_input,"w+");
           	   fwrite($fp,$failure_response);
        	fclose($fp);

		SendError($file_input);
		exit;
		//PULISCO IL BUFFER DI USCITA
		ob_get_clean();//OKKIO FONDAMENTALE!!!!!
	
	}//FINE if($conta_boundary<$conta_EO)


	### Caso in cui ci siano più allegati che ExtrinsicObject
	### Devo dare un errore XDSMissingDocumentMetadata
	else if ($conta_Document_id>$conta_EO){

         	//RESTITUISCE IL MESSAGGIO DI ERRORE
		$errorcode[] = "XDSMissingDocumentMetadata";
		$error_message[] = "There are more attached file (contentID) than ExtrinsicObject";
		$failure_response = makeSoapedFailureResponse($error_message,$errorcode);

		$file_input=$_SESSION['tmp_path'].$_SESSION['idfile']."-ExtrinsicObjectID_missing-".$_SESSION['idfile'];
	 	$fp = fopen($file_input,"w+");
           	   fwrite($fp,$failure_response);
        	fclose($fp);

		SendError($file_input);
		exit;
		//PULISCO IL BUFFER DI USCITA
		ob_get_clean();//OKKIO FONDAMENTALE!!!!!
	}

	else {
 		writeTimeFile($_SESSION['idfile']."--Repository: Ci sono $conta_EO ExtrinsicObject, $conta_Document_id Content-ID e $conta_boundary allegati");
		return true;
	}


}


function verificaContentMimeExtrinsicObjectMTOM($dom_ebXML){

	$valid=true;
	$ExtrinsicObject_array = $dom_ebXML->get_elements_by_tagname("ExtrinsicObject");
	$Document_array = $dom_ebXML->get_elements_by_tagname("Document");
	//$Document_id_attr = $Document_node->get_attribute('id');
	$conta_Document_id = count($Document_array);
	$conta_EO = count($ExtrinsicObject_array);
	if($conta_EO != $conta_Document_id){
		$valid=false;
		writeTimeFile($_SESSION['idfile']."--Repository: Gli Allegati o i Content-ID sono diversi dagli ExtrinsicObject");
		verificaAllegatiExtrinsicObjectMTOM($conta_EO,$conta_Document_id);
		exit;
	}

	else {
				//Ciclo su ExtrinsicObject
		for($index = 0 ; $index < $conta_EO ; $index++)
		{
			
 			#### SINGOLO NODO ExtrinsicObject
			$ExtrinsicObject_node = $ExtrinsicObject_array[$index];

			#### RICAVO ATTRIBUTO id DI ExtrinsicObject
			$ExtrinsicObject_id_attr = $ExtrinsicObject_node->get_attribute('id');
			$contenID_arr[$index]=$ExtrinsicObject_id_attr;
		
			#### RICAVO ATTRIBUTO mymeType
			$ExtrinsicObject_mimeType_attr = $ExtrinsicObject_node->get_attribute('mimeType');
			$mimeType_arr[$index]=$ExtrinsicObject_mimeType_attr;

			

			$trovato_id=false;
			//Ciclo su Document Content ID
			for($s=0;$s<$conta_Document_id && !$trovato_id;$s++){
				$Document_node = $Document_array[$s];
				$Document_id_attr = $Document_node->get_attribute('id');
				if($ExtrinsicObject_id_attr[$index]==$Document_id_attr[$s]){
					$trovato_id=true;
				}
				
			}



			// Non c'è corrispondenza tra ExtrinsicObject e Include
			if(!$trovato_id) {
				writeTimeFile($_SESSION['idfile']."--Repository: Non ho trovato corrispondenza tra : ".$ExtrinsicObject_id_attr[$index]."  e ".$Document_id_attr[$s]);
				$valid=false;
				$conta_Document_id--;
				verificaAllegatiExtrinsicObjectMTOM($conta_EO,$conta_Document_id);
				exit;
				}

		}
	}
	

$ret=array($valid,$Document_array,$mimeType_arr);
return $ret;
}








function makeErrorFromRegistry($registry_response_log){

         	//RESTITUISCE IL MESSAGGIO DI ERRORE
		$errorcode[] = "XDSMissingDocumentMetadata";
		$error_message[] = "There are more attached file than ExtrinsicObject";
		$failure_response = makeSoapedFailureResponse($error_message,$errorcode);

		$file_input=$_SESSION['tmp_path'].$_SESSION['idfile']."-Client_CONNECTION_ERROR-".$_SESSION['idfile'];
	 	$fp = fopen($file_input,"w+");
           	   fwrite($fp,$failure_response);
        	fclose($fp);

		SendError($file_input);
		exit;
		//PULISCO IL BUFFER DI USCITA
		ob_get_clean();//OKKIO FONDAMENTALE!!!!!
}



function verificaExtrinsicObject($dom_ebXML){

	$ExtrinsicObject_array = $dom_ebXML->get_elements_by_tagname("ExtrinsicObject");
	$conta_EO = count($ExtrinsicObject_array);
	if ($conta_EO>0){
		         	//RESTITUISCE IL MESSAGGIO DI ERRORE
		$errorcode[] = "XDSMissingDocument";
		$error_message[] = "XDSDocumentEntry exists in metadata with no corresponding attached document";
		$failure_response = makeSoapedFailureResponse($error_message,$errorcode);

		$file_input=$_SESSION['tmp_path'].$_SESSION['idfile']."-Document_missing-".$_SESSION['idfile'];
	 	$fp = fopen($file_input,"w+");
           	   fwrite($fp,$failure_response);
        	fclose($fp);

		SendError($file_input);
		exit;
		//PULISCO IL BUFFER DI USCITA
		ob_get_clean();//OKKIO FONDAMENTALE!!!!!
	}
	else {
		return true;
	}

}




function getSubmissionUniqueID($dom)
{
    	//$ebxml_value = searchForIds($dom,'RegistryPackage','uniqueId');
    
	$ebxml_value = '';

##### RADICE DEL DOCUMENTO ebXML
	$root_ebXML = $dom->document_element();
	
	##### ARRAY DEI NODI REGISTRYPACKAGE
	$dom_ebXML_RegistryPackage_node_array=$root_ebXML->get_elements_by_tagname("RegistryPackage");

	#### CICLO SU OGNI RegistryPackage ####
	for($index=0;$index<(count($dom_ebXML_RegistryPackage_node_array));$index++)
	{
	##### SINGOLO NODO REGISTRYPACKAGE
	$RegistryPackage_node = $dom_ebXML_RegistryPackage_node_array[$index];
	
	#### ARRAY DEI FIGLI DEL NODO REGISTRYPACKAGE ##############	
	$RegistryPackage_child_nodes = $RegistryPackage_node->child_nodes();
	#################################################################

################# PROCESSO TUTTI I NODI FIGLI DI REGISTRYPACKAGE
	for($k=0;$k<count($RegistryPackage_child_nodes);$k++)
	{
		#### SINGOLO NODO FIGLIO DI REGISTRYPACKAGE
		$RegistryPackage_child_node=$RegistryPackage_child_nodes[$k];
		#### NOME DEL NODO
		$RegistryPackage_child_node_tagname = $RegistryPackage_child_node->node_name();

		if($RegistryPackage_child_node_tagname=='ExternalIdentifier')
		{
			$externalidentifier_node = $RegistryPackage_child_node;
			$value_value= $externalidentifier_node->get_attribute('value');
			
			#### NODI FIGLI DI EXTERNALIDENTIFIER
			$externalidentifier_child_nodes = $externalidentifier_node->child_nodes();
		//print_r($name_node);
			for($q = 0;$q < count($externalidentifier_child_nodes);$q++)
			{
				$externalidentifier_child_node = $externalidentifier_child_nodes[$q];
				$externalidentifier_child_node_tagname = $externalidentifier_child_node->node_name();
				if($externalidentifier_child_node_tagname=='Name')
				{
					$name_node=$externalidentifier_child_node;

					$LocalizedString_nodes = $name_node->child_nodes();
		//print_r($LocalizedString_nodes);
			for($p = 0;$p < count($LocalizedString_nodes);$p++)
			{
				$LocalizedString_node = $LocalizedString_nodes[$p];//->node_name();
				$LocalizedString_node_tagname = $LocalizedString_node->node_name();

				if($LocalizedString_node_tagname == 'LocalizedString')
				{

					$LocalizedString_value =$LocalizedString_node->get_attribute('value');
					if(strpos(strtolower(trim($LocalizedString_value)),strtolower('SubmissionSet.uniqueId')))
					{
						$ebxml_value = $value_value;
					}


				}

			}

			}
			}
		}

	}

	}//END OF for($index=0;$index<(count($dom_ebXML_RegistryPackage_node_array));$index++)

	return $ebxml_value;
  }//end of getSubmissionUniqueID($dom_ebXML)


?>