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

include($lib_path."utilities.php");
include("./lib/log.php");
$idfile = idrandom_file();

$_SESSION['tmp_path']=$tmp_path;
$_SESSION['idfile']=$idfile;
$_SESSION['logActive']=$logActive;
$_SESSION['log_path']=$log_path;

//PULISCO LA CACHE TEMPORANEA
exec('rm -f '.$tmpQueryService_path."*");

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
/*
$ebxml_STRING = 
substr($ebxml_imbustato_soap_STRING,strpos($ebxml_imbustato_soap_STRING,"Body>")+5,(strpos($ebxml_imbustato_soap_STRING,"</SOAP-ENV:Body>")));
$ebxml_STRING = str_replace("</SOAP-ENV:Body></SOAP-ENV:Envelope>","",$ebxml_STRING);
*/

#########################################################################
$ebxml_STRING
=substr($ebxml_imbustato_soap_STRING,
                strpos($ebxml_imbustato_soap_STRING,"<query:AdhocQueryRequest"),
(strlen($ebxml_imbustato_soap_STRING)-strlen(substr($ebxml_imbustato_soap_STRING,strpos($ebxml_imbustato_soap_STRING,"</query:AdhocQueryRequest>")+26))));

$ebxml_STRING=str_replace((substr($ebxml_imbustato_soap_STRING,strpos($ebxml_imbustato_soap_STRING,"</query:AdhocQueryRequest>")+26)),
"",$ebxml_STRING);
###################################################################################

//SCRIVO L'AdhocQueryRequest SBUSTATO
$fp_AdhocQueryRequest = fopen($tmpQueryService_path."AdhocQueryRequest","w+");
	fwrite($fp_AdhocQueryRequest,$ebxml_STRING);
fclose($fp_AdhocQueryRequest);

####### VALIDAZIONE DELL'ebXML SECONDO LO SCHEMA
$comando_java_validation=("java -jar ".$path_to_VALIDATION_jar."valid.jar -xsd ".$path_to_XSD_file_sq." -xml ".$tmpQueryService_path."AdhocQueryRequest");

$fp_ebxml_val = fopen($tmpQueryService_path."comando_java_validation","w+");
	fwrite($fp_ebxml_val,$comando_java_validation);
fclose($fp_ebxml_val);

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

}//END OF if($error==0)

#### NEL CASO DI MANCATA VALIDAZIONE RESTITUISCE 
#### IL MESSAGGIO DI FAIL IN SOAP ED ESCE
if(!$isValid)
{
	### RESTITUISCE IL MESSAGGIO DI FAIL IN SOAP
    	$SOAPED_failure_response = makeSoapedFailureResponse($error_message,$logentry_query);

	### SCRIVO LA RISPOSTA IN UN FILE
	 $fp = fopen($tmpQueryService_path."SOAPED_failure_VALIDATION_response","w+");
           fwrite($fp,$SOAPED_failure_response);
         fclose($fp);

	### PULISCO IL BUFFER DI USCITA
	ob_get_clean();//OKKIO FONDAMENTALE!!!!!

	### HEADERS
	header("HTTP/1.1 200 OK");
	header("Path: $www_REG_path");
	header("Content-Type: text/xml;charset=UTF-8");
	header("Content-Length: ".(string)filesize($tmpQueryService_path."SOAPED_failure_VALIDATION_response"));
	### CONTENUTO DEL FILE DI RISPOSTA
	if($file = fopen($tmpQueryService_path."SOAPED_failure_VALIDATION_response",'rb'))
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

}//END OF if(!$isValid)

$fp_SCHEMA_val = fopen($tmpQueryService_path."SCHEMA_validation","w+");
	fwrite($fp_SCHEMA_val,"VALIDAZIONE DA SCHEMA ==> OK <==");
fclose($fp_SCHEMA_val);

#### OTTENGO L'OGGETTO DOM DALL'AdhocQueryRequest
$dom_AdhocQueryRequest = domxml_open_mem(file_get_contents($tmpQueryService_path."AdhocQueryRequest"));
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

//SCRIVO returnComposedObjects
$fp_returnComposedObjects = fopen($tmpQueryService_path."returnComposedObjects","wb+");
	fwrite($fp_returnComposedObjects,$returnComposedObjects_a);
fclose($fp_returnComposedObjects);

//SCRIVO returnType
$fp_returnType = fopen($tmpQueryService_path."returnType","wb+");
	fwrite($fp_returnType,$returnType_a);
fclose($fp_returnType);


// Parte per stored query


$SQLQuery_AdhocQuery_node_array = $root->get_elements_by_tagname("AdhocQuery");
$SQLQuery_AdhocQuery = $SQLQuery_AdhocQuery_node_array[0];
$AdhocQuery=$SQLQuery_AdhocQuery->get_attribute("id");


$fp_Slot_val = fopen($tmpQueryService_path."Slot","a+");
 


$SQLQuery_Slot_node_array = $root->get_elements_by_tagname("Slot");

# Conto quanti slot ci sono
for($h = 0;$h < count($SQLQuery_Slot_node_array);$h++)
{
   # Considero il singolo Slot
   $SQLQuery_Slot_node = $SQLQuery_Slot_node_array[$h];

   ##### TUTTI I NODI FIGLI DI Slot
   $SQLQuery_Slot_child_nodes = $SQLQuery_Slot_node->child_nodes();

	# Conto quanti figli ha Slot
	for($i=0;$i<count($SQLQuery_Slot_child_nodes);$i++){

		# Considero il singolo elemento
		$SQLQuery_Slot_child_node=$SQLQuery_Slot_child_nodes[$i];

		# Recupero il nome del nodo
	   	$SQLQuery_Slot_child_node_tagname=$SQLQuery_Slot_child_node->node_name();

		# Se il nome del nodo è ValueList		
		if($SQLQuery_Slot_child_node_tagname=='ValueList'){

			# Guardo i figli di ValueList
			$SQLQuery_Slot_child_nodes_ValueList = $SQLQuery_Slot_node->child_nodes();

			if(count($SQLQuery_Slot_child_nodes_ValueList)==3){
				$value_node = $SQLQuery_Slot_child_nodes_ValueList[1];

				$Value[$h][0]=$SQLQuery_Slot_node->get_attribute("name");
				$Value[$h][1] = $value_node->get_content();
			}
			
		}
		
	}


	fwrite($fp_Slot_val,$Value[$h][0]);
	fwrite($fp_Slot_val,$Value[$h][1]);

}


$conta_SlotSQ = count($Value);
$SQLStoredQuery = array();
switch ($AdhocQuery) {
//*************************FindDocuments******************//
    case "urn:uuid:14d4debf-8f97-4251-9a74-a90016b0af0d":
        $AdhocQuery_case="FindDocuments";
	for ($SQI=0;$SQI<$conta_SlotSQ;$SQI++) {
		if (strpos(strtoupper($Value[$SQI][0]),"XDSDOCUMENTENTRYPATIENTID")) {
			$SQPatientID = $Value[$SQI][1];
		}
		if (strpos(strtoupper($Value[$SQI][0]),"XDSDOCUMENTENTRYSTATUS")) {
			if (strpos(strtoupper($Value[$SQI][1]),"APPROVED")){
				$SQEntryStatus = 'Approved';
			}
			else {
				$SQEntryStatus = 'Deprecated';
			}
		}
	}



	$SQLStoredQuery_From = "SELECT doc.id FROM ExtrinsicObject doc, ExternalIdentifier patId ";
	$SQLStoredQuery_Required = "WHERE doc.objectType = 'urn:uuid:7edca82f-054d-47f2-a032-9b2a5b5186c1' AND doc.id = patId.registryobject AND patId.identificationScheme='urn:uuid:58a6f841-87b3-4a3e-92fd-a8ffeff98427' AND patId.value = '".trim($SQPatientID)."' AND doc.status = '".trim($SQEntryStatus)."'";
	$SQLStoredQuery_Optional = "";

	for ($SQI=0;$SQI<$conta_SlotSQ;$SQI++) {
		if (strpos(strtoupper($Value[$SQI][0]),"XDSDOCUMENTENTRYCLASSCODE")) {
			$SQLStoredQuery_From .= ", Classification clCode ";
			$SQLStoredQuery_Optional .= " AND (clCode.classifiedObject = doc.id AND clCode.classificationScheme = 'urn:uuid:41a5887f-8865-4c09-adf7-e362475b143a' AND clCode.nodeRepresentation = '".trim($Value[$SQI][1])."')";
		}

		if (strpos(strtoupper($Value[$SQI][0]),"XDSDOCUMENTENTRYCLASSCODESCHEME")) {
			$SQLStoredQuery_From .= ", Classification clCodeScheme ";
			$SQLStoredQuery_Optional .= " AND (clCodeScheme.parent = clCode.id AND clCodeScheme.name = 'codingScheme' AND clCodeScheme.value = '".trim($Value[$SQI][1])."')";
		}

		if (strpos(strtoupper($Value[$SQI][0]),"XDSDOCUMENTENTRYPRACTICESETTINGCODE")) {
			$SQLStoredQuery_From .= ", Classification psc ";
			$SQLStoredQuery_Optional .= " AND (psc.classifiedObject = doc.id AND psc.classificationScheme='urn:uuid:cccf5598-8b07-4b77-a05e-ae952c785ead' AND psc.nodeRepresentation = '".trim($Value[$SQI][1])."')";
		}

		/*Qui andrebbe ????????
		XDSDocumentEntryPracticeSettingCodeScheme
		This coding depends on the above clause being included.
		AND (psCodeScheme.parent = psc.id AND 
		psCodeScheme.name = 'codingScheme' AND 
		psCodeScheme.value IN '".trim($Value[$SQI][1])."') 
		*/

		if (strpos(strtoupper($Value[$SQI][0]),"XDSDOCUMENTENTRYCREATIONTIMEFROM")) {
			$SQLStoredQuery_From .= ", Slot crTimef ";
			$SQLStoredQuery_Optional .= " AND (crTimef.parent = doc.id AND crTimef.name = 'creationTime' AND crTimef.value >= '".trim($Value[$SQI][1])."')";
		}

		if (strpos(strtoupper($Value[$SQI][0]),"XDSSUBMISSIONSETSUBMISSIONTIMETO")) {
			$SQLStoredQuery_From .= ", Slot crTimet ";
			$SQLStoredQuery_Optional .= " AND (crTimet.parent = doc.id AND crTimet.name = 'creationTime' AND crTimet.value <= '".trim($Value[$SQI][1])."')";
		}

		if (strpos(strtoupper($Value[$SQI][0]),"XDSDOCUMENTENTRYSERVICESTARTTIMEFROM")) {
			$SQLStoredQuery_From .= ", Slot serStartTimef ";
			$SQLStoredQuery_Optional .= " AND (serStartTimef.parent = doc.id AND serStartTimef.name = 'serviceStartTime' AND serStartTimef.value >= '".trim($Value[$SQI][1])."')";
		}

		if (strpos(strtoupper($Value[$SQI][0]),"XDSDOCUMENTENTRYSERVICESTARTTIMETO")) {
			$SQLStoredQuery_From .= ", Slot serStartTimet ";
			$SQLStoredQuery_Optional .= " AND (serStartTimet.parent = doc.id AND serStartTimet.name = 'serviceStartTime' AND serStartTimet.value <= '".trim($Value[$SQI][1])."')";
		}

		if (strpos(strtoupper($Value[$SQI][0]),"XDSDOCUMENTENTRYSERVICESTOPTIMEFROM")) {
			$SQLStoredQuery_From .= ", Slot serStopTimef ";
			$SQLStoredQuery_Optional .= " AND (serStopTimef.parent = doc.id AND serStopTimef.name = 'serviceStoptTime' AND serStopTimef.value >= '".trim($Value[$SQI][1])."')";
		}

		if (strpos(strtoupper($Value[$SQI][0]),"XDSDOCUMENTENTRYSERVICESTOPTIMETO")) {
			$SQLStoredQuery_From .= ", Slot serStopTimet ";
			$SQLStoredQuery_Optional .= " AND (serStopTimet.parent = doc.id AND serStopTimet.name = 'serviceStopTime' AND serStopTimet.value <= '".trim($Value[$SQI][1])."')";
		}

		if (strpos(strtoupper($Value[$SQI][0]),"XDSDOCUMENTENTRYHEALTHCAREFACILITYTYPECODE")) {
			$SQLStoredQuery_From .= ", Classification hftc ";
			$SQLStoredQuery_Optional .= " AND (hftc.classifiedObject = doc.id AND hftc.classificationScheme = 'urn:uuid:f33fb8ac-18af-42cc-ae0e-ed0b0bdb91e1' AND hftc.nodeRepresentation = '".trim($Value[$SQI][1])."')";
		}
		/*Qui andrebbe ????????	
		XDSDocumentEntryHealthcareFacilityTypeCodeScheme	
		This coding depends on the above clause being included.
		AND (hftcScheme.parent = hftc.id AND
		hftcScheme.name = 'codingScheme' AND
		hftcScheme.value = '".trim($Value[$SQI][1])."')
		*/

		if (strpos(strtoupper($Value[$SQI][0]),"XDSDOCUMENTENTRYEVENTCODELIST")) {
			$SQLStoredQuery_From .= ", Classification ecl ";
			$SQLStoredQuery_Optional .= " AND (ecl.classifiedObject = doc.id AND ecl.classificationScheme = 'urn:uuid:2c6b8cb7-8b2a-4051-b291-b1ae6a575ef4' AND ecl.nodeRepresentation = '".trim($Value[$SQI][1])."')";
		}

		/*Qui andrebbe ????????	
		XDSDocumentEntryEventCodeListScheme	
		This coding depends on the above clause being included.
		AND (eclScheme.parent = ecl.id AND eclScheme.name = 'codingScheme' 
		AND eclScheme.value = '".trim($Value[$SQI][1])."')
		*/
	
		if (strpos(strtoupper($Value[$SQI][0]),"XDSDOCUMENTENTRYCONFIDENTIALITYCODE")) {
			$SQLStoredQuery_From .= ", Classification conf ";
			$SQLStoredQuery_Optional .= " AND (conf.classifiedObject = doc.id AND conf.classificationScheme = 'urn:uuid:f4f85eac-e6cb-4883-b524-f2705394840f' AND conf.nodeRepresentation = '".trim($Value[$SQI][1])."')";
		}

		if (strpos(strtoupper($Value[$SQI][0]),"XDSDOCUMENTENTRYFORMATCODE")) {
			$SQLStoredQuery_From .= ", Classification fmtCode ";
			$SQLStoredQuery_Optional .= " AND (fmtCode.classifiedObject = doc.id AND fmtCode.classificationScheme = 'urn:uuid:a09d5840-386c-46f2-b5ad-9c3699a4309d' AND fmtCode.nodeRepresentation = '".trim($Value[$SQI][1])."')";
		}

	}

	$SQLStoredQuery[0] = $SQLStoredQuery_From.$SQLStoredQuery_Required.$SQLStoredQuery_Optional;

        break;

//*************************FindSubmissionSets******************//
    case "urn:uuid:f26abbcb-ac74-4422-8a30-edb644bbc1a9":
        $AdhocQuery_case="FindSubmissionSets";

	for ($SQI=0;$SQI<$conta_SlotSQ;$SQI++) {
		if (strpos(strtoupper($Value[$SQI][0]),"XDSSUBMISSIONSETPATIENTID")) {
			$SQPatientID = $Value[$SQI][1];
		}
		if (strpos(strtoupper($Value[$SQI][0]),"XDSSUBMISSIONSETSTATUS")) {
			if (strpos(strtoupper($Value[$SQI][1]),"APPROVED")){
				$SQSubmissionStatus = 'Approved';
			}
			else {
				$SQSubmissionStatus = 'Deprecated';
			}
		}
	}
	$SQLStoredQuery_From = "SELECT ss.id FROM RegistryPackage ss, ExternalIdentifier patId ";
	$SQLStoredQuery_Required = "WHERE ss.status = '".trim($SQSubmissionStatus)."' AND (ss.id = patId.registryobject AND patId.identificationScheme= 'urn:uuid:6b5aea1a-874d-4603-a4bc-96a0a7b38446' AND patId.value = '".trim($SQPatientID)."')";


	$SQLStoredQuery_Optional = "";

	for ($SQI=0;$SQI<$conta_SlotSQ;$SQI++) {

		if (strpos(strtoupper($Value[$SQI][0]),"XDSSUBMISSIONSETSOURCEID")) {
			$SQLStoredQuery_From .= ", ExternalIdentifier sid ";
			$SQLStoredQuery_Optional .= " AND (sid.registryobject = ss.id AND sid.identificationScheme = 'urn:uuid:554ac39e-e3fe-47fe-b233-965d2a147832' AND sid.value = '".trim($Value[$SQI][1])."')";
		}

		if (strpos(strtoupper($Value[$SQI][0]),"XDSSUBMISSIONSETSUBMISSIONTIMEFROM")) {
			$SQLStoredQuery_From .= ", Slot subTimeFrom ";
			$SQLStoredQuery_Optional .= " AND (subTimeFrom.parent = ss.id AND subTimeFrom.name = 'submissionTime' AND subTimeFrom.value >= '".trim($Value[$SQI][1])."')";
		}

		if (strpos(strtoupper($Value[$SQI][0]),"XDSSUBMISSIONSETSUBMISSIONTIMETO")) {
			$SQLStoredQuery_From .= ", Slot subTimeTo ";
			$SQLStoredQuery_Optional .= " AND (subTimeTo.parent = ss.id AND subTimeTo.name = 'submissionTime' AND subTimeTo.value <= '".trim($Value[$SQI][1])."')";
		}

		if (strpos(strtoupper($Value[$SQI][0]),"XDSSUBMISSIONSETAUTHORPERSON")) {
			$SQLStoredQuery_From .= ", Slot ap ";
			$SQLStoredQuery_Optional .= " AND (ap.parent = ss.id AND ap.name = 'authorPerson' AND ap.value LIKE '".trim($Value[$SQI][1])."')";
		}

		if (strpos(strtoupper($Value[$SQI][0]),"XDSSUBMISSIONSETCONTENTTYPE")) {
			$SQLStoredQuery_From .= ", Classification ctc ";
			$SQLStoredQuery_Optional .= " AND (ctc.classifiedObject = ss.id AND ctc.classificationScheme = 'urn:uuid:aa543740-bdda-424e-8c96-df4873be8500' AND ctc.nodeRepresentation = '".trim($Value[$SQI][1])."')";
		}
	}

	$SQLStoredQuery[0] = $SQLStoredQuery_From.$SQLStoredQuery_Required.$SQLStoredQuery_Optional;

        break;

//*************************FindFolders******************//
    case "urn:uuid:958f3006-baad-4929-a4deff1114824431":
        $AdhocQuery_case="FindFolders";
	for ($SQI=0;$SQI<$conta_SlotSQ;$SQI++) {
		if (strpos(strtoupper($Value[$SQI][0]),"XDSFOLDERPATIENTID")) {
			$SQPatientID = $Value[$SQI][1];
		}
		if (strpos(strtoupper($Value[$SQI][0]),"XDSFOLDERSTATUS")) {
			if (strpos(strtoupper($Value[$SQI][1]),"APPROVED")){
				$SQFolderStatus = 'Approved';
			}
			else {
				$SQFolderStatus = 'Deprecated';
			}
		}
	}
	$SQLStoredQuery_From = "SELECT fol.id FROM RegistryPackage fol, ExternalIdentifier patId ";
	$SQLStoredQuery_Required = "WHERE fol.status = '".trim($SQFolderStatus)."' AND (patId.registryobject = fol.id AND patId.identificationScheme = 'urn:uuid:f64ffdf0-4b97-4e06-b79f-a52b38ec2f8a' AND patId.value = '".trim($SQPatientID)."')";


	$SQLStoredQuery_Optional = "";

	for ($SQI=0;$SQI<$conta_SlotSQ;$SQI++) {

		if (strpos(strtoupper($Value[$SQI][0]),"XDSFOLDERLASTUPDATETIMEFROM")) {
			$SQLStoredQuery_From .= ", Slot lupdateTimef ";
			$SQLStoredQuery_Optional .= " AND (lupdateTimef.parent = fol.id AND lupdateTimef.name = 'lastUpdateTime' AND lupdateTimef.value >= '".trim($Value[$SQI][1])."')";
		}

		if (strpos(strtoupper($Value[$SQI][0]),"XDSFOLDERLASTUPDATETIMETO")) {
			$SQLStoredQuery_From .= ", Slot lupdateTimet ";
			$SQLStoredQuery_Optional .= " AND (lupdateTimet.parent = fol.id AND lupdateTimet.name = 'lastUpdateTime' AND lupdateTimet.value <= '".trim($Value[$SQI][1])."')";
		}

		if (strpos(strtoupper($Value[$SQI][0]),"XDSFOLDERCODELIST")) {
			$SQLStoredQuery_From .= ", Classification cl ";
			$SQLStoredQuery_Optional .= " AND (cl.classifiedObject = fol.id AND cl.classificationScheme = 'urn:uuid:1ba97051-7806-41a8-a48b-8fce7af683c5' AND cl.nodeRepresentation = '".trim($Value[$SQI][1])."')";
		}

		/*Qui andrebbe ????????	
		XDSFolderCodeListScheme	
		This coding depends on the above clause being included.
		AND (clScheme.parent = cl.id AND clScheme.name = 'codingScheme' 
		AND clScheme.value = '".trim($Value[$SQI][1])."')
		*/

	}

	$SQLStoredQuery[0] = $SQLStoredQuery_From.$SQLStoredQuery_Required.$SQLStoredQuery_Optional;
        break;

//*************************GetAll******************//
    case "urn:uuid:10b545ea-725c-446d-9b95-8aeb444eddf3":
        $AdhocQuery_case="GetAll";
	for ($SQI=0;$SQI<$conta_SlotSQ;$SQI++) {
		if (strpos(strtoupper($Value[$SQI][0]),"PATIENTID")) {
			$SQPatientID = $Value[$SQI][1];
		}
		if (strpos(strtoupper($Value[$SQI][0]),"XDSDOCUMENTENTRYSTATUS")) {
			if (strpos(strtoupper($Value[$SQI][1]),"APPROVED")){
				$SQEntryStatus = 'Approved';
			}
			else {
				$SQEntryStatus = 'Deprecated';
			}
		}
		if (strpos(strtoupper($Value[$SQI][0]),"XDSSUBMISSIONSETSTATUS")) {
			if (strpos(strtoupper($Value[$SQI][1]),"APPROVED")){
				$SQSubmissionStatus = 'Approved';
			}
			else {
				$SQSubmissionStatus = 'Deprecated';
			}
		}
		if (strpos(strtoupper($Value[$SQI][0]),"XDSFOLDERSTATUS")) {
			if (strpos(strtoupper($Value[$SQI][1]),"APPROVED")){
				$SQFolderStatus = 'Approved';
			}
			else {
				$SQFolderStatus = 'Deprecated';
			}
		}
	}

	// SQL Part1
	$SQLStoredQuery_From_EO = "SELECT eo.id FROM ExtrinsicObject eo, ExternalIdentifier patId ";
	$SQLStoredQuery_Required_EO = "WHERE eo.status = '".trim($SQEntryStatus)."' AND (eo.objectType = 'urn:uuid:7edca82f-054d-47f2-a032-9b2a5b5186c1' AND patId.registryObject = eo.id AND patId.identificationScheme = 'urn:uuid:58a6f841-87b3-4a3e-92fd-a8ffeff98427' AND patId.value = '".trim($SQPatientID)."')";


	$SQLStoredQuery_Optional_EO = "";

	for ($SQI=0;$SQI<$conta_SlotSQ;$SQI++) {

		if (strpos(strtoupper($Value[$SQI][0]),"XDSDOCUMENTENTRYCONFIDENTIALITYCODE")) {
			$SQLStoredQuery_From_EO .= ", Classification cCode ";
			$SQLStoredQuery_Optional_EO .= " AND (cCode.classifiedObject = eo.id AND cCode.classificationScheme = 'urn:uuid:f4f85eac-e6cb-4883-b524-f2705394840f' AND cCode.nodeRepresentation = '".trim($Value[$SQI][1])."')";
		}
		if (strpos(strtoupper($Value[$SQI][0]),"XDSDOCUMENTENTRYFORMATCODE")) {
			$SQLStoredQuery_From_EO .= ", Classification fmtCode ";
			$SQLStoredQuery_Optional_EO .= " AND (fmtCode.classifiedObject = doc.id AND fmtCode.classificationScheme = 'urn:uuid:a09d5840-386c-46f2-b5ad-9c3699a4309d' AND fmtCode.nodeRepresentation = '".trim($Value[$SQI][1])."')";
		}
	}

	$SQLStoredQuery_EO = $SQLStoredQuery_From_EO.$SQLStoredQuery_Required_EO.$SQLStoredQuery_Optional_EO;

	// SQL Part2
	$SQLStoredQuery_From_RP = "SELECT rp.id FROM RegistryPackage rp, Classification cl, ExternalIdentifier patId ";
	$SQLStoredQuery_Required_RP = "WHERE (rp.status = '".trim($SQSubmissionStatus)."' AND cl.classifiedObject = rp.id AND cl.classificationNode = 'urn:uuid:a54d6aa5-d40d-43f9-88c5-b4633d873bdd' AND patId.registryObject = rp.id AND patId.identificationScheme = 'urn:uuid:6b5aea1a-874d-4603-a4bc-96a0a7b38446' AND patId.value = '".trim($SQPatientID)."') OR (rp.status = '".trim($SQFolderStatus)."' AND cl.classifiedObject = rp.id AND cl.classificationNode = 'urn:uuid:d9d542f3-6cc4-48b6-8870-ea235fbc94c2' AND patId.registryObject = rp.id AND patId.identificationScheme = 'urn:uuid:f64ffdf0-4b97-4e06-b79f-a52b38ec2f8a' AND patId.value = '".trim($SQPatientID)."')" ;


	$SQLStoredQuery_RP = $SQLStoredQuery_From_RP.$SQLStoredQuery_Required_RP;

	// SQL Part3
	$SQLStoredQuery_From_ASS = "SELECT DISTINCT ass.id FROM Association ass, ExtrinsicObject eo, RegistryPackage ss, RegistryPackage fol ";
	$SQLStoredQuery_Required_ASS = "WHERE ((ass.sourceObject = ss.id AND ass.targetObject = fol.id) OR (ass.sourceObject = ss.id AND ass.targetObject = eo.id) OR (ass.sourceObject = fol.id AND ass.targetObject = eo.id) ) AND eo.id IN (SELECT eo.id FROM ExtrinsicObject eo, ExternalIdentifier patId WHERE eo.status = '".trim($SQEntryStatus)."' AND patId.registryObject = eo.id AND patId.identificationScheme = 'urn:uuid:58a6f841-87b3-4a3e-92fd-a8ffeff98427' AND patId.value = '".trim($SQPatientID)."') AND ss.id IN (SELECT ss.id FROM RegistryPackage ss, ExternalIdentifier patId WHERE ss.status = '".trim($SQSubmissionStatus)."' AND patId.registryObject = ss.id AND patId.identificationScheme = 'urn:uuid:6b5aea1a-874d-4603-a4bc-96a0a7b38446' AND patId.value = '".trim($SQPatientID)."') AND fol.id IN (SELECT fol.id FROM RegistryPackage fol, ExternalIdentifier patId WHERE fol.status = '".trim($SQFolderStatus)."' AND patId.registryObject = fol.id AND patId.identificationScheme = 'urn:uuid:f64ffdf0-4b97-4e06-b79f-a52b38ec2f8a' AND patId.value = '".trim($SQPatientID)."')" ;


	$SQLStoredQuery_ASS = $SQLStoredQuery_From_ASS.$SQLStoredQuery_Required_ASS;
	$SQLStoredQuery[0]=$SQLStoredQuery_EO;
	$SQLStoredQuery[1]=$SQLStoredQuery_RP;
	$SQLStoredQuery[2]=$SQLStoredQuery_ASS;
	
        break;
	

//*************************GetDocuments******************//
    case "urn:uuid:5c4f972b-d56b-40ac-a5fc-c8ca9b40b9d4":
        $AdhocQuery_case="GetDocuments";
	if (strpos(strtoupper($Value[0][0]),"XDSDOCUMENTENTRYUUID")){
	$SQLStoredQuery[0] = "SELECT doc.id FROM ExtrinsicObject doc WHERE doc.id = '".trim($Value[0][1])."'";
	}

	if (strpos(strtoupper($Value[0][0]),"XDSDOCUMENTENTRYUNIQUEID")){
	$SQLStoredQuery[0] = "SELECT doc.id FROM ExtrinsicObject doc, ExternalIdentifier uniId WHERE uniId.registryobject = doc.id AND uniId.identificationScheme = 'urn:uuid:2e82c1f6-a085-4c72-9da3-8640a32e42ab' AND uniId.value = '".trim($Value[0][1])."'";
	}	
        break;

//*************************GetFolders******************//
    case "urn:uuid:5737b14c-8a1a-4539-b659-e03a34a5e1e4":
        $AdhocQuery_case="GetFolders";
	if (strpos(strtoupper($Value[0][0]),"XDSFOLDERENTRYUUID")){
	$SQLStoredQuery[0] = "SELECT fol.id FROM RegistryPackage fol WHERE fol.id = '".trim($Value[0][1])."'";
	}

	else if (strpos(strtoupper($Value[0][0]),"XDSFOLDERUNIQUEID")){
	$SQLStoredQuery[0] = "SELECT fol.id from RegistryPackage fol, ExternalIdentifier uniq WHERE uniq.registryObject = fol.id AND uniq.identificationScheme = 'urn:uuid:75df8f67-9973-4fbe-a900-df66cefecc5a' AND uniq.value = '".trim($Value[0][1])."'";
	}
        break;

//*************************GetAssociations******************//
    case "urn:uuid:a7ae438b-4bc2-4642-93e9-be891f7bb155":
        $AdhocQuery_case="GetAssociations";
	if (strpos(strtoupper($Value[0][0]),"UUID")){
	$SQLStoredQuery[0] = "SELECT DISTINCT ass.id FROM Association ass WHERE ass.sourceObject = '".trim($Value[0][1])."' OR ass.targetObject = '".trim($Value[0][1])."'";
	}
        break;

//*************************GetDocumentsAndAssociations******************//
    case "urn:uuid:bab9529a-4a10-40b3-a01ff68a615d247a":
        $AdhocQuery_case="GetDocumentsAndAssociations";

	// SQL Part1	
	if (strpos(strtoupper($Value[0][0]),"XDSDOCUMENTENTRYUUID")){
	$SQLStoredQuery[0] = "SELECT DISTINCT ass.id FROM Association ass WHERE ass.sourceObject = '".trim($Value[0][1])."' OR '".trim($Value[0][1])."' ass.targetObject = '".trim($Value[0][1])."'";
	}

	if (strpos(strtoupper($Value[0][0]),"XDSDOCUMENTENTRYUNIQUEID")){
	$SQLStoredQuery[0] = "SELECT doc.id FROM ExtrinsicObject doc, ExternalIdentifier uniqId WHERE uniqId.registryobject = doc.id AND uniqId.identificationScheme = 'urn:uuid:2e82c1f6-a085-4c72-9da3-8640a32e42ab' AND uniqId.value = '".trim($Value[0][1])."'";
	}

	// SQL Part2	
	if (strpos(strtoupper($Value[0][0]),"XDSDOCUMENTENTRYUUID")){
	$SQLStoredQuery[1] = "SELECT DISTINCT ass.id FROM Association ass WHERE ass.sourceObject = '".trim($Value[0][1])."' OR ass.targetObject = '".trim($Value[0][1])."'";
	}
	if (strpos(strtoupper($Value[0][0]),"XDSDOCUMENTENTRYUNIQUEID")){
	$SQLStoredQuery[1] = "SELECT DISTINCT ass.id FROM Association ass, ExtrinsicObject doc, ExternalIdentifier uniqId WHERE uniqId.registryobject = doc.id AND uniqId.identificationScheme = 'urn:uuid:2e82c1f6-a085-4c72-9da3-8640a32e42ab' AND uniqId.value = '".trim($Value[0][1])."' AND (ass.sourceObject = doc.id OR ass.targetObject = doc.id)";
	}
        break;

//*************************GetSubmissionSets******************//
    case "urn:uuid:51224314-5390-4169-9b91-b1980040715a":
        $AdhocQuery_case="GetSubmissionSets";
	if (strpos(strtoupper($Value[0][0]),"UUID")){
	$SQLStoredQuery[0] = "SELECT ss.id FROM RegistryPackage ss, Classification c, Association a WHERE c.classifiedObject = ss.id AND c.classificationNode = 'urn:uuid:a54d6aa5-d40d-43f9-88c5-b4633d873bdd' AND a.sourceObject = ss.id AND a.associationType = 'HasMember' AND a.targetObject = '".trim($Value[0][1])."'";
	}
        break;

//*************************GetSubmissionSetAndContents******************//
    case "urn:uuid:e8e3cb2c-e39c-46b9-99e4-c12f57260b83":
        $AdhocQuery_case="GetSubmissionSetAndContents";

	// SQL Part1	
	for ($SQI=0;$SQI<$conta_SlotSQ;$SQI++) {
		if (strpos(strtoupper($Value[$SQI][0]),"XDSSUBMISSIONSETENTRYUUID")){
		$REGUUID=$Value[$SQI][1];
		$SQLStoredQuery[0] = "SELECT ss.id FROM RegistryPackage ss WHERE ss.id = '".trim($Value[$SQI][1])."'";
		}

		if (strpos(strtoupper($Value[$SQI][0]),"XDSSUBMISSIONSETUNIQUEID")){
		$SQLStoredQuery[0] = "SELECT ss.id FROM RegistryPackage ss, ExternalIdentifier uniq WHERE uniq.registryObject = ss.id AND uniq.identificationScheme = 'urn:uuid:96fdda7c-d067-4183-912e-bf5ee74998a8' AND uniq.value = ".trim($Value[$SQI][1])."'";
		$REGUUID_arr=query_select($SQLStoredQuery[0]);
		$REGUUID = $REGUUID_arr[0][0];
		}
	}

	// SQL Part2
	$SQLStoredQuery_From_EO = "SELECT doc.id FROM ExtrinsicObject doc, Association a ";
	$SQLStoredQuery_Required_EO = "WHERE a.sourceObject = '".trim($REGUUID)."' AND a.associationType = 'HasMember' AND a.targetObject = doc.id";


	$SQLStoredQuery_Optional_EO = "";
	for ($SQI=0;$SQI<$conta_SlotSQ;$SQI++) {

		if (strpos(strtoupper($Value[$SQI][0]),"XDSDOCUMENTENTRYCONFIDENTIALITYCODE")) {
			$SQLStoredQuery_From_EO .= ", Classification cCode ";
			$SQLStoredQuery_Optional_EO .= " AND (conf.classificationScheme = 'urn:uuid:f4f85eac-e6cb-4883-b524-f2705394840f' AND conf.classifiedObject = doc.id AND conf.nodeRepresentation = '".trim($Value[$SQI][1])."')";
		}
		if (strpos(strtoupper($Value[$SQI][0]),"XDSDOCUMENTENTRYFORMATCODE")) {
			$SQLStoredQuery_From_EO .= ", Classification fmtCode ";
			$SQLStoredQuery_Optional_EO .= " AND (fmtCode.classifiedObject = doc.id AND fmtCode.classificationScheme = 'urn:uuid:a09d5840-386c-46f2-b5ad-9c3699a4309d' AND fmtCode.nodeRepresentation = '".trim($Value[$SQI][1])."')";
		}
	}
	
	$SQLStoredQuery[1] = $SQLStoredQuery_From_EO.$SQLStoredQuery_Required_EO.$SQLStoredQuery_Optional_EO;
	$DocUUIDs_arr=query_select($SQLStoredQuery[1]);

	// SQL Part3
	$SQLStoredQuery[2] = "SELECT fol.id FROM RegistryPackage fol, Association a WHERE a.associationType = 'HasMember' AND a.sourceObject = '".trim($REGUUID)."' AND a.targetObject = fol.id";
	$FolUUIDs_arr=query_select($SQLStoredQuery[2]);
	
	// SQL Part4
	$FolUUIDs = "'".trim($FolUUIDs_arr[0][0])."'";
	for ($contaFol=1;$contaFol<count($FolUUIDs_arr);$contaFol++){
	$FolUUIDs .= ",'".trim($FolUUIDs_arr[$contaFol][0])."'";
	}

	$DocUUIDs = "'".trim($DocUUIDs_arr[0][0])."'";
	for ($contaDoc=1;$contaDoc<count($DocUUIDs_arr);$contaDoc++){
	$DocUUIDs .= ",'".trim($DocUUIDs_arr[$contaDoc][0])."'";
	}

	$SQLStoredQuery[3] = "SELECT ass.id FROM Association ass WHERE ass.associationType = 'HasMember' AND ass.sourceObject = '".trim($REGUUID)."' AND (ass.targetObject IN (".$DocUUIDs.") OR ass.targetObject IN (".$FolUUIDs."))";

        break;

//*************************GetFolderAndContents******************//
    case "urn:uuid:b909a503-523d-4517-8acf-8e5834dfc4c7":
        $AdhocQuery_case="GetFolderAndContents";

	for ($SQI=0;$SQI<$conta_SlotSQ;$SQI++) {

	// SQL Part1
	if (strpos(strtoupper($Value[0][0]),"XDSFOLDERENTRYUUID")){
	$FolderUUID=trim($Value[0][1]);
	$SQLStoredQuery[0] = "SELECT fol.id FROM RegistryPackage fol WHERE fol.id = '".trim($Value[0][1])."'";
	}
	if (strpos(strtoupper($Value[0][0]),"XDSFOLDERUNIQUEID")){
	$SQLStoredQuery[0] = "SELECT fol.id from RegistryPackage fol, ExternalIdentifier uniq WHERE uniq.registryObject = fol.id AND uniq.identificationScheme = 'urn:uuid:75df8f67-9973-4fbe-a900-df66cefecc5a' AND uniq.value = '".trim($Value[0][1])."'";
	$FolderUUID_arr=query_select($SQLStoredQuery[0]);
	$FolderUUID = $FolderUUID_arr[0][0];
	}
	

	// SQL Part2
	$SQLStoredQuery_From_EO = "SELECT doc.id FROM ExtrinsicObject doc, Association a ";
	$SQLStoredQuery_Required_EO = "WHERE a.sourceObject = '$FolderUUID' AND a.associationType = 'HasMember' AND a.targetObject = doc.id";

	$SQLStoredQuery_Optional = "";

		if (strpos(strtoupper($Value[$SQI][0]),"XDSDOCUMENTENTRYCONFIDENTIALITYCODE")) {
			$SQLStoredQuery_From_EO .= ", Classification conf  ";
			$SQLStoredQuery_Optional_EO .= " AND ( conf.classificationScheme = 'urn:uuid:f4f85eac-e6cb-4883-b524-f2705394840f' AND conf.classifiedObject = doc.id AND conf.nodeRepresentation = '".trim($Value[$SQI][1])."')";
		}

		if (strpos(strtoupper($Value[$SQI][0]),"XDSDOCUMENTENTRYFORMATCODE")) {
			$SQLStoredQuery_From_EO .= ", Classfication fmtCode ";
			$SQLStoredQuery_Optional_EO .= " AND (fmtCode.classifiedObject = doc.id AND  fmtCode.classificationScheme = 'urn:uuid:a09d5840-386c-46f2-b5ad-9c3699a4309d' AND fmtCode.nodeRepresentation = '".trim($Value[$SQI][1])."')";
		}
	
	$SQLStoredQuery[1] = $SQLStoredQuery_From_EO.$SQLStoredQuery_Required_EO.$SQLStoredQuery_Optional_EO;
	$DocUUID_arr=query_select($SQLStoredQuery[1]);
	$DocUUID = $DocUUID_arr[0][0];
	

	// SQL Part3
	$SQLStoredQuery[2] = "SELECT ass.id FROM Association ass WHERE ass.associationType = 'HasMember' AND ass.sourceObject = '".$FolderUUID."' AND ass.targetObject = '".$DocUUID."'";
	}
  

        break;

//*************************GetFoldersForDocument******************//
    case "urn:uuid:10cae35a-c7f9-4cf5-b61efc3278ffb578":
        $AdhocQuery_case="GetFoldersForDocument";

	for ($SQI=0;$SQI<$conta_SlotSQ;$SQI++) {

	// SQL Part1
	if (strpos(strtoupper($Value[0][0]),"XDSDOCUMENTENTRYENTRYUUID")){
	$FolderUUID=trim($Value[0][1]);
	$SQLStoredQuery[0] = "SELECT fol.id FROM RegistryPackage fol, Association a, ExtrinsicObject doc, Classification c WHERE doc.id IN (SELECT doc.id FROM ExtrinsicObject doc WHERE doc.id = '".trim($Value[0][1])."')";
	}
	if (strpos(strtoupper($Value[0][0]),"XDSDOCUMENTENTRYUNIQUEID")){
	$SQLStoredQuery[0] = "SELECT fol.id FROM RegistryPackage fol, Association a, ExtrinsicObject doc, Classification c WHERE doc.id IN (SELECT doc.id FROM ExtrinsicObject doc, ExternalIdentifier uniqId WHERE uniqId.registryobject = doc.id AND uniqId.identificationScheme = 'urn:uuid:2e82c1f6-a085-4c72-9da3-8640a32e42ab' AND uniqId.value = '".trim($Value[0][1])."') AND a.targetObject = doc.id AND a.associationType = 'HasMember' AND a.sourceObject = fol.id AND c.classifiedObject = fol.id AND c.classificationNode = 'urn:uuid:d9d542f3-6cc4-48b6-8870-ea235fbc94c2'";
	}
	}
        break;

//*************************GetRelatedDocuments******************//
    case "urn:uuid:d90e5407-b356-4d91-a89f-873917b4b0e6":
        $AdhocQuery_case="GetRelatedDocuments";
	for ($SQI=0;$SQI<$conta_SlotSQ;$SQI++) {
		if (strpos(strtoupper($Value[$SQI][0]),"XDSDOCUMENTENTRYENTRYUUID")) {
			$DocUUID = $Value[$SQI][1];
		}
		if (strpos(strtoupper($Value[$SQI][0]),"XDSDOCUMENTENTRYUNIQUEID")) {
			$SQLSelectDOCID = "SELECT doc.id FROM ExtrinsicObject doc, ExternalIdentifier WHERE uniqId.registryobject = doc.id AND uniqId.identificationScheme = 'urn:uuid:2e82c1f6-a085-4c72-9da3-8640a32e42ab' AND uniqId.value = '".trim($Value[$SQI][1])."'";
			$DocUUID_arr=query_select($SQLSelectDOCID);
			$DocUUID = $DocUUID_arr[0][0];
			
		}
		if (strpos(strtoupper($Value[$SQI][0]),"ASSOCIATIONTYPES")) {
			$SQAssociation = $Value[$SQI][1];
		}

	}

	// SQL Part1
	$SQLStoredQuery[0] = "SELECT a.id FROM Association a, ExtrinsicObject doc WHERE doc.id = '".trim($DocUUID)."' AND a.associationType = '".trim($SQAssociation)."' AND (a.sourceObject = doc.id OR a.targetObject = doc.id)";
	$AssUUID_arr=query_select($SQLStoredQuery[0]);
	


	// SQL Part2
	$AssUUIDs = "'".trim($AssUUID_arr[0][0])."'";
	for ($contaAss=1;$contaAss<count($AssUUID_arr);$contaAss++){
	$AssUUIDs .= ",'".trim($AssUUID_arr[$contaAss][0])."'";
	}
	$SQLStoredQuery[1] = "SELECT doc.id FROM ExtrinsicObject doc, Association a WHERE a.id IN (".$AssUUIDs.") AND (doc.id = a.sourceObject OR doc.id = a.targetObject)";
	
        break;
}

$contaQuery=count($SQLStoredQuery);
fwrite($fp_Slot_val,$AdhocQuery_case."\n\r");
for($SQcount=0;$SQcount<$contaQuery;$SQcount++){
fwrite($fp_Slot_val,$SQLStoredQuery[$SQcount]."\n\r");
}

fclose($fp_Slot_val);

















/*
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

*/
















// qui devo avere la query sql
//SCRIVO LA QUERY
$fp_SQLQuery = fopen($tmpQueryService_path."SQLQuery_RICEVUTA","wb+");
	fwrite($fp_SQLQuery,$SQLQuery);
fclose($fp_SQLQuery);

###### CONTROLLO SQL RICEVUTA
include_once('reg_validation.php');



for($SQcount=0;$SQcount<$contaQuery;$SQcount++){
$SQLQuery = $SQLStoredQuery[$SQcount];
$controllo_query_array = controllaQuery($SQLQuery);

###### CASO DI VALIDAZIONE SQL ===NON=== PASSATA
if(!$controllo_query_array[0])
{
	#### STRINGA DI ERRORE
	$failure_response = $controllo_query_array[1];
	
	### RESTITUISCE IL MESSAGGIO DI FAIL IN SOAP
    	$SOAPED_failure_response = makeSoapedFailureResponse($failure_response,$logentry_query);

	### SCRIVO LA RISPOSTA IN UN FILE
	 $fp = fopen($tmpQueryService_path."SOAPED_failure_response","w+");
           fwrite($fp,$SOAPED_failure_response);
         fclose($fp);

	### PULISCO IL BUFFER DI USCITA
	ob_get_clean();//OKKIO FONDAMENTALE!!!!!

	### HEADERS
	header("HTTP/1.1 200 OK");
	header("Path: $www_REG_path");
	header("Content-Type: text/xml;charset=UTF-8");
	header("Content-Length: ".(string)filesize($tmpQueryService_path."SOAPED_failure_response"));
	### CONTENUTO DEL FILE DI RISPOSTA
	if($file = fopen($tmpQueryService_path."SOAPED_failure_response",'rb'))
	{
   		while((!feof($file)) && (connection_status()==0))
   		{
     			print(fread($file,1024*8));
      			flush();//NOTA BENE!!!!!!!!!
   		}

   		fclose($file);
	}
	### SPEDISCO E PULISCO IL BUFFER DI USCITA
	ob_end_flush();
	### BLOCCO L'ESECUZIONE DELLO SCRIPT
	exit;

}//END OF if(!$controllo_query_array[0])

#### SE SONO QUI HO SUPERATO TUTTI I VINCOLI DI VALIDAZIONE ####

$fp = fopen($tmpQueryService_path."POST_VALIDATION", "w+");
      fwrite($fp,'SUPERATO IL VINCOLO DI VALIDAZIONE SU TIPO DI SQL + SCHEMAS');
fclose($fp);

########################################################################
### ORA DEVO ESEGUIRE LA QUERY SUL DB DEL O3_XDS_REGISTRY_QUERY REGISTRY 

################ RISPOSTA ALLA QUERY (ARRAY)
###METTO A POSTO EVENTUALI STRINGHE DI COMANDO
$SQLQuery_ESEGUITA=adjustQuery($SQLQuery);#### IMPORTANTE!!!
###SCRIVO LA QUERY CHE EFFETTIVAMENTE LANCIO A DB
$fp_SQLQuery = fopen($tmpQueryService_path."SQLQuery_ESEGUITA","wb+");
	fwrite($fp_SQLQuery,$SQLQuery_ESEGUITA);
fclose($fp_SQLQuery);

###### ESEGUO LA QUERY
$SQLResponse = query_select($SQLQuery_ESEGUITA);

$fp_SQLResponse = fopen($tmpQueryService_path."SQLResponse","a+");
fwrite($fp_SQLResponse,"RISPOSTA DAL DB:\n");
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
	$failure_response = "\n[EMPTY RESULT] - SQL QUERY\n[  ".avoidHtmlEntitiesInterpretation($SQLQuery)." ]\n DID NOT GIVE ANY RESULTS IN THIS REGISTRY\n";
	
	### RESTITUISCE IL MESSAGGIO DI SUCCESS IN SOAP
	### ANCHE SE IL RISULTATO DELLA QUERY DA DB È VUOTO
    	$SOAPED_failure_response = makeSoapedSuccessQueryResponse($logentry_query,$failure_response);

	### SCRIVO LA RISPOSTA IN UN FILE
	 $fp = fopen($tmpQueryService_path."SOAPED_NORESULTS_response","w+");
           fwrite($fp,$SOAPED_failure_response);
         fclose($fp);

	### PULISCO IL BUFFER DI USCITA
	ob_get_clean();//OKKIO FONDAMENTALE!!!!!

	### HEADERS
	header("HTTP/1.1 200 OK");
	header("Path: $www_REG_path");
	header("Content-Type: text/xml;charset=UTF-8");
	header("Content-Length: ".(string)filesize($tmpQueryService_path."SOAPED_NORESULTS_response"));
	### CONTENUTO DEL FILE DI RISPOSTA
	if($file = fopen($tmpQueryService_path."SOAPED_NORESULTS_response",'rb'))
	{
   		while((!feof($file)) && (connection_status()==0))
   		{
     			print(fread($file,1024*8));
      			flush();//NOTA BENE!!!!!!!!!
   		}

   		fclose($file);
	}
	### SPEDISCO E PULISCO IL BUFFER DI USCITA
	ob_end_flush();
	### BLOCCO L'ESECUZIONE DELLO SCRIPT
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
		$dom_ebXML_ExtrinsicObject_root->set_namespace($ns_rim_path,$ns_rim);
		$dom_ebXML_ExtrinsicObject_root->add_namespace($ns_q_path,$ns_q);

		####OTTENGO DAL DB GLI ATTRIBUTI DI ExtrinsicObject
		$queryForExtrinsicObjectAttributes = "SELECT isOpaque,majorVersion,mimeType,minorVersion,objectType,status FROM ExtrinsicObject WHERE ExtrinsicObject.id = '$ExtrinsicObject_id'";
		$ExtrinsicObjectAttributes=query_select($queryForExtrinsicObjectAttributes);
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
		$Name_arr=query_select($queryForExtrinsicObject_Name);
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
		$Description_arr=query_select($queryForExtrinsicObject_Description);
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
		$Slot_arr=query_select($select_Slots);
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
				$sourcePatientInfo_Slots=query_select($select_sourcePatientInfo_Slots);
				writeSQLQueryService($sourcePatientInfo_Slots);
				
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
			$get_ExtrinsicObject_Classification="SELECT classificationScheme,classificationNode,classifiedObject,id,nodeRepresentation,objectType FROM Classification WHERE Classification.classifiedObject = '$ExtrinsicObject_id' AND Classification.nodeRepresentation != 'NULL'";
			$ExtrinsicObject_Classification_arr=query_select($get_ExtrinsicObject_Classification);
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
				$Name_arr=query_select($queryForClassification_Name);
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

				$Description_arr=query_select($queryForClassification_Description);
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

				#### SLOT
				$dom_ebXML_Classification_Slot=$dom_ebXML_ExtrinsicObject->create_element_ns($ns_rim_path,"Slot");
				$dom_ebXML_Classification_Slot=$dom_ebXML_ExtrinsicObject_Classification->append_child($dom_ebXML_Classification_Slot);

				$dom_ebXML_Classification_Slot_ValueList=$dom_ebXML_ExtrinsicObject->create_element_ns($ns_rim_path,"ValueList");
				$dom_ebXML_Classification_Slot_ValueList=$dom_ebXML_Classification_Slot->append_child($dom_ebXML_Classification_Slot_ValueList);

				$dom_ebXML_Classification_Slot_ValueList_Value=$dom_ebXML_ExtrinsicObject->create_element_ns($ns_rim_path,"Value");
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

			}//END OF for($t=0;$t<count($ExtrinsicObject_Classification_arr);$t++)

			#### NODI EXTERNALIDENTIFIER
			$get_ExtrinsicObject_ExternalIdentifier="SELECT identificationScheme,objectType,id,value FROM ExternalIdentifier WHERE ExternalIdentifier.registryObject = '$ExtrinsicObject_id'";
			$ExtrinsicObject_ExternalIdentifier_arr=query_select($get_ExtrinsicObject_ExternalIdentifier);
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
				$Name_arr=query_select($queryForExternalIdentifier_Name);
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
				$Description_arr=query_select($queryForExternalIdentifier_Description);
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
		$RegistryPackageAttributes=query_select($queryForRegistryPackageAttributes);
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
		$Name_arr=query_select($queryForRegistryPackage_Name);
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
		$Description_arr=query_select($queryForRegistryPackage_Description);
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
		$Slot_arr=query_select($select_Slots);
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
				$authorPerson_Slots=query_select($select_authorPerson_Slots);
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
			$get_RegistryPackage_Classification="SELECT classificationScheme,classificationNode,classifiedObject,id,nodeRepresentation,objectType FROM Classification WHERE Classification.classifiedObject = '$RegistryPackage_id'";
			$RegistryPackage_Classification_arr=query_select($get_RegistryPackage_Classification);
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
				$Name_arr=query_select($queryForClassification_Name);
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
				$Description_arr=query_select($queryForClassification_Description);
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
				$Slot_arr=query_select($select_Slots);
				writeSQLQueryService($select_Slots);

				#### RICAVO LE INFO SUL NODO SLOT
				$Slot=$Slot_arr[0];
				$Slot_name = $Slot[0];
				$Slot_value = $Slot[1];

				$dom_ebXML_Classification_Slot->set_attribute("name",$Slot_name);
				$dom_ebXML_Classification_Slot_ValueList_Value->set_content($Slot_value);

			}//END OF for($t=0;$t<count($RegistryPackage_Classification_arr);$t++)

			#### NODI EXTERNALIDENTIFIER
			$get_RegistryPackage_ExternalIdentifier="SELECT identificationScheme,objectType,id,value FROM ExternalIdentifier WHERE ExternalIdentifier.registryObject = '$RegistryPackage_id'";
			$RegistryPackage_ExternalIdentifier_arr=query_select($get_RegistryPackage_ExternalIdentifier);
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
				$Name_arr=query_select($queryForExternalIdentifier_Name);
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
				$Description_arr=query_select($queryForExternalIdentifier_Description);
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
		$RegistryPackageAttributes=query_select($queryForRegistryPackageAttributes);
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
		$Name_arr=query_select($queryForRegistryPackage_Name);
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
		$Description_arr=query_select($queryForRegistryPackage_Description);
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
				$dom_ebXML_RegistryPackage_Slot=$dom_ebXML_RegistryPackage->create_element_ns($ns_rim_path,"Slot");
				$dom_ebXML_RegistryPackage_Slot=$dom_ebXML_RegistryPackage_root->append_child($dom_ebXML_RegistryPackage_Slot);

				$select_authorPerson_Slots = "SELECT value FROM Slot WHERE Slot.parent = '$RegistryPackage_id' AND Slot.name = 'authorPerson'";
				$authorPerson_Slots=query_select($select_authorPerson_Slots);
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
			$RegistryPackage_Classification_arr=query_select($get_RegistryPackage_Classification);
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
				$Name_arr=query_select($queryForClassification_Name);
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
				$Description_arr=query_select($queryForClassification_Description);
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
				$Slot_arr=query_select($select_Slots);
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
			$RegistryPackage_ExternalIdentifier_arr=query_select($get_RegistryPackage_ExternalIdentifier);
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
				$Name_arr=query_select($queryForExternalIdentifier_Name);
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
				$Description_arr=query_select($queryForExternalIdentifier_Description);
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
		$AssociationAttributes=query_select($queryForAssociationAttributes);
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
		$Name_arr=query_select($queryForAssociation_Name);
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
		$Description_arr=query_select($queryForAssociation_Description);
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
		$Slot_arr=query_select($select_Slots);
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
$ebXML_Response_SOAPED_string = makeSoapedSuccessQueryResponse($logentry_query,$ebXML_Response_string);

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
include_once("reg_atna.php");
	
	$eventOutcomeIndicator="0"; //EventOutcomeIndicator 0 OK 12 ERROR
	$ip_registry=$reg_host; //Ip Registry

	$ip_consumer=$_SERVER['REMOTE_ADDR'];  //Ip consumer

	createQueryEvent($eventOutcomeIndicator,$ip_registry,$ip_consumer,$SUID,$SQLQuery);
	$java_atna_query=("java -jar ".$path_to_ATNA_jar."syslog.jar -u ".$ATNA_host." -p ".$ATNA_port." -f ".$atna_path."Query.xml");

	$fp_ebxml_val = fopen($tmp_path.$idfile."-comando_java_atna_query-".$idfile,"w+");
	fwrite($fp_ebxml_val,$java_atna_query);
	fclose($fp_ebxml_val);


	$java_call_result = exec("$java_atna_query");

	//$INSERT_atna_query = "INSERT INTO AuditableEvent (eventType,registryObject,timeStamp,Source) VALUES ('Query','".$SUID."',CURRENT_TIMESTAMP,'".$ip_consumer."')";

	$ris_query = query_exec($INSERT_atna_query);

} // Fine if($ATNA_active=='A')


// ATNA Export
if($ATNA_active=='A'){
include_once("reg_atna.php");
	
	$eventOutcomeIndicator="0"; //EventOutcomeIndicator 0 OK 12 ERROR
	$ip_registry=$reg_host; //Ip Registry

	$ip_consumer=$_SERVER['REMOTE_ADDR'];  //Ip consumer

	for($e=0;$e<count($ExtrinsicObject_ExternalIdentifier_arr);$e++) {

		//Study Instance UID
		if($ExtrinsicObject_ExternalIdentifier_arr[$e]['identificationScheme']=='urn:uuid:2e82c1f6-a085-4c72-9da3-8640a32e42ab'){
			$SUID=$ExtrinsicObject_ExternalIdentifier_arr[$e]['value'];
		}

		// Patient Id
		if($ExtrinsicObject_ExternalIdentifier_arr[$e]['identificationScheme']=='urn:uuid:58a6f841-87b3-4a3e-92fd-a8ffeff98427'){
			$pid=$ExtrinsicObject_ExternalIdentifier_arr[$e]['value'];
		}
	}

	// Patient Name
	for($s=0;$s<count($Slot_arr_EO);$s++){

		if($Slot_arr_EO[$s]['name']=='sourcePatientInfo' && substr_count(trim(adjustString($Slot_arr_EO[$s]['value'])),'PID-5')>0){
		$pna=$Slot_arr_EO[$s]['value'];
		}

	}


	$idName="Study Instance UID"; 
	
	createExportEvent($eventOutcomeIndicator,$ip_registry,$ip_consumer,$SUID,$idName,$pid,$pna);
	$java_atna_export=("java -jar ".$path_to_ATNA_jar."syslog.jar -u ".$ATNA_host." -p ".$ATNA_port." -f ".$atna_path."DataExport.xml");

	$fp_ebxml_val = fopen($tmp_path.$idfile."-comando_java_atna_export-".$idfile,"w+");
	fwrite($fp_ebxml_val,$java_atna_export);
	fclose($fp_ebxml_val);


	$java_call_result = exec("$java_atna_export");

	//$INSERT_atna_export = "INSERT INTO AuditableEvent (eventType,registryObject,timeStamp,user) VALUES ('Export','".$SUID."',CURRENT_TIMESTAMP,'".$ip_consumer."')";

	$ris_export = query_exec($INSERT_atna_export);

} // Fine if($ATNA_active=='A')
} // Fine for($SQcount=0;$SQcount<count($SQLStoredQuery);$SQcount++){
################## END OF REGISTRY RESPONSE TO QUERY ####################

unset($_SESSION['tmp_path']);
unset($_SESSION['idfile']);
unset($_SESSION['logActive']);
unset($_SESSION['log_query_path']);

?>