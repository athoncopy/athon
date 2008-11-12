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
//----------------------------------------------------//

##### CONFIGURAZIONE DEL REPOSITORY
include("REGISTRY_CONFIGURATION/REG_configuration.php");
#######################################
include_once($lib_path."domxml-php4-to-php5.php");
include($lib_path."utilities.php");
include("./lib/log.php");
include_once('reg_validation.php');
$idfile = idrandom_file();

$_SESSION['tmp_path']=$tmp_path;
$_SESSION['idfile']=$idfile;
$_SESSION['logActive']=$logActive;
$_SESSION['log_path']=$log_path;

//PULISCO LA CACHE TEMPORANEA
exec('rm -f '.$tmpQueryService_path."*");

if(!is_dir($tmpQueryService_path)){
mkdir($tmpQueryService_path, 0777,true);
}

$error_code=array();
$failure_response=array();

//RECUPERO GLI HEADERS RICEVUTI DA APACHE
$headers = apache_request_headers();

//COPIO IN LOCALE TUTTI GLI HEADERS RICEVUTI
$fp_headers_received = fopen($tmpQueryService_path."headers_received", "w+");
foreach ($headers as $header => $value) 
{
   fwrite ($fp_headers_received, "$header = $value  \n");	
}
fclose($fp_headers_received);

//AdhocQueryRequest IMBUSTATO
$fp= fopen($tmpQueryService_path."AdhocQueryRequest_imbustato_soap", "w+");
    fwrite($fp,$HTTP_RAW_POST_DATA);//RICAVO DALLA VAR $HTTP_RAW_POST_DATA
fclose($fp);

	//SBUSTO	
$ebxml_imbustato_soap_STRING = file_get_contents($tmpQueryService_path."AdhocQueryRequest_imbustato_soap");


#########################################################################
$ebxml_STRING
=substr($ebxml_imbustato_soap_STRING,
                strpos($ebxml_imbustato_soap_STRING,"<AdhocQueryRequest"),
(strlen($ebxml_imbustato_soap_STRING)-strlen(substr($ebxml_imbustato_soap_STRING,strpos($ebxml_imbustato_soap_STRING,"</AdhocQueryRequest")+20))));

$ebxml_STRING=str_replace((substr($ebxml_imbustato_soap_STRING,strpos($ebxml_imbustato_soap_STRING,"</AdhocQueryRequest")+20)),
"",$ebxml_STRING);
###################################################################################

//SCRIVO L'AdhocQueryRequest SBUSTATO
$fp_AdhocQueryRequest = fopen($tmpQueryService_path."AdhocQueryRequest","w+");
	fwrite($fp_AdhocQueryRequest,$ebxml_STRING);
fclose($fp_AdhocQueryRequest);


$schema='schemas/query.xsd';
$isValid = isValid($ebxml_STRING,$schema);

if ($isValid){
	writeTimeFile($idfile."--Query: Il documento e' valido");
}

$fp_SCHEMA_val = fopen($tmp_path.$idfile."-SCHEMA_validation-".$idfile,"w+");
	fwrite($fp_SCHEMA_val,"VALIDAZIONE DA SCHEMA ==> OK <==");
fclose($fp_SCHEMA_val);



#### OTTENGO L'OGGETTO DOM DALL'AdhocQueryRequest
$contents=file_get_contents($tmpQueryService_path."AdhocQueryRequest");
//writeTimeFile($contents);


$dom_AdhocQueryRequest = domxml_open_mem($contents);
if (!$dom = domxml_open_mem($contents)) {
  writeTimeFile($idfile."--Query: AdhocQueryRequest non corretto");
}


##############################################################################
##### PARAMETRI DA RICAVARE DALL'ebXML DI QUERY
$SQLQuery = '';  //TESTO DELLA QUERY
$returnComposedObjects_a = ''; // TRUE O FALSE
$returnType_a = ''; // LeafClass

//NODO DI ROOT DELL'OGGETTO DOM
$root = $dom_AdhocQueryRequest->document_element();
################# RECUPERO LE OPZIONI DELLA QUERY
$SQLQuery_options_node_array = $root->get_elements_by_tagname("ResponseOption");


for($u = 0;$u < count($SQLQuery_options_node_array);$u++)
{
   $SQLQuery_options_node = $SQLQuery_options_node_array[$u];

   $returnComposedObjects_a=$SQLQuery_options_node->get_attribute("returnComposedObjects");
   $returnType_a = $SQLQuery_options_node->get_attribute("returnType");
	
}//END OF for($u = 0;$u < count($SQLQuery_options_node_array);$u++)


#################### RECUPERO IL TESTO DELLA QUERY
$SQLQuery_node_array = $root->get_elements_by_tagname("SQLQuery");
for($i = 0;$i<count($SQLQuery_node_array);$i++) 
{
   $node = $SQLQuery_node_array[$i];
	
    ###### RICAVO LA QUERY IN FORMATO STRINGA ########
    //$SQLQuery = avoidHtmlEntitiesInterpretation(trim($node->get_content()));
    $SQLQuery = trim($node->get_content());

    //$SQLQuery = (trim($node->get_content()));
    //$SQLQuery = str_replace('&','&amp;',$SQLQuery);
    ###########################################################################
	
}//END OF for ($i = 0;$i<count($SQLQuery_node_array);$i++)

//SCRIVO LA QUERY


//Mi connetto al DB
$connessione=connectDB();




###### CONTROLLO SQL RICEVUTA
$controllo_query_array = controllaQuery($SQLQuery);

###### CASO DI VALIDAZIONE SQL ===NON=== PASSATA
if(!$controllo_query_array[0]){
  writeTimeFile($idfile."--Query: SUPERATO IL VINCOLO DI VALIDAZIONE SU TIPO DI SQL + SCHEMAS");
}


########################################################################
### ORA DEVO ESEGUIRE LA QUERY SUL DB DEL XDS_REGISTRY_QUERY REGISTRY 

################ RISPOSTA ALLA QUERY (ARRAY)
###METTO A POSTO EVENTUALI STRINGHE DI COMANDO
$SQLQuery_ESEGUITA=adjustQuery($SQLQuery);#### IMPORTANTE!!!
###SCRIVO LA QUERY CHE EFFETTIVAMENTE LANCIO A DB

###### ESEGUO LA QUERY
$SQLResponse = query_select2($SQLQuery_ESEGUITA,$connessione);

####################################################

#### CONTROLLO COME PRIMA COSA CHE LA SQL ABBIA RISULTATO
if(empty($SQLResponse))
{
	#### STRINGA DI ERRORE
	$failure_response = "[EMPTY RESULT] - SQL QUERY[  ".avoidHtmlEntitiesInterpretation($SQLQuery)." ] DID NOT GIVE ANY RESULTS IN THIS REGISTRY";
	
	### RESTITUISCE IL MESSAGGIO DI SUCCESS IN SOAP
	### ANCHE SE IL RISULTATO DELLA QUERY DA DB È VUOTO
    	$SOAPED_failure_response = makeSoapedSuccessQueryResponse($failure_response);

	$file_input=$tmpQueryService_path.$idfile."-SOAPED_NORESULTS_response-".$idfile;
	 $fp = fopen($file_input,"w+");
           fwrite($fp,$SOAPED_failure_response);
         fclose($fp);


	SendResponse($file_input);
	exit;

}//END OF if(empty($SQLResponse))


####### QUI SONO SICURO CHE LE QUERY DA ALMENO UN RISULTATO
$ebXML_Response_string = "";
$ebXML_Response_SOAPED_string = "";

##### COMINCIO A COSTRUIRE L'ebXML DI RISPOSTA
//$dom_ebXML_Response = domxml_new_doc("1.0");###documento
//$dom_ebXML_Response_root = $dom_ebXML_Response->create_element("SQLQueryResult");
//$dom_ebXML_Response_root->set_namespace("urn:oasis:names:tc:ebxml-regrep:rim:xsd:2.1","rim");
//$dom_ebXML_Response_root = $dom_ebXML_Response->append_child($dom_ebXML_Response_root);

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
		$dom_ebXML_ObjectRef_root->set_namespace($ns_rim_path,$ns_rim);
		$dom_ebXML_ObjectRef_root->add_namespace($ns_q_path,$ns_q);

		$dom_ebXML_ObjectRef_root->set_attribute("id",$ObjectRef_id);

		#### METTO SU STRINGA
		$ebXML_Response_string = $ebXML_Response_string.substr($dom_ebXML_ObjectRef->dump_mem(),21);
		
	}//END OF for($t=0;$t<count($SQLResponse);$t++)

}//END OF if($returnType_a=="ObjectRef" && $returnComposedObjects_a=="false")

else if($returnType_a=="ObjectRef" && $returnComposedObjects_a=="true")
{
	##### ???????????
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
		
		#### DOCUMENTENTRY
		$get_objectType_from_ExtrinsicObject="SELECT objectType FROM ExtrinsicObject WHERE ExtrinsicObject.id = '$id'";
		$objectType_from_ExtrinsicObject_arr=query_select2($get_objectType_from_ExtrinsicObject,$connessione);
		writeSQLQueryService($get_objectType_from_ExtrinsicObject);

		//$objectType_from_ExtrinsicObject=$objectType_from_ExtrinsicObject_arr[0]['objectType'];
		$objectType_from_ExtrinsicObject=$objectType_from_ExtrinsicObject_arr[0][0];
		if($objectType_from_ExtrinsicObject!=''){
			$mappa_type="SELECT code FROM ClassificationNode WHERE ClassificationNode.id = '$objectType_from_ExtrinsicObject'";
			$objectType_code_from_ExtrinsicObject_arr=query_select2($mappa_type,$connessione);
			writeSQLQueryService($mappa_type);
		
			//$objectType_code_from_ExtrinsicObject=$objectType_code_from_ExtrinsicObject_arr[0]['code'];
			$objectType_code_from_ExtrinsicObject=$objectType_code_from_ExtrinsicObject_arr[0][0];
		}
		else {
		##### SUBMISSIONSET
		$get_objectType_from_RegistryPackage="SELECT objectType FROM RegistryPackage WHERE RegistryPackage.id = '$id'";
		$objectType_from_RegistryPackage_arr=query_select2($get_objectType_from_RegistryPackage,$connessione);
		writeSQLQueryService($get_objectType_from_RegistryPackage);

		//$objectType_from_RegistryPackage=$objectType_from_RegistryPackage_arr[0]['objectType'];
		$objectType_from_RegistryPackage=$objectType_from_RegistryPackage_arr[0][0];
		if($objectType_from_RegistryPackage!=''){
			$mappa_type="SELECT code FROM ClassificationNode WHERE ClassificationNode.id = '$objectType_from_RegistryPackage'";
			$objectType_code_from_RegistryPackage_arr=query_select2($mappa_type,$connessione);
			writeSQLQueryService($mappa_type);

			$objectType_code_from_RegistryPackage=$objectType_code_from_RegistryPackage_arr[0][0];
		}

		##### ASSOCIATION
		$get_objectType_from_Association="SELECT objectType FROM Association WHERE Association.id = '$id'";
		$objectType_from_Association_arr=query_select2($get_objectType_from_Association,$connessione);
		writeSQLQueryService($get_objectType_from_Association);
		$objectType_from_Association=$objectType_from_Association_arr[0][0];
		}
		############### FINE DISCRIMINO IL TIPO DI RISULTATO ##############

	     if($objectType_code_from_ExtrinsicObject=="XDSDocumentEntry")
	     {
		writeSQLQueryService("\nSono nel caso XDSDocumentEntry");
		$ExtrinsicObject_id = $SQLResponse[$rr][0];

		$dom_ebXML_ExtrinsicObject = domxml_new_doc("1.0");
		## ROOT
		$dom_ebXML_ExtrinsicObject_root=$dom_ebXML_ExtrinsicObject->create_element("ExtrinsicObject");
		$dom_ebXML_ExtrinsicObject_root=$dom_ebXML_ExtrinsicObject->append_child($dom_ebXML_ExtrinsicObject_root);

		#### SETTO I NAMESPACES
		$dom_ebXML_ExtrinsicObject_root->set_namespace($ns_rim_path,$ns_rim);
		$dom_ebXML_ExtrinsicObject_root->add_namespace($ns_q_path,$ns_q);

		####OTTENGO DAL DB GLI ATTRIBUTI DI ExtrinsicObject
		$queryForExtrinsicObjectAttributes = "SELECT isOpaque,majorVersion,mimeType,minorVersion,objectType,status FROM ExtrinsicObject WHERE ExtrinsicObject.id = '$ExtrinsicObject_id'";
		$ExtrinsicObjectAttributes=query_select2($queryForExtrinsicObjectAttributes,$connessione);
		writeSQLQueryService($queryForExtrinsicObjectAttributes);

		$ExtrinsicObject_isOpaque = $ExtrinsicObjectAttributes[0][0];
		$ExtrinsicObject_majorVersion = $ExtrinsicObjectAttributes[0][1];
		$ExtrinsicObject_mimeType = $ExtrinsicObjectAttributes[0][2];
		$ExtrinsicObject_minorVersion = $ExtrinsicObjectAttributes[0][3];
		$ExtrinsicObject_objectType = $ExtrinsicObjectAttributes[0][4];
		$ExtrinsicObject_status = $ExtrinsicObjectAttributes[0][5];

		$dom_ebXML_ExtrinsicObject_root->set_attribute("id",$ExtrinsicObject_id);
		$dom_ebXML_ExtrinsicObject_root->set_attribute("isOpaque",$ExtrinsicObject_isOpaque);
		$dom_ebXML_ExtrinsicObject_root->set_attribute("majorVersion",$ExtrinsicObject_majorVersion);
		$dom_ebXML_ExtrinsicObject_root->set_attribute("mimeType",$ExtrinsicObject_mimeType);
		$dom_ebXML_ExtrinsicObject_root->set_attribute("minorVersion",$ExtrinsicObject_minorVersion);
		$dom_ebXML_ExtrinsicObject_root->set_attribute("objectType",$ExtrinsicObject_objectType);
		$dom_ebXML_ExtrinsicObject_root->set_attribute("status",$ExtrinsicObject_status);

		#### NAME
		$dom_ebXML_ExtrinsicObject_Name=$dom_ebXML_ExtrinsicObject->create_element_ns($ns_rim_path,"Name");
		$dom_ebXML_ExtrinsicObject_Name=$dom_ebXML_ExtrinsicObject_root->append_child($dom_ebXML_ExtrinsicObject_Name);

		$queryForExtrinsicObject_Name="SELECT charset,value,lang FROM Name WHERE Name.parent = '$ExtrinsicObject_id'";
		$Name_arr=query_select2($queryForExtrinsicObject_Name,$connessione);
		writeSQLQueryService($queryForExtrinsicObject_Name);

		$Name_charset = $Name_arr[0][0];
		$Name_value = $Name_arr[0][1];
		$Name_lang = $Name_arr[0][2];

		if(!empty($Name_arr))
		{
		$dom_ebXML_ExtrinsicObject_Name_LocalizedString=$dom_ebXML_ExtrinsicObject->create_element_ns($ns_rim_path,"LocalizedString");
		$dom_ebXML_ExtrinsicObject_Name_LocalizedString=$dom_ebXML_ExtrinsicObject_Name->append_child($dom_ebXML_ExtrinsicObject_Name_LocalizedString);

		$dom_ebXML_ExtrinsicObject_Name_LocalizedString->set_attribute("charset",$Name_charset);
		$dom_ebXML_ExtrinsicObject_Name_LocalizedString->set_attribute("value",$Name_value);
		$dom_ebXML_ExtrinsicObject_Name_LocalizedString->set_attribute("xml:lang",$Name_lang);
		}

		#### DESCRIPTION
		$dom_ebXML_ExtrinsicObject_Description=$dom_ebXML_ExtrinsicObject->create_element_ns($ns_rim_path,"Description");
		$dom_ebXML_ExtrinsicObject_Description=$dom_ebXML_ExtrinsicObject_root->append_child($dom_ebXML_ExtrinsicObject_Description);

		$queryForExtrinsicObject_Description="SELECT charset,value,lang FROM Description WHERE Description.parent = '$ExtrinsicObject_id'";
		$Description_arr=query_select2($queryForExtrinsicObject_Description,$connessione);
		writeSQLQueryService($queryForExtrinsicObject_Description);

		$Description_charset = $Description_arr[0][0];
		$Description_value = $Description_arr[0][1];
		$Description_lang = $Description_arr[0][2];

		if(!empty($Description_arr) && $Description_value!="NOT DECLARED")
		{
		$dom_ebXML_ExtrinsicObject_Description_LocalizedString=$dom_ebXML_ExtrinsicObject->create_element_ns($ns_rim_path,"LocalizedString");
		$dom_ebXML_ExtrinsicObject_Description_LocalizedString=$dom_ebXML_ExtrinsicObject_Description->append_child($dom_ebXML_ExtrinsicObject_Description_LocalizedString);

		$dom_ebXML_ExtrinsicObject_Description_LocalizedString->set_attribute("charset",$Description_charset);
		$dom_ebXML_ExtrinsicObject_Description_LocalizedString->set_attribute("value",$Description_value);
		$dom_ebXML_ExtrinsicObject_Description_LocalizedString->set_attribute("xml:lang",$Description_lang);
		}

		##### SLOT
		$select_Slots = "SELECT name,value FROM Slot WHERE Slot.parent = '$ExtrinsicObject_id'";
		$Slot_arr=query_select2($select_Slots,$connessione);
		writeSQLQueryService($select_Slots);
		$Slot_arr_EO=$Slot_arr;
		$repeat = true;
		for($s=0;$s<count($Slot_arr);$s++)
		{
			$Slot = $Slot_arr[$s];
			$Slot_name = $Slot[0];

			if($Slot_name=="sourcePatientInfo" && $repeat)
			{
				$dom_ebXML_ExtrinsicObject_Slot=$dom_ebXML_ExtrinsicObject->create_element_ns($ns_rim_path,"Slot");
				$dom_ebXML_ExtrinsicObject_Slot=$dom_ebXML_ExtrinsicObject_root->append_child($dom_ebXML_ExtrinsicObject_Slot);

				$select_sourcePatientInfo_Slots = "SELECT value FROM Slot WHERE Slot.parent = '$ExtrinsicObject_id' AND Slot.name = 'sourcePatientInfo'";
				$sourcePatientInfo_Slots=query_select2($select_sourcePatientInfo_Slots,$connessione);
				writeSQLQueryService($select_sourcePatientInfo_Slots);
				
				$dom_ebXML_ExtrinsicObject_Slot->set_attribute("name",$Slot_name);
				$dom_ebXML_ExtrinsicObject_Slot_ValueList=$dom_ebXML_ExtrinsicObject->create_element_ns($ns_rim_path,"ValueList");
				$dom_ebXML_ExtrinsicObject_Slot_ValueList=$dom_ebXML_ExtrinsicObject_Slot->append_child($dom_ebXML_ExtrinsicObject_Slot_ValueList);

				for($r=0;$r<count($sourcePatientInfo_Slots);$r++)
				{
					$sourcePatientInfo_Slot=$sourcePatientInfo_Slots[$r];
					$Slot_value = $sourcePatientInfo_Slot[0];

					$dom_ebXML_ExtrinsicObject_Slot_ValueList_Value=$dom_ebXML_ExtrinsicObject->create_element_ns($ns_rim_path,"Value");
					$dom_ebXML_ExtrinsicObject_Slot_ValueList_Value=$dom_ebXML_ExtrinsicObject_Slot_ValueList->append_child($dom_ebXML_ExtrinsicObject_Slot_ValueList_Value);

					$dom_ebXML_ExtrinsicObject_Slot_ValueList_Value->set_content($Slot_value);

				}//END OF for($r=0;$r<count($sourcePatientInfo_Slots);$r++)

				$repeat=false;

			}//END OF if($Slot_name=="sourcePatientInfo")
			if($Slot_name!="sourcePatientInfo")
			{
				$dom_ebXML_ExtrinsicObject_Slot=$dom_ebXML_ExtrinsicObject->create_element_ns($ns_rim_path,"Slot");
				$dom_ebXML_ExtrinsicObject_Slot=$dom_ebXML_ExtrinsicObject_root->append_child($dom_ebXML_ExtrinsicObject_Slot);

				$dom_ebXML_ExtrinsicObject_Slot->set_attribute("name",$Slot_name);
			
				$dom_ebXML_ExtrinsicObject_Slot_ValueList=$dom_ebXML_ExtrinsicObject->create_element_ns($ns_rim_path,"ValueList");
				$dom_ebXML_ExtrinsicObject_Slot_ValueList=$dom_ebXML_ExtrinsicObject_Slot->append_child($dom_ebXML_ExtrinsicObject_Slot_ValueList);

				$dom_ebXML_ExtrinsicObject_Slot_ValueList_Value=$dom_ebXML_ExtrinsicObject->create_element_ns($ns_rim_path,"Value");
				$dom_ebXML_ExtrinsicObject_Slot_ValueList_Value=$dom_ebXML_ExtrinsicObject_Slot_ValueList->append_child($dom_ebXML_ExtrinsicObject_Slot_ValueList_Value);

				$Slot_value = $Slot[1];
				$dom_ebXML_ExtrinsicObject_Slot_ValueList_Value->set_content($Slot_value);

			}//END OF elseif($Slot_name!="sourcePatientInfo")

		}//END OF for($s=0;$s<count($slot_arr);$s++)

		#### GESTISCO IL CASO IN CUI DEVO RITORNARE OGGETTI COMPOSTI
		if($returnComposedObjects_a=="true")
		{
			#### CLASSIFICATION + EXTERNALIDENTIFIER + OBJECTREF

			###### NODI CLASSIFICATION
			writeSQLQueryService("\nCiclo su Classification di ExtrinsicObject");
			//Query modificata per ebxml v2.1 (AuthorInstitution....)
			/*$get_ExtrinsicObject_Classification="SELECT classificationScheme,classificationNode,classifiedObject,id,nodeRepresentation,objectType FROM Classification WHERE Classification.classifiedObject = '$ExtrinsicObject_id' AND Classification.nodeRepresentation != 'NULL'";*/
			$get_ExtrinsicObject_Classification="SELECT classificationScheme,classificationNode,classifiedObject,id,nodeRepresentation,objectType FROM Classification WHERE Classification.classifiedObject = '$ExtrinsicObject_id'";
			$ExtrinsicObject_Classification_arr=query_select2($get_ExtrinsicObject_Classification,$connessione);
			writeSQLQueryService($get_ExtrinsicObject_Classification);

			#### CICLO SU TUTTI I NODI CLASSIFICATION
			for($t=0;$t<count($ExtrinsicObject_Classification_arr);$t++)
			{
				$ExtrinsicObject_Classification=$ExtrinsicObject_Classification_arr[$t];
				
				$dom_ebXML_ExtrinsicObject_Classification=$dom_ebXML_ExtrinsicObject->create_element_ns($ns_rim_path,"Classification");
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
// 				$dom_ebXML_ObjectRef=$dom_ebXML_ExtrinsicObject->create_element_ns($ns_rim_path,"ObjectRef");
// 				$dom_ebXML_ObjectRef=$dom_ebXML_ExtrinsicObject_root->append_child($dom_ebXML_ObjectRef);
// 
// 				#### SETTO I NAMESPACES
// 				$dom_ebXML_ObjectRef->add_namespace($ns_rim_path,$ns_rim);
// 				$dom_ebXML_ObjectRef->add_namespace($ns_q_path,$ns_q);
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
				$dom_ebXML_ExtrinsicObject_Classification->set_attribute("objectType",$ExtrinsicObject_Classification_objectType);
				#### NAME
				$dom_ebXML_Classification_Name=$dom_ebXML_ExtrinsicObject->create_element_ns($ns_rim_path,"Name");
				$dom_ebXML_Classification_Name=$dom_ebXML_ExtrinsicObject_Classification->append_child($dom_ebXML_Classification_Name);

				$queryForClassification_Name="SELECT charset,value,lang FROM Name WHERE Name.parent = '$ExtrinsicObject_Classification_id'";
				$Name_arr=query_select2($queryForClassification_Name,$connessione);
				writeSQLQueryService($queryForClassification_Name);

				$Name_charset = $Name_arr[0][0];
				$Name_value = $Name_arr[0][1];
				$Name_lang = $Name_arr[0][2];

				if(!empty($Name_arr))
				{
				$dom_ebXML_Classification_Name_LocalizedString=$dom_ebXML_ExtrinsicObject->create_element_ns($ns_rim_path,"LocalizedString");
				$dom_ebXML_Classification_Name_LocalizedString=$dom_ebXML_Classification_Name->append_child($dom_ebXML_Classification_Name_LocalizedString);

				$dom_ebXML_Classification_Name_LocalizedString->set_attribute("charset",$Name_charset);
				$dom_ebXML_Classification_Name_LocalizedString->set_attribute("value",$Name_value);
				$dom_ebXML_Classification_Name_LocalizedString->set_attribute("xml:lang",$Name_lang);
				}

				#### DESCRIPTION
				$queryForClassification_Description="SELECT charset,value,lang FROM Description WHERE Description.parent = '$ExtrinsicObject_Classification_id'";

				//$fp = fopen($tmp_path."DESCRIPTION","w+");
    				//fwrite($fp,$queryForClassification_Description);
				//fclose($fp);

				$Description_arr=query_select2($queryForClassification_Description,$connessione);
				writeSQLQueryService($queryForClassification_Description);

				$Description_charset = $Description_arr[0][0];
				$Description_value = $Description_arr[0][1];
				$Description_lang = $Description_arr[0][2];

				if(!empty($Description_arr) && $Description_value!="NOT DECLARED")
				{
				$dom_ebXML_Classification_Description=$dom_ebXML_ExtrinsicObject->create_element_ns($ns_rim_path,"Description");
				$dom_ebXML_Classification_Description=$dom_ebXML_ExtrinsicObject_Classification->append_child($dom_ebXML_Classification_Description);

				$dom_ebXML_Classification_Description_LocalizedString=$dom_ebXML_ExtrinsicObject->create_element_ns($ns_rim_path,"LocalizedString");
				$dom_ebXML_Classification_Description_LocalizedString=$dom_ebXML_Classification_Description->append_child($dom_ebXML_Classification_Description_LocalizedString);

				$dom_ebXML_Classification_Description_LocalizedString->set_attribute("charset",$Description_charset);
				$dom_ebXML_Classification_Description_LocalizedString->set_attribute("value",$Description_value);
				$dom_ebXML_Classification_Description_LocalizedString->set_attribute("xml:lang",$Description_lang);
				}

// Parte per Slot Multipli

				/*#### SLOT
				$dom_ebXML_Classification_Slot=$dom_ebXML_ExtrinsicObject->create_element_ns($ns_rim_path,"Slot");
				$dom_ebXML_Classification_Slot=$dom_ebXML_ExtrinsicObject_Classification->append_child($dom_ebXML_Classification_Slot);

				$dom_ebXML_Classification_Slot_ValueList=$dom_ebXML_ExtrinsicObject->create_element_ns($ns_rim_path,"ValueList");
				$dom_ebXML_Classification_Slot_ValueList=$dom_ebXML_Classification_Slot->append_child($dom_ebXML_Classification_Slot_ValueList);

				$dom_ebXML_Classification_Slot_ValueList_Value=$dom_ebXML_ExtrinsicObject->create_element_ns($ns_rim_path,"Value");
				$dom_ebXML_Classification_Slot_ValueList_Value=$dom_ebXML_Classification_Slot_ValueList->append_child($dom_ebXML_Classification_Slot_ValueList_Value);

				$select_Slots = "SELECT name,value FROM Slot WHERE Slot.parent = '$ExtrinsicObject_Classification_id'";
				$Slot_arr=query_select2($select_Slots,$connessione);
				writeSQLQueryService($select_Slots);

				#### RICAVO LE INFO SUL NODO SLOT
				$Slot=$Slot_arr[0];
				$Slot_name = $Slot[0];
				$Slot_value = $Slot[1];

				$dom_ebXML_Classification_Slot->set_attribute("name",$Slot_name);
				$dom_ebXML_Classification_Slot_ValueList_Value->set_content($Slot_value);*/



				#### SLOT

				$select_Slots = "SELECT name,value FROM Slot WHERE Slot.parent = '$ExtrinsicObject_Classification_id'";
				$Slot_arr=query_select2($select_Slots,$connessione);
				writeSQLQueryService($select_Slots);
				
				for($sl=0;$sl<count($Slot_arr);$sl++){

				$dom_ebXML_Classification_Slot=$dom_ebXML_ExtrinsicObject->create_element_ns($ns_rim_path,"Slot");
				$dom_ebXML_Classification_Slot=$dom_ebXML_ExtrinsicObject_Classification->append_child($dom_ebXML_Classification_Slot);

				$dom_ebXML_Classification_Slot_ValueList=$dom_ebXML_ExtrinsicObject->create_element_ns($ns_rim_path,"ValueList");
				$dom_ebXML_Classification_Slot_ValueList=$dom_ebXML_Classification_Slot->append_child($dom_ebXML_Classification_Slot_ValueList);

				$dom_ebXML_Classification_Slot_ValueList_Value=$dom_ebXML_ExtrinsicObject->create_element_ns($ns_rim_path,"Value");
				$dom_ebXML_Classification_Slot_ValueList_Value=$dom_ebXML_Classification_Slot_ValueList->append_child($dom_ebXML_Classification_Slot_ValueList_Value);



				#### RICAVO LE INFO SUL NODO SLOT
				$Slot=$Slot_arr[$sl];
				$Slot_name = $Slot[0];
				$Slot_value = $Slot[1];

				$dom_ebXML_Classification_Slot->set_attribute("name",$Slot_name);
				$dom_ebXML_Classification_Slot_ValueList_Value->set_content($Slot_value);
				}





			}//END OF for($t=0;$t<count($ExtrinsicObject_Classification_arr);$t++)

			#### NODI EXTERNALIDENTIFIER
			writeSQLQueryService("\nCiclo su ExternalIdentifier di ExtrinsicObject");
			$get_ExtrinsicObject_ExternalIdentifier="SELECT identificationScheme,objectType,id,value FROM ExternalIdentifier WHERE ExternalIdentifier.registryObject = '$ExtrinsicObject_id'";
			$ExtrinsicObject_ExternalIdentifier_arr=query_select2($get_ExtrinsicObject_ExternalIdentifier,$connessione);
			writeSQLQueryService($get_ExtrinsicObject_ExternalIdentifier);

			#### CICLO SU TUTTI I NODI EXTERNALIDENTIFIER
			for($e=0;$e<count($ExtrinsicObject_ExternalIdentifier_arr);$e++)
			{
				$ExtrinsicObject_ExternalIdentifier=$ExtrinsicObject_ExternalIdentifier_arr[$e];
				
				$dom_ebXML_ExtrinsicObject_ExternalIdentifier=$dom_ebXML_ExtrinsicObject->create_element_ns($ns_rim_path,"ExternalIdentifier");
				$dom_ebXML_ExtrinsicObject_ExternalIdentifier=$dom_ebXML_ExtrinsicObject_root->append_child($dom_ebXML_ExtrinsicObject_ExternalIdentifier);

				#### ATTRIBUTI DI EXTERNALIDENTIFIER
				$ExtrinsicObject_ExternalIdentifier_identificationScheme=$ExtrinsicObject_ExternalIdentifier[0];
				#### PREPARO PER OBJECTREF
		$ExtrinsicObject_ExternalIdentifier_identificationScheme_ARR_1[$ExtrinsicObject_ExternalIdentifier_identificationScheme]=$ExtrinsicObject_ExternalIdentifier_identificationScheme;
		$ExtrinsicObject_ExternalIdentifier_identificationScheme_ARR_2[]=$ExtrinsicObject_ExternalIdentifier_identificationScheme;
		########################

// 				#### DEVO DICHIARARE identificationScheme IN OBJECTREF
// 				$dom_ebXML_ObjectRef=$dom_ebXML_ExtrinsicObject->create_element_ns($ns_rim_path,"ObjectRef");
// 				$dom_ebXML_ObjectRef=$dom_ebXML_ExtrinsicObject_root->append_child($dom_ebXML_ObjectRef);
// 
// 				#### SETTO I NAMESPACES
// 				$dom_ebXML_ObjectRef->add_namespace($ns_rim_path,$ns_rim);
// 				$dom_ebXML_ObjectRef->add_namespace($ns_q_path,$ns_q);
// 
// 				$dom_ebXML_ObjectRef->set_attribute("id",$ExtrinsicObject_ExternalIdentifier_identificationScheme);
// 				############# OBJECTREF

				$ExtrinsicObject_ExternalIdentifier_objectType=$ExtrinsicObject_ExternalIdentifier[1];
				$ExtrinsicObject_ExternalIdentifier_id=$ExtrinsicObject_ExternalIdentifier[2];
				$ExtrinsicObject_ExternalIdentifier_value=$ExtrinsicObject_ExternalIdentifier[3];

				$dom_ebXML_ExtrinsicObject_ExternalIdentifier->set_attribute("identificationScheme",$ExtrinsicObject_ExternalIdentifier_identificationScheme);
				$dom_ebXML_ExtrinsicObject_ExternalIdentifier->set_attribute("objectType",$ExtrinsicObject_ExternalIdentifier_objectType);
				$dom_ebXML_ExtrinsicObject_ExternalIdentifier->set_attribute("id",$ExtrinsicObject_ExternalIdentifier_id);
				$dom_ebXML_ExtrinsicObject_ExternalIdentifier->set_attribute("value",$ExtrinsicObject_ExternalIdentifier_value);

				#### NAME
				$dom_ebXML_ExternalIdentifier_Name=$dom_ebXML_ExtrinsicObject->create_element_ns($ns_rim_path,"Name");
				$dom_ebXML_ExternalIdentifier_Name=$dom_ebXML_ExtrinsicObject_ExternalIdentifier->append_child($dom_ebXML_ExternalIdentifier_Name);

				$queryForExternalIdentifier_Name="SELECT charset,value,lang FROM Name WHERE Name.parent = '$ExtrinsicObject_ExternalIdentifier_id'";
				$Name_arr=query_select2($queryForExternalIdentifier_Name,$connessione);
				writeSQLQueryService($queryForExternalIdentifier_Name);

				$Name_charset = $Name_arr[0][0];
				$Name_value = $Name_arr[0][1];
				$Name_lang = $Name_arr[0][2];

				if(!empty($Name_arr))
				{
				$dom_ebXML_ExternalIdentifier_Name_LocalizedString=$dom_ebXML_ExtrinsicObject->create_element_ns($ns_rim_path,"LocalizedString");
				$dom_ebXML_ExternalIdentifier_Name_LocalizedString=$dom_ebXML_ExternalIdentifier_Name->append_child($dom_ebXML_ExternalIdentifier_Name_LocalizedString);

				$dom_ebXML_ExternalIdentifier_Name_LocalizedString->set_attribute("charset",$Name_charset);
				$dom_ebXML_ExternalIdentifier_Name_LocalizedString->set_attribute("value",$Name_value);
				$dom_ebXML_ExternalIdentifier_Name_LocalizedString->set_attribute("xml:lang",$Name_lang);
				}

				#### DESCRIPTION
				$queryForExternalIdentifier_Description="SELECT charset,value,lang FROM Description WHERE Description.parent = '$ExtrinsicObject_ExternalIdentifier_id'";
				$Description_arr=query_select2($queryForExternalIdentifier_Description,$connessione);
				writeSQLQueryService($queryForExternalIdentifier_Description);

				$Description_charset = $Description_arr[0][0];
				$Description_value = $Description_arr[0][1];
				$Description_lang = $Description_arr[0][2];

				if(!empty($Description_arr) && $Description_value!="NOT DECLARED")
				{
				$dom_ebXML_ExternalIdentifier_Description=$dom_ebXML_ExtrinsicObject->create_element_ns($ns_rim_path,"Description");
				$dom_ebXML_ExternalIdentifier_Description=$dom_ebXML_ExtrinsicObject_Classification->append_child($dom_ebXML_ExternalIdentifier_Description);

				$dom_ebXML_ExternalIdentifier_Description_LocalizedString=$dom_ebXML_ExtrinsicObject->create_element_ns($ns_rim_path,"LocalizedString");
				$dom_ebXML_ExternalIdentifier_Description_LocalizedString=$dom_ebXML_ExternalIdentifier_Description->append_child($dom_ebXML_ExternalIdentifier_Description_LocalizedString);

				$dom_ebXML_ExternalIdentifier_Description_LocalizedString->set_attribute("charset",$Description_charset);
				$dom_ebXML_ExternalIdentifier_Description_LocalizedString->set_attribute("value",$Description_value);
				$dom_ebXML_ExternalIdentifier_Description_LocalizedString->set_attribute("xml:lang",$Description_lang);
				}

			}//END OF for($e=0;$e<count($ExtrinsicObject_ExternalIdentifier_arr);$e++)

		}//END OF if($returnComposedObjects_a=="true")

		#### CONCATENO LE STINGHE RISULTANTI
		$ebXML_Response_string = $ebXML_Response_string.substr($dom_ebXML_ExtrinsicObject->dump_mem(),21);
	     }//END OF if($objectType_code_from_ExtrinsicObject=="XDSDocumentEntry")

	     if($objectType_code_from_RegistryPackage=="XDSSubmissionSet")
	     {
		writeSQLQueryService("Sono nel caso XDSSubmissionSet");

		$RegistryPackage_id = $SQLResponse[$rr][0];

		$dom_ebXML_RegistryPackage = domxml_new_doc("1.0");
		## ROOT
		$dom_ebXML_RegistryPackage_root=$dom_ebXML_RegistryPackage->create_element("RegistryPackage");
		$dom_ebXML_RegistryPackage_root=$dom_ebXML_RegistryPackage->append_child($dom_ebXML_RegistryPackage_root);

		#### SETTO I NAMESPACES
		$dom_ebXML_RegistryPackage_root->set_namespace($ns_rim_path,$ns_rim);
		$dom_ebXML_RegistryPackage_root->add_namespace($ns_q_path,$ns_q);

		####OTTENGO DAL DB GLI ATTRIBUTI DI RegistryPackage
		$queryForRegistryPackageAttributes = "SELECT majorVersion,minorVersion,objectType,status FROM RegistryPackage WHERE RegistryPackage.id = '$RegistryPackage_id'";
		$RegistryPackageAttributes=query_select2($queryForRegistryPackageAttributes,$connessione);
		writeSQLQueryService($queryForRegistryPackageAttributes);

		//$RegistryPackage_isOpaque = $RegistryPackageAttributes[0]['isOpaque'];
		$RegistryPackage_majorVersion = $RegistryPackageAttributes[0][0];
		//$RegistryPackage_mimeType = $RegistryPackageAttributes[0]['mimeType'];
		$RegistryPackage_minorVersion = $RegistryPackageAttributes[0][1];
		$RegistryPackage_objectType = $RegistryPackageAttributes[0][2];
		$RegistryPackage_status = $RegistryPackageAttributes[0][3];

		$dom_ebXML_RegistryPackage_root->set_attribute("id",$RegistryPackage_id);
		//$dom_ebXML_RegistryPackage_root->set_attribute("isOpaque",$RegistryPackage_isOpaque);
		$dom_ebXML_RegistryPackage_root->set_attribute("majorVersion",$RegistryPackage_majorVersion);
		//$dom_ebXML_RegistryPackage_root->set_attribute("mimeType",$RegistryPackage_mimeType);
		$dom_ebXML_RegistryPackage_root->set_attribute("minorVersion",$RegistryPackage_minorVersion);
		//$dom_ebXML_RegistryPackage_root->set_attribute("objectType",$RegistryPackage_objectType);
		$dom_ebXML_RegistryPackage_root->set_attribute("status",$RegistryPackage_status);

		#### NAME
		$dom_ebXML_RegistryPackage_Name=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim_path,"Name");
		$dom_ebXML_RegistryPackage_Name=$dom_ebXML_RegistryPackage_root->append_child($dom_ebXML_RegistryPackage_Name);

		$queryForRegistryPackage_Name="SELECT charset,value,lang FROM Name WHERE Name.parent = '$RegistryPackage_id'";
		$Name_arr=query_select2($queryForRegistryPackage_Name,$connessione);
		writeSQLQueryService($queryForRegistryPackage_Name);

		$Name_charset = $Name_arr[0][0];
		$Name_value = $Name_arr[0][1];
		$Name_lang = $Name_arr[0][2];

		if(!empty($Name_arr))
		{
		$dom_ebXML_RegistryPackage_Name_LocalizedString=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim_path,"LocalizedString");
		$dom_ebXML_RegistryPackage_Name_LocalizedString=$dom_ebXML_RegistryPackage_Name->append_child($dom_ebXML_RegistryPackage_Name_LocalizedString);

		$dom_ebXML_RegistryPackage_Name_LocalizedString->set_attribute("charset",$Name_charset);
		$dom_ebXML_RegistryPackage_Name_LocalizedString->set_attribute("value",$Name_value);
		$dom_ebXML_RegistryPackage_Name_LocalizedString->set_attribute("xml:lang",$Name_lang);
		}

		#### DESCRIPTION
		$dom_ebXML_RegistryPackage_Description=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim_path,"Description");
		$dom_ebXML_RegistryPackage_Description=$dom_ebXML_RegistryPackage_root->append_child($dom_ebXML_RegistryPackage_Description);

		$queryForRegistryPackage_Description="SELECT charset,value,lang FROM Description WHERE Description.parent = '$RegistryPackage_id'";
		$Description_arr=query_select2($queryForRegistryPackage_Description,$connessione);
		writeSQLQueryService($queryForRegistryPackage_Description);

		$Description_charset = $Description_arr[0][0];
		$Description_value = $Description_arr[0][1];
		$Description_lang = $Description_arr[0][2];

		if(!empty($Description_arr) && $Description_value!="NOT DECLARED")
		{
		$dom_ebXML_RegistryPackage_Description_LocalizedString=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim_path,"LocalizedString");
		$dom_ebXML_RegistryPackage_Description_LocalizedString=$dom_ebXML_RegistryPackage_Description->append_child($dom_ebXML_RegistryPackage_Description_LocalizedString);

		$dom_ebXML_RegistryPackage_Description_LocalizedString->set_attribute("charset",$Description_charset);
		$dom_ebXML_RegistryPackage_Description_LocalizedString->set_attribute("value",$Description_value);
		$dom_ebXML_RegistryPackage_Description_LocalizedString->set_attribute("xml:lang",$Description_lang);
		}

		##### SLOT
		$select_Slots = "SELECT name,value FROM Slot WHERE Slot.parent = '$RegistryPackage_id'";
		$Slot_arr=query_select2($select_Slots,$connessione);
		writeSQLQueryService($select_Slots);
		$repeat = true;
		for($s=0;$s<count($Slot_arr);$s++)
		{
			$Slot = $Slot_arr[$s];
			$Slot_name = $Slot[0];

			if($Slot_name=="authorPerson" && $repeat)
			{
				$dom_ebXML_RegistryPackage_Slot=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim_path,"Slot");
				$dom_ebXML_RegistryPackage_Slot=$dom_ebXML_RegistryPackage_root->append_child($dom_ebXML_RegistryPackage_Slot);

				$select_authorPerson_Slots = "SELECT value FROM Slot WHERE Slot.parent = '$RegistryPackage_id' AND Slot.name = 'authorPerson'";
				$authorPerson_Slots=query_select2($select_authorPerson_Slots,$connessione);
				writeSQLQueryService($select_authorPerson_Slots);
				
				
				$dom_ebXML_RegistryPackage_Slot->set_attribute("name",$Slot_name);
				$dom_ebXML_RegistryPackage_Slot_ValueList=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim_path,"ValueList");
				$dom_ebXML_RegistryPackage_Slot_ValueList=$dom_ebXML_RegistryPackage_Slot->append_child($dom_ebXML_RegistryPackage_Slot_ValueList);

				for($r=0;$r<count($authorPerson_Slots);$r++)
				{
					$authorPerson_Slot=$authorPerson_Slots[$r];
					$Slot_value = $authorPerson_Slot[0];

					$dom_ebXML_RegistryPackage_Slot_ValueList_Value=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim_path,"Value");
					$dom_ebXML_RegistryPackage_Slot_ValueList_Value=$dom_ebXML_RegistryPackage_Slot_ValueList->append_child($dom_ebXML_RegistryPackage_Slot_ValueList_Value);

					$dom_ebXML_RegistryPackage_Slot_ValueList_Value->set_content($Slot_value);

				}//END OF for($r=0;$r<count($authorPerson_Slots);$r++)

				$repeat=false;

			}//END OF if($Slot_name=="authorPerson")
			if($Slot_name!="authorPerson")
			{
				$dom_ebXML_RegistryPackage_Slot=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim_path,"Slot");
				$dom_ebXML_RegistryPackage_Slot=$dom_ebXML_RegistryPackage_root->append_child($dom_ebXML_RegistryPackage_Slot);

				$dom_ebXML_RegistryPackage_Slot->set_attribute("name",$Slot_name);
			
				$dom_ebXML_RegistryPackage_Slot_ValueList=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim_path,"ValueList");
				$dom_ebXML_RegistryPackage_Slot_ValueList=$dom_ebXML_RegistryPackage_Slot->append_child($dom_ebXML_RegistryPackage_Slot_ValueList);

				$dom_ebXML_RegistryPackage_Slot_ValueList_Value=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim_path,"Value");
				$dom_ebXML_RegistryPackage_Slot_ValueList_Value=$dom_ebXML_RegistryPackage_Slot_ValueList->append_child($dom_ebXML_RegistryPackage_Slot_ValueList_Value);

				$Slot_value = $Slot[1];
				$dom_ebXML_RegistryPackage_Slot_ValueList_Value->set_content($Slot_value);

			}//END OF elseif($Slot_name!="authorPerson")

		}//END OF for($s=0;$s<count($slot_arr);$s++)

		#### GESTISCO IL CASO IN CUI DEVO RITORNARE OGGETTI COMPOSTI
		if($returnComposedObjects_a=="true")
		{
			#### CLASSIFICATION + EXTERNALIDENTIFIER + OBJECTREF

			###### NODI CLASSIFICATION
			writeSQLQueryService("\nCiclo su Classification di RegistryPackage");
			$get_RegistryPackage_Classification="SELECT classificationScheme,classificationNode,classifiedObject,id,nodeRepresentation,objectType FROM Classification WHERE Classification.classifiedObject = '$RegistryPackage_id'";
			$RegistryPackage_Classification_arr=query_select2($get_RegistryPackage_Classification,$connessione);
			writeSQLQueryService($get_RegistryPackage_Classification);

			#### CICLO SU TUTTI I NODI CLASSIFICATION
			for($t=0;$t<count($RegistryPackage_Classification_arr);$t++)
			{
				$RegistryPackage_Classification=$RegistryPackage_Classification_arr[$t];
				
				$dom_ebXML_RegistryPackage_Classification=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim_path,"Classification");
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
// 				$dom_ebXML_ObjectRef=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim_path,"ObjectRef");
// 				$dom_ebXML_ObjectRef=$dom_ebXML_RegistryPackage_root->append_child($dom_ebXML_ObjectRef);
// 
// 				#### SETTO I NAMESPACES
// 				$dom_ebXML_ObjectRef->add_namespace($ns_rim_path,$ns_rim);
// 				$dom_ebXML_ObjectRef->add_namespace($ns_q_path,$ns_q);
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
				$dom_ebXML_RegistryPackage_Classification->set_attribute("objectType",$RegistryPackage_Classification_objectType);
				#### NAME
				$dom_ebXML_Classification_Name=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim_path,"Name");
				$dom_ebXML_Classification_Name=$dom_ebXML_RegistryPackage_Classification->append_child($dom_ebXML_Classification_Name);

				$queryForClassification_Name="SELECT charset,value,lang FROM Name WHERE Name.parent = '$RegistryPackage_Classification_id'";
				$Name_arr=query_select2($queryForClassification_Name,$connessione);
				writeSQLQueryService($queryForClassification_Name);

				$Name_charset = $Name_arr[0][0];
				$Name_value = $Name_arr[0][1];
				$Name_lang = $Name_arr[0][2];

				if(!empty($Name_arr))
				{
				$dom_ebXML_Classification_Name_LocalizedString=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim_path,"LocalizedString");
				$dom_ebXML_Classification_Name_LocalizedString=$dom_ebXML_Classification_Name->append_child($dom_ebXML_Classification_Name_LocalizedString);

				$dom_ebXML_Classification_Name_LocalizedString->set_attribute("charset",$Name_charset);
				$dom_ebXML_Classification_Name_LocalizedString->set_attribute("value",$Name_value);
				$dom_ebXML_Classification_Name_LocalizedString->set_attribute("xml:lang",$Name_lang);
				}

				#### DESCRIPTION
				$dom_ebXML_Classification_Description=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim_path,"Description");
				$dom_ebXML_Classification_Description=$dom_ebXML_RegistryPackage_Classification->append_child($dom_ebXML_Classification_Description);

				$queryForClassification_Description="SELECT charset,value,lang FROM Description WHERE Description.parent = '$RegistryPackage_Classification_id'";
				$Description_arr=query_select2($queryForClassification_Description,$connessione);
				writeSQLQueryService($queryForClassification_Description);

				$Description_charset = $Description_arr[0][0];
				$Description_value = $Description_arr[0][1];
				$Description_lang = $Description_arr[0][2];

				if(!empty($Description_arr) && $Description_value!="NOT DECLARED")
				{
				$dom_ebXML_Classification_Description_LocalizedString=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim_path,"LocalizedString");
				$dom_ebXML_Classification_Description_LocalizedString=$dom_ebXML_Classification_Description->append_child($dom_ebXML_Classification_Description_LocalizedString);

				$dom_ebXML_Classification_Description_LocalizedString->set_attribute("charset",$Description_charset);
				$dom_ebXML_Classification_Description_LocalizedString->set_attribute("value",$Description_value);
				$dom_ebXML_Classification_Description_LocalizedString->set_attribute("xml:lang",$Description_lang);
				}

				#### SLOT

				for($sll=0;$sll<count($Slot_arr);$sll++){

				$dom_ebXML_Classification_Slot=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim_path,"Slot");
				$dom_ebXML_Classification_Slot=$dom_ebXML_RegistryPackage_Classification->append_child($dom_ebXML_Classification_Slot);

				$dom_ebXML_Classification_Slot_ValueList=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim_path,"ValueList");
				$dom_ebXML_Classification_Slot_ValueList=$dom_ebXML_Classification_Slot->append_child($dom_ebXML_Classification_Slot_ValueList);

				$dom_ebXML_Classification_Slot_ValueList_Value=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim_path,"Value");
				$dom_ebXML_Classification_Slot_ValueList_Value=$dom_ebXML_Classification_Slot_ValueList->append_child($dom_ebXML_Classification_Slot_ValueList_Value);

				$select_Slots = "SELECT name,value FROM Slot WHERE Slot.parent = '$RegistryPackage_Classification_id'";
				$Slot_arr=query_select2($select_Slots,$connessione);
				writeSQLQueryService($select_Slots);

				#### RICAVO LE INFO SUL NODO SLOT
				$Slot=$Slot_arr[$sll];
				$Slot_name = $Slot[0];
				$Slot_value = $Slot[1];

				$dom_ebXML_Classification_Slot->set_attribute("name",$Slot_name);
				$dom_ebXML_Classification_Slot_ValueList_Value->set_content($Slot_value);
				}

			}//END OF for($t=0;$t<count($RegistryPackage_Classification_arr);$t++)

			#### NODI EXTERNALIDENTIFIER
			writeSQLQueryService("\nCiclo su ExternalIdentifier di RegistryPackage");
			$get_RegistryPackage_ExternalIdentifier="SELECT identificationScheme,objectType,id,value FROM ExternalIdentifier WHERE ExternalIdentifier.registryObject = '$RegistryPackage_id'";
			$RegistryPackage_ExternalIdentifier_arr=query_select2($get_RegistryPackage_ExternalIdentifier,$connessione);
			writeSQLQueryService($get_RegistryPackage_ExternalIdentifier);

			#### CICLO SU TUTTI I NODI EXTERNALIDENTIFIER
			for($e=0;$e<count($RegistryPackage_ExternalIdentifier_arr);$e++)
			{
				$RegistryPackage_ExternalIdentifier=$RegistryPackage_ExternalIdentifier_arr[$e];
				
				$dom_ebXML_RegistryPackage_ExternalIdentifier=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim_path,"ExternalIdentifier");
				$dom_ebXML_RegistryPackage_ExternalIdentifier=$dom_ebXML_RegistryPackage_root->append_child($dom_ebXML_RegistryPackage_ExternalIdentifier);

				#### ATTRIBUTI DI EXTERNALIDENTIFIER
				$RegistryPackage_ExternalIdentifier_identificationScheme=$RegistryPackage_ExternalIdentifier[0];
				#### PREPARO PER OBJECTREF
		$RegistryPackage_ExternalIdentifier_identificationScheme_ARR_1[$RegistryPackage_ExternalIdentifier_identificationScheme]=$RegistryPackage_ExternalIdentifier_identificationScheme;
		$RegistryPackage_ExternalIdentifier_identificationScheme_ARR_2[]=$RegistryPackage_ExternalIdentifier_identificationScheme;
		########################

// 				#### DEVO DICHIARARE identificationScheme IN OBJECTREF
// 				$dom_ebXML_ObjectRef=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim_path,"ObjectRef");
// 				$dom_ebXML_ObjectRef=$dom_ebXML_RegistryPackage_root->append_child($dom_ebXML_ObjectRef);
// 
// 				#### SETTO I NAMESPACES
// 				$dom_ebXML_ObjectRef->add_namespace($ns_rim_path,$ns_rim);
// 				$dom_ebXML_ObjectRef->add_namespace($ns_q_path,$ns_q);
// 
// 				$dom_ebXML_ObjectRef->set_attribute("id",$RegistryPackage_ExternalIdentifier_identificationScheme);
// 				############# OBJECTREF

				$RegistryPackage_ExternalIdentifier_objectType=$RegistryPackage_ExternalIdentifier[1];
				$RegistryPackage_ExternalIdentifier_id=$RegistryPackage_ExternalIdentifier[2];
				$RegistryPackage_ExternalIdentifier_value=$RegistryPackage_ExternalIdentifier[3];

				$dom_ebXML_RegistryPackage_ExternalIdentifier->set_attribute("identificationScheme",$RegistryPackage_ExternalIdentifier_identificationScheme);
				$dom_ebXML_RegistryPackage_ExternalIdentifier->set_attribute("objectType",$RegistryPackage_ExternalIdentifier_objectType);
				$dom_ebXML_RegistryPackage_ExternalIdentifier->set_attribute("id",$RegistryPackage_ExternalIdentifier_id);
				$dom_ebXML_RegistryPackage_ExternalIdentifier->set_attribute("value",$RegistryPackage_ExternalIdentifier_value);

				#### NAME
				$dom_ebXML_ExternalIdentifier_Name=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim_path,"Name");
				$dom_ebXML_ExternalIdentifier_Name=$dom_ebXML_RegistryPackage_ExternalIdentifier->append_child($dom_ebXML_ExternalIdentifier_Name);

				$queryForExternalIdentifier_Name="SELECT charset,value,lang FROM Name WHERE Name.parent = '$RegistryPackage_ExternalIdentifier_id'";
				$Name_arr=query_select2($queryForExternalIdentifier_Name,$connessione);
				writeSQLQueryService($queryForExternalIdentifier_Name);

				$Name_charset = $Name_arr[0][0];
				$Name_value = $Name_arr[0][1];
				$Name_lang = $Name_arr[0][2];

				if(!empty($Name_arr))
				{
				$dom_ebXML_ExternalIdentifier_Name_LocalizedString=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim_path,"LocalizedString");
				$dom_ebXML_ExternalIdentifier_Name_LocalizedString=$dom_ebXML_ExternalIdentifier_Name->append_child($dom_ebXML_ExternalIdentifier_Name_LocalizedString);

				$dom_ebXML_ExternalIdentifier_Name_LocalizedString->set_attribute("charset",$Name_charset);
				$dom_ebXML_ExternalIdentifier_Name_LocalizedString->set_attribute("value",$Name_value);
				$dom_ebXML_ExternalIdentifier_Name_LocalizedString->set_attribute("xml:lang",$Name_lang);
				}

				#### DESCRIPTION
				$dom_ebXML_ExternalIdentifier_Description=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim_path,"Description");
				$dom_ebXML_ExternalIdentifier_Description=$dom_ebXML_RegistryPackage_ExternalIdentifier->append_child($dom_ebXML_ExternalIdentifier_Description);

				$queryForExternalIdentifier_Description="SELECT charset,value,lang FROM Description WHERE Description.parent = '$RegistryPackage_ExternalIdentifier_id'";
				$Description_arr=query_select2($queryForExternalIdentifier_Description,$connessione);
				writeSQLQueryService($queryForExternalIdentifier_Description);

				$Description_charset = $Description_arr[0][0];
				$Description_value = $Description_arr[0][1];
				$Description_lang = $Description_arr[0][2];

				if(!empty($Description_arr) && $Description_value!="NOT DECLARED")
				{
				$dom_ebXML_ExternalIdentifier_Description_LocalizedString=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim_path,"LocalizedString");
				$dom_ebXML_ExternalIdentifier_Description_LocalizedString=$dom_ebXML_ExternalIdentifier_Description->append_child($dom_ebXML_ExternalIdentifier_Description_LocalizedString);

				$dom_ebXML_ExternalIdentifier_Description_LocalizedString->set_attribute("charset",$Description_charset);
				$dom_ebXML_ExternalIdentifier_Description_LocalizedString->set_attribute("value",$Description_value);
				$dom_ebXML_ExternalIdentifier_Description_LocalizedString->set_attribute("xml:lang",$Description_lang);
				}

			}//END OF for($e=0;$e<count($RegistryPackage_ExternalIdentifier_arr);$e++)

		}//END OF if($returnComposedObjects_a=="true")

		#### CONCATENO LE STINGHE RISULTANTI
		$ebXML_Response_string = $ebXML_Response_string.substr($dom_ebXML_RegistryPackage->dump_mem(),21);

	     }//END OF if($objectType_code_from_RegistryPackage=="XDSSubmissionSet")

	     if($objectType_code_from_RegistryPackage=="XDSFolder")
	     {
		writeSQLQueryService("Sono nel caso XDSFolder");

		$fp=fopen("tmp/REQUEST_OBJECT","w+");
    		fwrite($fp,"REQUESTED_OBJECT =  $objectType_code_from_RegistryPackage");
		fclose($fp);

		$RegistryPackage_id = $SQLResponse[$rr][0];

		$dom_ebXML_RegistryPackage = domxml_new_doc("1.0");
		## ROOT
		$dom_ebXML_RegistryPackage_root=$dom_ebXML_RegistryPackage->create_element("RegistryPackage");
		$dom_ebXML_RegistryPackage_root=$dom_ebXML_RegistryPackage->append_child($dom_ebXML_RegistryPackage_root);

		#### SETTO I NAMESPACES
		$dom_ebXML_RegistryPackage_root->set_namespace($ns_rim_path,$ns_rim);
		$dom_ebXML_RegistryPackage_root->add_namespace($ns_q_path,$ns_q);

		####OTTENGO DAL DB GLI ATTRIBUTI DI RegistryPackage
		$queryForRegistryPackageAttributes = "SELECT majorVersion,minorVersion,objectType,status FROM RegistryPackage WHERE RegistryPackage.id = '$RegistryPackage_id'";
		$RegistryPackageAttributes=query_select2($queryForRegistryPackageAttributes,$connessione);
		writeSQLQueryService($queryForRegistryPackageAttributes);

		//$RegistryPackage_isOpaque = $RegistryPackageAttributes[0]['isOpaque'];
		$RegistryPackage_majorVersion = $RegistryPackageAttributes[0][0];
		//$RegistryPackage_mimeType = $RegistryPackageAttributes[0]['mimeType'];
		$RegistryPackage_minorVersion = $RegistryPackageAttributes[0][1];
		$RegistryPackage_objectType = $RegistryPackageAttributes[0][2];
		$RegistryPackage_status = $RegistryPackageAttributes[0][3];

		$dom_ebXML_RegistryPackage_root->set_attribute("id",$RegistryPackage_id);
		//$dom_ebXML_RegistryPackage_root->set_attribute("isOpaque",$RegistryPackage_isOpaque);
		$dom_ebXML_RegistryPackage_root->set_attribute("majorVersion",$RegistryPackage_majorVersion);
		//$dom_ebXML_RegistryPackage_root->set_attribute("mimeType",$RegistryPackage_mimeType);
		$dom_ebXML_RegistryPackage_root->set_attribute("minorVersion",$RegistryPackage_minorVersion);
		//$dom_ebXML_RegistryPackage_root->set_attribute("objectType",$RegistryPackage_objectType);
		$dom_ebXML_RegistryPackage_root->set_attribute("status",$RegistryPackage_status);

		#### NAME
		$dom_ebXML_RegistryPackage_Name=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim_path,"Name");
		$dom_ebXML_RegistryPackage_Name=$dom_ebXML_RegistryPackage_root->append_child($dom_ebXML_RegistryPackage_Name);

		$queryForRegistryPackage_Name="SELECT charset,value,lang FROM Name WHERE Name.parent = '$RegistryPackage_id'";
		$Name_arr=query_select2($queryForRegistryPackage_Name,$connessione);
		writeSQLQueryService($queryForRegistryPackage_Name);

		$Name_charset = $Name_arr[0][0];
		$Name_value = $Name_arr[0][1];
		$Name_lang = $Name_arr[0][2];

		if(!empty($Name_arr))
		{
		$dom_ebXML_RegistryPackage_Name_LocalizedString=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim_path,"LocalizedString");
		$dom_ebXML_RegistryPackage_Name_LocalizedString=$dom_ebXML_RegistryPackage_Name->append_child($dom_ebXML_RegistryPackage_Name_LocalizedString);

		$dom_ebXML_RegistryPackage_Name_LocalizedString->set_attribute("charset",$Name_charset);
		$dom_ebXML_RegistryPackage_Name_LocalizedString->set_attribute("value",$Name_value);
		$dom_ebXML_RegistryPackage_Name_LocalizedString->set_attribute("xml:lang",$Name_lang);
		}

		#### DESCRIPTION
		$dom_ebXML_RegistryPackage_Description=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim_path,"Description");
		$dom_ebXML_RegistryPackage_Description=$dom_ebXML_RegistryPackage_root->append_child($dom_ebXML_RegistryPackage_Description);

		$queryForRegistryPackage_Description="SELECT charset,value,lang FROM Description WHERE Description.parent = '$RegistryPackage_id'";
		$Description_arr=query_select2($queryForRegistryPackage_Description,$connessione);
		writeSQLQueryService($queryForRegistryPackage_Description);

		$Description_charset = $Description_arr[0][0];
		$Description_value = $Description_arr[0][1];
		$Description_lang = $Description_arr[0][2];

		if(!empty($Description_arr) && $Description_value!="NOT DECLARED")
		{
		$dom_ebXML_RegistryPackage_Description_LocalizedString=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim_path,"LocalizedString");
		$dom_ebXML_RegistryPackage_Description_LocalizedString=$dom_ebXML_RegistryPackage_Description->append_child($dom_ebXML_RegistryPackage_Description_LocalizedString);

		$dom_ebXML_RegistryPackage_Description_LocalizedString->set_attribute("charset",$Description_charset);
		$dom_ebXML_RegistryPackage_Description_LocalizedString->set_attribute("value",$Description_value);
		$dom_ebXML_RegistryPackage_Description_LocalizedString->set_attribute("xml:lang",$Description_lang);
		}

		##### SLOT
		$select_Slots = "SELECT name,value FROM Slot WHERE Slot.parent = '$RegistryPackage_id'";
		$Slot_arr=query_select2($select_Slots,$connessione);
		writeSQLQueryService($select_Slots);
		$repeat = true;
		if(!empty($Slot_arr))
		{
		for($s=0;$s<count($Slot_arr);$s++)
		{
			$Slot = $Slot_arr[$s];
			$Slot_name = $Slot[0];

			if($Slot_name=="authorPerson" && $repeat)
			{
				$dom_ebXML_RegistryPackage_Slot=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim_path,"Slot");
				$dom_ebXML_RegistryPackage_Slot=$dom_ebXML_RegistryPackage_root->append_child($dom_ebXML_RegistryPackage_Slot);

				$select_authorPerson_Slots = "SELECT value FROM Slot WHERE Slot.parent = '$RegistryPackage_id' AND Slot.name = 'authorPerson'";
				$authorPerson_Slots=query_select2($select_authorPerson_Slots,$connessione);
				writeSQLQueryService($select_authorPerson_Slots);
				
				$dom_ebXML_RegistryPackage_Slot->set_attribute("name",$Slot_name);
				$dom_ebXML_RegistryPackage_Slot_ValueList=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim_path,"ValueList");
				$dom_ebXML_RegistryPackage_Slot_ValueList=$dom_ebXML_RegistryPackage_Slot->append_child($dom_ebXML_RegistryPackage_Slot_ValueList);

				for($r=0;$r<count($authorPerson_Slots);$r++)
				{
					$authorPerson_Slot=$authorPerson_Slots[$r];
					$Slot_value = $authorPerson_Slot[0];

					$dom_ebXML_RegistryPackage_Slot_ValueList_Value=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim_path,"Value");
					$dom_ebXML_RegistryPackage_Slot_ValueList_Value=$dom_ebXML_RegistryPackage_Slot_ValueList->append_child($dom_ebXML_RegistryPackage_Slot_ValueList_Value);

					$dom_ebXML_RegistryPackage_Slot_ValueList_Value->set_content($Slot_value);

				}//END OF for($r=0;$r<count($authorPerson_Slots);$r++)

				$repeat=false;

			}//END OF if($Slot_name=="authorPerson")
			if($Slot_name!="authorPerson")
			{
				$dom_ebXML_RegistryPackage_Slot=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim_path,"Slot");
				$dom_ebXML_RegistryPackage_Slot=$dom_ebXML_RegistryPackage_root->append_child($dom_ebXML_RegistryPackage_Slot);

				$dom_ebXML_RegistryPackage_Slot->set_attribute("name",$Slot_name);
			
				$dom_ebXML_RegistryPackage_Slot_ValueList=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim_path,"ValueList");
				$dom_ebXML_RegistryPackage_Slot_ValueList=$dom_ebXML_RegistryPackage_Slot->append_child($dom_ebXML_RegistryPackage_Slot_ValueList);

				$dom_ebXML_RegistryPackage_Slot_ValueList_Value=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim_path,"Value");
				$dom_ebXML_RegistryPackage_Slot_ValueList_Value=$dom_ebXML_RegistryPackage_Slot_ValueList->append_child($dom_ebXML_RegistryPackage_Slot_ValueList_Value);

				$Slot_value = $Slot[1];
				$dom_ebXML_RegistryPackage_Slot_ValueList_Value->set_content($Slot_value);

			}//END OF if($Slot_name!="authorPerson")

		}//END OF for($s=0;$s<count($slot_arr);$s++)

		}//END OF if(!empty($Slot_arr))

		#### GESTISCO IL CASO IN CUI DEVO RITORNARE OGGETTI COMPOSTI
		if($returnComposedObjects_a=="true")
		{
			#### CLASSIFICATION + EXTERNALIDENTIFIER + OBJECTREF

			###### NODI CLASSIFICATION
			$get_RegistryPackage_Classification="SELECT classificationScheme,classificationNode,classifiedObject,id,nodeRepresentation,objectType FROM Classification WHERE Classification.classifiedObject = '$RegistryPackage_id'";
			$RegistryPackage_Classification_arr=query_select2($get_RegistryPackage_Classification,$connessione);
			writeSQLQueryService($get_RegistryPackage_Classification);

			if(!empty($RegistryPackage_Classification_arr))
			{
			#### CICLO SU TUTTI I NODI CLASSIFICATION
			for($t=0;$t<count($RegistryPackage_Classification_arr);$t++)
			{
				$RegistryPackage_Classification=$RegistryPackage_Classification_arr[$t];
				
				$dom_ebXML_RegistryPackage_Classification=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim_path,"Classification");
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
// 				$dom_ebXML_ObjectRef=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim_path,"ObjectRef");
// 				$dom_ebXML_ObjectRef=$dom_ebXML_RegistryPackage_root->append_child($dom_ebXML_ObjectRef);
// 
// 				#### SETTO I NAMESPACES
// 				$dom_ebXML_ObjectRef->add_namespace($ns_rim_path,$ns_rim);
// 				$dom_ebXML_ObjectRef->add_namespace($ns_q_path,$ns_q);
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
				$dom_ebXML_RegistryPackage_Classification->set_attribute("objectType",$RegistryPackage_Classification_objectType);
				#### NAME
				$dom_ebXML_Classification_Name=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim_path,"Name");
				$dom_ebXML_Classification_Name=$dom_ebXML_RegistryPackage_Classification->append_child($dom_ebXML_Classification_Name);

				$queryForClassification_Name="SELECT charset,value,lang FROM Name WHERE Name.parent = '$RegistryPackage_Classification_id'";
				$Name_arr=query_select2($queryForClassification_Name,$connessione);
				writeSQLQueryService($queryForClassification_Name);

				$Name_charset = $Name_arr[0][0];
				$Name_value = $Name_arr[0][1];
				$Name_lang = $Name_arr[0][2];

				if(!empty($Name_arr))
				{
				$dom_ebXML_Classification_Name_LocalizedString=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim_path,"LocalizedString");
				$dom_ebXML_Classification_Name_LocalizedString=$dom_ebXML_Classification_Name->append_child($dom_ebXML_Classification_Name_LocalizedString);

				$dom_ebXML_Classification_Name_LocalizedString->set_attribute("charset",$Name_charset);
				$dom_ebXML_Classification_Name_LocalizedString->set_attribute("value",$Name_value);
				$dom_ebXML_Classification_Name_LocalizedString->set_attribute("xml:lang",$Name_lang);
				}

				#### DESCRIPTION
				$dom_ebXML_Classification_Description=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim_path,"Description");
				$dom_ebXML_Classification_Description=$dom_ebXML_RegistryPackage_Classification->append_child($dom_ebXML_Classification_Description);

				$queryForClassification_Description="SELECT charset,value,lang FROM Description WHERE Description.parent = '$RegistryPackage_Classification_id'";
				$Description_arr=query_select2($queryForClassification_Description,$connessione);
				writeSQLQueryService($queryForClassification_Description);

				$Description_charset = $Description_arr[0][0];
				$Description_value = $Description_arr[0][1];
				$Description_lang = $Description_arr[0][2];

				if(!empty($Description_arr) && $Description_value!="NOT DECLARED")
				{
				$dom_ebXML_Classification_Description_LocalizedString=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim_path,"LocalizedString");
				$dom_ebXML_Classification_Description_LocalizedString=$dom_ebXML_Classification_Description->append_child($dom_ebXML_Classification_Description_LocalizedString);

				$dom_ebXML_Classification_Description_LocalizedString->set_attribute("charset",$Description_charset);
				$dom_ebXML_Classification_Description_LocalizedString->set_attribute("value",$Description_value);
				$dom_ebXML_Classification_Description_LocalizedString->set_attribute("xml:lang",$Description_lang);
				}

				#### SLOT
				$dom_ebXML_Classification_Slot=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim_path,"Slot");
				$dom_ebXML_Classification_Slot=$dom_ebXML_RegistryPackage_Classification->append_child($dom_ebXML_Classification_Slot);

				$dom_ebXML_Classification_Slot_ValueList=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim_path,"ValueList");
				$dom_ebXML_Classification_Slot_ValueList=$dom_ebXML_Classification_Slot->append_child($dom_ebXML_Classification_Slot_ValueList);

				$dom_ebXML_Classification_Slot_ValueList_Value=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim_path,"Value");
				$dom_ebXML_Classification_Slot_ValueList_Value=$dom_ebXML_Classification_Slot_ValueList->append_child($dom_ebXML_Classification_Slot_ValueList_Value);

				$select_Slots = "SELECT name,value FROM Slot WHERE Slot.parent = '$RegistryPackage_Classification_id'";
				$Slot_arr=query_select2($select_Slots,$connessione);
				writeSQLQueryService($select_Slots);

				#### RICAVO LE INFO SUL NODO SLOT
				$Slot=$Slot_arr[0];
				$Slot_name = $Slot[0];
				$Slot_value = $Slot[1];

				$dom_ebXML_Classification_Slot->set_attribute("name",$Slot_name);
				$dom_ebXML_Classification_Slot_ValueList_Value->set_content($Slot_value);

			}//END OF for($t=0;$t<count($RegistryPackage_Classification_arr);$t++)

			}//END OF if(!empty($RegistryPackage_Classification_arr))

			#### NODI EXTERNALIDENTIFIER
			$get_RegistryPackage_ExternalIdentifier="SELECT identificationScheme,objectType,id,value FROM ExternalIdentifier WHERE ExternalIdentifier.registryObject = '$RegistryPackage_id'";
			$RegistryPackage_ExternalIdentifier_arr=query_select2($get_RegistryPackage_ExternalIdentifier,$connessione);
			writeSQLQueryService($get_RegistryPackage_ExternalIdentifier);

			#### CICLO SU TUTTI I NODI EXTERNALIDENTIFIER
			for($e=0;$e<count($RegistryPackage_ExternalIdentifier_arr);$e++)
			{
				$RegistryPackage_ExternalIdentifier=$RegistryPackage_ExternalIdentifier_arr[$e];
				
				$dom_ebXML_RegistryPackage_ExternalIdentifier=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim_path,"ExternalIdentifier");
				$dom_ebXML_RegistryPackage_ExternalIdentifier=$dom_ebXML_RegistryPackage_root->append_child($dom_ebXML_RegistryPackage_ExternalIdentifier);

				#### ATTRIBUTI DI EXTERNALIDENTIFIER
				$RegistryPackage_ExternalIdentifier_identificationScheme=$RegistryPackage_ExternalIdentifier[0];
				#### PREPARO PER OBJECTREF
		$RegistryPackage_ExternalIdentifier_identificationScheme_ARR_1[$RegistryPackage_ExternalIdentifier_identificationScheme]=$RegistryPackage_ExternalIdentifier_identificationScheme;
		$RegistryPackage_ExternalIdentifier_identificationScheme_ARR_2[]=$RegistryPackage_ExternalIdentifier_identificationScheme;
		########################

// 				#### DEVO DICHIARARE identificationScheme IN OBJECTREF
// 				$dom_ebXML_ObjectRef=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim_path,"ObjectRef");
// 				$dom_ebXML_ObjectRef=$dom_ebXML_RegistryPackage_root->append_child($dom_ebXML_ObjectRef);
// 
// 				#### SETTO I NAMESPACES
// 				$dom_ebXML_ObjectRef->add_namespace($ns_rim_path,$ns_rim);
// 				$dom_ebXML_ObjectRef->add_namespace($ns_q_path,$ns_q);
// 
// 				$dom_ebXML_ObjectRef->set_attribute("id",$RegistryPackage_ExternalIdentifier_identificationScheme);
// 				############# OBJECTREF

				$RegistryPackage_ExternalIdentifier_objectType=$RegistryPackage_ExternalIdentifier[1];
				$RegistryPackage_ExternalIdentifier_id=$RegistryPackage_ExternalIdentifier[2];
				$RegistryPackage_ExternalIdentifier_value=$RegistryPackage_ExternalIdentifier[3];

				$dom_ebXML_RegistryPackage_ExternalIdentifier->set_attribute("identificationScheme",$RegistryPackage_ExternalIdentifier_identificationScheme);
				$dom_ebXML_RegistryPackage_ExternalIdentifier->set_attribute("objectType",$RegistryPackage_ExternalIdentifier_objectType);
				$dom_ebXML_RegistryPackage_ExternalIdentifier->set_attribute("id",$RegistryPackage_ExternalIdentifier_id);
				$dom_ebXML_RegistryPackage_ExternalIdentifier->set_attribute("value",$RegistryPackage_ExternalIdentifier_value);

				#### NAME
				$dom_ebXML_ExternalIdentifier_Name=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim_path,"Name");
				$dom_ebXML_ExternalIdentifier_Name=$dom_ebXML_RegistryPackage_ExternalIdentifier->append_child($dom_ebXML_ExternalIdentifier_Name);

				$queryForExternalIdentifier_Name="SELECT charset,value,lang FROM Name WHERE Name.parent = '$RegistryPackage_ExternalIdentifier_id'";
				$Name_arr=query_select2($queryForExternalIdentifier_Name,$connessione);
				writeSQLQueryService($queryForExternalIdentifier_Name);

				$Name_charset = $Name_arr[0][0];
				$Name_value = $Name_arr[0][1];
				$Name_lang = $Name_arr[0][2];

				if(!empty($Name_arr))
				{
				$dom_ebXML_ExternalIdentifier_Name_LocalizedString=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim_path,"LocalizedString");
				$dom_ebXML_ExternalIdentifier_Name_LocalizedString=$dom_ebXML_ExternalIdentifier_Name->append_child($dom_ebXML_ExternalIdentifier_Name_LocalizedString);

				$dom_ebXML_ExternalIdentifier_Name_LocalizedString->set_attribute("charset",$Name_charset);
				$dom_ebXML_ExternalIdentifier_Name_LocalizedString->set_attribute("value",$Name_value);
				$dom_ebXML_ExternalIdentifier_Name_LocalizedString->set_attribute("xml:lang",$Name_lang);
				}

				#### DESCRIPTION
				$dom_ebXML_ExternalIdentifier_Description=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim_path,"Description");
				$dom_ebXML_ExternalIdentifier_Description=$dom_ebXML_RegistryPackage_ExternalIdentifier->append_child($dom_ebXML_ExternalIdentifier_Description);

				$queryForExternalIdentifier_Description="SELECT charset,value,lang FROM Description WHERE Description.parent = '$RegistryPackage_ExternalIdentifier_id'";
				$Description_arr=query_select2($queryForExternalIdentifier_Description,$connessione);
				writeSQLQueryService($queryForExternalIdentifier_Description);

				$Description_charset = $Description_arr[0][0];
				$Description_value = $Description_arr[0][1];
				$Description_lang = $Description_arr[0][2];

				if(!empty($Description_arr) && $Description_value!="NOT DECLARED")
				{
				$dom_ebXML_ExternalIdentifier_Description_LocalizedString=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim_path,"LocalizedString");
				$dom_ebXML_ExternalIdentifier_Description_LocalizedString=$dom_ebXML_ExternalIdentifier_Description->append_child($dom_ebXML_ExternalIdentifier_Description_LocalizedString);

				$dom_ebXML_ExternalIdentifier_Description_LocalizedString->set_attribute("charset",$Description_charset);
				$dom_ebXML_ExternalIdentifier_Description_LocalizedString->set_attribute("value",$Description_value);
				$dom_ebXML_ExternalIdentifier_Description_LocalizedString->set_attribute("xml:lang",$Description_lang);
				}

			}//END OF for($e=0;$e<count($RegistryPackage_ExternalIdentifier_arr);$e++)

		}//END OF if($returnComposedObjects_a=="true")

		#### CONCATENO LE STINGHE RISULTANTI
		$ebXML_Response_string = $ebXML_Response_string.substr($dom_ebXML_RegistryPackage->dump_mem(),21);

	     }//END OF if($objectType_code_from_RegistryPackage=="XDSFolder")

	     ##### ASSOCIATION
	     if($objectType_from_Association=="Association")
	     {
		writeSQLQueryService("Sono nel caso Association");

		$Association_id = $SQLResponse[$rr][0];

		$dom_ebXML_Association = domxml_new_doc("1.0");
		## ROOT
		$dom_ebXML_Association_root=$dom_ebXML_Association->create_element("Association");
		$dom_ebXML_Association_root=$dom_ebXML_Association->append_child($dom_ebXML_Association_root);

		#### SETTO I NAMESPACES
		$dom_ebXML_Association_root->set_namespace($ns_rim_path,$ns_rim);
		$dom_ebXML_Association_root->add_namespace($ns_q_path,$ns_q);

		####OTTENGO DAL DB GLI ATTRIBUTI DI Association
		$queryForAssociationAttributes = "SELECT associationType,objectType,sourceObject,targetObject FROM Association WHERE Association.id = '$Association_id'";
		$AssociationAttributes=query_select2($queryForAssociationAttributes,$connessione);
		writeSQLQueryService($queryForAssociationAttributes);

		$Association_associationType = $AssociationAttributes[0][0];
		$Association_objectType = $AssociationAttributes[0][1];
		$Association_sourceObject = $AssociationAttributes[0][2];
		$Association_targetObject = $AssociationAttributes[0][3];

		$dom_ebXML_Association_root->set_attribute("id",$Association_id);
		$dom_ebXML_Association_root->set_attribute("associationType",$Association_associationType);
		$dom_ebXML_Association_root->set_attribute("objectType",$Association_objectType);
		$dom_ebXML_Association_root->set_attribute("sourceObject",$Association_sourceObject);
		$dom_ebXML_Association_root->set_attribute("targetObject",$Association_targetObject);

		#### PREPARO PER OBJECTREF
		$Association_sourceObject_ARR_1[$Association_sourceObject]=$Association_sourceObject;
		$Association_sourceObject_ARR_2[]=$Association_sourceObject;
		$Association_targetObject_ARR_1[$Association_targetObject]=$Association_targetObject;
		$Association_targetObject_ARR_2[]=$Association_targetObject;
		##################################################

		#### NAME
		$dom_ebXML_Association_Name=$dom_ebXML_Association->create_element_ns($ns_rim_path,"Name");
		$dom_ebXML_Association_Name=$dom_ebXML_Association_root->append_child($dom_ebXML_Association_Name);

		$queryForAssociation_Name="SELECT charset,value,lang FROM Name WHERE Name.parent = '$Association_id'";
		$Name_arr=query_select2($queryForAssociation_Name,$connessione);
		writeSQLQueryService($queryForAssociation_Name);

		$Name_charset = $Name_arr[0][0];
		$Name_value = $Name_arr[0][1];
		$Name_lang = $Name_arr[0][2];

		if(!empty($Name_arr))
		{
		$dom_ebXML_Association_Name_LocalizedString=$dom_ebXML_Association->create_element_ns($ns_rim_path,"LocalizedString");
		$dom_ebXML_Association_Name_LocalizedString=$dom_ebXML_Association_Name->append_child($dom_ebXML_Association_Name_LocalizedString);

		$dom_ebXML_Association_Name_LocalizedString->set_attribute("charset",$Name_charset);
		$dom_ebXML_Association_Name_LocalizedString->set_attribute("value",$Name_value);
		$dom_ebXML_Association_Name_LocalizedString->set_attribute("xml:lang",$Name_lang);
		}

		#### DESCRIPTION
		$dom_ebXML_Association_Description=$dom_ebXML_Association->create_element_ns($ns_rim_path,"Description");
		$dom_ebXML_Association_Description=$dom_ebXML_Association_root->append_child($dom_ebXML_Association_Description);

		$queryForAssociation_Description="SELECT charset,value,lang FROM Description WHERE Description.parent = '$Association_id'";
		$Description_arr=query_select2($queryForAssociation_Description,$connessione);
		writeSQLQueryService($queryForAssociation_Description);

		$Description_charset = $Description_arr[0][0];
		$Description_value = $Description_arr[0][1];
		$Description_lang = $Description_arr[0][2];

		if(!empty($Description_arr) && $Description_value!="NOT DECLARED")
		{
		$dom_ebXML_Association_Description_LocalizedString=$dom_ebXML_Association->create_element_ns($ns_rim_path,"LocalizedString");
		$dom_ebXML_Association_Description_LocalizedString=$dom_ebXML_Association_Description->append_child($dom_ebXML_Association_Description_LocalizedString);

		$dom_ebXML_Association_Description_LocalizedString->set_attribute("charset",$Description_charset);
		$dom_ebXML_Association_Description_LocalizedString->set_attribute("value",$Description_value);
		$dom_ebXML_Association_Description_LocalizedString->set_attribute("xml:lang",$Description_lang);
		}

		##### SLOT
		$select_Slots = "SELECT name,value FROM Slot WHERE Slot.parent = '$Association_id'";
		$Slot_arr=query_select2($select_Slots,$connessione);
		writeSQLQueryService($select_Slots);
		$repeat = true;
		for($s=0;$s<count($Slot_arr);$s++)
		{
			$Slot = $Slot_arr[$s];
			$Slot_name = $Slot[0];

			$dom_ebXML_Association_Slot=$dom_ebXML_Association->create_element_ns($ns_rim_path,"Slot");
			$dom_ebXML_Association_Slot=$dom_ebXML_Association_root->append_child($dom_ebXML_Association_Slot);

			$dom_ebXML_Association_Slot->set_attribute("name",$Slot_name);
			
			$dom_ebXML_Association_Slot_ValueList=$dom_ebXML_Association->create_element_ns($ns_rim_path,"ValueList");
			$dom_ebXML_Association_Slot_ValueList=$dom_ebXML_Association_Slot->append_child($dom_ebXML_Association_Slot_ValueList);

			$dom_ebXML_Association_Slot_ValueList_Value=$dom_ebXML_Association->create_element_ns($ns_rim_path,"Value");
			$dom_ebXML_Association_Slot_ValueList_Value=$dom_ebXML_Association_Slot_ValueList->append_child($dom_ebXML_Association_Slot_ValueList_Value);

			$Slot_value = $Slot[1];
			$dom_ebXML_Association_Slot_ValueList_Value->set_content($Slot_value);

		}//END OF for($s=0;$s<count($Slot_arr);$s++)

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
			$dom_ebXML_ObjectRef_root->set_namespace($ns_rim_path,$ns_rim);
			$dom_ebXML_ObjectRef_root->add_namespace($ns_q_path,$ns_q);

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
			$dom_ebXML_ObjectRef_root->set_namespace($ns_rim_path,$ns_rim);
			$dom_ebXML_ObjectRef_root->add_namespace($ns_q_path,$ns_q);

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
			$dom_ebXML_ObjectRef_root->set_namespace($ns_rim_path,$ns_rim);
			$dom_ebXML_ObjectRef_root->add_namespace($ns_q_path,$ns_q);

			$dom_ebXML_ObjectRef_root->set_attribute("id",$identificationScheme);
			
			#### CONCATENO LE STRINGHE RISULTANTI
			$ebXML_Response_string = $ebXML_Response_string.substr($dom_ebXML_ObjectRef->dump_mem(),21);	

		}#### identificationScheme

	}##### EXTRINSICOBJECT

	$fp= fopen("tmp/CONTROLLO_OBJECTREF", "w+");
    	fwrite($fp,"RegistryPackage_Classification_classificationScheme_ARR_1 = ".count($RegistryPackage_Classification_classificationScheme_ARR_1)."  RegistryPackage_Classification_classificationNode_ARR_1 = ".count($RegistryPackage_Classification_classificationNode_ARR_1)."   RegistryPackage_ExternalIdentifier_identificationScheme_ARR_1 = ".count($RegistryPackage_ExternalIdentifier_identificationScheme_ARR_1));
	fclose($fp);

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
			$dom_ebXML_ObjectRef_root->set_namespace($ns_rim_path,$ns_rim);
			$dom_ebXML_ObjectRef_root->add_namespace($ns_q_path,$ns_q);

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
			$dom_ebXML_ObjectRef_root->set_namespace($ns_rim_path,$ns_rim);
			$dom_ebXML_ObjectRef_root->add_namespace($ns_q_path,$ns_q);

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
			$dom_ebXML_ObjectRef_root->set_namespace($ns_rim_path,$ns_rim);
			$dom_ebXML_ObjectRef_root->add_namespace($ns_q_path,$ns_q);

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
			$dom_ebXML_ObjectRef_root->set_namespace($ns_rim_path,$ns_rim);
			$dom_ebXML_ObjectRef_root->add_namespace($ns_q_path,$ns_q);

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
			$dom_ebXML_ObjectRef_root->set_namespace($ns_rim_path,$ns_rim);
			$dom_ebXML_ObjectRef_root->add_namespace($ns_q_path,$ns_q);

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


######################################################################
#### METTO L'ebXML SU STRINGA
//$ebXML_Response_string = substr($dom_ebXML_Response->dump_mem(),21);

##### IMBUSTO PER LA SPEDIZIONE
$ebXML_Response_SOAPED_string = makeSoapedSuccessQueryResponse($ebXML_Response_string);

#####################################################################
#################### RISPONDO ALLA QUERY ############################

###### SCRIVO L'ebXML IMBUSTATO SOAP
$fp_ebxml_response_imbustato = fopen($tmpQueryService_path."ebxmlResponseSOAP.xml","wb+");
	fwrite($fp_ebxml_response_imbustato,$ebXML_Response_SOAPED_string);
fclose($fp_ebxml_response_imbustato);

############## PULISCO IL BUFFER DI USCITA
ob_get_clean();### OKKIO FONDAMENTALE!!!!!

################QUI CI VA IL RESPONSE

#### HEADERS
header("HTTP/1.1 200 OK");
$path_header = "Path: $www_REG_path";
if($http=="TLS")
{
	##### NEL CASO TLS AGGIUNGO LA DICITURA SECURE
	$path_header = $path_header."; Secure";
}
header($path_header);
header("Content-Type: text/xml;charset=UTF-8");
header("Content-Length: ".(string)filesize($tmpQueryService_path."ebxmlResponseSOAP.xml"));

##### FILE BODY
if($file = fopen($tmpQueryService_path."ebxmlResponseSOAP.xml",'rb'))
{
   	while((!feof($file)) && (connection_status()==0))
   	{
      		print(fread($file,1024*8));
      		flush();//NOTA BENE!!!!!!!!!

   	}//END OF while((!feof($file)) && (connection_status()==0))

   	fclose($file);

}//END OF if($file = fopen($tmpQueryService_path."ebxmlResponseSOAP.xml",'rb'))

//------------------------------------------------//
//SPEDISCO E PULISCO IL BUFFER DI USCITA
ob_end_flush();//OKKIO FONDAMENTALE!!!!!!!!



// ATNA Query
if($ATNA_active=='A'){
		$today = date("Y-m-d");
		$cur_hour = date("H:i:s");
		$datetime = $today."T".$cur_hour;
		require_once('./lib/syslog.php');
        $syslog = new Syslog();
$message_query ="<AuditMessage xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:noNamespaceSchemaLocation=\"healthcare-security-audit.xsd\">
		<EventIdentification EventActionCode=\"E\" EventDateTime=\"$datetime\" EventOutcomeIndicator=\"0\">
			<EventID code=\"110112\" codeSystemName=\"DCM\" displayName=\"Query\"/>
			<EventTypeCode code=\"ITI-16\" codeSystemName=\"IHE Transactions\" displayName=\"Registry SQL Query\"/>
		</EventIdentification>
		<AuditSourceIdentification AuditSourceID=\"MARIS REGISTRY\"/>
		<ActiveParticipant UserID=\"MARIS VIEW\" NetworkAccessPointTypeCode=\"2\" NetworkAccessPointID=\"".$_SERVER['REMOTE_ADDR']."\"  UserIsRequestor=\"true\">
        		<RoleIDCode code=\"110153\" codeSystemName=\"DCM\" displayName=\"Source\"/>
		</ActiveParticipant>
		<ActiveParticipant UserID=\"http://".$reg_host.":".$reg_port.$reg_path."\" NetworkAccessPointTypeCode=\"2\" NetworkAccessPointID=\"".$reg_host."\"  UserIsRequestor=\"false\">
        		<RoleIDCode code=\"110152\" codeSystemName=\"DCM\" displayName=\"Destination\"/>
    		</ActiveParticipant>
		<ParticipantObjectIdentification ParticipantObjectID=\"empty\" ParticipantObjectTypeCode=\"2\" ParticipantObjectTypeCodeRole=\"24\">
        		<ParticipantObjectIDTypeCode code=\"ITI-16\" codeSystemName=\"IHE Transactions\" displayName=\"Registry SQL Query\"/>
		<ParticipantObjectQuery>".base64_encode($SQLQuery_ESEGUITA)."</ParticipantObjectQuery>    	</ParticipantObjectIdentification>
		</AuditMessage>";


// ParticipantObjectID da TF deve essere vuoto ma non valida da syslog nist


		//manca la parte relativa al recupero del patientID.  <ParticipantObjectIdentification ParticipantObjectID=\"".trim($patient_id)."\" ParticipantObjectTypeCode=\"1\" ParticipantObjectTypeCodeRole=\"1\"><ParticipantObjectIDTypeCode code=\"2\"/></ParticipantObjectIdentification>
		$logSyslog=$syslog->Send($ATNA_host,$ATNA_port,$message_query);
		
	

} // Fine if($ATNA_active=='A')


################## END OF REGISTRY RESPONSE TO QUERY ####################


//Mi disconnetto dal DB
disconnectDB($connessione);

unset($_SESSION['tmp_path']);
unset($_SESSION['idfile']);
unset($_SESSION['logActive']);
unset($_SESSION['log_query_path']);

?>