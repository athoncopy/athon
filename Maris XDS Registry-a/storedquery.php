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
//Parte per calcolare i tempi di esecuzione
$mtime = microtime();
$mtime = explode(" ",$mtime);
$mtime = $mtime[1] + $mtime[0];
$starttime = $mtime;
##### CONFIGURAZIONE DEL REPOSITORY
include("REGISTRY_CONFIGURATION/REG_configuration.php");
#######################################

include($lib_path."utilities.php");
include("./lib/log.php");
include_once($lib_path."domxml-php4-to-php5.php");
include_once('reg_validation.php');
ob_clean();
$idfile = idrandom_file();

$_SESSION['tmp_path']=$tmp_path;
$_SESSION['idfile']=$idfile;
$_SESSION['logActive']=$logActive;
$_SESSION['log_path']=$log_path;
$_SESSION['tmpQueryService_path']=$tmpQueryService_path;

if(!is_dir($tmpQueryService_path)){
mkdir($tmpQueryService_path, 0777,true);
}

//PULISCO LA CACHE TEMPORANEA
//exec('rm -f '.$tmpQueryService_path."*");

//RECUPERO GLI HEADERS RICEVUTI DA APACHE
$headers = apache_request_headers();

//COPIO IN LOCALE TUTTI GLI HEADERS RICEVUTI
$fp_headers_received = fopen($tmpQueryService_path.$idfile."headers_received".$idfile, "w+");
foreach ($headers as $header => $value) 
{
   fwrite ($fp_headers_received, "$header = $value  \n");	
}
fclose($fp_headers_received);

//AdhocQueryRequest IMBUSTATO
$fp= fopen($tmpQueryService_path.$idfile."AdhocQueryRequest_imbustato_soap".$idfile, "w+");
    fwrite($fp,$HTTP_RAW_POST_DATA);//RICAVO DALLA VAR $HTTP_RAW_POST_DATA
fclose($fp);


$ebxml_imbustato_soap_STRING=$HTTP_RAW_POST_DATA;

	//SBUSTO	
//$ebxml_imbustato_soap_STRING = file_get_contents($tmpQueryService_path."AdhocQueryRequest_imbustato_soap");
$dom_XML_completo = domxml_open_mem($ebxml_imbustato_soap_STRING);


$root_completo = $dom_XML_completo->document_element();

//Ottengo Action
$Action_array = $root_completo->get_elements_by_tagname("Action");

if(count($Action_array)>0){
	$Action_node = $Action_array[0];
	$Action=$Action_node->get_content();
}
else 
{
$Action="";
}

//Ottengo MessageID
$MessageID_array = $root_completo->get_elements_by_tagname("MessageID");
if(count($MessageID_array)>0){
	$MessageID_node = $MessageID_array[0];
	$MessageID=$MessageID_node->get_content();
}
else 
{
	$MessageID="";
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



//SCRIVO L'AdhocQueryRequest SBUSTATO
$fp_AdhocQueryRequest = fopen($tmpQueryService_path.$idfile."AdhocQueryRequest".$idfile,"w+");
	fwrite($fp_AdhocQueryRequest,$ebxml_STRING);
fclose($fp_AdhocQueryRequest);



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
	$file_input=$tmpQueryService_path.$idfile."-SOAPED_failure_response-".$idfile;
	 $fp = fopen($file_input,"w+");
           fwrite($fp,$SOAPED_failure_response);
         fclose($fp);


	SendResponse($file_input);
	exit;


}


###### CONTROLLO SQL RICEVUTA


$SQLResponse=array();
$SQLResponse_array=array();

$fp_SQLResponse = fopen($tmpQueryService_path.$idfile."-SQLResponse-".$idfile,"a+");
fwrite($fp_SQLResponse,"RISPOSTA DAL DB:\n");

for($SQcount=0;$SQcount<$contaQuery;$SQcount++){
$SQLQuery = $SQLStoredQuery[$SQcount];
$controllo_query_array = controllaQuery($SQLQuery);
########################################################################
### ORA DEVO ESEGUIRE LA QUERY SUL DB DEL XDS_REGISTRY_QUERY REGISTRY 

################ RISPOSTA ALLA QUERY (ARRAY)
###METTO A POSTO EVENTUALI STRINGHE DI COMANDO
$SQLQuery_ESEGUITA=adjustQuery($SQLQuery);#### IMPORTANTE!!!
###SCRIVO LA QUERY CHE EFFETTIVAMENTE LANCIO A DB
$fp_SQLQuery = fopen($tmpQueryService_path.$idfile."-SQLQuery_ESEGUITA-".$idfile,"a+");
	fwrite($fp_SQLQuery,$SQLQuery_ESEGUITA."\n\r");
fclose($fp_SQLQuery);

###### ESEGUO LA QUERY
$SQLResponse_array = query_select($SQLQuery_ESEGUITA);

$SQLResponse=array_merge($SQLResponse,$SQLResponse_array);


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
	$failure_response = "[EMPTY RESULT] - SQL QUERY[  ".avoidHtmlEntitiesInterpretation($SQLQuery)." ] DID NOT GIVE ANY RESULTS IN THIS REGISTRY";
	
	### RESTITUISCE IL MESSAGGIO DI SUCCESS IN SOAP
	### ANCHE SE IL RISULTATO DELLA QUERY DA DB È VUOTO
    	$SOAPED_failure_response = makeSoapedSuccessStoredQueryResponse($Action,$MessageID,"");

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
		
		#### DOCUMENTENTRY
		$get_objectType_from_ExtrinsicObject="SELECT objectType FROM ExtrinsicObject WHERE ExtrinsicObject.id = '$id'";
		$objectType_from_ExtrinsicObject_arr=query_select($get_objectType_from_ExtrinsicObject);
		writeSQLQueryService($get_objectType_from_ExtrinsicObject);

		//$objectType_from_ExtrinsicObject=$objectType_from_ExtrinsicObject_arr[0]['objectType'];
		$objectType_from_ExtrinsicObject=$objectType_from_ExtrinsicObject_arr[0][0];
		$mappa_type="SELECT code FROM ClassificationNode WHERE ClassificationNode.id = '$objectType_from_ExtrinsicObject'";
		$objectType_code_from_ExtrinsicObject_arr=query_select($mappa_type);
		writeSQLQueryService($mappa_type);

		//$objectType_code_from_ExtrinsicObject=$objectType_code_from_ExtrinsicObject_arr[0]['code'];
		$objectType_code_from_ExtrinsicObject=$objectType_code_from_ExtrinsicObject_arr[0][0];

		##### SUBMISSIONSET
		$get_objectType_from_RegistryPackage="SELECT objectType FROM RegistryPackage WHERE RegistryPackage.id = '$id'";
		$objectType_from_RegistryPackage_arr=query_select($get_objectType_from_RegistryPackage);
		writeSQLQueryService($get_objectType_from_RegistryPackage);

		//$objectType_from_RegistryPackage=$objectType_from_RegistryPackage_arr[0]['objectType'];
		$objectType_from_RegistryPackage=$objectType_from_RegistryPackage_arr[0][0];
		$mappa_type="SELECT code FROM ClassificationNode WHERE ClassificationNode.id = '$objectType_from_RegistryPackage'";
		$objectType_code_from_RegistryPackage_arr=query_select($mappa_type);
		writeSQLQueryService($mappa_type);

		$objectType_code_from_RegistryPackage=$objectType_code_from_RegistryPackage_arr[0][0];

		##### ASSOCIATION
		$get_objectType_from_Association="SELECT objectType FROM Association WHERE Association.id = '$id'";
		$objectType_from_Association_arr=query_select($get_objectType_from_Association);
		writeSQLQueryService($get_objectType_from_Association);
		$objectType_from_Association=$objectType_from_Association_arr[0][0];

		############### FINE DISCRIMINO IL TIPO DI RISULTATO ##############

	     if($objectType_code_from_ExtrinsicObject=="XDSDocumentEntry")
	     {
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

		
		##### SLOT
		$select_Slots = "SELECT name,value FROM Slot WHERE Slot.parent = '$ExtrinsicObject_id'";
		$Slot_arr=query_select($select_Slots);
		writeSQLQueryService($select_Slots);
		$Slot_arr_EO=$Slot_arr;
		$repeat = true;
		$repeatURI = true; 
		for($s=0;$s<count($Slot_arr);$s++)
		{
			$Slot = $Slot_arr[$s];
			$Slot_name = $Slot[0];

			if($Slot_name=="sourcePatientInfo" && $repeat)
			{
				$dom_ebXML_ExtrinsicObject_Slot=$dom_ebXML_ExtrinsicObject->create_element_ns($ns_rim3_path,"Slot");
				$dom_ebXML_ExtrinsicObject_Slot=$dom_ebXML_ExtrinsicObject_root->append_child($dom_ebXML_ExtrinsicObject_Slot);

				$select_sourcePatientInfo_Slots = "SELECT value FROM Slot WHERE Slot.parent = '$ExtrinsicObject_id' AND Slot.name = 'sourcePatientInfo'";
				$sourcePatientInfo_Slots=query_select($select_sourcePatientInfo_Slots);
				writeSQLQueryService($select_sourcePatientInfo_Slots);
				
				$dom_ebXML_ExtrinsicObject_Slot->set_attribute("name",$Slot_name);
				$dom_ebXML_ExtrinsicObject_Slot_ValueList=$dom_ebXML_ExtrinsicObject->create_element_ns($ns_rim3_path,"ValueList");
				$dom_ebXML_ExtrinsicObject_Slot_ValueList=$dom_ebXML_ExtrinsicObject_Slot->append_child($dom_ebXML_ExtrinsicObject_Slot_ValueList);

				for($r=0;$r<count($sourcePatientInfo_Slots);$r++)
				{
					$sourcePatientInfo_Slot=$sourcePatientInfo_Slots[$r];
					$Slot_value = $sourcePatientInfo_Slot[0];

					$dom_ebXML_ExtrinsicObject_Slot_ValueList_Value=$dom_ebXML_ExtrinsicObject->create_element_ns($ns_rim3_path,"Value");
					$dom_ebXML_ExtrinsicObject_Slot_ValueList_Value=$dom_ebXML_ExtrinsicObject_Slot_ValueList->append_child($dom_ebXML_ExtrinsicObject_Slot_ValueList_Value);

					$dom_ebXML_ExtrinsicObject_Slot_ValueList_Value->set_content($Slot_value);

				}//END OF for($r=0;$r<count($sourcePatientInfo_Slots);$r++)

				$repeat=false;

			}//END OF if($Slot_name=="sourcePatientInfo")
			
			if($Slot_name=="URI" && $repeatURI)
			{
				$dom_ebXML_ExtrinsicObject_Slot=$dom_ebXML_ExtrinsicObject->create_element_ns($ns_rim3_path,"Slot");
				$dom_ebXML_ExtrinsicObject_Slot=$dom_ebXML_ExtrinsicObject_root->append_child($dom_ebXML_ExtrinsicObject_Slot);

				$select_sourcePatientInfo_Slots = "SELECT value FROM Slot WHERE Slot.parent = '$ExtrinsicObject_id' AND Slot.name = 'URI'";
				$sourcePatientInfo_Slots=query_select($select_sourcePatientInfo_Slots);
				writeSQLQueryService($sourcePatientInfo_Slots);
				
				$dom_ebXML_ExtrinsicObject_Slot->set_attribute("name",$Slot_name);
				$dom_ebXML_ExtrinsicObject_Slot_ValueList=$dom_ebXML_ExtrinsicObject->create_element_ns($ns_rim3_path,"ValueList");
				$dom_ebXML_ExtrinsicObject_Slot_ValueList=$dom_ebXML_ExtrinsicObject_Slot->append_child($dom_ebXML_ExtrinsicObject_Slot_ValueList);

				for($r=0;$r<count($sourcePatientInfo_Slots);$r++)
				{
					$sourcePatientInfo_Slot=$sourcePatientInfo_Slots[$r];
					$Slot_value = $sourcePatientInfo_Slot[0];

					$dom_ebXML_ExtrinsicObject_Slot_ValueList_Value=$dom_ebXML_ExtrinsicObject->create_element_ns($ns_rim3_path,"Value");
					$dom_ebXML_ExtrinsicObject_Slot_ValueList_Value=$dom_ebXML_ExtrinsicObject_Slot_ValueList->append_child($dom_ebXML_ExtrinsicObject_Slot_ValueList_Value);

					$dom_ebXML_ExtrinsicObject_Slot_ValueList_Value->set_content($Slot_value);

				}//END OF for($r=0;$r<count($sourcePatientInfo_Slots);$r++)

				$repeatURI=false;

			}//END OF if($Slot_name=="URI")

			if($Slot_name!="sourcePatientInfo" && $Slot_name!="URI")
			//if($Slot_name!="sourcePatientInfo")
			{
				$dom_ebXML_ExtrinsicObject_Slot=$dom_ebXML_ExtrinsicObject->create_element_ns($ns_rim3_path,"Slot");
				$dom_ebXML_ExtrinsicObject_Slot=$dom_ebXML_ExtrinsicObject_root->append_child($dom_ebXML_ExtrinsicObject_Slot);

				$dom_ebXML_ExtrinsicObject_Slot->set_attribute("name",$Slot_name);
			
				$dom_ebXML_ExtrinsicObject_Slot_ValueList=$dom_ebXML_ExtrinsicObject->create_element_ns($ns_rim3_path,"ValueList");
				$dom_ebXML_ExtrinsicObject_Slot_ValueList=$dom_ebXML_ExtrinsicObject_Slot->append_child($dom_ebXML_ExtrinsicObject_Slot_ValueList);

				$dom_ebXML_ExtrinsicObject_Slot_ValueList_Value=$dom_ebXML_ExtrinsicObject->create_element_ns($ns_rim3_path,"Value");
				$dom_ebXML_ExtrinsicObject_Slot_ValueList_Value=$dom_ebXML_ExtrinsicObject_Slot_ValueList->append_child($dom_ebXML_ExtrinsicObject_Slot_ValueList_Value);

				$Slot_value = $Slot[1];
				$dom_ebXML_ExtrinsicObject_Slot_ValueList_Value->set_content($Slot_value);

			}//END OF elseif($Slot_name!="sourcePatientInfo")

		}//END OF for($s=0;$s<count($slot_arr);$s++)

		#### NAME
		$dom_ebXML_ExtrinsicObject_Name=$dom_ebXML_ExtrinsicObject->create_element_ns($ns_rim3_path,"Name");
		$dom_ebXML_ExtrinsicObject_Name=$dom_ebXML_ExtrinsicObject_root->append_child($dom_ebXML_ExtrinsicObject_Name);

		$queryForExtrinsicObject_Name="SELECT charset,value,lang FROM Name WHERE Name.parent = '$ExtrinsicObject_id'";
		$Name_arr=query_select($queryForExtrinsicObject_Name);
		writeSQLQueryService($queryForExtrinsicObject_Name);

		$Name_charset = $Name_arr[0][0];
		$Name_value = $Name_arr[0][1];
		$Name_lang = $Name_arr[0][2];

		if(!empty($Name_arr))
		{
		$dom_ebXML_ExtrinsicObject_Name_LocalizedString=$dom_ebXML_ExtrinsicObject->create_element_ns($ns_rim3_path,"LocalizedString");
		$dom_ebXML_ExtrinsicObject_Name_LocalizedString=$dom_ebXML_ExtrinsicObject_Name->append_child($dom_ebXML_ExtrinsicObject_Name_LocalizedString);

		$dom_ebXML_ExtrinsicObject_Name_LocalizedString->set_attribute("charset",$Name_charset);
		$dom_ebXML_ExtrinsicObject_Name_LocalizedString->set_attribute("value",$Name_value);
		$dom_ebXML_ExtrinsicObject_Name_LocalizedString->set_attribute("xml:lang",$Name_lang);
		}

		#### DESCRIPTION
		$dom_ebXML_ExtrinsicObject_Description=$dom_ebXML_ExtrinsicObject->create_element_ns($ns_rim3_path,"Description");
		$dom_ebXML_ExtrinsicObject_Description=$dom_ebXML_ExtrinsicObject_root->append_child($dom_ebXML_ExtrinsicObject_Description);

		$queryForExtrinsicObject_Description="SELECT charset,value,lang FROM Description WHERE Description.parent = '$ExtrinsicObject_id'";
		$Description_arr=query_select($queryForExtrinsicObject_Description);
		writeSQLQueryService($queryForExtrinsicObject_Description);

		$Description_charset = $Description_arr[0][0];
		$Description_value = $Description_arr[0][1];
		$Description_lang = $Description_arr[0][2];

		if(!empty($Description_arr) && $Description_value!="NOT DECLARED")
		{
		$dom_ebXML_ExtrinsicObject_Description_LocalizedString=$dom_ebXML_ExtrinsicObject->create_element_ns($ns_rim3_path,"LocalizedString");
		$dom_ebXML_ExtrinsicObject_Description_LocalizedString=$dom_ebXML_ExtrinsicObject_Description->append_child($dom_ebXML_ExtrinsicObject_Description_LocalizedString);

		$dom_ebXML_ExtrinsicObject_Description_LocalizedString->set_attribute("charset",$Description_charset);
		$dom_ebXML_ExtrinsicObject_Description_LocalizedString->set_attribute("value",$Description_value);
		$dom_ebXML_ExtrinsicObject_Description_LocalizedString->set_attribute("xml:lang",$Description_lang);
		}


		#### GESTISCO IL CASO IN CUI DEVO RITORNARE OGGETTI COMPOSTI
		if($returnComposedObjects_a=="true")
		{
			#### CLASSIFICATION + EXTERNALIDENTIFIER + OBJECTREF

			###### NODI CLASSIFICATION
			$get_ExtrinsicObject_Classification="SELECT classificationScheme,classificationNode,classifiedObject,id,nodeRepresentation,objectType FROM Classification WHERE Classification.classifiedObject = '$ExtrinsicObject_id' AND Classification.nodeRepresentation != 'NULL'";
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
				

				#### SLOT
				writeSQLQueryService("Slot dentro ExtrinsicObject.Classification");
				$dom_ebXML_Classification_Slot=$dom_ebXML_ExtrinsicObject->create_element_ns($ns_rim3_path,"Slot");
				$dom_ebXML_Classification_Slot=$dom_ebXML_ExtrinsicObject_Classification->append_child($dom_ebXML_Classification_Slot);

				$dom_ebXML_Classification_Slot_ValueList=$dom_ebXML_ExtrinsicObject->create_element_ns($ns_rim3_path,"ValueList");
				$dom_ebXML_Classification_Slot_ValueList=$dom_ebXML_Classification_Slot->append_child($dom_ebXML_Classification_Slot_ValueList);

				$dom_ebXML_Classification_Slot_ValueList_Value=$dom_ebXML_ExtrinsicObject->create_element_ns($ns_rim3_path,"Value");
				$dom_ebXML_Classification_Slot_ValueList_Value=$dom_ebXML_Classification_Slot_ValueList->append_child($dom_ebXML_Classification_Slot_ValueList_Value);

				$select_Slots = "SELECT name,value FROM Slot WHERE Slot.parent = '$ExtrinsicObject_Classification_id'";
				$Slot_arr=query_select($select_Slots);
				writeSQLQueryService($select_Slots);

				#### RICAVO LE INFO SUL NODO SLOT
				$Slot=$Slot_arr[0];
				$Slot_name = $Slot[0];
				$Slot_value = $Slot[1];

				$dom_ebXML_Classification_Slot->set_attribute("name",$Slot_name);
				$dom_ebXML_Classification_Slot_ValueList_Value->set_content($Slot_value);



				#### NAME
				$dom_ebXML_Classification_Name=$dom_ebXML_ExtrinsicObject->create_element_ns($ns_rim3_path,"Name");
				$dom_ebXML_Classification_Name=$dom_ebXML_ExtrinsicObject_Classification->append_child($dom_ebXML_Classification_Name);

				$queryForClassification_Name="SELECT charset,value,lang FROM Name WHERE Name.parent = '$ExtrinsicObject_Classification_id'";
				$Name_arr=query_select($queryForClassification_Name);
				writeSQLQueryService($queryForClassification_Name);

				$Name_charset = $Name_arr[0][0];
				$Name_value = $Name_arr[0][1];
				$Name_lang = $Name_arr[0][2];

				if(!empty($Name_arr))
				{
				$dom_ebXML_Classification_Name_LocalizedString=$dom_ebXML_ExtrinsicObject->create_element_ns($ns_rim3_path,"LocalizedString");
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

				$Description_arr=query_select($queryForClassification_Description);
				writeSQLQueryService($queryForClassification_Description);

				$Description_charset = $Description_arr[0][0];
				$Description_value = $Description_arr[0][1];
				$Description_lang = $Description_arr[0][2];

				if(!empty($Description_arr) && $Description_value!="NOT DECLARED")
				{
				$dom_ebXML_Classification_Description=$dom_ebXML_ExtrinsicObject->create_element_ns($ns_rim3_path,"Description");
				$dom_ebXML_Classification_Description=$dom_ebXML_ExtrinsicObject_Classification->append_child($dom_ebXML_Classification_Description);

				$dom_ebXML_Classification_Description_LocalizedString=$dom_ebXML_ExtrinsicObject->create_element_ns($ns_rim3_path,"LocalizedString");
				$dom_ebXML_Classification_Description_LocalizedString=$dom_ebXML_Classification_Description->append_child($dom_ebXML_Classification_Description_LocalizedString);

				$dom_ebXML_Classification_Description_LocalizedString->set_attribute("charset",$Description_charset);
				$dom_ebXML_Classification_Description_LocalizedString->set_attribute("value",$Description_value);
				$dom_ebXML_Classification_Description_LocalizedString->set_attribute("xml:lang",$Description_lang);
				}

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

				#### NAME
				$dom_ebXML_ExternalIdentifier_Name=$dom_ebXML_ExtrinsicObject->create_element_ns($ns_rim3_path,"Name");
				$dom_ebXML_ExternalIdentifier_Name=$dom_ebXML_ExtrinsicObject_ExternalIdentifier->append_child($dom_ebXML_ExternalIdentifier_Name);

				$queryForExternalIdentifier_Name="SELECT charset,value,lang FROM Name WHERE Name.parent = '$ExtrinsicObject_ExternalIdentifier_id'";
				$Name_arr=query_select($queryForExternalIdentifier_Name);
				writeSQLQueryService($queryForExternalIdentifier_Name);

				$Name_charset = $Name_arr[0][0];
				$Name_value = $Name_arr[0][1];
				$Name_lang = $Name_arr[0][2];

				if(!empty($Name_arr))
				{
				$dom_ebXML_ExternalIdentifier_Name_LocalizedString=$dom_ebXML_ExtrinsicObject->create_element_ns($ns_rim3_path,"LocalizedString");
				$dom_ebXML_ExternalIdentifier_Name_LocalizedString=$dom_ebXML_ExternalIdentifier_Name->append_child($dom_ebXML_ExternalIdentifier_Name_LocalizedString);

				$dom_ebXML_ExternalIdentifier_Name_LocalizedString->set_attribute("charset",$Name_charset);
				$dom_ebXML_ExternalIdentifier_Name_LocalizedString->set_attribute("value",$Name_value);
				$dom_ebXML_ExternalIdentifier_Name_LocalizedString->set_attribute("xml:lang",$Name_lang);
				}

				#### DESCRIPTION
				$queryForExternalIdentifier_Description="SELECT charset,value,lang FROM Description WHERE Description.parent = '$ExtrinsicObject_ExternalIdentifier_id'";
				$Description_arr=query_select($queryForExternalIdentifier_Description);
				writeSQLQueryService($queryForExternalIdentifier_Description);

				$Description_charset = $Description_arr[0][0];
				$Description_value = $Description_arr[0][1];
				$Description_lang = $Description_arr[0][2];

				if(!empty($Description_arr) && $Description_value!="NOT DECLARED")
				{
				$dom_ebXML_ExternalIdentifier_Description=$dom_ebXML_ExtrinsicObject->create_element_ns($ns_rim3_path,"Description");
				$dom_ebXML_ExternalIdentifier_Description=$dom_ebXML_ExtrinsicObject_Classification->append_child($dom_ebXML_ExternalIdentifier_Description);

				$dom_ebXML_ExternalIdentifier_Description_LocalizedString=$dom_ebXML_ExtrinsicObject->create_element_ns($ns_rim3_path,"LocalizedString");
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

		
		##### SLOT
		$select_Slots = "SELECT name,value FROM Slot WHERE Slot.parent = '$RegistryPackage_id'";
		$Slot_arr=query_select($select_Slots);
		writeSQLQueryService($select_Slots);
		$repeat = true;
		for($s=0;$s<count($Slot_arr);$s++)
		{
			$Slot = $Slot_arr[$s];
			$Slot_name = $Slot[0];

			if($Slot_name=="authorPerson" && $repeat)
			{
				$dom_ebXML_RegistryPackage_Slot=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim3_path,"Slot");
				$dom_ebXML_RegistryPackage_Slot=$dom_ebXML_RegistryPackage_root->append_child($dom_ebXML_RegistryPackage_Slot);

				$select_authorPerson_Slots = "SELECT value FROM Slot WHERE Slot.parent = '$RegistryPackage_id' AND Slot.name = 'authorPerson'";
				$authorPerson_Slots=query_select($select_authorPerson_Slots);
				writeSQLQueryService($select_authorPerson_Slots);
				
				
				$dom_ebXML_RegistryPackage_Slot->set_attribute("name",$Slot_name);
				$dom_ebXML_RegistryPackage_Slot_ValueList=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim3_path,"ValueList");
				$dom_ebXML_RegistryPackage_Slot_ValueList=$dom_ebXML_RegistryPackage_Slot->append_child($dom_ebXML_RegistryPackage_Slot_ValueList);

				for($r=0;$r<count($authorPerson_Slots);$r++)
				{
					$authorPerson_Slot=$authorPerson_Slots[$r];
					$Slot_value = $authorPerson_Slot[0];

					$dom_ebXML_RegistryPackage_Slot_ValueList_Value=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim3_path,"Value");
					$dom_ebXML_RegistryPackage_Slot_ValueList_Value=$dom_ebXML_RegistryPackage_Slot_ValueList->append_child($dom_ebXML_RegistryPackage_Slot_ValueList_Value);

					$dom_ebXML_RegistryPackage_Slot_ValueList_Value->set_content($Slot_value);

				}//END OF for($r=0;$r<count($authorPerson_Slots);$r++)

				$repeat=false;

			}//END OF if($Slot_name=="authorPerson")
			if($Slot_name!="authorPerson")
			{
				$dom_ebXML_RegistryPackage_Slot=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim3_path,"Slot");
				$dom_ebXML_RegistryPackage_Slot=$dom_ebXML_RegistryPackage_root->append_child($dom_ebXML_RegistryPackage_Slot);

				$dom_ebXML_RegistryPackage_Slot->set_attribute("name",$Slot_name);
			
				$dom_ebXML_RegistryPackage_Slot_ValueList=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim3_path,"ValueList");
				$dom_ebXML_RegistryPackage_Slot_ValueList=$dom_ebXML_RegistryPackage_Slot->append_child($dom_ebXML_RegistryPackage_Slot_ValueList);

				$dom_ebXML_RegistryPackage_Slot_ValueList_Value=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim3_path,"Value");
				$dom_ebXML_RegistryPackage_Slot_ValueList_Value=$dom_ebXML_RegistryPackage_Slot_ValueList->append_child($dom_ebXML_RegistryPackage_Slot_ValueList_Value);

				$Slot_value = $Slot[1];
				$dom_ebXML_RegistryPackage_Slot_ValueList_Value->set_content($Slot_value);

			}//END OF elseif($Slot_name!="authorPerson")

		}//END OF for($s=0;$s<count($slot_arr);$s++)


		#### NAME
		$dom_ebXML_RegistryPackage_Name=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim3_path,"Name");
		$dom_ebXML_RegistryPackage_Name=$dom_ebXML_RegistryPackage_root->append_child($dom_ebXML_RegistryPackage_Name);

		$queryForRegistryPackage_Name="SELECT charset,value,lang FROM Name WHERE Name.parent = '$RegistryPackage_id'";
		$Name_arr=query_select($queryForRegistryPackage_Name);
		writeSQLQueryService($queryForRegistryPackage_Name);

		$Name_charset = $Name_arr[0][0];
		$Name_value = $Name_arr[0][1];
		$Name_lang = $Name_arr[0][2];

		if(!empty($Name_arr))
		{
		$dom_ebXML_RegistryPackage_Name_LocalizedString=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim3_path,"LocalizedString");
		$dom_ebXML_RegistryPackage_Name_LocalizedString=$dom_ebXML_RegistryPackage_Name->append_child($dom_ebXML_RegistryPackage_Name_LocalizedString);

		$dom_ebXML_RegistryPackage_Name_LocalizedString->set_attribute("charset",$Name_charset);
		$dom_ebXML_RegistryPackage_Name_LocalizedString->set_attribute("value",$Name_value);
		$dom_ebXML_RegistryPackage_Name_LocalizedString->set_attribute("xml:lang",$Name_lang);
		}

		#### DESCRIPTION
		$dom_ebXML_RegistryPackage_Description=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim3_path,"Description");
		$dom_ebXML_RegistryPackage_Description=$dom_ebXML_RegistryPackage_root->append_child($dom_ebXML_RegistryPackage_Description);

		$queryForRegistryPackage_Description="SELECT charset,value,lang FROM Description WHERE Description.parent = '$RegistryPackage_id'";
		$Description_arr=query_select($queryForRegistryPackage_Description);
		writeSQLQueryService($queryForRegistryPackage_Description);

		$Description_charset = $Description_arr[0][0];
		$Description_value = $Description_arr[0][1];
		$Description_lang = $Description_arr[0][2];

		if(!empty($Description_arr) && $Description_value!="NOT DECLARED")
		{
		$dom_ebXML_RegistryPackage_Description_LocalizedString=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim3_path,"LocalizedString");
		$dom_ebXML_RegistryPackage_Description_LocalizedString=$dom_ebXML_RegistryPackage_Description->append_child($dom_ebXML_RegistryPackage_Description_LocalizedString);

		$dom_ebXML_RegistryPackage_Description_LocalizedString->set_attribute("charset",$Description_charset);
		$dom_ebXML_RegistryPackage_Description_LocalizedString->set_attribute("value",$Description_value);
		$dom_ebXML_RegistryPackage_Description_LocalizedString->set_attribute("xml:lang",$Description_lang);
		}


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
				//$dom_ebXML_RegistryPackage_Classification->set_attribute("classificationNode",$RegistryPackage_Classification_classificationNode);
				$dom_ebXML_RegistryPackage_Classification->set_attribute("classifiedObject",$RegistryPackage_Classification_classifiedObject);
				$dom_ebXML_RegistryPackage_Classification->set_attribute("id",$RegistryPackage_Classification_id);
				$dom_ebXML_RegistryPackage_Classification->set_attribute("nodeRepresentation",$RegistryPackage_Classification_nodeRepresentation);
				$dom_ebXML_RegistryPackage_Classification->set_attribute("objectType",$namespace_objectType.$RegistryPackage_Classification_objectType);


				#### SLOT
				$dom_ebXML_Classification_Slot=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim3_path,"Slot");
				$dom_ebXML_Classification_Slot=$dom_ebXML_RegistryPackage_Classification->append_child($dom_ebXML_Classification_Slot);

				$dom_ebXML_Classification_Slot_ValueList=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim3_path,"ValueList");
				$dom_ebXML_Classification_Slot_ValueList=$dom_ebXML_Classification_Slot->append_child($dom_ebXML_Classification_Slot_ValueList);

				$dom_ebXML_Classification_Slot_ValueList_Value=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim3_path,"Value");
				$dom_ebXML_Classification_Slot_ValueList_Value=$dom_ebXML_Classification_Slot_ValueList->append_child($dom_ebXML_Classification_Slot_ValueList_Value);

				$select_Slots = "SELECT name,value FROM Slot WHERE Slot.parent = '$RegistryPackage_Classification_id'";
				$Slot_arr=query_select($select_Slots);
				writeSQLQueryService($select_Slots);

				#### RICAVO LE INFO SUL NODO SLOT
				$Slot=$Slot_arr[0];
				$Slot_name = $Slot[0];
				$Slot_value = $Slot[1];

				$dom_ebXML_Classification_Slot->set_attribute("name",$Slot_name);
				$dom_ebXML_Classification_Slot_ValueList_Value->set_content($Slot_value);


				#### NAME
				$dom_ebXML_Classification_Name=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim3_path,"Name");
				$dom_ebXML_Classification_Name=$dom_ebXML_RegistryPackage_Classification->append_child($dom_ebXML_Classification_Name);

				$queryForClassification_Name="SELECT charset,value,lang FROM Name WHERE Name.parent = '$RegistryPackage_Classification_id'";
				$Name_arr=query_select($queryForClassification_Name);
				writeSQLQueryService($queryForClassification_Name);

				$Name_charset = $Name_arr[0][0];
				$Name_value = $Name_arr[0][1];
				$Name_lang = $Name_arr[0][2];

				if(!empty($Name_arr))
				{
				$dom_ebXML_Classification_Name_LocalizedString=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim3_path,"LocalizedString");
				$dom_ebXML_Classification_Name_LocalizedString=$dom_ebXML_Classification_Name->append_child($dom_ebXML_Classification_Name_LocalizedString);

				$dom_ebXML_Classification_Name_LocalizedString->set_attribute("charset",$Name_charset);
				$dom_ebXML_Classification_Name_LocalizedString->set_attribute("value",$Name_value);
				$dom_ebXML_Classification_Name_LocalizedString->set_attribute("xml:lang",$Name_lang);
				}

				#### DESCRIPTION
				$dom_ebXML_Classification_Description=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim3_path,"Description");
				$dom_ebXML_Classification_Description=$dom_ebXML_RegistryPackage_Classification->append_child($dom_ebXML_Classification_Description);

				$queryForClassification_Description="SELECT charset,value,lang FROM Description WHERE Description.parent = '$RegistryPackage_Classification_id'";
				$Description_arr=query_select($queryForClassification_Description);
				writeSQLQueryService($queryForClassification_Description);

				$Description_charset = $Description_arr[0][0];
				$Description_value = $Description_arr[0][1];
				$Description_lang = $Description_arr[0][2];

				if(!empty($Description_arr) && $Description_value!="NOT DECLARED")
				{
				$dom_ebXML_Classification_Description_LocalizedString=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim3_path,"LocalizedString");
				$dom_ebXML_Classification_Description_LocalizedString=$dom_ebXML_Classification_Description->append_child($dom_ebXML_Classification_Description_LocalizedString);

				$dom_ebXML_Classification_Description_LocalizedString->set_attribute("charset",$Description_charset);
				$dom_ebXML_Classification_Description_LocalizedString->set_attribute("value",$Description_value);
				$dom_ebXML_Classification_Description_LocalizedString->set_attribute("xml:lang",$Description_lang);
				}


			}//END OF for($t=0;$t<count($RegistryPackage_Classification_arr);$t++)
















	
		$dom_ebXML_RegistryPackage_Classification=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim3_path,"Classification");
		$dom_ebXML_RegistryPackage_Classification=$dom_ebXML_RegistryPackage_root->append_child($dom_ebXML_RegistryPackage_Classification);

		$dom_ebXML_RegistryPackage_Classification->set_attribute("classificationNode",$RegistryPackage_Classification_classificationNode);
		$dom_ebXML_RegistryPackage_Classification->set_attribute("classifiedObject",$RegistryPackage_Classification_classifiedObject);
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

				#### NAME
				$dom_ebXML_ExternalIdentifier_Name=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim3_path,"Name");
				$dom_ebXML_ExternalIdentifier_Name=$dom_ebXML_RegistryPackage_ExternalIdentifier->append_child($dom_ebXML_ExternalIdentifier_Name);

				$queryForExternalIdentifier_Name="SELECT charset,value,lang FROM Name WHERE Name.parent = '$RegistryPackage_ExternalIdentifier_id'";
				$Name_arr=query_select($queryForExternalIdentifier_Name);
				writeSQLQueryService($queryForExternalIdentifier_Name);

				$Name_charset = $Name_arr[0][0];
				$Name_value = $Name_arr[0][1];
				$Name_lang = $Name_arr[0][2];

				if(!empty($Name_arr))
				{
				$dom_ebXML_ExternalIdentifier_Name_LocalizedString=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim3_path,"LocalizedString");
				$dom_ebXML_ExternalIdentifier_Name_LocalizedString=$dom_ebXML_ExternalIdentifier_Name->append_child($dom_ebXML_ExternalIdentifier_Name_LocalizedString);

				$dom_ebXML_ExternalIdentifier_Name_LocalizedString->set_attribute("charset",$Name_charset);
				$dom_ebXML_ExternalIdentifier_Name_LocalizedString->set_attribute("value",$Name_value);
				$dom_ebXML_ExternalIdentifier_Name_LocalizedString->set_attribute("xml:lang",$Name_lang);
				}

				#### DESCRIPTION
				$dom_ebXML_ExternalIdentifier_Description=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim3_path,"Description");
				$dom_ebXML_ExternalIdentifier_Description=$dom_ebXML_RegistryPackage_ExternalIdentifier->append_child($dom_ebXML_ExternalIdentifier_Description);

				$queryForExternalIdentifier_Description="SELECT charset,value,lang FROM Description WHERE Description.parent = '$RegistryPackage_ExternalIdentifier_id'";
				$Description_arr=query_select($queryForExternalIdentifier_Description);
				writeSQLQueryService($queryForExternalIdentifier_Description);

				$Description_charset = $Description_arr[0][0];
				$Description_value = $Description_arr[0][1];
				$Description_lang = $Description_arr[0][2];

				if(!empty($Description_arr) && $Description_value!="NOT DECLARED")
				{
				$dom_ebXML_ExternalIdentifier_Description_LocalizedString=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim3_path,"LocalizedString");
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
		$fp=fopen("tmp/REQUEST_OBJECT","w+");
    		fwrite($fp,"REQUESTED_OBJECT =  $objectType_code_from_RegistryPackage");
		fclose($fp);

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
		$dom_ebXML_RegistryPackage_root->set_attribute("status",$RegistryPackage_status);

		
		##### SLOT
		$select_Slots = "SELECT name,value FROM Slot WHERE Slot.parent = '$RegistryPackage_id'";
		$Slot_arr=query_select($select_Slots);
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
				$dom_ebXML_RegistryPackage_Slot=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim3_path,"Slot");
				$dom_ebXML_RegistryPackage_Slot=$dom_ebXML_RegistryPackage_root->append_child($dom_ebXML_RegistryPackage_Slot);

				$select_authorPerson_Slots = "SELECT value FROM Slot WHERE Slot.parent = '$RegistryPackage_id' AND Slot.name = 'authorPerson'";
				$authorPerson_Slots=query_select($select_authorPerson_Slots);
				writeSQLQueryService($select_authorPerson_Slots);
				
				$dom_ebXML_RegistryPackage_Slot->set_attribute("name",$Slot_name);
				$dom_ebXML_RegistryPackage_Slot_ValueList=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim3_path,"ValueList");
				$dom_ebXML_RegistryPackage_Slot_ValueList=$dom_ebXML_RegistryPackage_Slot->append_child($dom_ebXML_RegistryPackage_Slot_ValueList);

				for($r=0;$r<count($authorPerson_Slots);$r++)
				{
					$authorPerson_Slot=$authorPerson_Slots[$r];
					$Slot_value = $authorPerson_Slot[0];

					$dom_ebXML_RegistryPackage_Slot_ValueList_Value=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim3_path,"Value");
					$dom_ebXML_RegistryPackage_Slot_ValueList_Value=$dom_ebXML_RegistryPackage_Slot_ValueList->append_child($dom_ebXML_RegistryPackage_Slot_ValueList_Value);

					$dom_ebXML_RegistryPackage_Slot_ValueList_Value->set_content($Slot_value);

				}//END OF for($r=0;$r<count($authorPerson_Slots);$r++)

				$repeat=false;

			}//END OF if($Slot_name=="authorPerson")
			if($Slot_name!="authorPerson")
			{
				$dom_ebXML_RegistryPackage_Slot=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim3_path,"Slot");
				$dom_ebXML_RegistryPackage_Slot=$dom_ebXML_RegistryPackage_root->append_child($dom_ebXML_RegistryPackage_Slot);

				$dom_ebXML_RegistryPackage_Slot->set_attribute("name",$Slot_name);
			
				$dom_ebXML_RegistryPackage_Slot_ValueList=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim3_path,"ValueList");
				$dom_ebXML_RegistryPackage_Slot_ValueList=$dom_ebXML_RegistryPackage_Slot->append_child($dom_ebXML_RegistryPackage_Slot_ValueList);

				$dom_ebXML_RegistryPackage_Slot_ValueList_Value=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim3_path,"Value");
				$dom_ebXML_RegistryPackage_Slot_ValueList_Value=$dom_ebXML_RegistryPackage_Slot_ValueList->append_child($dom_ebXML_RegistryPackage_Slot_ValueList_Value);

				$Slot_value = $Slot[1];
				$dom_ebXML_RegistryPackage_Slot_ValueList_Value->set_content($Slot_value);

			}//END OF if($Slot_name!="authorPerson")

		}//END OF for($s=0;$s<count($slot_arr);$s++)

		}//END OF if(!empty($Slot_arr))


		#### NAME
		$dom_ebXML_RegistryPackage_Name=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim3_path,"Name");
		$dom_ebXML_RegistryPackage_Name=$dom_ebXML_RegistryPackage_root->append_child($dom_ebXML_RegistryPackage_Name);

		$queryForRegistryPackage_Name="SELECT charset,value,lang FROM Name WHERE Name.parent = '$RegistryPackage_id'";
		$Name_arr=query_select($queryForRegistryPackage_Name);
		writeSQLQueryService($queryForRegistryPackage_Name);

		$Name_charset = $Name_arr[0][0];
		$Name_value = $Name_arr[0][1];
		$Name_lang = $Name_arr[0][2];

		if(!empty($Name_arr))
		{
		$dom_ebXML_RegistryPackage_Name_LocalizedString=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim3_path,"LocalizedString");
		$dom_ebXML_RegistryPackage_Name_LocalizedString=$dom_ebXML_RegistryPackage_Name->append_child($dom_ebXML_RegistryPackage_Name_LocalizedString);

		$dom_ebXML_RegistryPackage_Name_LocalizedString->set_attribute("charset",$Name_charset);
		$dom_ebXML_RegistryPackage_Name_LocalizedString->set_attribute("value",$Name_value);
		$dom_ebXML_RegistryPackage_Name_LocalizedString->set_attribute("xml:lang",$Name_lang);
		}

		#### DESCRIPTION
		$dom_ebXML_RegistryPackage_Description=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim3_path,"Description");
		$dom_ebXML_RegistryPackage_Description=$dom_ebXML_RegistryPackage_root->append_child($dom_ebXML_RegistryPackage_Description);

		$queryForRegistryPackage_Description="SELECT charset,value,lang FROM Description WHERE Description.parent = '$RegistryPackage_id'";
		$Description_arr=query_select($queryForRegistryPackage_Description);
		writeSQLQueryService($queryForRegistryPackage_Description);

		$Description_charset = $Description_arr[0][0];
		$Description_value = $Description_arr[0][1];
		$Description_lang = $Description_arr[0][2];

		if(!empty($Description_arr) && $Description_value!="NOT DECLARED")
		{
		$dom_ebXML_RegistryPackage_Description_LocalizedString=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim3_path,"LocalizedString");
		$dom_ebXML_RegistryPackage_Description_LocalizedString=$dom_ebXML_RegistryPackage_Description->append_child($dom_ebXML_RegistryPackage_Description_LocalizedString);

		$dom_ebXML_RegistryPackage_Description_LocalizedString->set_attribute("charset",$Description_charset);
		$dom_ebXML_RegistryPackage_Description_LocalizedString->set_attribute("value",$Description_value);
		$dom_ebXML_RegistryPackage_Description_LocalizedString->set_attribute("xml:lang",$Description_lang);
		}


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
				$dom_ebXML_RegistryPackage_Classification->set_attribute("objectType",$RegistryPackage_Classification_objectType);
				
				#### SLOT
				$dom_ebXML_Classification_Slot=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim3_path,"Slot");
				$dom_ebXML_Classification_Slot=$dom_ebXML_RegistryPackage_Classification->append_child($dom_ebXML_Classification_Slot);

				$dom_ebXML_Classification_Slot_ValueList=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim3_path,"ValueList");
				$dom_ebXML_Classification_Slot_ValueList=$dom_ebXML_Classification_Slot->append_child($dom_ebXML_Classification_Slot_ValueList);

				$dom_ebXML_Classification_Slot_ValueList_Value=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim3_path,"Value");
				$dom_ebXML_Classification_Slot_ValueList_Value=$dom_ebXML_Classification_Slot_ValueList->append_child($dom_ebXML_Classification_Slot_ValueList_Value);

				$select_Slots = "SELECT name,value FROM Slot WHERE Slot.parent = '$RegistryPackage_Classification_id'";
				$Slot_arr=query_select($select_Slots);
				writeSQLQueryService($select_Slots);

				#### RICAVO LE INFO SUL NODO SLOT
				$Slot=$Slot_arr[0];
				$Slot_name = $Slot[0];
				$Slot_value = $Slot[1];

				$dom_ebXML_Classification_Slot->set_attribute("name",$Slot_name);
				$dom_ebXML_Classification_Slot_ValueList_Value->set_content($Slot_value);


				#### NAME
				$dom_ebXML_Classification_Name=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim3_path,"Name");
				$dom_ebXML_Classification_Name=$dom_ebXML_RegistryPackage_Classification->append_child($dom_ebXML_Classification_Name);

				$queryForClassification_Name="SELECT charset,value,lang FROM Name WHERE Name.parent = '$RegistryPackage_Classification_id'";
				$Name_arr=query_select($queryForClassification_Name);
				writeSQLQueryService($queryForClassification_Name);

				$Name_charset = $Name_arr[0][0];
				$Name_value = $Name_arr[0][1];
				$Name_lang = $Name_arr[0][2];

				if(!empty($Name_arr))
				{
				$dom_ebXML_Classification_Name_LocalizedString=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim3_path,"LocalizedString");
				$dom_ebXML_Classification_Name_LocalizedString=$dom_ebXML_Classification_Name->append_child($dom_ebXML_Classification_Name_LocalizedString);

				$dom_ebXML_Classification_Name_LocalizedString->set_attribute("charset",$Name_charset);
				$dom_ebXML_Classification_Name_LocalizedString->set_attribute("value",$Name_value);
				$dom_ebXML_Classification_Name_LocalizedString->set_attribute("xml:lang",$Name_lang);
				}

				#### DESCRIPTION
				$dom_ebXML_Classification_Description=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim3_path,"Description");
				$dom_ebXML_Classification_Description=$dom_ebXML_RegistryPackage_Classification->append_child($dom_ebXML_Classification_Description);

				$queryForClassification_Description="SELECT charset,value,lang FROM Description WHERE Description.parent = '$RegistryPackage_Classification_id'";
				$Description_arr=query_select($queryForClassification_Description);
				writeSQLQueryService($queryForClassification_Description);

				$Description_charset = $Description_arr[0][0];
				$Description_value = $Description_arr[0][1];
				$Description_lang = $Description_arr[0][2];

				if(!empty($Description_arr) && $Description_value!="NOT DECLARED")
				{
				$dom_ebXML_Classification_Description_LocalizedString=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim3_path,"LocalizedString");
				$dom_ebXML_Classification_Description_LocalizedString=$dom_ebXML_Classification_Description->append_child($dom_ebXML_Classification_Description_LocalizedString);

				$dom_ebXML_Classification_Description_LocalizedString->set_attribute("charset",$Description_charset);
				$dom_ebXML_Classification_Description_LocalizedString->set_attribute("value",$Description_value);
				$dom_ebXML_Classification_Description_LocalizedString->set_attribute("xml:lang",$Description_lang);
				}



			}//END OF for($t=0;$t<count($RegistryPackage_Classification_arr);$t++)

			}//END OF if(!empty($RegistryPackage_Classification_arr))

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

				#### NAME
				$dom_ebXML_ExternalIdentifier_Name=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim3_path,"Name");
				$dom_ebXML_ExternalIdentifier_Name=$dom_ebXML_RegistryPackage_ExternalIdentifier->append_child($dom_ebXML_ExternalIdentifier_Name);

				$queryForExternalIdentifier_Name="SELECT charset,value,lang FROM Name WHERE Name.parent = '$RegistryPackage_ExternalIdentifier_id'";
				$Name_arr=query_select($queryForExternalIdentifier_Name);
				writeSQLQueryService($queryForExternalIdentifier_Name);

				$Name_charset = $Name_arr[0][0];
				$Name_value = $Name_arr[0][1];
				$Name_lang = $Name_arr[0][2];

				if(!empty($Name_arr))
				{
				$dom_ebXML_ExternalIdentifier_Name_LocalizedString=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim3_path,"LocalizedString");
				$dom_ebXML_ExternalIdentifier_Name_LocalizedString=$dom_ebXML_ExternalIdentifier_Name->append_child($dom_ebXML_ExternalIdentifier_Name_LocalizedString);

				$dom_ebXML_ExternalIdentifier_Name_LocalizedString->set_attribute("charset",$Name_charset);
				$dom_ebXML_ExternalIdentifier_Name_LocalizedString->set_attribute("value",$Name_value);
				$dom_ebXML_ExternalIdentifier_Name_LocalizedString->set_attribute("xml:lang",$Name_lang);
				}

				#### DESCRIPTION
				$dom_ebXML_ExternalIdentifier_Description=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim3_path,"Description");
				$dom_ebXML_ExternalIdentifier_Description=$dom_ebXML_RegistryPackage_ExternalIdentifier->append_child($dom_ebXML_ExternalIdentifier_Description);

				$queryForExternalIdentifier_Description="SELECT charset,value,lang FROM Description WHERE Description.parent = '$RegistryPackage_ExternalIdentifier_id'";
				$Description_arr=query_select($queryForExternalIdentifier_Description);
				writeSQLQueryService($queryForExternalIdentifier_Description);

				$Description_charset = $Description_arr[0][0];
				$Description_value = $Description_arr[0][1];
				$Description_lang = $Description_arr[0][2];

				if(!empty($Description_arr) && $Description_value!="NOT DECLARED")
				{
				$dom_ebXML_ExternalIdentifier_Description_LocalizedString=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim3_path,"LocalizedString");
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

		
		##### SLOT
		$select_Slots = "SELECT name,value FROM Slot WHERE Slot.parent = '$Association_id'";
		$Slot_arr=query_select($select_Slots);
		writeSQLQueryService($select_Slots);
		$repeat = true;
		for($s=0;$s<count($Slot_arr);$s++)
		{
			$Slot = $Slot_arr[$s];
			$Slot_name = $Slot[0];

			$dom_ebXML_Association_Slot=$dom_ebXML_Association->create_element_ns($ns_rim3_path,"Slot");
			$dom_ebXML_Association_Slot=$dom_ebXML_Association_root->append_child($dom_ebXML_Association_Slot);

			$dom_ebXML_Association_Slot->set_attribute("name",$Slot_name);
			
			$dom_ebXML_Association_Slot_ValueList=$dom_ebXML_Association->create_element_ns($ns_rim3_path,"ValueList");
			$dom_ebXML_Association_Slot_ValueList=$dom_ebXML_Association_Slot->append_child($dom_ebXML_Association_Slot_ValueList);

			$dom_ebXML_Association_Slot_ValueList_Value=$dom_ebXML_Association->create_element_ns($ns_rim3_path,"Value");
			$dom_ebXML_Association_Slot_ValueList_Value=$dom_ebXML_Association_Slot_ValueList->append_child($dom_ebXML_Association_Slot_ValueList_Value);

			$Slot_value = $Slot[1];
			$dom_ebXML_Association_Slot_ValueList_Value->set_content($Slot_value);

		}//END OF for($s=0;$s<count($Slot_arr);$s++)

		#### NAME
		$dom_ebXML_Association_Name=$dom_ebXML_Association->create_element_ns($ns_rim3_path,"Name");
		$dom_ebXML_Association_Name=$dom_ebXML_Association_root->append_child($dom_ebXML_Association_Name);

		$queryForAssociation_Name="SELECT charset,value,lang FROM Name WHERE Name.parent = '$Association_id'";
		$Name_arr=query_select($queryForAssociation_Name);
		writeSQLQueryService($queryForAssociation_Name);

		$Name_charset = $Name_arr[0][0];
		$Name_value = $Name_arr[0][1];
		$Name_lang = $Name_arr[0][2];

		if(!empty($Name_arr))
		{
		$dom_ebXML_Association_Name_LocalizedString=$dom_ebXML_Association->create_element_ns($ns_rim3_path,"LocalizedString");
		$dom_ebXML_Association_Name_LocalizedString=$dom_ebXML_Association_Name->append_child($dom_ebXML_Association_Name_LocalizedString);

		$dom_ebXML_Association_Name_LocalizedString->set_attribute("charset",$Name_charset);
		$dom_ebXML_Association_Name_LocalizedString->set_attribute("value",$Name_value);
		$dom_ebXML_Association_Name_LocalizedString->set_attribute("xml:lang",$Name_lang);
		}

		#### DESCRIPTION
		$dom_ebXML_Association_Description=$dom_ebXML_Association->create_element_ns($ns_rim3_path,"Description");
		$dom_ebXML_Association_Description=$dom_ebXML_Association_root->append_child($dom_ebXML_Association_Description);

		$queryForAssociation_Description="SELECT charset,value,lang FROM Description WHERE Description.parent = '$Association_id'";
		$Description_arr=query_select($queryForAssociation_Description);
		writeSQLQueryService($queryForAssociation_Description);

		$Description_charset = $Description_arr[0][0];
		$Description_value = $Description_arr[0][1];
		$Description_lang = $Description_arr[0][2];

		if(!empty($Description_arr) && $Description_value!="NOT DECLARED")
		{
		$dom_ebXML_Association_Description_LocalizedString=$dom_ebXML_Association->create_element_ns($ns_rim3_path,"LocalizedString");
		$dom_ebXML_Association_Description_LocalizedString=$dom_ebXML_Association_Description->append_child($dom_ebXML_Association_Description_LocalizedString);

		$dom_ebXML_Association_Description_LocalizedString->set_attribute("charset",$Description_charset);
		$dom_ebXML_Association_Description_LocalizedString->set_attribute("value",$Description_value);
		$dom_ebXML_Association_Description_LocalizedString->set_attribute("xml:lang",$Description_lang);
		}


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
		<ActiveParticipant UserID=\"http://".$reg_host.":".$reg_port.$reg_path."\" NetworkAccessPointTypeCode=\"2\" NetworkAccessPointID=\"".$reg_host."\"  UserIsRequestor=\"false\">
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





//Parte per calcolare i tempi di esecuzione
$mtime = microtime();
$mtime = explode(" ",$mtime);
$mtime = $mtime[1] + $mtime[0];
$endtime = $mtime;
$totaltime = number_format($endtime - $starttime,15);

$STAT_QUERY="INSERT INTO STATS (REPOSITORY,DATA,EXECUTION_TIME,OPERATION) VALUES ('".$_SERVER['REMOTE_ADDR']."',CURRENT_TIMESTAMP,'$totaltime','QUERY')";
$ris = query_exec2($STAT_QUERY,$connessione);
writeSQLQueryService($ris.": ".$STAT_QUERY);



unset($_SESSION['tmp_path']);
unset($_SESSION['idfile']);
unset($_SESSION['logActive']);
unset($_SESSION['log_query_path']);
unset($_SESSION['tmpQueryService_path']);

######################################################################
#### METTO L'ebXML SU STRINGA
//$ebXML_Response_string = substr($dom_ebXML_Response->dump_mem(),21);
$ebXML_Response_SOAPED_string = makeSoapedSuccessStoredQueryResponse($Action,$MessageID,$ebXML_Response_string);

	### SCRIVO LA RISPOSTA IN UN FILE
	$file_input=$tmpQueryService_path.$idfile."ebxmlResponseSOAP.xml".$idfile;
	 $fp = fopen($file_input,"w+");
           fwrite($fp,$ebXML_Response_SOAPED_string);
         fclose($fp);
	writeTimeFile($idfile."--StoredQuery: Creo file ebxmlResponseSOAP");

	SendResponse($file_input);







//} // Fine for($SQcount=0;$SQcount<count($SQLStoredQuery);$SQcount++){
################## END OF REGISTRY RESPONSE TO QUERY ####################


?>