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
//----------------------------------------------------//

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


include_once('reg_validation.php');
require_once('./lib/query_utilities.php');
ob_clean();
$idfile = idrandom_file();

$_SESSION['tmp_path']=$tmp_path;
$_SESSION['idfile']=$idfile;
$_SESSION['logActive']=$logActive;
$_SESSION['log_path']=$log_path;
$_SESSION['tmpQueryService_path']=$tmpQueryService_path;
$_SESSION['www_REG_path']=$_SERVER['PHP_SELF'];
$www_REG_path = $_SERVER['PHP_SELF'];

// Creo la cartella per i file temporanei
if(!is_dir($tmpQueryService_path)){
	$createtmpdir=false;
	$ntmpdir=0;
	while(!$createtmpdir && $ntmpdir<10){
		$cmdtmpdir=mkdir($tmpQueryService_path, 0777,true);
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
		
		$file_input=$idfile."-folder_failure_response.xml";
		writeTmpQueryFiles($folder_response,$file_input,true);
		SendResponseFile($tmpQueryService_path.$file_input);
		//SendResponse($folder_response,"application/soap+xml",(string)filesize($tmpQueryService_path.$idfile."-folder_failure_response.xml"));
		exit;
	
	}
	
}

//PULISCO LA CACHE TEMPORANEA
//exec('rm -f '.$tmpQueryService_path."*");

//RECUPERO GLI HEADERS RICEVUTI DA APACHE
$headers = apache_request_headers();

//COPIO IN LOCALE TUTTI GLI HEADERS RICEVUTI
if($clean_cache!="O"){
writeTmpQueryFiles($headers,$idfile."-headers_received-".$idfile);
}


//AdhocQueryRequest IMBUSTATO
$ebxml_imbustato_soap_STRING=$HTTP_RAW_POST_DATA;
if($clean_cache!="O"){
writeTmpQueryFiles($ebxml_imbustato_soap_STRING,$idfile."-AdhocQueryRequest.xml");
}

//SBUSTO	
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


if($registry_status=="O") {
	$errorcode[]="XDSRegistryNotAvailable";
	$error_message[] = "Registry is down for maintenance";
	$status_response = makeSoapedFailureResponse($error_message,$errorcode,$Action,$MessageID);
	writeTimeFile($_SESSION['idfile']."--Registry: Registry is down");
	
	$file_input=$idfile."-down_failure_response.xml";
	writeTmpQueryFiles($status_response,$file_input,true);
	SendResponseFile($tmpQueryService_path.$file_input);
	//SendResponse($status_response);
	exit;
}

if($Action==""){
	$failure_response=array("You must set the Action of the Request");
	$error_code=array("XDSRegistryActionError");
	$SOAPED_failure_response = makeSoapedFailureResponse($failure_response,$error_code,$Action,$MessageID);
	$file_input=$idfile."-SOAPED_Action_failure.xml";
	writeTmpQueryFiles($SOAPED_failure_response,$file_input,true);
	SendResponseFile($_SESSION['tmpQueryService_path'].$file_input);
	exit;
}
elseif($Action!="urn:ihe:iti:2007:RegistryStoredQuery"){
	$failure_response=array("This is a Registry Stored Query transaction and you don't use the Action urn:ihe:iti:2007:RegistryStoredQuery");
	$error_code=array("XDSRegistryActionError");
	$SOAPED_failure_response = makeSoapedFailureResponse($failure_response,$error_code,$Action,$MessageID);
	$file_input=$idfile."-SOAPED_Action_failure.xml";
	writeTmpQueryFiles($SOAPED_failure_response,$file_input,true);
	SendResponseFile($_SESSION['tmpQueryService_path'].$file_input);
	exit;
}

//Ottengo Reply Address

$namespacequery=givenamescape('urn:oasis:names:tc:ebxml-regrep:xsd:query:3.0',$ebxml_imbustato_soap_STRING);


if($namespacequery==''){
$inizioAdhocQueryRequest="<AdhocQueryRequest";
$fineAdhocQueryRequest="</AdhocQueryRequest>";
$ebxml_STRING
=substr($ebxml_imbustato_soap_STRING,
                strpos($ebxml_imbustato_soap_STRING,$inizioAdhocQueryRequest),
(strlen($ebxml_imbustato_soap_STRING)-strlen(substr($ebxml_imbustato_soap_STRING,strpos($ebxml_imbustato_soap_STRING,$fineAdhocQueryRequest)+strlen($fineAdhocQueryRequest)))));

$ebxml_STRING=str_replace((substr($ebxml_imbustato_soap_STRING,strpos($ebxml_imbustato_soap_STRING,$fineAdhocQueryRequest)+strlen($fineAdhocQueryRequest))),"",$ebxml_STRING);

}

else {
$inizioAdhocQueryRequest="<$namespacequery:AdhocQueryRequest";
$fineAdhocQueryRequest="</$namespacequery:AdhocQueryRequest>";
$ebxml_STRING
=substr($ebxml_imbustato_soap_STRING,
                strpos($ebxml_imbustato_soap_STRING,$inizioAdhocQueryRequest),
(strlen($ebxml_imbustato_soap_STRING)-strlen(substr($ebxml_imbustato_soap_STRING,strpos($ebxml_imbustato_soap_STRING,$fineAdhocQueryRequest)+strlen($fineAdhocQueryRequest)))));

$ebxml_STRING=str_replace((substr($ebxml_imbustato_soap_STRING,strpos($ebxml_imbustato_soap_STRING,$fineAdhocQueryRequest)+strlen($fineAdhocQueryRequest))),"",$ebxml_STRING);
}

###################################################################################



$error_code=array();
$failure_response=array();

####### VALIDAZIONE DELL'ebXML SECONDO LO SCHEMA
$schema='schemas3/query.xsd';
$isValid = isValid($ebxml_STRING,$schema);

if ($isValid){
writeTimeFile($idfile."--StoredQuery: Il metadata e' valido");}

//Creo la query dalle StoredQuery
require_once('lib/createQueryfromStoredQuery.php');

//Se trovo almeno un errore
if(count($error_code)>0){

$SOAPED_failure_response = makeSoapedFailureStoredQueryResponse($failure_response,$error_code,$Action,$MessageID);

	### SCRIVO LA RISPOSTA IN UN FILE
	$file_input=$idfile."-SOAPED_failure_response.xml";
	writeTmpQueryFiles($SOAPED_failure_response,$file_input,true);
	SendResponseFile($tmpQueryService_path.$file_input);
	//SendResponse($SOAPED_failure_response,"application/soap+xml",(string)filesize($tmpQueryService_path.$idfile."-SOAPED_failure_response.xml"));
	exit;


}


###### CONTROLLO SQL RICEVUTA


$SQLResponse=array();
$SQLResponse_array=array();

$fp_SQLResponse = fopen($tmpQueryService_path.$idfile."-SQLResponse-".$idfile,"a+");
fwrite($fp_SQLResponse,"RISPOSTA DAL DB:\n");
$trovato=true;
for($SQcount=0;$SQcount<$contaQuery && $trovato;$SQcount++){
$SQLQuery = $SQLStoredQuery[$SQcount];
$controllo_query_array = controllaQuery($SQLQuery,$Action,$MessageID);
########################################################################
### ORA DEVO ESEGUIRE LA QUERY SUL DB DEL XDS_REGISTRY_QUERY REGISTRY 

################ RISPOSTA ALLA QUERY (ARRAY)
###METTO A POSTO EVENTUALI STRINGHE DI COMANDO
$SQLQuery_ESEGUITA=adjustQuery($SQLQuery);#### IMPORTANTE!!!
###SCRIVO LA QUERY CHE EFFETTIVAMENTE LANCIO A DB
###### ESEGUO LA QUERY
$SQLResponse_array = query_select($SQLQuery_ESEGUITA);
//writeTmpQueryFiles($SQLQuery_ESEGUITA,$idfile."-Query_eseguita-".$idfile);
if($SQLResponse_array[0]!=""){
	$SQLResponse=array_merge($SQLResponse,$SQLResponse_array);
}
else {
	$trovato=true;
}

}

for($uu=0;$uu<count($SQLResponse);$uu++)
{
	$value = $SQLResponse[$uu][0];
	fwrite($fp_SQLResponse,"\n$value\n");

}
fclose($fp_SQLResponse);


####################################################

#### CONTROLLO COME PRIMA COSA CHE LA SQL ABBIA RISULTATO
if(empty($SQLResponse))
{
	#### STRINGA DI ERRORE
	$failure_response = "[EMPTY RESULT] - STORED QUERY[  ".avoidHtmlEntitiesInterpretation($SQLQuery)." ] DID NOT GIVE ANY RESULTS IN THIS REGISTRY";
	
	### RESTITUISCE IL MESSAGGIO DI SUCCESS IN SOAP
	### ANCHE SE IL RISULTATO DELLA QUERY DA DB È VUOTO
    	$SOAPED_failure_response = makeSoapedSuccessStoredQueryResponse($Action,$MessageID,"");
	$file_input=$idfile."-SOAPED_NORESULTS_response.xml";
	writeTmpQueryFiles($SOAPED_failure_response,$file_input,true);

	SendResponseFile($tmpQueryService_path.$file_input);
	//SendResponse($SOAPED_failure_response,"application/soap+xml",(string)filesize($tmpQueryService_path.$idfile."-SOAPED_NORESULTS_response.xml"));
	exit;

}//END OF if(empty($SQLResponse))



####### QUI SONO SICURO CHE LE QUERY DA ALMENO UN RISULTATO
$ebXML_Response_string = "";
$ebXML_Response_SOAPED_string = "";

##### COMINCIO A COSTRUIRE L'ebXML DI RISPOSTA
############### DISTINGUO I CASI A SECONDA DEGLI ATTRIBUTI
############### returnType  E  returnComposedObjects
if($returnType_a=="ObjectRef" && $returnComposedObjects_a=="false")
{
	#### SOLO OBJECTREF ID = EXTRINSICOBJECT ID (NON SIMBOLICO!)	

	for($rr=0;$rr<count($SQLResponse);$rr++)
	{
		$ObjectRef_id = $SQLResponse[$rr][0];

		$dom_ebXML_ObjectRef = domxml_new_doc("1.0");
		$dom_ebXML_ObjectRef_root=$dom_ebXML_ObjectRef->create_element("ObjectRef");
		$dom_ebXML_ObjectRef_root=$dom_ebXML_ObjectRef->append_child($dom_ebXML_ObjectRef_root);

		#### SETTO I NAMESPACES
		$dom_ebXML_ObjectRef_root->set_namespace($ns_rim3_path,$ns_rim3);
		$dom_ebXML_ObjectRef_root->add_namespace($ns_q3_path,$ns_q3);

		$dom_ebXML_ObjectRef_root->set_attribute("id",$ObjectRef_id);

		#### METTO SU STRINGA
		$ebXML_Response_string = $ebXML_Response_string.substr($dom_ebXML_ObjectRef->dump_mem(),21);
		
	}//END OF for($t=0;$t<count($SQLResponse);$t++)

}//END OF if($returnType_a=="ObjectRef" && $returnComposedObjects_a=="false")

else if($returnType_a=="ObjectRef" && $returnComposedObjects_a=="true")
{
	##### ??????????? Da verificare cosa bisogna dare indietro
	for($rr=0;$rr<count($SQLResponse);$rr++)
	{
		$ObjectRef_id = $SQLResponse[$rr][0];

		$dom_ebXML_ObjectRef = domxml_new_doc("1.0");
		$dom_ebXML_ObjectRef_root=$dom_ebXML_ObjectRef->create_element("ObjectRef");
		$dom_ebXML_ObjectRef_root=$dom_ebXML_ObjectRef->append_child($dom_ebXML_ObjectRef_root);

		#### SETTO I NAMESPACES
		$dom_ebXML_ObjectRef_root->set_namespace($ns_rim3_path,$ns_rim3);
		$dom_ebXML_ObjectRef_root->add_namespace($ns_q3_path,$ns_q3);

		$dom_ebXML_ObjectRef_root->set_attribute("id",$ObjectRef_id);

		#### METTO SU STRINGA
		$ebXML_Response_string = $ebXML_Response_string.substr($dom_ebXML_ObjectRef->dump_mem(),21);
		
	}//END OF for($t=0;$t<count($SQLResponse);$t++)


}//END OF if($returnType_a=="ObjectRef" && $returnComposedObjects_a=="true")

else if($returnType_a=="LeafClass")
{

	$namespace_objectType="urn:oasis:names:tc:ebxml-regrep:ObjectType:RegistryObject:";
	$namespace_status="urn:oasis:names:tc:ebxml-regrep:StatusType:";
	$namespace_associationType="urn:oasis:names:tc:ebxml-regrep:AssociationType:";
	$objectType_code_from_ExtrinsicObject="";
	$objectType_code_from_RegistryPackage="";
	$objectType_from_Association="";

	$ExtrinsicObject_Classification_classificationScheme_ARR_1=array();
	$ExtrinsicObject_Classification_classificationScheme_ARR_2=array();
	$ExtrinsicObject_Classification_classificationNode_ARR_1=array();
	$ExtrinsicObject_Classification_classificationNode_ARR_2=array();
	$ExtrinsicObject_ExternalIdentifier_identificationScheme_ARR_1=array();
	$ExtrinsicObject_ExternalIdentifier_identificationScheme_ARR_2=array();

	$RegistryPackage_Classification_classificationScheme_ARR_1=array();
	$RegistryPackage_Classification_classificationScheme_ARR_2=array();
	$RegistryPackage_Classification_classificationNode_ARR_1=array();
	$RegistryPackage_Classification_classificationNode_ARR_2=array();
	$RegistryPackage_ExternalIdentifier_identificationScheme_ARR_1=array();
	$RegistryPackage_ExternalIdentifier_identificationScheme_ARR_2=array();

	$Association_sourceObject_ARR_1=array();
	$Association_sourceObject_ARR_2=array();
	$Association_targetObject_ARR_1=array();
	$Association_targetObject_ARR_2=array();

	#### NO NODI CLASSIFICATION

	for($rr=0;$rr<count($SQLResponse);$rr++)
	{
		##### QUI HO L'ID DALLA SELECT RICEVUTA
		$id = $SQLResponse[$rr][0];
                
		############### DISCRIMINO IL TIPO DI RISULTATO ##############
		writeSQLQueryService("FOR NUMERO: ".$rr);
		#### DOCUMENTENTRY
		$get_objectType_from_ExtrinsicObject="SELECT objectType FROM ExtrinsicObject WHERE ExtrinsicObject.id = '$id'";
		$objectType_from_ExtrinsicObject_arr=query_select($get_objectType_from_ExtrinsicObject);
		writeSQLQueryService($get_objectType_from_ExtrinsicObject);

		//$objectType_from_ExtrinsicObject=$objectType_from_ExtrinsicObject_arr[0]['objectType'];
		$objectType_from_ExtrinsicObject=$objectType_from_ExtrinsicObject_arr[0][0];
		if($objectType_from_ExtrinsicObject!=''){
			$mappa_type="SELECT code FROM ClassificationNode WHERE ClassificationNode.id = '$objectType_from_ExtrinsicObject'";
			$objectType_code_from_ExtrinsicObject_arr=query_select($mappa_type);
			writeSQLQueryService($mappa_type);

			//$objectType_code_from_ExtrinsicObject=$objectType_code_from_ExtrinsicObject_arr[0]['code'];
			$objectType_code_from_ExtrinsicObject=$objectType_code_from_ExtrinsicObject_arr[0][0];
		}
		else {
		##### SUBMISSIONSET
		$get_objectType_from_RegistryPackage="SELECT objectType FROM RegistryPackage WHERE RegistryPackage.id = '$id'";
		$objectType_from_RegistryPackage_arr=query_select($get_objectType_from_RegistryPackage);
		writeSQLQueryService($get_objectType_from_RegistryPackage);

		//$objectType_from_RegistryPackage=$objectType_from_RegistryPackage_arr[0]['objectType'];
		$objectType_from_RegistryPackage=$objectType_from_RegistryPackage_arr[0][0];
		if($objectType_from_RegistryPackage!=''){
			$mappa_type="SELECT code FROM ClassificationNode WHERE ClassificationNode.id = '$objectType_from_RegistryPackage'";
			$objectType_code_from_RegistryPackage_arr=query_select($mappa_type);
			writeSQLQueryService($mappa_type);

			$objectType_code_from_RegistryPackage=$objectType_code_from_RegistryPackage_arr[0][0];

		}
		##### ASSOCIATION
		$get_objectType_from_Association="SELECT objectType FROM Association WHERE Association.id = '$id'";
		$objectType_from_Association_arr=query_select($get_objectType_from_Association);
		writeSQLQueryService($get_objectType_from_Association);
		$objectType_from_Association=$objectType_from_Association_arr[0][0];
		}


		############### FINE DISCRIMINO IL TIPO DI RISULTATO ##############

	     if($objectType_code_from_ExtrinsicObject=="XDSDocumentEntry")
	     {

		writeSQLQueryService("XDSDocumentEntry");
		//Devo risettare $objectType_code_from_ExtrinsicObject a "" altrimenti rimane associata alla variabile XDSDocumentEntry
		$objectType_code_from_ExtrinsicObject="";
		$ExtrinsicObject_id = $SQLResponse[$rr][0];

		$dom_ebXML_ExtrinsicObject = domxml_new_doc("1.0");
		## ROOT
		$dom_ebXML_ExtrinsicObject_root=$dom_ebXML_ExtrinsicObject->create_element("ExtrinsicObject");
		$dom_ebXML_ExtrinsicObject_root=$dom_ebXML_ExtrinsicObject->append_child($dom_ebXML_ExtrinsicObject_root);

		#### SETTO I NAMESPACES
		$dom_ebXML_ExtrinsicObject_root->set_namespace($ns_rim3_path,$ns_rim3);
		$dom_ebXML_ExtrinsicObject_root->add_namespace($ns_q3_path,$ns_q3);

		####OTTENGO DAL DB GLI ATTRIBUTI DI ExtrinsicObject
		$queryForExtrinsicObjectAttributes = "SELECT isOpaque,mimeType,objectType,status FROM ExtrinsicObject WHERE ExtrinsicObject.id = '$ExtrinsicObject_id'";
		$ExtrinsicObjectAttributes=query_select($queryForExtrinsicObjectAttributes);
		writeSQLQueryService($queryForExtrinsicObjectAttributes);

		$ExtrinsicObject_isOpaque = $ExtrinsicObjectAttributes[0][0];
		//$ExtrinsicObject_majorVersion = $ExtrinsicObjectAttributes[0][1];
		$ExtrinsicObject_mimeType = $ExtrinsicObjectAttributes[0][1];
		//$ExtrinsicObject_minorVersion = $ExtrinsicObjectAttributes[0][3];
		$ExtrinsicObject_objectType = $ExtrinsicObjectAttributes[0][2];
		$ExtrinsicObject_status = $ExtrinsicObjectAttributes[0][3];

		$dom_ebXML_ExtrinsicObject_root->set_attribute("id",$ExtrinsicObject_id);
		$dom_ebXML_ExtrinsicObject_root->set_attribute("isOpaque",$ExtrinsicObject_isOpaque);
		//$dom_ebXML_ExtrinsicObject_root->set_attribute("majorVersion",$ExtrinsicObject_majorVersion);
		$dom_ebXML_ExtrinsicObject_root->set_attribute("mimeType",$ExtrinsicObject_mimeType);
		//$dom_ebXML_ExtrinsicObject_root->set_attribute("minorVersion",$ExtrinsicObject_minorVersion);
		$dom_ebXML_ExtrinsicObject_root->set_attribute("objectType",$ExtrinsicObject_objectType);
		$dom_ebXML_ExtrinsicObject_root->set_attribute("status",$namespace_status.$ExtrinsicObject_status);

		appendSlot($dom_ebXML_ExtrinsicObject,$dom_ebXML_ExtrinsicObject_root,$ns_rim3_path,$ExtrinsicObject_id,"XDSDocumentEntry",$connessione);

		appendName($dom_ebXML_ExtrinsicObject,$dom_ebXML_ExtrinsicObject_root,$ns_rim3_path,$ExtrinsicObject_id,$connessione);
		
		appendDescription($dom_ebXML_ExtrinsicObject,$dom_ebXML_ExtrinsicObject_root,$ns_rim3_path,$ExtrinsicObject_id,$connessione);

		
		
		#### GESTISCO IL CASO IN CUI DEVO RITORNARE OGGETTI COMPOSTI
		if($returnComposedObjects_a=="true")
		{
			#### CLASSIFICATION + EXTERNALIDENTIFIER + OBJECTREF

			###### NODI CLASSIFICATION
			//Query modificata per ebxml v2.1 (AuthorInstitution....)
			/*$get_ExtrinsicObject_Classification="SELECT classificationScheme,classificationNode,classifiedObject,id,nodeRepresentation,objectType FROM Classification WHERE Classification.classifiedObject = '$ExtrinsicObject_id' AND Classification.nodeRepresentation != 'NULL'";*/
			$get_ExtrinsicObject_Classification="SELECT classificationScheme,classificationNode,classifiedObject,id,nodeRepresentation,objectType FROM Classification WHERE Classification.classifiedObject = '$ExtrinsicObject_id'";
			$ExtrinsicObject_Classification_arr=query_select($get_ExtrinsicObject_Classification);
			writeSQLQueryService($get_ExtrinsicObject_Classification);

			#### CICLO SU TUTTI I NODI CLASSIFICATION
			for($t=0;$t<count($ExtrinsicObject_Classification_arr);$t++)
			{
				$ExtrinsicObject_Classification=$ExtrinsicObject_Classification_arr[$t];
				
				$dom_ebXML_ExtrinsicObject_Classification=$dom_ebXML_ExtrinsicObject->create_element_ns($ns_rim3_path,"Classification");
				$dom_ebXML_ExtrinsicObject_Classification=$dom_ebXML_ExtrinsicObject_root->append_child($dom_ebXML_ExtrinsicObject_Classification);

				#### ATTRIBUTI DI CLASSIFICATION
				$ExtrinsicObject_Classification_classificationScheme=$ExtrinsicObject_Classification[0];
				$ExtrinsicObject_Classification_classificationNode=$ExtrinsicObject_Classification[1];

				#### PREPARO PER OBJECTREF
		$ExtrinsicObject_Classification_classificationScheme_ARR_1[$ExtrinsicObject_Classification_classificationScheme]=$ExtrinsicObject_Classification_classificationScheme;
		$ExtrinsicObject_Classification_classificationScheme_ARR_2[]=$ExtrinsicObject_Classification_classificationScheme;
		$ExtrinsicObject_Classification_classificationNode_ARR_1[$ExtrinsicObject_Classification_classificationNode]=$ExtrinsicObject_Classification_classificationNode;
		$ExtrinsicObject_Classification_classificationNode_ARR_2[]=$ExtrinsicObject_Classification_classificationNode;
		########################

// 				#### DEVO DICHIARARE classificationScheme IN OBJECTREF
// 				$dom_ebXML_ObjectRef=$dom_ebXML_ExtrinsicObject->create_element_ns($ns_rim3_path,"ObjectRef");
// 				$dom_ebXML_ObjectRef=$dom_ebXML_ExtrinsicObject_root->append_child($dom_ebXML_ObjectRef);
// 
// 				#### SETTO I NAMESPACES
// 				$dom_ebXML_ObjectRef->add_namespace($ns_rim3_path,$ns_rim3);
// 				$dom_ebXML_ObjectRef->add_namespace($ns_q3_path,$ns_q3);
// 
// 				$dom_ebXML_ObjectRef->set_attribute("id",$ExtrinsicObject_Classification_classificationScheme);
// 				############# OBJECTREF

				$ExtrinsicObject_Classification_classifiedObject=$ExtrinsicObject_Classification[2];
				$ExtrinsicObject_Classification_id=$ExtrinsicObject_Classification[3];
				$ExtrinsicObject_Classification_nodeRepresentation=$ExtrinsicObject_Classification[4];
				$ExtrinsicObject_Classification_objectType=$ExtrinsicObject_Classification[5];

				$dom_ebXML_ExtrinsicObject_Classification->set_attribute("classificationScheme",$ExtrinsicObject_Classification_classificationScheme);
				$dom_ebXML_ExtrinsicObject_Classification->set_attribute("classificationNode",$ExtrinsicObject_Classification_classificationNode);
				$dom_ebXML_ExtrinsicObject_Classification->set_attribute("classifiedObject",$ExtrinsicObject_Classification_classifiedObject);
				$dom_ebXML_ExtrinsicObject_Classification->set_attribute("id",$ExtrinsicObject_Classification_id);
				$dom_ebXML_ExtrinsicObject_Classification->set_attribute("nodeRepresentation",$ExtrinsicObject_Classification_nodeRepresentation);
				$dom_ebXML_ExtrinsicObject_Classification->set_attribute("objectType",$namespace_objectType.$ExtrinsicObject_Classification_objectType);

				appendSlot_Classification($dom_ebXML_ExtrinsicObject,$dom_ebXML_ExtrinsicObject_Classification,$ns_rim3_path,$ExtrinsicObject_Classification_id,$connessione);
				
				appendName_Classification($dom_ebXML_ExtrinsicObject,$dom_ebXML_ExtrinsicObject_Classification,$ns_rim3_path,$ExtrinsicObject_Classification_id,$connessione);

				appendDescription_Classification($dom_ebXML_ExtrinsicObject,$dom_ebXML_ExtrinsicObject_Classification,$ns_rim3_path,$ExtrinsicObject_Classification_id,$connessione);
				

			}//END OF for($t=0;$t<count($ExtrinsicObject_Classification_arr);$t++)

			#### NODI EXTERNALIDENTIFIER
			$get_ExtrinsicObject_ExternalIdentifier="SELECT identificationScheme,objectType,id,value,registryObject FROM ExternalIdentifier WHERE ExternalIdentifier.registryObject = '$ExtrinsicObject_id'";
			$ExtrinsicObject_ExternalIdentifier_arr=query_select($get_ExtrinsicObject_ExternalIdentifier);
			writeSQLQueryService($get_ExtrinsicObject_ExternalIdentifier);

			#### CICLO SU TUTTI I NODI EXTERNALIDENTIFIER
			for($e=0;$e<count($ExtrinsicObject_ExternalIdentifier_arr);$e++)
			{
				$ExtrinsicObject_ExternalIdentifier=$ExtrinsicObject_ExternalIdentifier_arr[$e];
				
				$dom_ebXML_ExtrinsicObject_ExternalIdentifier=$dom_ebXML_ExtrinsicObject->create_element_ns($ns_rim3_path,"ExternalIdentifier");
				$dom_ebXML_ExtrinsicObject_ExternalIdentifier=$dom_ebXML_ExtrinsicObject_root->append_child($dom_ebXML_ExtrinsicObject_ExternalIdentifier);

				#### ATTRIBUTI DI EXTERNALIDENTIFIER
				$ExtrinsicObject_ExternalIdentifier_identificationScheme=$ExtrinsicObject_ExternalIdentifier[0];
				#### PREPARO PER OBJECTREF
		$ExtrinsicObject_ExternalIdentifier_identificationScheme_ARR_1[$ExtrinsicObject_ExternalIdentifier_identificationScheme]=$ExtrinsicObject_ExternalIdentifier_identificationScheme;
		$ExtrinsicObject_ExternalIdentifier_identificationScheme_ARR_2[]=$ExtrinsicObject_ExternalIdentifier_identificationScheme;
		########################

// 				#### DEVO DICHIARARE identificationScheme IN OBJECTREF
// 				$dom_ebXML_ObjectRef=$dom_ebXML_ExtrinsicObject->create_element_ns($ns_rim3_path,"ObjectRef");
// 				$dom_ebXML_ObjectRef=$dom_ebXML_ExtrinsicObject_root->append_child($dom_ebXML_ObjectRef);
// 
// 				#### SETTO I NAMESPACES
// 				$dom_ebXML_ObjectRef->add_namespace($ns_rim3_path,$ns_rim3);
// 				$dom_ebXML_ObjectRef->add_namespace($ns_q3_path,$ns_q3);
// 
// 				$dom_ebXML_ObjectRef->set_attribute("id",$ExtrinsicObject_ExternalIdentifier_identificationScheme);
// 				############# OBJECTREF

				$ExtrinsicObject_ExternalIdentifier_objectType=$ExtrinsicObject_ExternalIdentifier[1];
				$ExtrinsicObject_ExternalIdentifier_id=$ExtrinsicObject_ExternalIdentifier[2];
				$ExtrinsicObject_ExternalIdentifier_value=$ExtrinsicObject_ExternalIdentifier[3];
				$ExtrinsicObject_ExternalIdentifier_registryObject=$ExtrinsicObject_ExternalIdentifier[4];

				$dom_ebXML_ExtrinsicObject_ExternalIdentifier->set_attribute("identificationScheme",$ExtrinsicObject_ExternalIdentifier_identificationScheme);
				$dom_ebXML_ExtrinsicObject_ExternalIdentifier->set_attribute("objectType",$namespace_objectType.$ExtrinsicObject_ExternalIdentifier_objectType);
				$dom_ebXML_ExtrinsicObject_ExternalIdentifier->set_attribute("registryObject",$ExtrinsicObject_ExternalIdentifier_registryObject);
				$dom_ebXML_ExtrinsicObject_ExternalIdentifier->set_attribute("id",$ExtrinsicObject_ExternalIdentifier_id);
				$dom_ebXML_ExtrinsicObject_ExternalIdentifier->set_attribute("value",$ExtrinsicObject_ExternalIdentifier_value);

				appendName_ExternalIdentifier($dom_ebXML_ExtrinsicObject,$dom_ebXML_ExtrinsicObject_ExternalIdentifier,$ns_rim3_path,$ExtrinsicObject_ExternalIdentifier_id,$connessione);

				appendDescription_ExternalIdentifier($dom_ebXML_ExtrinsicObject,$dom_ebXML_ExtrinsicObject_ExternalIdentifier,$ns_rim3_path,$ExtrinsicObject_ExternalIdentifier_id,$connessione);

			}//END OF for($e=0;$e<count($ExtrinsicObject_ExternalIdentifier_arr);$e++)

		}//END OF if($returnComposedObjects_a=="true")

		#### CONCATENO LE STINGHE RISULTANTI
		$ebXML_Response_string = $ebXML_Response_string.substr($dom_ebXML_ExtrinsicObject->dump_mem(),21);
	     }//END OF if($objectType_code_from_ExtrinsicObject=="XDSDocumentEntry")

	     if($objectType_code_from_RegistryPackage=="XDSSubmissionSet")
	     {
		writeSQLQueryService("XDSSubmissionSet");
		//Devo risettare $objectType_code_from_RegistryPackage a "" altrimenti rimane associata alla variabile XDSSubmissionSet
		$objectType_code_from_RegistryPackage="";
		$RegistryPackage_id = $SQLResponse[$rr][0];

		$dom_ebXML_RegistryPackage = domxml_new_doc("1.0");
		## ROOT
		$dom_ebXML_RegistryPackage_root=$dom_ebXML_RegistryPackage->create_element("RegistryPackage");
		$dom_ebXML_RegistryPackage_root=$dom_ebXML_RegistryPackage->append_child($dom_ebXML_RegistryPackage_root);

		#### SETTO I NAMESPACES
		$dom_ebXML_RegistryPackage_root->set_namespace($ns_rim3_path,$ns_rim3);
		$dom_ebXML_RegistryPackage_root->add_namespace($ns_q3_path,$ns_q3);

		####OTTENGO DAL DB GLI ATTRIBUTI DI RegistryPackage
		$queryForRegistryPackageAttributes = "SELECT objectType,status FROM RegistryPackage WHERE RegistryPackage.id = '$RegistryPackage_id'";
		$RegistryPackageAttributes=query_select($queryForRegistryPackageAttributes);
		writeSQLQueryService($queryForRegistryPackageAttributes);

		//$RegistryPackage_isOpaque = $RegistryPackageAttributes[0]['isOpaque'];
		//$RegistryPackage_majorVersion = $RegistryPackageAttributes[0][0];
		//$RegistryPackage_mimeType = $RegistryPackageAttributes[0]['mimeType'];
		//$RegistryPackage_minorVersion = $RegistryPackageAttributes[0][1];
		$RegistryPackage_objectType = $RegistryPackageAttributes[0][0];
		$RegistryPackage_status = $RegistryPackageAttributes[0][1];

		$dom_ebXML_RegistryPackage_root->set_attribute("id",$RegistryPackage_id);
		//$dom_ebXML_RegistryPackage_root->set_attribute("isOpaque",$RegistryPackage_isOpaque);
		//$dom_ebXML_RegistryPackage_root->set_attribute("majorVersion",$RegistryPackage_majorVersion);
		//$dom_ebXML_RegistryPackage_root->set_attribute("mimeType",$RegistryPackage_mimeType);
		//$dom_ebXML_RegistryPackage_root->set_attribute("minorVersion",$RegistryPackage_minorVersion);
		//$dom_ebXML_RegistryPackage_root->set_attribute("objectType",$RegistryPackage_objectType);
		$dom_ebXML_RegistryPackage_root->set_attribute("status",$namespace_status.$RegistryPackage_status);

		appendSlot($dom_ebXML_RegistryPackage,$dom_ebXML_RegistryPackage_root,$ns_rim3_path,$RegistryPackage_id,"XDSSubmissionSet",$connessione);

		appendName($dom_ebXML_RegistryPackage,$dom_ebXML_RegistryPackage_root,$ns_rim3_path,$RegistryPackage_id,$connessione);

		appendDescription($dom_ebXML_RegistryPackage,$dom_ebXML_RegistryPackage_root,$ns_rim3_path,$RegistryPackage_id,$connessione);

		#### GESTISCO IL CASO IN CUI DEVO RITORNARE OGGETTI COMPOSTI
		if($returnComposedObjects_a=="true")
		{
			#### CLASSIFICATION + EXTERNALIDENTIFIER + OBJECTREF

			###### NODI CLASSIFICATION
			$get_RegistryPackage_Classification="SELECT classificationScheme,classificationNode,classifiedObject,id,nodeRepresentation,objectType FROM Classification WHERE Classification.classifiedObject = '$RegistryPackage_id'";
			$RegistryPackage_Classification_arr=query_select($get_RegistryPackage_Classification);
			writeSQLQueryService($get_RegistryPackage_Classification);

			#### CICLO SU TUTTI I NODI CLASSIFICATION
			for($t=0;$t<count($RegistryPackage_Classification_arr);$t++)
			{
				$RegistryPackage_Classification=$RegistryPackage_Classification_arr[$t];
				
				$dom_ebXML_RegistryPackage_Classification=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim3_path,"Classification");
				$dom_ebXML_RegistryPackage_Classification=$dom_ebXML_RegistryPackage_root->append_child($dom_ebXML_RegistryPackage_Classification);

				#### ATTRIBUTI DI CLASSIFICATION
				$RegistryPackage_Classification_classificationScheme=$RegistryPackage_Classification[0];
				$RegistryPackage_Classification_classificationNode=$RegistryPackage_Classification[1];
				#### PREPARO PER OBJECTREF
		$RegistryPackage_Classification_classificationScheme_ARR_1[$RegistryPackage_Classification_classificationScheme]=$RegistryPackage_Classification_classificationScheme;
		$RegistryPackage_Classification_classificationScheme_ARR_2[]=$RegistryPackage_Classification_classificationScheme;
		$RegistryPackage_Classification_classificationNode_ARR_1[$RegistryPackage_Classification_classificationNode]=$RegistryPackage_Classification_classificationNode;
		$RegistryPackage_Classification_classificationNode_ARR_2[]=$RegistryPackage_Classification_classificationNode;
		########################

// 				#### DEVO DICHIARARE classificationScheme IN OBJECTREF
// 				$dom_ebXML_ObjectRef=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim3_path,"ObjectRef");
// 				$dom_ebXML_ObjectRef=$dom_ebXML_RegistryPackage_root->append_child($dom_ebXML_ObjectRef);
// 
// 				#### SETTO I NAMESPACES
// 				$dom_ebXML_ObjectRef->add_namespace($ns_rim3_path,$ns_rim3);
// 				$dom_ebXML_ObjectRef->add_namespace($ns_q3_path,$ns_q3);
// 
// 				$dom_ebXML_ObjectRef->set_attribute("id",$RegistryPackage_Classification_classificationScheme);
// 				############# OBJECTREF

				$RegistryPackage_Classification_classifiedObject=$RegistryPackage_Classification[2];
				$RegistryPackage_Classification_id=$RegistryPackage_Classification[3];
				$RegistryPackage_Classification_nodeRepresentation=$RegistryPackage_Classification[4];
				$RegistryPackage_Classification_objectType=$RegistryPackage_Classification[5];

				$dom_ebXML_RegistryPackage_Classification->set_attribute("classificationScheme",$RegistryPackage_Classification_classificationScheme);
				//Non so se ci va o no
				//$dom_ebXML_RegistryPackage_Classification->set_attribute("classificationNode",$RegistryPackage_Classification_classificationNode);
				$dom_ebXML_RegistryPackage_Classification->set_attribute("classifiedObject",$RegistryPackage_Classification_classifiedObject);
				$dom_ebXML_RegistryPackage_Classification->set_attribute("id",$RegistryPackage_Classification_id);
				$dom_ebXML_RegistryPackage_Classification->set_attribute("nodeRepresentation",$RegistryPackage_Classification_nodeRepresentation);
				$dom_ebXML_RegistryPackage_Classification->set_attribute("objectType",$namespace_objectType.$RegistryPackage_Classification_objectType);

				appendSlot_Classification($dom_ebXML_RegistryPackage,$dom_ebXML_RegistryPackage_Classification,$ns_rim3_path,$RegistryPackage_Classification_id,$connessione);

				appendName_Classification($dom_ebXML_RegistryPackage,$dom_ebXML_RegistryPackage_Classification,$ns_rim3_path,$RegistryPackage_Classification_id,$connessione);

				appendDescription_Classification($dom_ebXML_RegistryPackage,$dom_ebXML_RegistryPackage_Classification,$ns_rim3_path,$RegistryPackage_Classification_id,$connessione);


			}//END OF for($t=0;$t<count($RegistryPackage_Classification_arr);$t++)


            //Ho bisogno di una classification di con classificationNode="urn:uuid:a54d6aa5-d40d-43f9-88c5-b4633d873bdd"
	
		$dom_ebXML_RegistryPackage_Classification=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim3_path,"Classification");
		$dom_ebXML_RegistryPackage_Classification=$dom_ebXML_RegistryPackage_root->append_child($dom_ebXML_RegistryPackage_Classification);

		$dom_ebXML_RegistryPackage_Classification->set_attribute("classificationNode","urn:uuid:a54d6aa5-d40d-43f9-88c5-b4633d873bdd");
		$dom_ebXML_RegistryPackage_Classification->set_attribute("classifiedObject",$RegistryPackage_id);
		$dom_ebXML_RegistryPackage_Classification->set_attribute("id","urn:uuid:18e31fd4-9368-4457-8a69-e7f3a372e9e3");
		$dom_ebXML_RegistryPackage_Classification->set_attribute("objectType",$namespace_objectType.$RegistryPackage_Classification_objectType);






			#### NODI EXTERNALIDENTIFIER
			$get_RegistryPackage_ExternalIdentifier="SELECT identificationScheme,objectType,id,value,registryObject FROM ExternalIdentifier WHERE ExternalIdentifier.registryObject = '$RegistryPackage_id'";
			$RegistryPackage_ExternalIdentifier_arr=query_select($get_RegistryPackage_ExternalIdentifier);
			writeSQLQueryService($get_RegistryPackage_ExternalIdentifier);

			#### CICLO SU TUTTI I NODI EXTERNALIDENTIFIER
			for($e=0;$e<count($RegistryPackage_ExternalIdentifier_arr);$e++)
			{
				$RegistryPackage_ExternalIdentifier=$RegistryPackage_ExternalIdentifier_arr[$e];
				
				$dom_ebXML_RegistryPackage_ExternalIdentifier=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim3_path,"ExternalIdentifier");
				$dom_ebXML_RegistryPackage_ExternalIdentifier=$dom_ebXML_RegistryPackage_root->append_child($dom_ebXML_RegistryPackage_ExternalIdentifier);

				#### ATTRIBUTI DI EXTERNALIDENTIFIER
				$RegistryPackage_ExternalIdentifier_identificationScheme=$RegistryPackage_ExternalIdentifier[0];
				#### PREPARO PER OBJECTREF
		$RegistryPackage_ExternalIdentifier_identificationScheme_ARR_1[$RegistryPackage_ExternalIdentifier_identificationScheme]=$RegistryPackage_ExternalIdentifier_identificationScheme;
		$RegistryPackage_ExternalIdentifier_identificationScheme_ARR_2[]=$RegistryPackage_ExternalIdentifier_identificationScheme;
		########################

// 				#### DEVO DICHIARARE identificationScheme IN OBJECTREF
// 				$dom_ebXML_ObjectRef=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim3_path,"ObjectRef");
// 				$dom_ebXML_ObjectRef=$dom_ebXML_RegistryPackage_root->append_child($dom_ebXML_ObjectRef);
// 
// 				#### SETTO I NAMESPACES
// 				$dom_ebXML_ObjectRef->add_namespace($ns_rim3_path,$ns_rim3);
// 				$dom_ebXML_ObjectRef->add_namespace($ns_q3_path,$ns_q3);
// 
// 				$dom_ebXML_ObjectRef->set_attribute("id",$RegistryPackage_ExternalIdentifier_identificationScheme);
// 				############# OBJECTREF

				$RegistryPackage_ExternalIdentifier_objectType=$RegistryPackage_ExternalIdentifier[1];
				$RegistryPackage_ExternalIdentifier_id=$RegistryPackage_ExternalIdentifier[2];
				$RegistryPackage_ExternalIdentifier_value=$RegistryPackage_ExternalIdentifier[3];
				$RegistryPackage_ExternalIdentifier_registryObject=$RegistryPackage_ExternalIdentifier[4];

				$dom_ebXML_RegistryPackage_ExternalIdentifier->set_attribute("identificationScheme",$RegistryPackage_ExternalIdentifier_identificationScheme);
				$dom_ebXML_RegistryPackage_ExternalIdentifier->set_attribute("objectType",$namespace_objectType.$RegistryPackage_ExternalIdentifier_objectType);
				$dom_ebXML_RegistryPackage_ExternalIdentifier->set_attribute("id",$RegistryPackage_ExternalIdentifier_id);
				$dom_ebXML_RegistryPackage_ExternalIdentifier->set_attribute("value",$RegistryPackage_ExternalIdentifier_value);
				$dom_ebXML_RegistryPackage_ExternalIdentifier->set_attribute("registryObject",$RegistryPackage_ExternalIdentifier_registryObject);

				appendName_ExternalIdentifier($dom_ebXML_RegistryPackage,$dom_ebXML_RegistryPackage_ExternalIdentifier,$ns_rim3_path,$RegistryPackage_ExternalIdentifier_id,$connessione);

				appendDescription_ExternalIdentifier($dom_ebXML_RegistryPackage,$dom_ebXML_RegistryPackage_ExternalIdentifier,$ns_rim3_path,$RegistryPackage_ExternalIdentifier_id,$connessione);
			}//END OF for($e=0;$e<count($RegistryPackage_ExternalIdentifier_arr);$e++)

		}//END OF if($returnComposedObjects_a=="true")

		#### CONCATENO LE STINGHE RISULTANTI
		$ebXML_Response_string = $ebXML_Response_string.substr($dom_ebXML_RegistryPackage->dump_mem(),21);

	     }//END OF if($objectType_code_from_RegistryPackage=="XDSSubmissionSet")

	     if($objectType_code_from_RegistryPackage=="XDSFolder")
	     {
		writeSQLQueryService("XDSFolder");
		//Devo risettare $objectType_code_from_RegistryPackage a "" altrimenti rimane associata alla variabile XDSSubmissionSet
		$objectType_code_from_RegistryPackage="";

		$RegistryPackage_id = $SQLResponse[$rr][0];

		$dom_ebXML_RegistryPackage = domxml_new_doc("1.0");
		## ROOT
		$dom_ebXML_RegistryPackage_root=$dom_ebXML_RegistryPackage->create_element("RegistryPackage");
		$dom_ebXML_RegistryPackage_root=$dom_ebXML_RegistryPackage->append_child($dom_ebXML_RegistryPackage_root);

		#### SETTO I NAMESPACES
		$dom_ebXML_RegistryPackage_root->set_namespace($ns_rim3_path,$ns_rim3);
		$dom_ebXML_RegistryPackage_root->add_namespace($ns_q3_path,$ns_q3);

		####OTTENGO DAL DB GLI ATTRIBUTI DI RegistryPackage
		$queryForRegistryPackageAttributes = "SELECT objectType,status FROM RegistryPackage WHERE RegistryPackage.id = '$RegistryPackage_id'";
		$RegistryPackageAttributes=query_select($queryForRegistryPackageAttributes);
		writeSQLQueryService($queryForRegistryPackageAttributes);

		//$RegistryPackage_isOpaque = $RegistryPackageAttributes[0]['isOpaque'];
		//$RegistryPackage_majorVersion = $RegistryPackageAttributes[0][0];
		//$RegistryPackage_mimeType = $RegistryPackageAttributes[0]['mimeType'];
		//$RegistryPackage_minorVersion = $RegistryPackageAttributes[0][1];
		$RegistryPackage_objectType = $RegistryPackageAttributes[0][0];
		$RegistryPackage_status = $RegistryPackageAttributes[0][1];

		$dom_ebXML_RegistryPackage_root->set_attribute("id",$RegistryPackage_id);
		//$dom_ebXML_RegistryPackage_root->set_attribute("isOpaque",$RegistryPackage_isOpaque);
		//$dom_ebXML_RegistryPackage_root->set_attribute("majorVersion",$RegistryPackage_majorVersion);
		//$dom_ebXML_RegistryPackage_root->set_attribute("mimeType",$RegistryPackage_mimeType);
		//$dom_ebXML_RegistryPackage_root->set_attribute("minorVersion",$RegistryPackage_minorVersion);
		//$dom_ebXML_RegistryPackage_root->set_attribute("objectType",$RegistryPackage_objectType);
		$dom_ebXML_RegistryPackage_root->set_attribute("status",$namespace_status.$RegistryPackage_status);

		appendSlot($dom_ebXML_RegistryPackage,$dom_ebXML_RegistryPackage_root,$ns_rim3_path,$RegistryPackage_id,"XDSFolder",$connessione);	

		appendName($dom_ebXML_RegistryPackage,$dom_ebXML_RegistryPackage_root,$ns_rim3_path,$RegistryPackage_id,$connessione);

		appendDescription($dom_ebXML_RegistryPackage,$dom_ebXML_RegistryPackage_root,$ns_rim3_path,$RegistryPackage_id,$connessione);



		#### GESTISCO IL CASO IN CUI DEVO RITORNARE OGGETTI COMPOSTI
		if($returnComposedObjects_a=="true")
		{
			#### CLASSIFICATION + EXTERNALIDENTIFIER + OBJECTREF

			###### NODI CLASSIFICATION
			$get_RegistryPackage_Classification="SELECT classificationScheme,classificationNode,classifiedObject,id,nodeRepresentation,objectType FROM Classification WHERE Classification.classifiedObject = '$RegistryPackage_id'";
			$RegistryPackage_Classification_arr=query_select($get_RegistryPackage_Classification);
			writeSQLQueryService($get_RegistryPackage_Classification);

			if(!empty($RegistryPackage_Classification_arr))
			{
			#### CICLO SU TUTTI I NODI CLASSIFICATION
			for($t=0;$t<count($RegistryPackage_Classification_arr);$t++)
			{
				$RegistryPackage_Classification=$RegistryPackage_Classification_arr[$t];
				
				$dom_ebXML_RegistryPackage_Classification=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim3_path,"Classification");
				$dom_ebXML_RegistryPackage_Classification=$dom_ebXML_RegistryPackage_root->append_child($dom_ebXML_RegistryPackage_Classification);

				#### ATTRIBUTI DI CLASSIFICATION
				$RegistryPackage_Classification_classificationScheme=$RegistryPackage_Classification[0];
				$RegistryPackage_Classification_classificationNode=$RegistryPackage_Classification[1];
				#### PREPARO PER OBJECTREF
		$RegistryPackage_Classification_classificationScheme_ARR_1[$RegistryPackage_Classification_classificationScheme]=$RegistryPackage_Classification_classificationScheme;
		$RegistryPackage_Classification_classificationScheme_ARR_2[]=$RegistryPackage_Classification_classificationScheme;
		$RegistryPackage_Classification_classificationNode_ARR_1[$RegistryPackage_Classification_classificationNode]=$RegistryPackage_Classification_classificationNode;
		$RegistryPackage_Classification_classificationNode_ARR_2[]=$RegistryPackage_Classification_classificationNode;
		########################

// 				#### DEVO DICHIARARE classificationScheme IN OBJECTREF
// 				$dom_ebXML_ObjectRef=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim3_path,"ObjectRef");
// 				$dom_ebXML_ObjectRef=$dom_ebXML_RegistryPackage_root->append_child($dom_ebXML_ObjectRef);
// 
// 				#### SETTO I NAMESPACES
// 				$dom_ebXML_ObjectRef->add_namespace($ns_rim3_path,$ns_rim3);
// 				$dom_ebXML_ObjectRef->add_namespace($ns_q3_path,$ns_q3);
// 
// 				$dom_ebXML_ObjectRef->set_attribute("id",$RegistryPackage_Classification_classificationScheme);
// 				############# OBJECTREF

				$RegistryPackage_Classification_classifiedObject=$RegistryPackage_Classification[2];
				$RegistryPackage_Classification_id=$RegistryPackage_Classification[3];
				$RegistryPackage_Classification_nodeRepresentation=$RegistryPackage_Classification[4];
				$RegistryPackage_Classification_objectType=$RegistryPackage_Classification[5];

				$dom_ebXML_RegistryPackage_Classification->set_attribute("classificationScheme",$RegistryPackage_Classification_classificationScheme);
				$dom_ebXML_RegistryPackage_Classification->set_attribute("classificationNode",$RegistryPackage_Classification_classificationNode);
				$dom_ebXML_RegistryPackage_Classification->set_attribute("classifiedObject",$RegistryPackage_Classification_classifiedObject);
				$dom_ebXML_RegistryPackage_Classification->set_attribute("id",$RegistryPackage_Classification_id);
				$dom_ebXML_RegistryPackage_Classification->set_attribute("nodeRepresentation",$RegistryPackage_Classification_nodeRepresentation);
				$dom_ebXML_RegistryPackage_Classification->set_attribute("objectType",$namespace_objectType.$RegistryPackage_Classification_objectType);
	
				appendSlot_Classification($dom_ebXML_RegistryPackage,$dom_ebXML_RegistryPackage_Classification,$ns_rim3_path,$RegistryPackage_Classification_id,$connessione);	
			
				appendName_Classification($dom_ebXML_RegistryPackage,$dom_ebXML_RegistryPackage_Classification,$ns_rim3_path,$RegistryPackage_Classification_id,$connessione);

				appendDescription_Classification($dom_ebXML_RegistryPackage,$dom_ebXML_RegistryPackage_Classification,$ns_rim3_path,$RegistryPackage_Classification_id,$connessione);




			}//END OF for($t=0;$t<count($RegistryPackage_Classification_arr);$t++)


			}//END OF if(!empty($RegistryPackage_Classification_arr))




            //Ho bisogno di una classification di con classificationNode="urn:uuid:d9d542f3-6cc4-48b6-8870-ea235fbc94c2"

		$dom_ebXML_RegistryPackage_Classification=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim3_path,"Classification");
		$dom_ebXML_RegistryPackage_Classification=$dom_ebXML_RegistryPackage_root->append_child($dom_ebXML_RegistryPackage_Classification);

		$dom_ebXML_RegistryPackage_Classification->set_attribute("classificationNode","urn:uuid:d9d542f3-6cc4-48b6-8870-ea235fbc94c2");
		$dom_ebXML_RegistryPackage_Classification->set_attribute("classifiedObject",$RegistryPackage_id);
		$dom_ebXML_RegistryPackage_Classification->set_attribute("id","urn:uuid:18e31fd4-9368-4457-8a69-e7f3a372e9e4");
		$dom_ebXML_RegistryPackage_Classification->set_attribute("objectType",$namespace_objectType.$RegistryPackage_Classification_objectType);





			#### NODI EXTERNALIDENTIFIER
			$get_RegistryPackage_ExternalIdentifier="SELECT identificationScheme,objectType,id,value,registryObject FROM ExternalIdentifier WHERE ExternalIdentifier.registryObject = '$RegistryPackage_id'";
			$RegistryPackage_ExternalIdentifier_arr=query_select($get_RegistryPackage_ExternalIdentifier);
			writeSQLQueryService($get_RegistryPackage_ExternalIdentifier);

			#### CICLO SU TUTTI I NODI EXTERNALIDENTIFIER
			for($e=0;$e<count($RegistryPackage_ExternalIdentifier_arr);$e++)
			{
				$RegistryPackage_ExternalIdentifier=$RegistryPackage_ExternalIdentifier_arr[$e];
				
				$dom_ebXML_RegistryPackage_ExternalIdentifier=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim3_path,"ExternalIdentifier");
				$dom_ebXML_RegistryPackage_ExternalIdentifier=$dom_ebXML_RegistryPackage_root->append_child($dom_ebXML_RegistryPackage_ExternalIdentifier);

				#### ATTRIBUTI DI EXTERNALIDENTIFIER
				$RegistryPackage_ExternalIdentifier_identificationScheme=$RegistryPackage_ExternalIdentifier[0];
				#### PREPARO PER OBJECTREF
		$RegistryPackage_ExternalIdentifier_identificationScheme_ARR_1[$RegistryPackage_ExternalIdentifier_identificationScheme]=$RegistryPackage_ExternalIdentifier_identificationScheme;
		$RegistryPackage_ExternalIdentifier_identificationScheme_ARR_2[]=$RegistryPackage_ExternalIdentifier_identificationScheme;
		########################

// 				#### DEVO DICHIARARE identificationScheme IN OBJECTREF
// 				$dom_ebXML_ObjectRef=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim3_path,"ObjectRef");
// 				$dom_ebXML_ObjectRef=$dom_ebXML_RegistryPackage_root->append_child($dom_ebXML_ObjectRef);
// 
// 				#### SETTO I NAMESPACES
// 				$dom_ebXML_ObjectRef->add_namespace($ns_rim3_path,$ns_rim3);
// 				$dom_ebXML_ObjectRef->add_namespace($ns_q3_path,$ns_q3);
// 
// 				$dom_ebXML_ObjectRef->set_attribute("id",$RegistryPackage_ExternalIdentifier_identificationScheme);
// 				############# OBJECTREF

				$RegistryPackage_ExternalIdentifier_objectType=$RegistryPackage_ExternalIdentifier[1];
				$RegistryPackage_ExternalIdentifier_id=$RegistryPackage_ExternalIdentifier[2];
				$RegistryPackage_ExternalIdentifier_value=$RegistryPackage_ExternalIdentifier[3];
				$RegistryPackage_ExternalIdentifier_registryObject=$RegistryPackage_ExternalIdentifier[4];

				$dom_ebXML_RegistryPackage_ExternalIdentifier->set_attribute("identificationScheme",$RegistryPackage_ExternalIdentifier_identificationScheme);
				$dom_ebXML_RegistryPackage_ExternalIdentifier->set_attribute("objectType",$RegistryPackage_ExternalIdentifier_objectType);
				$dom_ebXML_RegistryPackage_ExternalIdentifier->set_attribute("id",$RegistryPackage_ExternalIdentifier_id);
				$dom_ebXML_RegistryPackage_ExternalIdentifier->set_attribute("value",$RegistryPackage_ExternalIdentifier_value);
				$dom_ebXML_RegistryPackage_ExternalIdentifier->set_attribute("registryObject",$RegistryPackage_ExternalIdentifier_registryObject);

				appendName_ExternalIdentifier($dom_ebXML_RegistryPackage,$dom_ebXML_RegistryPackage_ExternalIdentifier,$ns_rim3_path,$RegistryPackage_ExternalIdentifier_id,$connessione);

				appendDescription_ExternalIdentifier($dom_ebXML_RegistryPackage,$dom_ebXML_RegistryPackage_ExternalIdentifier,$ns_rim3_path,$RegistryPackage_ExternalIdentifier_id,$connessione);
			}//END OF for($e=0;$e<count($RegistryPackage_ExternalIdentifier_arr);$e++)

		}//END OF if($returnComposedObjects_a=="true")

		#### CONCATENO LE STINGHE RISULTANTI
		$ebXML_Response_string = $ebXML_Response_string.substr($dom_ebXML_RegistryPackage->dump_mem(),21);

	     }//END OF if($objectType_code_from_RegistryPackage=="XDSFolder")

	     ##### ASSOCIATION
	     if($objectType_from_Association=="Association")
	     {
		writeSQLQueryService("Association");
		//Devo risettare $objectType_from_Association a "" altrimenti rimane associata alla variabile Association
		$objectType_from_Association="";
		$Association_id = $SQLResponse[$rr][0];

		$dom_ebXML_Association = domxml_new_doc("1.0");
		## ROOT
		$dom_ebXML_Association_root=$dom_ebXML_Association->create_element("Association");
		$dom_ebXML_Association_root=$dom_ebXML_Association->append_child($dom_ebXML_Association_root);

		#### SETTO I NAMESPACES
		$dom_ebXML_Association_root->set_namespace($ns_rim3_path,$ns_rim3);
		$dom_ebXML_Association_root->add_namespace($ns_q3_path,$ns_q3);

		####OTTENGO DAL DB GLI ATTRIBUTI DI Association
		$queryForAssociationAttributes = "SELECT associationType,objectType,sourceObject,targetObject FROM Association WHERE Association.id = '$Association_id'";
		$AssociationAttributes=query_select($queryForAssociationAttributes);
		writeSQLQueryService($queryForAssociationAttributes);

		$Association_associationType = $AssociationAttributes[0][0];
		$Association_objectType = $AssociationAttributes[0][1];
		$Association_sourceObject = $AssociationAttributes[0][2];
		$Association_targetObject = $AssociationAttributes[0][3];

		$dom_ebXML_Association_root->set_attribute("id",$Association_id);
		$dom_ebXML_Association_root->set_attribute("associationType",$namespace_associationType.$Association_associationType);
		$dom_ebXML_Association_root->set_attribute("objectType",$namespace_objectType.$Association_objectType);
		$dom_ebXML_Association_root->set_attribute("sourceObject",$Association_sourceObject);
		$dom_ebXML_Association_root->set_attribute("targetObject",$Association_targetObject);

		#### PREPARO PER OBJECTREF
		$Association_sourceObject_ARR_1[$Association_sourceObject]=$Association_sourceObject;
		$Association_sourceObject_ARR_2[]=$Association_sourceObject;
		$Association_targetObject_ARR_1[$Association_targetObject]=$Association_targetObject;
		$Association_targetObject_ARR_2[]=$Association_targetObject;
		##################################################

		appendSlot($dom_ebXML_Association,$dom_ebXML_Association_root,$ns_rim3_path,$Association_id,"Association",$connessione);

		appendName($dom_ebXML_Association,$dom_ebXML_Association_root,$ns_rim3_path,$Association_id,$connessione);

		appendDescription($dom_ebXML_Association,$dom_ebXML_Association_root,$ns_rim3_path,$Association_id,$connessione);


		#### CONCATENO LE STINGHE RISULTANTI
		$ebXML_Response_string = $ebXML_Response_string.substr($dom_ebXML_Association->dump_mem(),21);

	     }//END OF if($objectType_from_Association=="Association")

	}//END OF for($t=0;$t<count($SQLResponse);$t++)

	############################ INSERISCO GLI OBJECTREF
####### ATTENZIONE: FUORI DAL CICLO for($t=0;$t<count($SQLResponse);$t++)
	
	##### EXTRINSICOBJECT
	if(!empty($ExtrinsicObject_Classification_classificationScheme_ARR_1) && !empty($ExtrinsicObject_Classification_classificationNode_ARR_1) && !empty($ExtrinsicObject_ExternalIdentifier_identificationScheme_ARR_1))
	{
		### classificationScheme
		for($d=0;$d<count($ExtrinsicObject_Classification_classificationScheme_ARR_1);$d++)
		{
			#### ID
			$classificationScheme=$ExtrinsicObject_Classification_classificationScheme_ARR_2[$d];

			$dom_ebXML_ObjectRef = domxml_new_doc("1.0");
			$dom_ebXML_ObjectRef_root=$dom_ebXML_ObjectRef->create_element("ObjectRef");
			$dom_ebXML_ObjectRef_root=$dom_ebXML_ObjectRef->append_child($dom_ebXML_ObjectRef_root);

			#### SETTO I NAMESPACES
			$dom_ebXML_ObjectRef_root->set_namespace($ns_rim3_path,$ns_rim3);
			$dom_ebXML_ObjectRef_root->add_namespace($ns_q3_path,$ns_q3);

			$dom_ebXML_ObjectRef_root->set_attribute("id",$classificationScheme);
			
			#### CONCATENO LE STINGHE RISULTANTI
			$ebXML_Response_string = $ebXML_Response_string.substr($dom_ebXML_ObjectRef->dump_mem(),21);	

		}### classificationScheme

		### classificationNode
		for($d=0;$d<count($ExtrinsicObject_Classification_classificationNode_ARR_1);$d++)
		{
			#### ID
			$classificationNode=$ExtrinsicObject_Classification_classificationNode_ARR_2[$d];

			$dom_ebXML_ObjectRef = domxml_new_doc("1.0");
			$dom_ebXML_ObjectRef_root=$dom_ebXML_ObjectRef->create_element("ObjectRef");
			$dom_ebXML_ObjectRef_root=$dom_ebXML_ObjectRef->append_child($dom_ebXML_ObjectRef_root);

			#### SETTO I NAMESPACES
			$dom_ebXML_ObjectRef_root->set_namespace($ns_rim3_path,$ns_rim3);
			$dom_ebXML_ObjectRef_root->add_namespace($ns_q3_path,$ns_q3);

			$dom_ebXML_ObjectRef_root->set_attribute("id",$classificationNode);
			
			#### CONCATENO LE STINGHE RISULTANTI
			$ebXML_Response_string = $ebXML_Response_string.substr($dom_ebXML_ObjectRef->dump_mem(),21);	

		}### classificationNode

		#### identificationScheme
		for($d=0;$d<count($ExtrinsicObject_ExternalIdentifier_identificationScheme_ARR_1);$d++)
		{
			#### ID
			$identificationScheme=$ExtrinsicObject_ExternalIdentifier_identificationScheme_ARR_2[$d];

			$dom_ebXML_ObjectRef = domxml_new_doc("1.0");
			$dom_ebXML_ObjectRef_root=$dom_ebXML_ObjectRef->create_element("ObjectRef");
			$dom_ebXML_ObjectRef_root=$dom_ebXML_ObjectRef->append_child($dom_ebXML_ObjectRef_root);

			#### SETTO I NAMESPACES
			$dom_ebXML_ObjectRef_root->set_namespace($ns_rim3_path,$ns_rim3);
			$dom_ebXML_ObjectRef_root->add_namespace($ns_q3_path,$ns_q3);

			$dom_ebXML_ObjectRef_root->set_attribute("id",$identificationScheme);
			
			#### CONCATENO LE STRINGHE RISULTANTI
			$ebXML_Response_string = $ebXML_Response_string.substr($dom_ebXML_ObjectRef->dump_mem(),21);	

		}#### identificationScheme

	}##### EXTRINSICOBJECT

	/*$fp= fopen("tmp/CONTROLLO_OBJECTREF", "w+");
    	fwrite($fp,"RegistryPackage_Classification_classificationScheme_ARR_1 = ".count($RegistryPackage_Classification_classificationScheme_ARR_1)."  RegistryPackage_Classification_classificationNode_ARR_1 = ".count($RegistryPackage_Classification_classificationNode_ARR_1)."   RegistryPackage_ExternalIdentifier_identificationScheme_ARR_1 = ".count($RegistryPackage_ExternalIdentifier_identificationScheme_ARR_1));
	fclose($fp);*/

	###### REGISTRYPACKAGE
	if(!empty($RegistryPackage_Classification_classificationScheme_ARR_1) && !empty($RegistryPackage_Classification_classificationNode_ARR_1) && !empty($RegistryPackage_ExternalIdentifier_identificationScheme_ARR_1))
	{
		### classificationScheme
		for($d=0;$d<count($RegistryPackage_Classification_classificationScheme_ARR_1);$d++)
		{
			#### ID
			$classificationScheme=$RegistryPackage_Classification_classificationScheme_ARR_2[$d];

			$dom_ebXML_ObjectRef = domxml_new_doc("1.0");
			$dom_ebXML_ObjectRef_root=$dom_ebXML_ObjectRef->create_element("ObjectRef");
			$dom_ebXML_ObjectRef_root=$dom_ebXML_ObjectRef->append_child($dom_ebXML_ObjectRef_root);

			#### SETTO I NAMESPACES
			$dom_ebXML_ObjectRef_root->set_namespace($ns_rim3_path,$ns_rim3);
			$dom_ebXML_ObjectRef_root->add_namespace($ns_q3_path,$ns_q3);

			$dom_ebXML_ObjectRef_root->set_attribute("id",$classificationScheme);
			
			#### CONCATENO LE STINGHE RISULTANTI
			$ebXML_Response_string = $ebXML_Response_string.substr($dom_ebXML_ObjectRef->dump_mem(),21);	

		}### classificationScheme

		### classificationNode
		for($d=0;$d<count($RegistryPackage_Classification_classificationNode_ARR_1);$d++)
		{
			#### ID
			$classificationNode=$RegistryPackage_Classification_classificationNode_ARR_2[$d];

			$dom_ebXML_ObjectRef = domxml_new_doc("1.0");
			$dom_ebXML_ObjectRef_root=$dom_ebXML_ObjectRef->create_element("ObjectRef");
			$dom_ebXML_ObjectRef_root=$dom_ebXML_ObjectRef->append_child($dom_ebXML_ObjectRef_root);

			#### SETTO I NAMESPACES
			$dom_ebXML_ObjectRef_root->set_namespace($ns_rim3_path,$ns_rim3);
			$dom_ebXML_ObjectRef_root->add_namespace($ns_q3_path,$ns_q3);

			$dom_ebXML_ObjectRef_root->set_attribute("id",$classificationNode);
			
			#### CONCATENO LE STINGHE RISULTANTI
			$ebXML_Response_string = $ebXML_Response_string.substr($dom_ebXML_ObjectRef->dump_mem(),21);	

		}### classificationNode

		#### identificationScheme
		for($d=0;$d<count($RegistryPackage_ExternalIdentifier_identificationScheme_ARR_1);$d++)
		{
			#### ID
			$identificationScheme=$RegistryPackage_ExternalIdentifier_identificationScheme_ARR_2[$d];

			$dom_ebXML_ObjectRef = domxml_new_doc("1.0");
			$dom_ebXML_ObjectRef_root=$dom_ebXML_ObjectRef->create_element("ObjectRef");
			$dom_ebXML_ObjectRef_root=$dom_ebXML_ObjectRef->append_child($dom_ebXML_ObjectRef_root);

			#### SETTO I NAMESPACES
			$dom_ebXML_ObjectRef_root->set_namespace($ns_rim3_path,$ns_rim3);
			$dom_ebXML_ObjectRef_root->add_namespace($ns_q3_path,$ns_q3);

			$dom_ebXML_ObjectRef_root->set_attribute("id",$identificationScheme);
			
			#### CONCATENO LE STRINGHE RISULTANTI
			$ebXML_Response_string = $ebXML_Response_string.substr($dom_ebXML_ObjectRef->dump_mem(),21);	

		}#### identificationScheme

	}##### REGISTRYPACKAGE

	##### ASSOCIATION
	if(!empty($Association_sourceObject_ARR_1) && !empty($Association_targetObject_ARR_1))
	{
		### sourceObject
		for($d=0;$d<count($Association_sourceObject_ARR_1);$d++)
		{
			#### ID
			$sourceObject=$Association_sourceObject_ARR_2[$d];

			$dom_ebXML_ObjectRef = domxml_new_doc("1.0");
			$dom_ebXML_ObjectRef_root=$dom_ebXML_ObjectRef->create_element("ObjectRef");
			$dom_ebXML_ObjectRef_root=$dom_ebXML_ObjectRef->append_child($dom_ebXML_ObjectRef_root);

			#### SETTO I NAMESPACES
			$dom_ebXML_ObjectRef_root->set_namespace($ns_rim3_path,$ns_rim3);
			$dom_ebXML_ObjectRef_root->add_namespace($ns_q3_path,$ns_q3);

			$dom_ebXML_ObjectRef_root->set_attribute("id",$sourceObject);
			
			#### CONCATENO LE STINGHE RISULTANTI
			$ebXML_Response_string = $ebXML_Response_string.substr($dom_ebXML_ObjectRef->dump_mem(),21);	

		}### sourceObject

		#### targetObject
		for($d=0;$d<count($Association_targetObject_ARR_1);$d++)
		{
			#### ID
			$targetObject=$Association_targetObject_ARR_2[$d];

			$dom_ebXML_ObjectRef = domxml_new_doc("1.0");
			$dom_ebXML_ObjectRef_root=$dom_ebXML_ObjectRef->create_element("ObjectRef");
			$dom_ebXML_ObjectRef_root=$dom_ebXML_ObjectRef->append_child($dom_ebXML_ObjectRef_root);

			#### SETTO I NAMESPACES
			$dom_ebXML_ObjectRef_root->set_namespace($ns_rim3_path,$ns_rim3);
			$dom_ebXML_ObjectRef_root->add_namespace($ns_q3_path,$ns_q3);

			$dom_ebXML_ObjectRef_root->set_attribute("id",$targetObject);
			
			#### CONCATENO LE STRINGHE RISULTANTI
			$ebXML_Response_string = $ebXML_Response_string.substr($dom_ebXML_ObjectRef->dump_mem(),21);	

		}#### targetObject

	}//ASSOCIATION

############################ FINE INSERISCO GLI OBJECTREF

}//END OF if($returnType_a=="LeafClass")

// else if($returnType_a=="LeafClass" && $returnComposedObjects_a=="true")
// {
// 	#### SI NODI CLASSIFICATION
// 
// 	
// 
// }//END OF if($returnType_a=="LeafClass" && $returnComposedObjects_a=="true")





// ATNA Stored Query
if($ATNA_active=='A'){
		$today = date("Y-m-d");
		$cur_hour = date("H:i:s");
		$datetime = $today."T".$cur_hour;
		require_once('./lib/syslog.php');
        $syslog = new Syslog();
$message_query ="<AuditMessage xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:noNamespaceSchemaLocation=\"healthcare-security-audit.xsd\">
		<EventIdentification EventActionCode=\"E\" EventDateTime=\"$datetime\" EventOutcomeIndicator=\"0\">
			<EventID code=\"110112\" codeSystemName=\"DCM\" displayName=\"Query\"/>
			<EventTypeCode code=\"ITI-18\" codeSystemName=\"IHE Transactions\" displayName=\"Registry Stored Query\"/>
		</EventIdentification>
		<AuditSourceIdentification AuditSourceID=\"MARIS REGISTRY\"/>
			<ActiveParticipant UserID=\"Consumer\" NetworkAccessPointTypeCode=\"2\" NetworkAccessPointID=\"".$_SERVER['REMOTE_ADDR']."\"  UserIsRequestor=\"true\">
        		<RoleIDCode code=\"110153\" codeSystemName=\"DCM\" displayName=\"Source\"/>
		</ActiveParticipant>
		<ActiveParticipant UserID=\"".$http_protocol.$ip_server.":".$port_server.$www_REG_path."\" NetworkAccessPointTypeCode=\"2\" NetworkAccessPointID=\"".$reg_host."\"  UserIsRequestor=\"false\">
        		<RoleIDCode code=\"110152\" codeSystemName=\"DCM\" displayName=\"Destination\"/>
    		</ActiveParticipant>
		<ParticipantObjectIdentification ParticipantObjectID=\"empty\" ParticipantObjectTypeCode=\"2\" ParticipantObjectTypeCodeRole=\"24\">
        		<ParticipantObjectIDTypeCode code=\"ITI-16\" codeSystemName=\"IHE Transactions\" displayName=\"Registry Stored Query\"/>
		<ParticipantObjectQuery>".base64_encode($SQLQuery_ESEGUITA)."</ParticipantObjectQuery>    	</ParticipantObjectIdentification>
		</AuditMessage>";


		// ParticipantObjectID da TF deve essere vuoto ma non valida da syslog nist


		//manca la parte relativa al recupero del patientID.  <ParticipantObjectIdentification ParticipantObjectID=\"".trim($patient_id)."\" ParticipantObjectTypeCode=\"1\" ParticipantObjectTypeCodeRole=\"1\"><ParticipantObjectIDTypeCode code=\"2\"/></ParticipantObjectIdentification>
		$logSyslog=$syslog->Send($ATNA_host,$ATNA_port,$message_query);
		
	writeTimeFile($idfile."--StoredQuery: Ho spedito il messaggio di ATNA");

} // Fine if($ATNA_active=='A')



//Statistiche
if($statActive=="A") {
	//Parte per calcolare i tempi di esecuzione
	$mtime = microtime();
	$mtime = explode(" ",$mtime);
	$mtime = $mtime[1] + $mtime[0];
	$endtime = $mtime;
	$totaltime = number_format($endtime - $starttime,15);

	$STAT_QUERY="INSERT INTO STATS (REPOSITORY,DATA,EXECUTION_TIME,OPERATION) VALUES ('".$_SERVER['REMOTE_ADDR']."',CURRENT_TIMESTAMP,'$totaltime','STOREDQUERY-B')";
	$ris = query_exec2($STAT_QUERY,$connessione);
	writeSQLQueryService($ris.": ".$STAT_QUERY);
}




######################################################################
#### METTO L'ebXML SU STRINGA
$ebXML_Response_SOAPED_string = makeSoapedSuccessStoredQueryResponse($Action,$MessageID,$ebXML_Response_string);

### SCRIVO LA RISPOSTA IN UN FILE
$file_input=$idfile."-ebxmlResponseSOAP.xml";
writeTmpQueryFiles($ebXML_Response_SOAPED_string,$file_input,true);
writeTimeFile($idfile."--StoredQuery: Creo file ebxmlResponseSOAP");

SendResponseFile($tmpQueryService_path.$file_input);
//SendResponse($ebXML_Response_SOAPED_string,"application/soap+xml",(string)filesize($tmpQueryService_path.$idfile."-ebxmlResponseSOAP.xml"));


// Clean tmp folder
$system=PHP_OS;

$windows=substr_count(strtoupper($system),"WIN");


if($clean_cache=="O")
{
	if ($windows>0){
	exec('del tmpQueryService\\'.$idfile."* /q");	
	}
	else{	
	exec('rm -Rf '.$tmpQueryService_path.$idfile."*");
	}

}


unset($_SESSION['tmp_path']);
unset($_SESSION['idfile']);
unset($_SESSION['logActive']);
unset($_SESSION['log_query_path']);
unset($_SESSION['tmpQueryService_path']);

?>