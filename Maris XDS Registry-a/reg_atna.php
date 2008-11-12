<?php
# ------------------------------------------------------------------------------------
# MARIS XDS REGISTRY
# Copyright (C) 2007 - 2010  MARiS Project
# Dpt. Medical and Diagnostic Sciences, University of Padova - csaccavini@rad.unipd.it
# This program is distributed under the terms and conditions of the GPL
# See the LICENSE files for details
# ------------------------------------------------------------------------------------

####### UTILITIES DI CREAZIONE DEI MESSAGGI DI AUDIT

###### EVENTO IMPORT
function createImportEvent($eventOutcomeIndicator,$ip_registry,$ip_source,$SUID,$pid,$pna,$idName)
{
	include("REGISTRY_CONFIGURATION/REG_configuration.php");
	//$path_to_IMPORT = "./atna/message/DataImport.xml";
	##### OGGETTO DOM SUL FILE DI STRUTTURA
	$string_IMPORT = file_get_contents($path_to_IMPORT);


	$dom_IMPORT = domxml_open_mem($string_IMPORT);

	##### IMPORT DOM ROOT
	$dom_IMPORT_root=$dom_IMPORT->document_element();

	#### NODO EVENTIDENTIFICATION
	$EventIdentification_arr=$dom_IMPORT_root->get_elements_by_tagname("EventIdentification");

	#### RECUPERO DATA E ORA ATTUALI
	$today = date("Y-m-d");
	$cur_hour = date("H:i:s");
	$datetime = $today."T".$cur_hour;

	for($i=0;$i<count($EventIdentification_arr);$i++)
	{
		$EventIdentification=$EventIdentification_arr[$i];

     		#### ATTRIBUTO DATETIME
		$EventIdentification->set_attribute("EventDateTime",$datetime);
     		#### ATTRIBUTO EVENTOUTCOMEINDICATOR
		$EventIdentification->set_attribute("EventOutcomeIndicator",$eventOutcomeIndicator);

	}//END OF for($i=0;$i<count($EventIdentification_arr);$i++)

	#### NODO ACTIVEPARTICIPANT
	$ActiveParticipant_arr=$dom_IMPORT_root->get_elements_by_tagname("ActiveParticipant");

		$ActiveParticipant=$ActiveParticipant_arr[0];

		// Registry
		#### ATTIBUTO USERID
		$ActiveParticipant->set_attribute("UserID","XDS Registry");
		#### ATTRIBUTO NETWORKACCESPOINTID
		$ActiveParticipant->set_attribute("NetworkAccessPointID",$ip_registry);

		$ActiveParticipant=$ActiveParticipant_arr[1];

		// Source
		#### ATTIBUTO USERID
		$ActiveParticipant->set_attribute("UserID","XDS Source");
		#### ATTRIBUTO NETWORKACCESPOINTID
		$ActiveParticipant->set_attribute("NetworkAccessPointID",$ip_source);


	##### NODO PARTICIPANTOBJECTIDENTIFICATION
	$ParticipantObjectIdentification_arr=$dom_IMPORT_root->get_elements_by_tagname("ParticipantObjectIdentification");

	for($i=0;$i<count($ParticipantObjectIdentification_arr);$i++)
	{
		$ParticipantObjectIdentification=$ParticipantObjectIdentification_arr[$i];

		#### The Study Instance UID
		if($i==0)
		{
			#### ATTRIBUTO PARTICIPANTOBJECTID
			$ParticipantObjectIdentification->set_attribute("ParticipantObjectID",$SUID);

			$ParticipantObjectIdentification_childs=$ParticipantObjectIdentification->child_nodes();

			$ParticipantObjectIDTypeCode=$ParticipantObjectIdentification_childs[1];

			$tagname=$ParticipantObjectIDTypeCode->tagname();

			if($tagname=="ParticipantObjectIDTypeCode")
			{
				#### ATTIBUTO DISPLAYNAME
				$ParticipantObjectIDTypeCode->set_attribute("displayName",$idName);
			}

		}//END OF if($i==0)

		#### The patient ID
		if($i==1)
		{
			#### ATTRIBUTO PARTICIPANTOBJECTID
			$ParticipantObjectIdentification->set_attribute("ParticipantObjectID",$pid);

			$ParticipantObjectIdentification_childs=$ParticipantObjectIdentification->child_nodes();

			$child=$ParticipantObjectIdentification_childs[3];

			$tagname=$child->tagname();

			if($tagname=="ParticipantObjectName")
			{

				#### ATTIBUTO DISPLAYNAME
				$child->set_content($pna);

			}//END OF if($tagname=="ParticipantObjectName")

		}//END OF if($i==1)

	}//END OF for($i=0;$i<count($ParticipantObjectIdentification_arr);$i++)


	#### RITORNO LA STRINGA DA DOM MODIFICATO
	$IMPORT_STRING = $dom_IMPORT->dump_mem();
	$IMPORT_STRING_2 = stristr($IMPORT_STRING, '<AuditMessage>');

	###### SCRIVO IL FILE NEI LOG
	$filename = "DataImport.xml";
	$fp_IMPORT = fopen($atna_path.$filename,"w+");
    		fwrite($fp_IMPORT,$IMPORT_STRING_2);
	fclose($fp_IMPORT);

	##### COMPONGO L'ARRAY DA RITORNARE
	//$ret = array($IMPORT_STRING_2,$filename);

	//return $ret;
	return $IMPORT_STRING_2;

}//END OF createImportEvent

#####################################################

####### EVENTO EXPORT
function createExportEvent($eventOutcomeIndicator,$ip_registry,$ip_consumer,$SUID,$idName,$pid,$pna)
{
	include("REGISTRY_CONFIGURATION/REG_configuration.php");

	##### OGGETTO DOM SUL FILE DI STRUTTURA
	$string_EXPORT=file_get_contents($path_to_EXPORT);
	$dom_EXPORT = domxml_open_mem($string_EXPORT);

	//EventOutcomeIndicator 0 OK 12 ERROR
	//ActiveParticipant UserID=""  A CHI SPEDISCO I DATI
	//NetworkAccessPointID=""  ip A CUI SPEDISCO I DATI

	##### EXPORT DOM ROOT
	$dom_EXPORT_root=$dom_EXPORT->document_element();

	#### NODO EVENTIDENTIFICATION
	$EventIdentification_arr=$dom_EXPORT_root->get_elements_by_tagname("EventIdentification");

	#### RECUPERO DATA E ORA ATTUALI
	$today = date("Y-m-d");
	$cur_hour = date("H:i:s");
	$datetime = $today."T".$cur_hour;

	for($i=0;$i<count($EventIdentification_arr);$i++)
	{
		$EventIdentification=$EventIdentification_arr[$i];

     		#### ATTRIBUTO DATETIME
		$EventIdentification->set_attribute("EventDateTime",$datetime);
     		#### ATTRIBUTO EVENTOUTCOMEINDICATOR
		$EventIdentification->set_attribute("EventOutcomeIndicator",$eventOutcomeIndicator);

	}//END OF for($i=0;$i<count($EventIdentification_arr);$i++)

	#### NODO ACTIVEPARTICIPANT
	$ActiveParticipant_arr=$dom_EXPORT_root->get_elements_by_tagname("ActiveParticipant");

		$ActiveParticipant=$ActiveParticipant_arr[0];

		#### ATTIBUTO USERID
		$ActiveParticipant->set_attribute("UserID","XDS Registry");
		#### ATTRIBUTO NETWORKACCESPOINTID
		$ActiveParticipant->set_attribute("NetworkAccessPointID",$ip_registry);

		$ActiveParticipant=$ActiveParticipant_arr[1];

		#### ATTIBUTO USERID
		$ActiveParticipant->set_attribute("UserID","XDS Consumer");
		#### ATTRIBUTO NETWORKACCESPOINTID
		$ActiveParticipant->set_attribute("NetworkAccessPointID",$ip_consumer);


	##### NODO PARTICIPANTOBJECTIDENTIFICATION
	$ParticipantObjectIdentification_arr=$dom_EXPORT_root->get_elements_by_tagname("ParticipantObjectIdentification");

	for($i=0;$i<count($ParticipantObjectIdentification_arr);$i++)
	{
		$ParticipantObjectIdentification=$ParticipantObjectIdentification_arr[$i];

		#### The Study Instance UID
		if($i==0)
		{
			#### ATTRIBUTO PARTICIPANTOBJECTID
			$ParticipantObjectIdentification->set_attribute("ParticipantObjectID",$SUID);

			$ParticipantObjectIdentification_childs=$ParticipantObjectIdentification->child_nodes();

			$ParticipantObjectIDTypeCode=$ParticipantObjectIdentification_childs[1];

			$tagname=$ParticipantObjectIDTypeCode->tagname();

			if($tagname=="ParticipantObjectIDTypeCode")
			{
				#### ATTIBUTO DISPLAYNAME
				$ParticipantObjectIDTypeCode->set_attribute("displayName",$idName);
			}

		}//END OF if($i==0)

		#### The patient ID
		if($i==1)
		{
			#### ATTRIBUTO PARTICIPANTOBJECTID
			$ParticipantObjectIdentification->set_attribute("ParticipantObjectID",$pid);

			$ParticipantObjectIdentification_childs=$ParticipantObjectIdentification->child_nodes();

			$child=$ParticipantObjectIdentification_childs[3];

			$tagname=$child->tagname();

			if($tagname=="ParticipantObjectName")
			{
				#### ATTIBUTO DISPLAYNAME
				$child->set_content($pna);

			}//END OF if($tagname=="ParticipantObjectName")

		}//END OF if($i==1)

	}//END OF for($i=0;$i<count($ParticipantObjectIdentification_arr);$i++)

	#### RITORNO LA STRINGA DA DOM MODIFICATO
	$EXPORT_STRING = $dom_EXPORT->dump_mem();
	$EXPORT_STRING_2 = stristr($EXPORT_STRING, '<AuditMessage>');

	###### SCRIVO IL FILE NEI LOG
	/*$filename = "DataExport.xml";
	$fp_EXPORT = fopen($atna_path.$filename,"w+");
    		fwrite($fp_EXPORT,$EXPORT_STRING_2);
	fclose($fp_EXPORT);*/

	##### COMPONGO L'ARRAY DA RITORNARE
	//$ret = array($EXPORT_STRING_2,$filename);

	return $EXPORT_STRING_2;

}//END OF createExportEvent

######################################################

####### EVENTO QUERY
function createQueryEvent($eventOutcomeIndicator,$ip_registry,$ip_consumer,$SUID,$query)
{
	include("REGISTRY_CONFIGURATION/REG_configuration.php");

	##### OGGETTO DOM SUL FILE DI STRUTTURA
	$string_QUERY=file_get_contents($path_to_QUERY);
	$dom_QUERY = domxml_open_mem($string_QUERY);

	##### QUERY DOM ROOT
	$dom_QUERY_root=$dom_QUERY->document_element();

	#### NODO EVENTIDENTIFICATION
	$EventIdentification_arr=$dom_QUERY_root->get_elements_by_tagname("EventIdentification");

	#### RECUPERO DATA E ORA ATTUALI
	$today = date("Y-m-d");
	$cur_hour = date("H:i:s");
	$datetime = $today."T".$cur_hour;

	for($i=0;$i<count($EventIdentification_arr);$i++)
	{
		$EventIdentification=$EventIdentification_arr[$i];

     		#### ATTRIBUTO DATETIME
		$EventIdentification->set_attribute("EventDateTime",$datetime);
     		#### ATTRIBUTO EVENTOUTCOMEINDICATOR
		$EventIdentification->set_attribute("EventOutcomeIndicator",$eventOutcomeIndicator);

	}//END OF for($i=0;$i<count($EventIdentification_arr);$i++)

	### NODO ACTIVEPARTICIPANT
	$ActiveParticipant_arr=$dom_QUERY_root->get_elements_by_tagname("ActiveParticipant");

	
		$ActiveParticipant=$ActiveParticipant_arr[0];

		#### ATTIBUTO USERID
		$ActiveParticipant->set_attribute("UserID","XDS Registry");
		#### ATTRIBUTO NETWORKACCESPOINTID
		$ActiveParticipant->set_attribute("NetworkAccessPointID",$ip_registry);

		$ActiveParticipant=$ActiveParticipant_arr[1];

		#### ATTIBUTO USERID
		$ActiveParticipant->set_attribute("UserID","XDS Consumer");
		#### ATTRIBUTO NETWORKACCESPOINTID
		$ActiveParticipant->set_attribute("NetworkAccessPointID",$ip_consumer);

	

	##### NODO PARTICIPANTOBJECTIDENTIFICATION
	$ParticipantObjectIdentification_arr=$dom_QUERY_root->get_elements_by_tagname("ParticipantObjectIdentification");

	$ParticipantObjectIdentification=$ParticipantObjectIdentification_arr[0];
	$ParticipantObjectIdentification_childs=$ParticipantObjectIdentification->child_nodes();

//$ParticipantObjectIdentification->set_attribute("ParticipantObjectID",$SUID);

for($f=0;$f<count($ParticipantObjectIdentification_childs);$f++)
{
	$ParticipantObjectIdentification_child=$ParticipantObjectIdentification_childs[$f];

	
	#### The query
		if($f==3)
		{
			$tagname=$ParticipantObjectIdentification_child->tagname();

			if($tagname=="ParticipantObjectQuery")
			{
				#### ATTIBUTO DISPLAYNAME
				$ParticipantObjectIdentification_child->set_content(base64_encode($query));

			}//END OF if($tagname=="ParticipantObjectName")

		}

         }
	#### RITORNO LA STRINGA DA DOM MODIFICATO
	$QUERY_STRING = $dom_QUERY->dump_mem();
	$QUERY_STRING_2 = stristr($QUERY_STRING, '<AuditMessage>');

	###### SCRIVO IL FILE NEI LOG
	$filename = "Query.xml";
	/*$fp_QUERY = fopen($atna_path.$filename,"w+");
    		fwrite($fp_QUERY,$QUERY_STRING_2);
	fclose($fp_QUERY);*/

	##### COMPONGO L'ARRAY DA RITORNARE
	//$ret = array($QUERY_STRING_2,$filename);

	return $QUERY_STRING_2;
 

}//END OF createQueryEvent

?>