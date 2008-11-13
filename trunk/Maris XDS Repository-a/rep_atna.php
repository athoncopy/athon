<?php
# ------------------------------------------------------------------------------------
# MARIS XDS REPOSITORY
# Copyright (C) 2007 - 2010  MARiS Project
# Dpt. Medical and Diagnostic Sciences, University of Padova - csaccavini@rad.unipd.it
# This program is distributed under the terms and conditions of the GPL
# See the LICENSE files for details
# ------------------------------------------------------------------------------------

####### UTILITIES DI CREAZIONE DEI MESSAGGI DI AUDIT

####### EVENTO EXPORT
function createExportEvent($eventOutcomeIndicator,$ip_repository,$ip_consumer)
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
		// Repository
		$ActiveParticipant=$ActiveParticipant_arr[0];

		#### ATTIBUTO USERID
		$ActiveParticipant->set_attribute("UserID","XDS Repository");
		#### ATTRIBUTO NETWORKACCESPOINTID
		$ActiveParticipant->set_attribute("NetworkAccessPointID",$ip_repository);

		// Consumer
		$ActiveParticipant=$ActiveParticipant_arr[1];

		#### ATTIBUTO USERID
		$ActiveParticipant->set_attribute("UserID","XDS Consumer");
		#### ATTRIBUTO NETWORKACCESPOINTID
		$ActiveParticipant->set_attribute("NetworkAccessPointID",$ip_consumer);


	#### RITORNO LA STRINGA DA DOM MODIFICATO
	$EXPORT_STRING = $dom_EXPORT->dump_mem();

	###### SCRIVO IL FILE NEI LOG
	$EXPORT_STRING_2 = stristr($EXPORT_STRING, '<AuditMessage>');
	
	$filename = "DataExport.xml";
	$fp_EXPORT = fopen($atna_path.$filename,"w+");
    		fwrite($fp_EXPORT,$EXPORT_STRING_2);
	fclose($fp_EXPORT);

	##### COMPONGO L'ARRAY DA RITORNARE
	//$ret = array($EXPORT_STRING,$filename);

	return $EXPORT_STRING_2;

}//END OF createExportEvent

######################################################


?>