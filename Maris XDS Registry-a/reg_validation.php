<?php
# ------------------------------------------------------------------------------------
# MARIS XDS REGISTRY
# Copyright (C) 2007 - 2010  MARiS Project
# Dpt. Medical and Diagnostic Sciences, University of Padova - csaccavini@rad.unipd.it
# This program is distributed under the terms and conditions of the GPL
# See the LICENSE files for details
# ------------------------------------------------------------------------------------

#### INCLUDO LE LIBRERIE DI SCRITTURA SU DB DEL REGISTRY
require_once('./config/config.php');
require_once('./lib/functions_'.$database.'.php');
//include($lib_path."utilities.php");

writeSQLQuery('-------------------------------------------------------------------------------------');
writeSQLQuery('reg_validation.php');
//RICERCA ALL'INTERNO DELL'ebXML 
  // $tag = 'ExtrinsicObject' $id = 'uniqueId'  --->  XDSDocumentEntry.uniqueId
  // $tag = 'RegistryPackage' $id = 'uniqueId'  --->  XDSSubmissionSet.uniqueId 

//true = valido
//false = NON valido
function validate_XDSSubmissionSetUniqueId($dom,$idfile)
{
writeSQLQuery('---------------------------validate_XDSSubmissionSetUniqueId--------------------------------');

	//$fp_uniqueIdQuery = fopen("tmp/".$idfile."-SubmissionSetUniqueIdQuery-".$idfile,"w+");
		
    	//$ebxml_value = searchForIds($dom,'RegistryPackage','uniqueId');
    
	$ebxml_value = '';

##### RADICE DEL DOCUMENTO ebXML
	$root_ebXML = $dom->document_element();
	
	##### ARRAY DEI NODI REGISTRYPACKAGE
	$dom_ebXML_RegistryPackage_node_array=$root_ebXML->get_elements_by_tagname("RegistryPackage");

	#### CICLO SU OGNI RegistryPackage ####
	$isEmpty_1 = false;
	$failure_1 = "";
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

    	//QUERY AL DB
    	//$query = "SELECT * FROM SUBMISSIONS WHERE XDSSubmissionSet_uniqueId = '$ebxml_value'";
	$query = "SELECT * FROM ExternalIdentifier WHERE  value = '$ebxml_value'";

		//fwrite($fp_uniqueIdQuery,$query);
	//fclose($fp_uniqueIdQuery);


    	$res = query_select($query); //array bidimensionale
	writeSQLQuery($query);
    	$isEmpty_1 = (empty($res));

	if(!$isEmpty_1)
	{
		$failure_1="\nXDSSubmissionSet.uniqueId $ebxml_value (urn:uuid:96fdda7c-d067-4183-912e-bf5ee74998a8) already exists in registry\n";
		
	}//END OF if(!$isEmpty)

    	$ret = array($isEmpty_1,$failure_1);

    	return $ret;

}//end of validate_XDSSubmissionSetUniqueId($dom)

function validate_XDSDocumentEntryPatientIdInsert($dom)
{
writeSQLQuery('-----------------------------validate_XDSDocumentEntryPatientIdInsert------------------------------');

//$log = new Log_REG("REG");
//$log = new Log_REG();
	/*
!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
versione che non richiede la presenza del patientId nella tabella Patient
!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
Per superare controllo su patientID 
	# $isEmpty_2 deve essere false
	# $failure_2 è log di errore (non interessa)
	# $patientIdS è controllo che tutti gli attachments di una submission siano dello stesso paziente. Non deve essere forzato 
	# $ExtrinsicObject_node_id_attr_array non va forzato
*/

	$ebxml_value = '';

##### RADICE DEL DOCUMENTO ebXML
	$root_ebXML = $dom->document_element();
	
	##### ARRAY DEI NODI ExtrinsicObject
	$dom_ebXML_ExtrinsicObject_node_array=$root_ebXML->get_elements_by_tagname("ExtrinsicObject");

	$isEmpty_2 = false;
	$failure_2 = "";
	$patientIdS=array();
	$ExtrinsicObject_node_id_attr_array=array();
	if(!empty($dom_ebXML_ExtrinsicObject_node_array))
	{
// 		$fp_patientIdQuery = fopen("tmp/DocumentEntryPatientIdQuery","w+");

	#### CICLO SU OGNI ExtrinsicObject ####
// 	$isEmpty_2 = false;
// 	$failure_2 = "";
	$ExtrinsicObject_node_id_attr_array=array();
	$patientIdS=array();
	for($index=0;$index<(count($dom_ebXML_ExtrinsicObject_node_array));$index++)
	{
	##### NODO ExtrinsicObject RELATIVO AL DOCUMENTO NUMERO $index
	$ExtrinsicObject_node = $dom_ebXML_ExtrinsicObject_node_array[$index];

	### RECUPERO L'ATTRIBUTO id DEL NODO EXTRINSICOBJECT
	$ExtrinsicObject_node_id_attr = $ExtrinsicObject_node->get_attribute('id');
	### INSERISCO NELL'ARRAY DA TORNARE
	$ExtrinsicObject_node_id_attr_array[$index]=$ExtrinsicObject_node_id_attr;
	
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
			//$value_value= avoidHtmlEntitiesInterpretation($externalidentifier_node->get_attribute('value'));
			$value_value=$externalidentifier_node->get_attribute('value');
			
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
					if(strpos(strtolower(trim($LocalizedString_value)),strtolower('DocumentEntry.patientId')))
					{
						$ebxml_value = $value_value;
						$patientIdS[$index]=$ebxml_value;
					}
					
				}

			}

				}
			}
		}

	}

	//QUERY AL DB
    	$query = "SELECT * FROM Patient WHERE PID3 = '$ebxml_value'";
	writeSQLQuery($query);
// 		fwrite($fp_patientIdQuery,$query);
// 	fclose($fp_patientIdQuery);
    	
	### EFFETTUO LA QUERY ED OTTENGO IL RISULTATO
	$res = query_select($query); //array bidimensionale
	 
    	$isEmpty_2 = ((empty($res)) || $isEmpty_2);
    	if($isEmpty_2)###---> patientId non noto --> lo inserisco nella tabella Patient
	{
		writeTimeFile("Registry: patId SCONOSCIUTO");
		$insertPatient= adjustQuery("INSERT INTO Patient (ID,PID3,InsertDate) VALUES ('','$ebxml_value',CURRENT_TIMESTAMP)");
		query_exec($insertPatient);
		writeSQLQuery($insertPatient);


	}
	
	}//END OF for($index=0;$index<(count($dom_ebXML_ExtrinsicObject_node_array));$index++)
	}
$isEmpty_2= false;
	$ret = array($isEmpty_2,$failure_2,$patientIdS,$ExtrinsicObject_node_id_attr_array);

	return $ret;

}//end of validate_XDSDocumentEntryPatientId($dom)


/*
!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
validate_XDSDocumentEntryPatientId con effettivo controllo su patientId
!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
*/

function validate_XDSDocumentEntryPatientIdError($dom)
{
	writeSQLQuery('-----------------------------validate_XDSDocumentEntryPatientIdError------------------------------');
	$ebxml_value = '';

##### RADICE DEL DOCUMENTO ebXML
	$root_ebXML = $dom->document_element();
	
	##### ARRAY DEI NODI ExtrinsicObject
	$dom_ebXML_ExtrinsicObject_node_array=$root_ebXML->get_elements_by_tagname("ExtrinsicObject");

	$isEmpty_2 = false;
	$failure_2 = "";
	$patientIdS=array();
	$ExtrinsicObject_node_id_attr_array=array();
	if(!empty($dom_ebXML_ExtrinsicObject_node_array))
	{
// 		$fp_patientIdQuery = fopen("tmp/DocumentEntryPatientIdQuery","w+");

	#### CICLO SU OGNI ExtrinsicObject ####
// 	$isEmpty_2 = false;
// 	$failure_2 = "";
	$ExtrinsicObject_node_id_attr_array=array();
	$patientIdS=array();
	for($index=0;$index<(count($dom_ebXML_ExtrinsicObject_node_array));$index++)
	{
	##### NODO ExtrinsicObject RELATIVO AL DOCUMENTO NUMERO $index
	$ExtrinsicObject_node = $dom_ebXML_ExtrinsicObject_node_array[$index];

	### RECUPERO L'ATTRIBUTO id DEL NODO EXTRINSICOBJECT
	$ExtrinsicObject_node_id_attr = $ExtrinsicObject_node->get_attribute('id');
	### INSERISCO NELL'ARRAY DA TORNARE
	$ExtrinsicObject_node_id_attr_array[$index]=$ExtrinsicObject_node_id_attr;
	
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
			//$value_value= avoidHtmlEntitiesInterpretation($externalidentifier_node->get_attribute('value'));
			$value_value=$externalidentifier_node->get_attribute('value');
			
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
					if(strpos(strtolower(trim($LocalizedString_value)),strtolower('DocumentEntry.patientId')))
					{
						$ebxml_value = $value_value;
						$patientIdS[$index]=$ebxml_value;
					}
					
				}

			}

				}
			}
		}

	}

	//QUERY AL DB
    	$query = "SELECT * FROM Patient WHERE  PID3 = '$ebxml_value'";
	writeSQLQuery($query); 
// 		fwrite($fp_patientIdQuery,$query);
// 	fclose($fp_patientIdQuery);
    	
	### EFFETTUO LA QUERY ED OTTENGO IL RISULTATO
	$res = query_select($query); //array bidimensionale
	 
    	$isEmpty_2 = ((empty($res)) || $isEmpty_2);
    	if($isEmpty_2)###---> patientId non noto --> eccezione
	{
		$failure_2=$failure_2."\nDocument '$ExtrinsicObject_node_id_attr' - ExternalIdentifier XDSDocumentEntry.patientId ".htmlentities($ebxml_value)."  (urn:uuid:58a6f841-87b3-4a3e-92fd-a8ffeff98427) is required but not found\n";
	}

	}//END OF for($index=0;$index<(count($dom_ebXML_ExtrinsicObject_node_array));$index++)
	}
	$ret = array($isEmpty_2,$failure_2,$patientIdS,$ExtrinsicObject_node_id_attr_array);

	return $ret;

}//end of validate_XDSDocumentEntryPatientId($dom)




##### XDSDocumentEntry.uniqueId
function validate_XDSDocumentEntryUniqueId($dom)
{
	writeSQLQuery('----------------------------validate_XDSDocumentEntryUniqueId-------------------------------');
	$ebxml_value = '';

##### RADICE DEL DOCUMENTO ebXML
	$root_ebXML = $dom->document_element();
	
	##### ARRAY DEI NODI ExtrinsicObject
	$dom_ebXML_ExtrinsicObject_node_array=$root_ebXML->get_elements_by_tagname("ExtrinsicObject");

	$isEmpty_5 = true;
	$failure_5 = "";
	$ExtrinsicObject_node_id_attr_array=array();
	if(!empty($dom_ebXML_ExtrinsicObject_node_array))
	{


	#### CICLO SU OGNI ExtrinsicObject ####
	$ExtrinsicObject_node_id_attr_array=array();
	for($index=0;$index<(count($dom_ebXML_ExtrinsicObject_node_array));$index++)
	{
	##### NODO ExtrinsicObject RELATIVO AL DOCUMENTO NUMERO $index
	$ExtrinsicObject_node = $dom_ebXML_ExtrinsicObject_node_array[$index];
	
	### RECUPERO L'ATTRIBUTO id DEL NODO EXTRINSICOBJECT
	$ExtrinsicObject_node_id_attr = $ExtrinsicObject_node->get_attribute('id');
	### INSERISCO NELL'ARRAY DA TORNARE
	$ExtrinsicObject_node_id_attr_array[$index]=$ExtrinsicObject_node_id_attr;

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

		if($ExtrinsicObject_child_node_tagname=='Slot')
		{
			$slot_node=$ExtrinsicObject_child_node;
			$slot_name=$slot_node->get_attribute('name');
			if($slot_name=='hash')
			{
				#### NODI FIGLI DI SLOT
				$slot_child_nodes = $slot_node->child_nodes();

				for($q = 0;$q < count($slot_child_nodes);$q++)
				{
					$slot_child_node = $slot_child_nodes[$q];
				$slot_child_node_tagname = $slot_child_node->node_name();
				if($slot_child_node_tagname=='ValueList')
				{
					$valuelist_node = $slot_child_node;
				//print_r($valuelist_node);
					$valuelist_child_nodes = $valuelist_node->child_nodes();
				//print_r($valuelist_child_nodes);
					## UN SOLO VALUE
					if(count($valuelist_child_nodes)==3)
					{
					  $value_node = $valuelist_child_nodes[1];
					  $hash_value = $value_node->get_content();
					}
				     }
				}
			}
		}
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
						$ebxml_value = $value_value;
						$DocumentEntry_uniqueId[$index] = $value_value;
					}
					
				}

			}

				}
			}
		}//END OF if($ExtrinsicObject_child_node_tagname=='ExternalIdentifier')

	}

	if($ebxml_value!="")
	{
	//QUERY AL DB
    	$query = "SELECT * FROM ExternalIdentifier WHERE  value = '$ebxml_value'";
	writeSQLQuery($query);
    	
	### EFFETTUO LA QUERY ED OTTENGO IL RISULTATO
	$res = query_select($query); //array bidimensionale
	 
    	$isEmpty_5 = ((empty($res)) && $isEmpty_5);
    	if(!$isEmpty_5)###---> uniqueId già presente
	{
	     ###DEVO CONFRONTARE L'HASH
	     $query_1 = "SELECT * FROM Slot WHERE parent = '".$res[0]['registryObject']."' AND name = 'hash'";
	     writeSQLQuery($query_1);
		$fp_registryObjectQuery = fopen("tmp/registryObjectQuery","w+");
		fwrite($fp_registryObjectQuery,$query_1);
		fclose($fp_registryObjectQuery);

	     $res_1 = query_select($query_1);
	     $value_current=$res_1[0]['value'];
	     if($value_current!=$hash_value)
	     {
		$failure_5=$failure_5."\nDocument '$ExtrinsicObject_node_id_attr' - ExternalIdentifier XDSDocumentEntry.uniqueId $ebxml_value (urn:uuid:2e82c1f6-a085-4c72-9da3-8640a32e42ab) already exists in registry with a Different hash value ($value_current)\n";

	      }//END OF if($res_1[0]['value']==$hash_value)
	      else $isEmpty_5 = true;

	}//END OF if(!$isEmpty_5)

	}
	else continue;

	}//END OF for($index=0;$index<(count($dom_ebXML_ExtrinsicObject_node_array));$index++)
	
	}

	$ret = array($isEmpty_5,$failure_5,$ExtrinsicObject_node_id_attr_array,$DocumentEntry_uniqueId);

	return $ret;
  
}//end of validate_XDSDocumentEntryUniqueId($dom)

function validate_XDSSubmissionSetPatientIdError($dom,$idfile)
{
	writeSQLQuery('--------------------------validate_XDSSubmissionSetPatientId---------------------------------');
	//$fp_patientIdQuery = fopen("tmp/".$idfile."-SubmissionSetPatientIdQuery-".$idfile,"w+");
		
    	//$ebxml_value = searchForIds($dom,'RegistryPackage','uniqueId');
    
	$ebxml_value = '';

##### RADICE DEL DOCUMENTO ebXML
	$root_ebXML = $dom->document_element();
	
	##### ARRAY DEI NODI REGISTRYPACKAGE
	$dom_ebXML_RegistryPackage_node_array=$root_ebXML->get_elements_by_tagname("RegistryPackage");

	#### CICLO SU OGNI RegistryPackage ####
	$isEmpty_3 = false;
	$failure_3 = "";
	for($index=0;$index<(count($dom_ebXML_RegistryPackage_node_array));$index++)
	{
	##### NODO REGISTRYPACKAGE RELATIVO AL DOCUMENTO NUMERO $index
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
			//$value_value= avoidHtmlEntitiesInterpretation($externalidentifier_node->get_attribute('value'));
			$value_value=$externalidentifier_node->get_attribute('value');
			
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
					if(strpos(strtolower(trim($LocalizedString_value)),strtolower('SubmissionSet.patientId')))
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
    	$query = "SELECT * FROM Patient WHERE  PID3 = '$ebxml_value'";
	writeSQLQuery($query); 
		//fwrite($fp_patientIdQuery,$query);
	//fclose($fp_patientIdQuery);
    	$res = query_select($query); //array bidimensionale
	 
    	$isEmpty_3 = (empty($res));
	if($isEmpty_3)
	{
		$failure_3="\nExternalIdentifier XDSSubmissionSet.patientId ".htmlentities($ebxml_value)." (urn:uuid:6b5aea1a-874d-4603-a4bc-96a0a7b38446) is required but not found\n";
	}
    	$ret = array($isEmpty_3,$failure_3,$ebxml_value);

    	return $ret;

}//end of validate_XDSSubmissionSetPatientId($dom)

function validate_XDSSubmissionSetPatientIdInsert($dom,$idfile)
{
	writeSQLQuery('--------------------------validate_XDSSubmissionSetPatientId---------------------------------');
	//$fp_patientIdQuery = fopen("tmp/".$idfile."-SubmissionSetPatientIdQuery-".$idfile,"w+");
		
    	//$ebxml_value = searchForIds($dom,'RegistryPackage','uniqueId');
    
	$ebxml_value = '';

##### RADICE DEL DOCUMENTO ebXML
	$root_ebXML = $dom->document_element();
	$patientIdS=array();
	##### ARRAY DEI NODI REGISTRYPACKAGE
	$dom_ebXML_RegistryPackage_node_array=$root_ebXML->get_elements_by_tagname("RegistryPackage");

	#### CICLO SU OGNI RegistryPackage ####
	$isEmpty_3 = false;
	$failure_3 = "";
	for($index=0;$index<(count($dom_ebXML_RegistryPackage_node_array));$index++)
	{
	##### NODO REGISTRYPACKAGE RELATIVO AL DOCUMENTO NUMERO $index
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
			//$value_value= avoidHtmlEntitiesInterpretation($externalidentifier_node->get_attribute('value'));
			$value_value=$externalidentifier_node->get_attribute('value');
			
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
					if(strpos(strtolower(trim($LocalizedString_value)),strtolower('SubmissionSet.patientId')))
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
    	$query = "SELECT * FROM Patient WHERE PID3 = '$ebxml_value'";
	writeSQLQuery($query); 
		//fwrite($fp_patientIdQuery,$query);
	//fclose($fp_patientIdQuery);
    	$res = query_select($query); //array bidimensionale
	 
    	$isEmpty_3 = (empty($res));
	if($isEmpty_3)
	{

		writeTimeFile("Registry: patId SCONOSCIUTO");
		$insertPatient= adjustQuery("INSERT INTO Patient (ID,PID3,InsertDate) VALUES ('','$ebxml_value',CURRENT_TIMESTAMP)");
		query_exec($insertPatient);
		writeSQLQuery($insertPatient);
		//$failure_3="\nExternalIdentifier XDSSubmissionSet.patientId ".htmlentities($ebxml_value)." (urn:uuid:6b5aea1a-874d-4603-a4bc-96a0a7b38446) is required but not found\n";
	}
    	$ret = array($isEmpty_3,$failure_3,$ebxml_value);

    	return $ret;

}//end of validate_XDSSubmissionSetPatientId($dom)


function validate_XDSFolderUniqueId($dom,$idfile)
{
	writeSQLQuery('----------------------------validate_XDSFolderUniqueId-------------------------------');
	//$fp_uniqueIdQuery = fopen("tmp/".$idfile."-FolderUniqueIdQuery-".$idfile,"w+");
		
    	//$ebxml_value = searchForIds($dom,'RegistryPackage','uniqueId');
    
	$ebxml_value = '';

##### RADICE DEL DOCUMENTO ebXML
	$root_ebXML = $dom->document_element();
	
	##### ARRAY DEI NODI REGISTRYPACKAGE
	$dom_ebXML_RegistryPackage_node_array=$root_ebXML->get_elements_by_tagname("RegistryPackage");

	#### CICLO SU OGNI RegistryPackage ####
	$isEmpty_4 = false;
	$failure_4 = "";
	$conta_RegistryFolder=0;
	for($index=0;$index<(count($dom_ebXML_RegistryPackage_node_array));$index++)
	{
	
	##### NODO REGISTRYPACKAGE RELATIVO AL DOCUMENTO NUMERO $index
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
					if(strpos(strtolower(trim($LocalizedString_value)),strtolower('Folder.uniqueId')))
					{
					
						$ebxml_value = $value_value;
						$FolderUniqueId[$conta_RegistryFolder] = $value_value;
						$conta_RegistryFolder++;
					}
					
				}

			}

				}
			}
		}

	}

	}//END OF for($index=0;$index<(count($dom_ebXML_RegistryPackage_node_array));$index++)

    	### QUERY AL DB
    	$query = "SELECT * FROM ExternalIdentifier WHERE  value = '$ebxml_value'";
	writeSQLQuery($query); 
		//fwrite($fp_uniqueIdQuery,$query);
	//fclose($fp_uniqueIdQuery);
    	$res = query_select($query); //array bidimensionale

    	$isEmpty_4 = (empty($res));
	if(!$isEmpty_4)
	{
		$failure_4="\nExternalIdentifier - XDSFolder.uniqueId $ebxml_value (urn:uuid:75df8f67-9973-4fbe-a900-df66cefecc5a) already exists in registry\n";
	}

    	$ret = array($isEmpty_4,$failure_4,$FolderUniqueId);

    	return $ret;

}//end of validate_XDSFolderUniqueId($dom)

function validate_XDSFolderPatientId($dom,$XDSDocumentEntryPatientId_arr,$XDSSubmissionSetPatientId,$ExtrinsicObject_node_id,$idfile)
{
	writeSQLQuery('--------------------------validate_XDSFolderPatientId---------------------------------');
	//$fp_patientIdQuery = fopen("tmp/".$idfile."-FolderPatientIdQuery-".$idfile,"w+");
		
    	//$ebxml_value = searchForIds($dom,'RegistryPackage','uniqueId');
    
	$ebxml_value = '';

##### RADICE DEL DOCUMENTO ebXML
	$root_ebXML = $dom->document_element();
	
	##### ARRAY DEI NODI REGISTRYPACKAGE
	$dom_ebXML_RegistryPackage_node_array=$root_ebXML->get_elements_by_tagname("RegistryPackage");

	#### CICLO SU OGNI RegistryPackage ####
	$isEmpty_8 = false;
	$failure_8 = "";
	$isPatIdOk_DocumentEntry = true;
	$failure_patId_DocumentEntry = "";
	$isPatIdOk_SubmissionSet = true;
	$failure_patId_SubmissionSet = "";
	$conta_RegistryFolder=0;
	for($index=0;$index<(count($dom_ebXML_RegistryPackage_node_array));$index++)
	{
	##### NODO REGISTRYPACKAGE RELATIVO AL DOCUMENTO NUMERO $index
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
			//$value_value= avoidHtmlEntitiesInterpretation($externalidentifier_node->get_attribute('value'));
			$value_value=$externalidentifier_node->get_attribute('value');
			
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
					if(strpos(strtolower(trim($LocalizedString_value)),strtolower('Folder.patientId')))
					{
						$ebxml_value = $value_value;
						$FolderPatientId[$conta_RegistryFolder] = $value_value;
						$conta_RegistryFolder++;
					}
					else continue;
					
				}

			}

				}
			}
		}

	}
	
	if($ebxml_value!="")
	{
		#### DOCUMENT ENTRY PATID
		for($e=0;$e<count($XDSDocumentEntryPatientId_arr);$e++)
		{
			$patId_DocumentEntry=$XDSDocumentEntryPatientId_arr[$e];
			if($patId_DocumentEntry != $ebxml_value)###eccezione
			{
				$isPatIdOk_DocumentEntry = false && $isPatIdOk_DocumentEntry;
				$failure_patId_DocumentEntry = $failure_patId_DocumentEntry."\nDocument '$ExtrinsicObject_node_id[0]' - XDSDocumentEntry.patientId does not match XDSFolder.patientId ".htmlentities($ebxml_value)."\n";

			}//END OF if($patId_DocumentEntry != $ebxml_value)

		}//END OF for($e=0;$e<count($XDSDocumentEntryPatientId_arr);$e++)

		#### SUBMISSION SET PATID
		if($XDSSubmissionSetPatientId != $ebxml_value)###eccezione
		{
			$isPatIdOk_SubmissionSet = false && $isPatIdOk_SubmissionSet;
			$failure_patId_SubmissionSet = $failure_patId_SubmissionSet."\nDocument '$ExtrinsicObject_node_id[0]' - XDSSubmissionSet.patientId does not match XDSFolder.patientId $ebxml_value\n";

		}//END OF if($XDSSubmissionSetPatientId != $ebxml_value)
		

    		### QUERY AL DB
    		$query = "SELECT * FROM Patient WHERE  PID3 = '$ebxml_value'";
	 	writeSQLQuery($query);

		//fwrite($fp_patientIdQuery,$query);
		//fclose($fp_patientIdQuery);
    		$res = query_select($query); //array bidimensionale

    		$isEmpty_8 = ((empty($res)) || $isEmpty_8);
		if($isEmpty_8)
		{
			$failure_8=$failure_8."\nExternalIdentifier - XDSFolder.patientId $ebxml_value (urn:uuid:f64ffdf0-4b97-4e06-b79f-a52b38ec2f8a) is required but not found\n";
		}
	}
	//else continue;
	}//END OF for($index=0;$index<(count($dom_ebXML_RegistryPackage_node_array));$index++)

    	$ret = array($isEmpty_8,$failure_8,$isPatIdOk_DocumentEntry,$failure_patId_DocumentEntry,$isPatIdOk_SubmissionSet,$failure_patId_SubmissionSet,$ebxml_value,$FolderPatientId);

    	return $ret;

}//end of validate_XDSFolderPatientId($dom)

### VERIFICA LA POSSIBILITA' DI AGGIUNGERE UN DOCUMENTO ADUN FOLDER VIA PATIENT IDs
function verifyAddDocToFolder($dom,$XDSDocumentEntryPatientId_arr)
{	
	writeSQLQuery('----------------------------verifyAddDocToFolder-------------------------------');
	#### DEVO CERCARE L'ASSOCIATION
	##### RADICE DEL DOCUMENTO ebXML
	$root_ebXML = $dom->document_element();

	##### ARRAY DEI NODI LeafRegistryObjectList
	$dom_ebXML_LeafRegistryObjectList_node_array=$root_ebXML->get_elements_by_tagname("LeafRegistryObjectList");

	##### NODO LeafRegistryObjectList
	$dom_ebXML_LeafRegistryObjectList_node=$dom_ebXML_LeafRegistryObjectList_node_array[0];

	##### TUTTI I NODI FIGLI DI LeafRegistryObjectList
	$dom_ebXML_LeafRegistryObjectList_child_nodes= $dom_ebXML_LeafRegistryObjectList_node->child_nodes();

	$isFolderCreated = true;
	$isFolderCreated_failure = "";
	$isAddAllowed = true;
	$isAddAllowed_failure = "";
	for($i=0;$i<count($dom_ebXML_LeafRegistryObjectList_child_nodes);$i++)
	{
		#### SINGOLO NODO 
		$dom_ebXML_LeafRegistryObjectList_child_node=$dom_ebXML_LeafRegistryObjectList_child_nodes[$i];

		##### tagname
		$dom_ebXML_LeafRegistryObjectList_child_node_tagname=$dom_ebXML_LeafRegistryObjectList_child_node->node_name();

		#### SOLO I NODI ASSOCIATION
		if($dom_ebXML_LeafRegistryObjectList_child_node_tagname=='Association')
		{
			$association_node = $dom_ebXML_LeafRegistryObjectList_child_node;

			$association_node_associationType=$association_node->get_attribute('associationType');
			$value_sourceObject= $association_node->get_attribute('sourceObject');
			if($association_node_associationType=='HasMember' && strpos(strtolower(trim($value_sourceObject)),strtolower('Folder')))
			{
			##### FIGLI DI ASSOCIATION
			$association_node_childs = $association_node->child_nodes();
			#### IN QUESTO CASO $value_sourceObject E' LA FOLDER UUID !!!
			#### VERIFICO DI ESSERE NEL CASO DI ADD
			if(!isSimbolic($value_sourceObject) && empty($association_node_childs))
			{
				$value_targetObject=$association_node->get_attribute('targetObject');

				$query_for_isFolderCreated="SELECT name FROM Slot WHERE Slot.parent = '$value_sourceObject'";
				$ris_isFolderCreated=query_select($query_for_isFolderCreated);
				writeSQLQuery($query_for_isFolderCreated);

				//$fp=fopen("tmp/query_for_isFolderCreated","w+");
				//fwrite($fp,$query_for_isFolderCreated);
				//fclose($fp);

				#### FOLDER NON ESISTENTE
				if($ris_isFolderCreated[0]['name']!="lastUpdateTime")
				{
					$isFolderCreated=false && $isFolderCreated;
					$isFolderCreated_failure=$isFolderCreated_failure."\nERROR - Folder '$value_sourceObject' is not created in the Registry\n";

				}//END OF if(empty($ris_isFolderCreated[0]))

				else{
					for($t=0;$t<count($XDSDocumentEntryPatientId_arr);$t++)
					{
						$patId=$XDSDocumentEntryPatientId_arr[$t];
						$query_for_isAddAllowed="SELECT * FROM ExternalIdentifier WHERE ExternalIdentifier.registryObject = '$value_sourceObject' AND ExternalIdentifier.value = '$patId'";
						$ris_isAddAllowed=query_select($query_for_isAddAllowed);
						writeSQLQuery($query_for_isAddAllowed);

						$fp=fopen("tmp/query_for_isAddAllowed","w+");
						fwrite($fp,$query_for_isAddAllowed);
						fclose($fp);

						if(empty($ris_isAddAllowed))
						{
							$isAddAllowed=false && $isAddAllowed;
							$isAddAllowed_failure=$isAddAllowed_failure."\nERROR - Failed to add Document '$value_targetObject' to the Folder '$value_sourceObject' because XDSDocumentEntry.patientId does not match with XDSFolder.patientId\n";

						}//END OF if($value!=$patId)
	
					}//END OF for($t=0;$t<count($XDSDocumentEntryPatientId_arr);$t++)
				    
				   }//END OF ELSE
				
			}//END OF if(!isSimbolic($value_sourceObject))

			}//END OF if($association_node_associationType=='HasMember')

		}//END OF if($dom_ebXML_LeafRegistryObjectList_child_node_tagname=='Association')

	}//END OF for($i=0;$i<count($dom_ebXML_LeafRegistryObjectList_child_nodes);$i++)

	#### ARRAY DI RITORNO
	$ret = array($isFolderCreated,$isFolderCreated_failure,$isAddAllowed,$isAddAllowed_failure);

	return $ret;

}//END OF verifyAddDocToFolder($dom,$FolderPatientId,$XDSDocumentEntryPatientId_arr)

#### CONFRONTO XDSDocumentEntry.patientId vs XDSSubmissionSet.patientId
function confrontaPatientIds($XDSSubmissionSetPatientId,$XDSDocumentEntryPatientId_arr,$ExtrinsicObject_node_id_attr_array)
{
	$patientId_conf="";
	$patientId_conf_bool = false;

	for($r=0;$r<count($XDSDocumentEntryPatientId_arr);$r++)
	{
		$XDSDocumentEntryPatientId=$XDSDocumentEntryPatientId_arr[$r];
		$ExtrinsicObject_node_id_attr=$ExtrinsicObject_node_id_attr_array[$r];
		if($XDSDocumentEntryPatientId!=$XDSSubmissionSetPatientId)
		{
			$patientId_conf_bool = true;

			$patientId_conf=$patientId_conf."\nDocument '$ExtrinsicObject_node_id_attr' - patientId ($XDSDocumentEntryPatientId) does not match the SubmissionSet.patientId ($XDSSubmissionSetPatientId)\n";
		
		}//END OF if($XDSDocumentEntryPatientId!=$XDSSubmissionSetPatientId)

	}//END OF for($r=0;$r<count($XDSDocumentEntryPatientId_arr);$r++)

	#### COMPONGO L'ARRAY DI RISPOSTA
	$arr = array($patientId_conf_bool,$patientId_conf);
	### RETURN
	return $arr;

}//END OF confrontaPatientIds

##### VALIDAZIONE DEL mimeType PER OGNI DOCUMENTO SOTTOMESSO
function validate_ExtrinsicObject_mimeType($dom)
{
	writeSQLQuery('---------------------------validate_ExtrinsicObject_mimeType--------------------------------');
##### RADICE DEL DOCUMENTO ebXML
	$root_ebXML = $dom->document_element();
	
	##### ARRAY DEI NODI ExtrinsicObject
	$dom_ebXML_ExtrinsicObject_node_array=$root_ebXML->get_elements_by_tagname("ExtrinsicObject");

	$isEmpty_6 = false;
	$failure_6 = "";
	if(!empty($dom_ebXML_ExtrinsicObject_node_array))##
	{
// 		$fp_mimeTypeQuery = fopen("tmp/mimeTypeQuery","w+");

	#### CICLO SU OGNI ExtrinsicObject ####
// 	$isEmpty_6 = false;
// 	$failure_6 = "";
	$ExtrinsicObject_node_id_attr_array=array();
	$ExtrinsicObject_node_mimeType_attr_array=array();
	$idS=array();
	$mimeTypeS=array();
	for($index=0;$index<(count($dom_ebXML_ExtrinsicObject_node_array));$index++)
	{
	##### NODO ExtrinsicObject RELATIVO AL DOCUMENTO NUMERO $index
	$ExtrinsicObject_node = $dom_ebXML_ExtrinsicObject_node_array[$index];

	### RECUPERO L'ATTRIBUTO mimeType DEL NODO EXTRINSICOBJECT
	$ExtrinsicObject_node_id_attr = $ExtrinsicObject_node->get_attribute('id');
	$ExtrinsicObject_node_mimeType_attr = $ExtrinsicObject_node->get_attribute('mimeType');
	### INSERISCO NELL'ARRAY DA TORNARE
	$ExtrinsicObject_node_id_attr_array[$index]=$ExtrinsicObject_node_id_attr;
	$ExtrinsicObject_node_mimeType_attr_array[$index]=$ExtrinsicObject_node_mimeType_attr;

	### QUERY AL DB
    	$query = "SELECT * FROM mimeType WHERE code = '$ExtrinsicObject_node_mimeType_attr'";
	writeSQLQuery($query); 


	$res = query_select($query);

	$isEmpty_6 = (empty($res));
	if($isEmpty_6)
	{
		$failure_6=$failure_6."\nDocument '$ExtrinsicObject_node_id_attr' - mimeType $ExtrinsicObject_node_mimeType_attr is not on the approved list for this Affinity Domain\n";

	}//END OF if($isEmpty_6)

	}//END OF for($index=0;$index<(count($dom_ebXML_ExtrinsicObject_node_array)
	}

	$ret = array($isEmpty_6,$failure_6);

	return $ret;

}//END OF validate_ExtrinsicObject_mimeType($dom)

##### SUPPORTO AL REPLACEMENT
function validate_Replacement($dom,$DocumentEntryPatientId_array)
{
	writeSQLQuery('---------------------------validate_Replacement--------------------------------');
	$ExtrinsicObject_node_id_attr_arr=array();
	$Association_node_sourceObject_attr_arr=array();
	$Association_node_targetObject_attr_arr=array();

	##### RADICE DEL DOCUMENTO ebXML
	$root_ebXML = $dom->document_element();

	##### ARRAY DEI NODI LeafRegistryObjectList
	$dom_ebXML_LeafRegistryObjectList_node_array=$root_ebXML->get_elements_by_tagname("LeafRegistryObjectList");

	##### NODO LeafRegistryObjectList
	$dom_ebXML_LeafRegistryObjectList_node=$dom_ebXML_LeafRegistryObjectList_node_array[0];

	##### TUTTI I NODI FIGLI DI LeafRegistryObjectList
	$dom_ebXML_LeafRegistryObjectList_child_nodes= $dom_ebXML_LeafRegistryObjectList_node->child_nodes();

	for($i=0;$i<count($dom_ebXML_LeafRegistryObjectList_child_nodes);$i++)
	{
		#### SINGOLO NODO 
		$dom_ebXML_LeafRegistryObjectList_child_node=$dom_ebXML_LeafRegistryObjectList_child_nodes[$i];

		##### tagname
		$dom_ebXML_LeafRegistryObjectList_child_node_tagname=$dom_ebXML_LeafRegistryObjectList_child_node->node_name();

		if($dom_ebXML_LeafRegistryObjectList_child_node_tagname=='ExtrinsicObject')
		{
			#### SINGOLO NODO EXTRINSICOBJECT
			$ExtrinsicObject_node = $dom_ebXML_LeafRegistryObjectList_child_node;

			$ExtrinsicObject_node_id_attr=$ExtrinsicObject_node->get_attribute('id');

			$ExtrinsicObject_node_id_attr_arr[$ExtrinsicObject_node_id_attr]=$ExtrinsicObject_node_id_attr;

		}//END OF if($dom_ebXML_LeafRegistryObjectList_child_node_tagname=='ExtrinsicObject')

		#### SOLO I NODI CLASSIFICATION
		if($dom_ebXML_LeafRegistryObjectList_child_node_tagname=='Association')
		{
			#### SINGOLO NODO ASSOCIATION
			$Association_node = $dom_ebXML_LeafRegistryObjectList_child_node;

			#### ATTRIBUTO associationType
			$Association_node_associationType_attr=$Association_node->get_attribute('associationType');

			#### ATTRIBUTO sourceObject
			$Association_node_sourceObject_attr=$Association_node->get_attribute('sourceObject');

			#### ATTRIBUTO sourceObject
			$Association_node_targetObject_attr=$Association_node->get_attribute('targetObject');

			$performControls = true;
			$patId_coherence_failure="";
			$patId_coherence = true;
			if($Association_node_associationType_attr=='RPLC' || $Association_node_associationType_attr=='XFRM_RPLC')
			{
				$Association_node_sourceObject_attr_arr[]=$Association_node_sourceObject_attr;

				$Association_node_targetObject_attr_arr[]=$Association_node_targetObject_attr;

				$query_for_patId_coherence="SELECT value FROM ExternalIdentifier WHERE registryObject = '$Association_node_targetObject_attr' AND  identificationScheme = 'urn:uuid:58a6f841-87b3-4a3e-92fd-a8ffeff98427'";

				$patId_res = query_select($query_for_patId_coherence);
				writeSQLQuery($query_for_patId_coherence);

				if(!empty($patId_res))//ECCEZIONE
				{
				     $targetObject_patID=$patId_res[0]["value"];
				     $source_patID=$DocumentEntryPatientId_array[0];
				     #### VERIFICO IL MATCHING
				     if($targetObject_patID!=$source_patID)
				     {
					$patId_coherence = false && $patId_coherence;
	
					$patId_coherence_failure=$patId_coherence_failure."\nERROR in Replacement targetObject '$Association_node_targetObject_attr' with '$Association_node_sourceObject_attr' - XDSDocumentEntry.patientId $source_patID in the Submission does not match the original one $targetObject_patID\n";

				      }//END OF if($targetObject_patID!=$source_patID)

				}//END OF if(empty($patId_res))

			}//END OF IF  RPLC || XFRM_RPLC

			else{
				$performControls = false;
			    }
	
		}//END OF if($dom_ebXML_LeafRegistryObjectList_child_node_tagname=='Association')

	}//END OF for($i=0;$i<count($dom_ebXML_LeafRegistryObjectList_child_nodes);$i++)

$is_sourceObject_Valid = true;
$error_on_sourceObject="";

$is_targetObject_Valid = true;
$error_on_targetObject="";

$isEmpty_7 = false;

if($performControls)
{
     for($r=0;$r<count($Association_node_sourceObject_attr_arr);$r++)
     {
	$Association_node_sourceObject_attr=$Association_node_sourceObject_attr_arr[$r];
	if(!($ExtrinsicObject_node_id_attr_arr[$Association_node_sourceObject_attr]))
	{
		$is_sourceObject_Valid = $is_sourceObject_Valid && false;

		$error_on_sourceObject=$error_on_sourceObject."\nAssociation sourceObject ($Association_node_sourceObject_attr) does not match any ExtrinsicObject id in the metadata\n";
	
	}//END OF if(!($ExtrinsicObject_node_id_attr_arr[$Association_node_sourceObject_attr]))

     }//END OF for($r=0;$r<count($Association_node_sourceObject_attr_arr);$r++)

     for($s=0;$s<count($Association_node_targetObject_attr_arr);$s++)
     {
	$Association_node_targetObject_attr=$Association_node_targetObject_attr_arr[$s];

	$query = "SELECT * FROM ExtrinsicObject WHERE ExtrinsicObject.id = '$Association_node_targetObject_attr'";
	writeSQLQuery($query);

	$res = query_select($query);

	$isEmpty_7 = (empty($res));
	if($isEmpty_7)
	{
		$is_targetObject_Valid = $is_targetObject_Validv && false;

		$error_on_targetObject=$error_on_targetObject."\nAssociation: targetObject ($Association_node_targetObject_attr) is not present in the Registry\n";

	}//END OF if($isEmpty_7)

     }//END OF for($s=0;$s<count($Association_node_targetObject_attr_arr);$s++)

     

}//END OF if($performControls)

	$arr=array($is_sourceObject_Valid,$error_on_sourceObject,$is_targetObject_Valid,$error_on_targetObject,$patId_coherence,$patId_coherence_failure);

	return $arr;

}//END OF validate_Replacement($dom)


##### CONTROLLO DELLA PRESENZA NELL'EBXML SI HASH + SIZE + URI
##### IN CASO CONTRARIO RESTITUISCE ERROR IN VALIDATION
function arePresent_HASH_SIZE_URI($dom)
{
	##### RADICE DEL DOCUMENTO ebXML
	$root_ebXML = $dom->document_element();
	
	##### ARRAY DEI NODI ExtrinsicObject
	$dom_ebXML_ExtrinsicObject_node_array=$root_ebXML->get_elements_by_tagname("ExtrinsicObject");

	$hash_bool = true;####INDISPENSABILE: CASO NO EXTRINSICOBJECT!!!
	$error_on_hash="";
	$size_bool = true;
	$error_on_size="";
	$URI_bool = true;
	$error_on_URI="";
	$error = "\n[ERROR] - THIS IS A REGISTER TRANSACTION: YOU MUST SPECIFY  ";

	$slot_count=0;
	$hash_count=0;

	if(!empty($dom_ebXML_ExtrinsicObject_node_array))
	{
		for($index=0;$index<(count($dom_ebXML_ExtrinsicObject_node_array));$index++)
		{
			$hash_bool = false;
			$size_bool = false;
			$URI_bool = false;

			#####SINGOLO NODO ExtrinsicObject
			$ExtrinsicObject_node = $dom_ebXML_ExtrinsicObject_node_array[$index];

			### RECUPERO L'ATTRIBUTO id DEL NODO EXTRINSICOBJECT
			$ExtrinsicObject_node_id_attr = $ExtrinsicObject_node->get_attribute('id');

			$child_array_nodes = $ExtrinsicObject_node->child_nodes();
			for($j = 0;$j<count($child_array_nodes);$j++)
			{
				$nod = $child_array_nodes[$j];
				$nod_name = $nod->node_name();
				if($nod->node_type() == XML_ELEMENT_NODE) 
				{
					$name = $nod->node_name();
					if($name == "Slot")
					{
						$slot_count=$slot_count+1;

						$attribute = $nod->get_attribute("name");

			if(strtoupper($attribute) != "HASH")
			{
				$hash_count++;
 				$hash_bool = (false || $hash_bool);
				
// 				$error_on_hash=$error_on_hash."$error HASH   VALUE FOR DOCUMENT '$ExtrinsicObject_node_id_attr'\n";
				
			}
			else if(strtoupper($attribute) == "HASH"){

				$hash_bool = ($hash_bool || true);
				$error_on_hash_1="";
			     }
			if(strtoupper($attribute) != "SIZE")
			{
				$size_bool = (false || $size_bool);
// 				$error_on_size=$error_on_size."$error SIZE   VALUE FOR DOCUMENT '$ExtrinsicObject_node_id_attr'\n";
			}
			else if(strtoupper($attribute) == "SIZE"){

				$size_bool = ($size_bool || true);
				$error_on_size_1="";
			     }

			if(strtoupper($attribute) != "URI")
			{
				$URI_bool = (false || $URI_bool);
// 				$error_on_URI=$error_on_URI."$error URI   VALUE FOR DOCUMENT '$ExtrinsicObject_node_id_attr'\n";
			}
			else if(strtoupper($attribute) == "URI"){

				$URI_bool = ($URI_bool || true);
				$error_on_URI_1="";
			     }
					}//END OF if($name == "Slot")
			
				}//END OF if($nod->node_type() == XML_ELEMENT_NODE)
		
			}//END OF for($j = 0;$j<count($child_array_nodes);$j++)

			if(!$hash_bool)
			{
			$error_on_hash=$error_on_hash."$error -- HASH --   VALUE FOR DOCUMENT '$ExtrinsicObject_node_id_attr'\n";
			}
			if(!$size_bool)
			{
			$error_on_size=$error_on_size."$error -- SIZE --    VALUE FOR DOCUMENT '$ExtrinsicObject_node_id_attr'\n";
			}
			if(!$URI_bool)
			{
			$error_on_URI=$error_on_URI."$error -- URI --    VALUE FOR DOCUMENT '$ExtrinsicObject_node_id_attr'\n";
			}

		}//END OF for($index=0;$index<(count($dom_ebXML_ExtrinsicObject_node_array));$index++)

	}//END OF if(!empty($dom_ebXML_ExtrinsicObject_node_array))

############# SETTO LE VARIABILI BOOLEANE
	if($error_on_hash!="") $hash_bool=false;
	if($error_on_size!="") $size_bool=false;
	if($error_on_URI!="") $URI_bool=false;
	
####ARRAY DI RITORNO
	$ret = array($hash_bool,$error_on_hash,$size_bool,$error_on_size,$URI_bool,$error_on_URI);

	return $ret;

}//END OF arePresent_HASH_SIZE_URI($dom)

#############################################################################
###############################################################################
##################################################################################
###### FUNZIONE DI CONTROLLO DELLA QUERY
##### ACCETTO SOLO QUERY DEL TIPO:  SELECT eo.id  FROM...
function controllaQuery($SQLQuery)
{
	$isQueryAllowed = true;###DEFAULT
	$queryError = "";

	$pos_1=strpos(strtoupper($SQLQuery),"SELECT");
	$pos_2=strpos(strtoupper($SQLQuery),"*");
	//$pos_3=strpos(strtoupper($SQLQuery),"%");
	//$pos_4=strpos(strtoupper($SQLQuery),"LIKE");

	## Notate l'uso di ===
	### Il == non avrebbe risposto come atteso
	##### ACCETTO SOLO QUERY DEL TIPO:  SELECT eo.id  FROM....
	if(!($pos_1===0) || $pos_2) // || $pos_3) // || $pos_4)
	{
		$isQueryAllowed = false;
		$queryError="\n[ERROR: NOT PROPER QUERY] - YOU ARE NOT ALLOWED TO PERFORM THIS KIND OF QUERY TO THIS REGISTRY\n[  ".avoidHtmlEntitiesInterpretation($SQLQuery)." ]\n";
	}
	else{
		$fp_controllaQuery = fopen("tmpQueryService/controllaQuery","w+");
		fwrite($fp_controllaQuery," - QUERY ACCETTATA - \n[ \"$SQLQuery\" ]\n");
		fclose($fp_controllaQuery);
	    }

	$ret = array($isQueryAllowed,$queryError);
	return $ret;

}//END OF controllaQuery($SQLQuery)

?>
