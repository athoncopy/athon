<?php

####### UTILITIES DI CREAZIONE DEI MESSAGGI DI AUDIT

###### EVENTO IMPORT
function createImportEvent($eventOutcomeIndicator,$ip,$id,$pid,$pna)
{
	include("config/REP_configuration.php");

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

	for($i=0;$i<count($ActiveParticipant_arr);$i++)
	{
		$ActiveParticipant=$ActiveParticipant_arr[$i];

		#### ATTIBUTO USERID
		$ActiveParticipant->set_attribute("UserID",$ip);
		#### ATTRIBUTO NETWORKACCESPOINTID
		$ActiveParticipant->set_attribute("NetworkAccessPointID",$ip);

	}//END OF for($i=0;$i<count($ActiveParticipant_arr);$i++)

	##### NODO PARTICIPANTOBJECTIDENTIFICATION
	$ParticipantObjectIdentification_arr=$dom_IMPORT_root->get_elements_by_tagname("ParticipantObjectIdentification");

	for($i=0;$i<count($ParticipantObjectIdentification_arr);$i++)
	{
		$ParticipantObjectIdentification=$ParticipantObjectIdentification_arr[$i];

		#### The Study Instance UID
		if($i==0)
		{
			#### ATTRIBUTO PARTICIPANTOBJECTID
			$ParticipantObjectIdentification->set_attribute("ParticipantObjectID",$id);

			$ParticipantObjectIdentification_childs=$ParticipantObjectIdentification->child_nodes();

			$ParticipantObjectIDTypeCode=$ParticipantObjectIdentification_childs[1];

			$tagname=$ParticipantObjectIDTypeCode->tagname();

			if($tagname=="ParticipantObjectIDTypeCode")
			{
				#### ATTIBUTO DISPLAYNAME
				$ParticipantObjectIDTypeCode->set_attribute("displayName",$id);
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

	###### SCRIVO IL FILE NEI LOG
	$filename = "IMPORT.xml";
	$fp_IMPORT = fopen($atna_path.$filename,"w+");
    		fwrite($fp_IMPORT,$IMPORT_STRING);
	fclose($fp_IMPORT);

	##### COMPONGO L'ARRAY DA RITORNARE
	$ret = array($IMPORT_STRING,$filename);

	return $ret;

}//END OF createImportEvent

#####################################################

####### EVENTO EXPORT
function createExportEvent($eventOutcomeIndicator,$ip,$id,$pid,$pna)
{
	include("config/REP_configuration.php");

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

	for($i=0;$i<count($ActiveParticipant_arr);$i++)
	{
		$ActiveParticipant=$ActiveParticipant_arr[$i];

		#### ATTIBUTO USERID
		$ActiveParticipant->set_attribute("UserID",$ip);
		#### ATTRIBUTO NETWORKACCESPOINTID
		$ActiveParticipant->set_attribute("NetworkAccessPointID",$ip);

	}//END OF for($i=0;$i<count($ActiveParticipant_arr);$i++)

	##### NODO PARTICIPANTOBJECTIDENTIFICATION
	$ParticipantObjectIdentification_arr=$dom_EXPORT_root->get_elements_by_tagname("ParticipantObjectIdentification");

	for($i=0;$i<count($ParticipantObjectIdentification_arr);$i++)
	{
		$ParticipantObjectIdentification=$ParticipantObjectIdentification_arr[$i];

		#### The Study Instance UID
		if($i==0)
		{
			#### ATTRIBUTO PARTICIPANTOBJECTID
			$ParticipantObjectIdentification->set_attribute("ParticipantObjectID",$id);

			$ParticipantObjectIdentification_childs=$ParticipantObjectIdentification->child_nodes();

			$ParticipantObjectIDTypeCode=$ParticipantObjectIdentification_childs[1];

			$tagname=$ParticipantObjectIDTypeCode->tagname();

			if($tagname=="ParticipantObjectIDTypeCode")
			{
				#### ATTIBUTO DISPLAYNAME
				$ParticipantObjectIDTypeCode->set_attribute("displayName",$id);
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

	###### SCRIVO IL FILE NEI LOG
	$EXPORT_STRING_2 = stristr($EXPORT_STRING, '<AuditMessage>');
	
	$filename = "DataExport.xml";
	$fp_EXPORT = fopen($atna_path.$filename,"w+");
    		fwrite($fp_EXPORT,$EXPORT_STRING_2);
	fclose($fp_EXPORT);

	##### COMPONGO L'ARRAY DA RITORNARE
	$ret = array($EXPORT_STRING,$filename);

	return $ret;

}//END OF createExportEvent

######################################################

####### EVENTO QUERY
function createQueryEvent($eventOutcomeIndicator,$ip,$query)
{
	include("config/REP_configuration.php");

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

	for($i=0;$i<count($ActiveParticipant_arr);$i++)
	{
		$ActiveParticipant=$ActiveParticipant_arr[$i];

		#### ATTIBUTO USERID
		$ActiveParticipant->set_attribute("UserID",$ip);
		#### ATTRIBUTO NETWORKACCESPOINTID
		$ActiveParticipant->set_attribute("NetworkAccessPointID",$ip);

	}//END OF for($i=0;$i<count($ActiveParticipant_arr);$i++)

	##### NODO PARTICIPANTOBJECTIDENTIFICATION
	$ParticipantObjectIdentification_arr=$dom_QUERY_root->get_elements_by_tagname("ParticipantObjectIdentification");

	$ParticipantObjectIdentification=$ParticipantObjectIdentification_arr[0];
$ParticipantObjectIdentification_childs=$ParticipantObjectIdentification->child_nodes();

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

// 		#### The query in base 64
// 		if($f==5)
// 		{
// 			$tagname=$ParticipantObjectIdentification_child->tagname();
// 
// 			if($tagname=="ParticipantObjectDetail")
// 			{
// 				#### ATTIBUTO DISPLAYNAME
// 				$ParticipantObjectIdentification_child->set_attribute("value",base64_encode($query));
// 
// 			}//END OF if($tagname=="ParticipantObjectDetail")
// 
// 		}//END OF if($i==5)

}//END OF for($f=0;$f<count($ParticipantObjectIdentification_childs);$f++)

	#### RITORNO LA STRINGA DA DOM MODIFICATO
	$QUERY_STRING = $dom_QUERY->dump_mem();

	###### SCRIVO IL FILE NEI LOG
	$filename = "QUERY.xml";
	$fp_QUERY = fopen($atna_path.$filename,"w+");
    		fwrite($fp_QUERY,$QUERY_STRING);
	fclose($fp_QUERY);

	##### COMPONGO L'ARRAY DA RITORNARE
	$ret = array($QUERY_STRING,$filename);

	return $ret;

}//END OF createQueryEvent

?>