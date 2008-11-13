<?php
# ------------------------------------------------------------------------------------
# MARIS XDS REPOSITORY
# Copyright (C) 2007 - 2010  MARiS Project
# Dpt. Medical and Diagnostic Sciences, University of Padova - csaccavini@rad.unipd.it
# This program is distributed under the terms and conditions of the GPL
# See the LICENSE files for details
# ------------------------------------------------------------------------------------

require_once("./config/REP_configuration.php");

ob_start(); //Non stampa niente a monitor ma mette tutto su un buffer
require_once($lib_path."domxml-php4-to-php5.php");
$connessione=connectDB();

//ob_get_clean();//OKKIO FONDAMENTALE!!!!! Pulisco il buffer
//ob_end_flush();// Spedisco il contenuto del buffer
$token=$_GET["token"];
$get_token="SELECT URI FROM DOCUMENTS WHERE KEY_PROG=$token";
$uri_token=query_select2($get_token,$connessione);


// Da verificare se si possono usare funzioni php al posto di java
if($ATNA_active=='A'){
	$eventOutcomeIndicator="0"; //EventOutcomeIndicator 0 OK 12 ERROR
	$today = date("Y-m-d");
	$cur_hour = date("H:i:s");
	$datetime = $today."T".$cur_hour;
	$message_export="<AuditMessage xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:noNamespaceSchemaLocation=\"healthcare-security-audit.xsd\">
	<EventIdentification EventActionCode=\"R\" EventDateTime=\"$datetime\" EventOutcomeIndicator=\"0\">
        	<EventID code=\"110106\" codeSystemName=\"DCM\" displayName=\"Export\"/>
        	<EventTypeCode code=\"ITI-17\" codeSystemName=\"IHE Transactions\" displayName=\"Retrieve Document\"/>
    	</EventIdentification>
	<ActiveParticipant UserID=\"MARIS VIEW\" NetworkAccessPointTypeCode=\"2\" NetworkAccessPointID=\"".$_SERVER['REMOTE_ADDR']."\"  UserIsRequestor=\"true\">
        	<RoleIDCode code=\"110153\" codeSystemName=\"DCM\" displayName=\"Source\"/>
	</ActiveParticipant>
	<ActiveParticipant UserID=\"http://".$rep_host.":".$rep_port.$www_REP_path."getDocument.php\" NetworkAccessPointTypeCode=\"2\" NetworkAccessPointID=\"".$reg_host."\"  UserIsRequestor=\"false\">
        	<RoleIDCode code=\"110152\" codeSystemName=\"DCM\" displayName=\"Destination\"/>
    	</ActiveParticipant>
	<AuditSourceIdentification AuditSourceID=\"MARIS REPOSITORY\"/>
	<ParticipantObjectIdentification ParticipantObjectID=\"http://".$rep_host.":".$rep_port.$www_REP_path."getDocument.php?token=".$token."\" ParticipantObjectTypeCode=\"2\" ParticipantObjectTypeCodeRole=\"3\">
        	<ParticipantObjectIDTypeCode code=\"12\"/>
    	</ParticipantObjectIdentification>
	</AuditMessage>";



	require_once('./lib/syslog.php');
        $syslog = new Syslog();
        $logSyslog=$syslog->Send($ATNA_host,$ATNA_port,$message_export);

}

disconnectDB($connessione);

header("Location: ".$www_REP_path.$uri_token[0][0]);

?>
