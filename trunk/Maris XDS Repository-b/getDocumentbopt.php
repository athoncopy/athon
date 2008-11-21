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

ob_start();

include_once('./config/config.php');
include_once("config/REP_configuration.php");
include_once('./lib/functions_'.$database.'.php');
include_once("./lib/log.php");
include_once("./lib/utilities.php");
//include_once("./lib/domxml-php4-to-php5.php");


$idfile = idrandom_file();

$log = new Log("REP");
$log->set_tmp_path($tmp_retrieve_path);
$log->set_idfile($idfile);
$log->setLogActive($logActive);
$log->setCurrentLogPath($log_path);
$log->setCurrentFileSLogPath($tmp_retrieve_path);

$_SESSION['tmp_path']=$tmp_retrieve_path;
$_SESSION['idfile']=$idfile;
$_SESSION['logActive']=$logActive;
$_SESSION['log_path']=$log_path;

$headers = apache_request_headers();

writeTimeFile($idfile."--Repository Retrive: RECUPERO GLI HEADERS RICEVUTI DA APACHE");

if($save_files)
$log->writeLogFileS($headers,$idfile."-headers_received-".$idfile,"M");

//$input = $HTTP_RAW_POST_DATA;

$errorcode=array();
$error_message=array();

$xdsheader=true;

$input = $HTTP_RAW_POST_DATA;


if($save_files)
$log->writeLogFileS($input,$idfile."-pre_decode_received-".$idfile.".xml","M");

preg_match('(<([^\t\n\r\f\v";<]+:)?(ENVELOPE))',strtoupper($input),$matches);

$presoap=$matches[1];
$body = substr($input,strpos(strtoupper($input),"<".$presoap."ENVELOPE"));


if($save_files)
$log->writeLogFileS($body,$idfile."-body-".$idfile,"M");
//echo $body;

writeTimeFile($idfile."--Repository Retrieve: Ho recuperato soapenv");



$dom = new DomDocument;
$dom->preserveWhiteSpace = FALSE;
$dom->loadXML($body);
$Action_node = $dom->getElementsByTagName('Action');
$Action=$Action_node->item(0)->nodeValue;
writeTimeFile($idfile."--Repository Retrieve: Action: ".$Action);

$MessageID_node = $dom->getElementsByTagName('MessageID');
$MessageID=$MessageID_node->item(0)->nodeValue;
writeTimeFile($idfile."--Repository Retrieve: MessageID: ".$MessageID);

if(!$Action){
$Action="urn:ihe:iti:2007:RetrieveDocumentSet";
writeTimeFile($idfile."--Repository Retrieve: Action2: ".$Action);
$xdsheader=false;
}

if($Action=="urn:ihe:iti:2007:RetrieveDocumentSet"){
	$DocumentRequests = $dom->getElementsByTagName('DocumentRequest');

	$DocumentRequests_array=array();
	$file=array();
	foreach( $DocumentRequests as $DocumentRequest ){
		$RepositoryUniqueIds = $DocumentRequest->getElementsByTagName('RepositoryUniqueId');
		$RepositoryUniqueId=$RepositoryUniqueIds->item(0)->nodeValue;
		writeTimeFile($idfile."--Repository Retrieve: RepositoryUniqueId: ".$RepositoryUniqueId);
		$DocumentUniqueIds = $DocumentRequest->getElementsByTagName('DocumentUniqueId');
		$DocumentUniqueId=$DocumentUniqueIds->item(0)->nodeValue;
		writeTimeFile($idfile."--Repository Retrieve: DocumentUniqueId: ".$DocumentUniqueId);
		$DocumentRequests_array[]=array($RepositoryUniqueId,$DocumentUniqueId);
		}

	$numero_documenti=count($DocumentRequests_array);
	for($i=0;$i<$numero_documenti;$i++){
		$get_repUniqueId="SELECT UNIQUEID FROM CONFIG";
		$res_repUniqueId=query_select($get_repUniqueId);
		if($res_repUniqueId[0][0]==$DocumentRequests_array[$i][0]){
		//se il repository unique id corrisponde alla richiesta
		$get_DocUniqueId="SELECT XDSDOCUMENTENTRY_UNIQUEID,URI,MIMETYPE FROM DOCUMENTS WHERE XDSDOCUMENTENTRY_UNIQUEID='".$DocumentRequests_array[$i][1]."'";
		$res_DocUniqueId=query_select($get_DocUniqueId);
		$file[$i] = file_get_contents("./".$res_DocUniqueId[0][1], "r");
		$log->writeLogFileS($file[$i],$idfile."-file-".$i."-".$idfile,"M");
		
		//writeTimeFile($idfile."--GetDocument Retrieve: File $i: ".$file[$i]);

		writeTimeFile($idfile."--GetDocument Retrieve: La dimensione del file è:".filesize($tmp_retrieve_path.$idfile."-file-".$i."-".$idfile));

		}
		else {
		writeTimeFile($idfile."--GetDocument Retrieve: il repository UniqueId non corrisponde ".$res_repUniqueId[0][0]." diverso da ".$DocumentRequests_array[$i][0]);
		//Devo gestire un errore
		$errorcode[]="XDSRepositoryMetadataError";
		$error_message[] = "Repository.uniqueID '".$res_repUniqueId[0][0]."' is different form your submission '".$DocumentRequests_array[$i][0]."'";
		$repositoryUniqueId_response = makeSoapedFailureResponse($error_message,$errorcode,$Action);
			
		$file_input=$_SESSION['tmp_path'].$_SESSION['idfile']."-repositoryUniqueId_failure_response-".$_SESSION['idfile'];
		$fp = fopen($file_input, "wb+");
            	  fwrite($fp,$repositoryUniqueId_response);
         	fclose($fp);

		SendError($file_input);
		exit;
		}
	}


$boundary = md5(time());
$Content_ID=md5(time()+1);
//$messageID=md5(time()+2);
$idDoc=array();




$SOAP_stringaxml= "--".$boundary.CRLF."Content-Type: application/xop+xml; charset=UTF-8; type=\"application/soap+xml\"".CRLF."Content-Transfer-Encoding: binary
Content-ID: <0.urn:uuid:".$Content_ID."@apache.org>".CRLF.CRLF.
"<?xml version='1.0' encoding='UTF-8'?>".CRLF.
"<soapenv:Envelope xmlns:soapenv=\"http://www.w3.org/2003/05/soap-envelope\"
    xmlns:wsa=\"http://www.w3.org/2005/08/addressing\">";
if($xdsheader){
	$SOAP_stringaxml.="
    <soapenv:Header>
        <wsa:Action>urn:ihe:iti:2007:RetrieveDocumentSetResponse</wsa:Action>
        <wsa:RelatesTo>$MessageID</wsa:RelatesTo>
    </soapenv:Header>";
}
$SOAP_stringaxml.="
    <soapenv:Body>
        <xdsb:RetrieveDocumentSetResponse xmlns:xdsb=\"urn:ihe:iti:xds-b:2007\">
            <rs:RegistryResponse xmlns:rs=\"urn:oasis:names:tc:ebxml-regrep:xsd:rs:3.0\"
                status=\"urn:oasis:names:tc:ebxml-regrep:ResponseStatusType:Success\"/>";
	for($y=0;$y<$numero_documenti;$y++){
	$idDoc[$y]=md5(time()+3+$y);
	$SOAP_stringaxml.= "
            <xdsb:DocumentResponse>
                <xdsb:RepositoryUniqueId>".$DocumentRequests_array[$y][0]."</xdsb:RepositoryUniqueId>
                <xdsb:DocumentUniqueId>".$DocumentRequests_array[$y][1]."</xdsb:DocumentUniqueId>
                <xdsb:mimeType>".$res_DocUniqueId[$y][2]."</xdsb:mimeType>
                <xdsb:Document>
                    <xop:Include href=\"cid:1.urn:uuid:".$idDoc[$y]."@apache.org\"
                        xmlns:xop=\"http://www.w3.org/2004/08/xop/include\"/>
                </xdsb:Document>
            </xdsb:DocumentResponse>";
	}
	$SOAP_stringaxml.="
        </xdsb:RetrieveDocumentSetResponse>
    </soapenv:Body>
</soapenv:Envelope>".CRLF.CRLF;
       $lista_file='';
	for($a=0;$a<sizeof($numero_documenti);$a++){
       	$lista_file.="--".$boundary; 
       	$lista_file.=CRLF."Content-Type: application/octet-stream".CRLF;
	$lista_file.="Content-Transfer-Encoding: binary".CRLF;
	$s=$a+1;
	$lista_file.="Content-ID: <$s.urn:uuid:".$idDoc[$a]."@apache.org>".CRLF.CRLF;
		$lista_file.=$file[$a];
    	}

	$lista_file.=CRLF."--".$boundary."--";

	$fp_forwarded = fopen($tmp_retrieve_path.$idfile."-repositoryGet_response-".$idfile,"wb+");
	fwrite($fp_forwarded,$SOAP_stringaxml.$lista_file);
	fclose($fp_forwarded);

	//$data_length=filesize($tmp_retrieve_path."submission");
	
	ob_get_clean();
	//header("HTTP/1.1 200 OK");
	//$path_header = "Path: $www_REG_path";
	$head_content_type="Content-Type: multipart/related; boundary=\"".$boundary."\"; type=\"application/xop+xml\"; start=\"0.urn:uuid:".$Content_ID."@apache.org\"; start-info=\"application/soap+xml\";";
	$head_soap_action="SOAPAction = \"urn:ihe:iti:2007:RetrieveDocumentSetResponse\"";
	header($head_content_type);
	header($head_soap_action);
	//header("Transfer-Encoding = chunked");
	//header("Content-Length: ".(string)filesize($tmp_retrieve_path.$idfile."-repositoryGet_response-".$idfile));


	/*$header="Content-Type: multipart/related; boundary=".$boundary."; type=\"application/xop+xml\"; start=\"0.urn:uuid:".$Content_ID."@apache.org\"; start-info=\"application/soap+xml\"; SOAPAction = \"urn:ihe:iti:2007:RetrieveDocumentSetResponse\"".CRLF.
	"User-Agent: myAgent".CRLF.
	//"Host: $rep_host".":"."$rep_port".CRLF.
	//"Transfer-Encoding = chunked".CRLF.
	//"Connection: close".CRLF.
	//"Content-Length: $data_length".CRLF.
	"Content-Description: MTOM/XOP".CRLF.
        CRLF;*/

	$pacchetto=$head_content_type."\r\n\r\n".$SOAP_stringaxml.$lista_file;
	$log->writeLogFileS($pacchetto,$idfile."-HTTP_POSTED-".$idfile,"M");
	//ob_end_flush();


if($file = fopen($tmp_retrieve_path.$idfile."-repositoryGet_response-".$idfile,'rb'))
{
   while((!feof($file)) && (connection_status()==0))
   {	
	writeTimeFile($idfile."--Repository Sto inviando il file");
      	print(fread($file, 1024*8));
      	flush();//NOTA BENE!!!!!!!!!
   }

   fclose($file);
}




}

else {
writeTimeFile($idfile."--Repository $Action non è un'azione permessa");
}



if($ATNA_active=='A'){
include_once("rep_atna.php");

$eventOutcomeIndicator="0"; //EventOutcomeIndicator 0 OK 12 ERROR
//$ip_repository=$rep_host; 
//$ip_consumer=$_SERVER['REMOTE_ADDR']; 

	$today = date("Y-m-d");
	$cur_hour = date("H:i:s");
	$datetime = $today."T".$cur_hour;
//$message=createExportEvent($eventOutcomeIndicator,$ip_repository,$ip_consumer);
$message_export="<AuditMessage xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:noNamespaceSchemaLocation=\"healthcare-security-audit.xsd\">
<EventIdentification EventActionCode=\"R\" EventDateTime=\"$datetime\" EventOutcomeIndicator=\"0\">
        	<EventID code=\"110106\" codeSystemName=\"DCM\" displayName=\"Export\"/>
        	<EventTypeCode code=\"ITI-43\" codeSystemName=\"IHE Transactions\" displayName=\"Retrieve Document Set\"/>
    	</EventIdentification>
	<ActiveParticipant UserID=\"".$_SERVER['REMOTE_ADDR']."\" UserIsRequestor=\"true\">
        	<RoleIDCode code=\"110153\" codeSystemName=\"DCM\" displayName=\"Source\"/>
	</ActiveParticipant>
	<ActiveParticipant UserID=\"http://".$rep_host.":".$rep_port.$www_REP_path."getDocumentbopt.php\"  UserIsRequestor=\"false\">
        	<RoleIDCode code=\"110152\" codeSystemName=\"DCM\" displayName=\"Destination\"/>
    	</ActiveParticipant>
	<AuditSourceIdentification AuditSourceID=\"MARIS REPOSITORY\"/>
<ParticipantObjectIdentification ParticipantObjectID=\"http://".$rep_host.":".$rep_port.$www_REP_path."getDocumentbopt.php\" ParticipantObjectTypeCode=\"2\" ParticipantObjectTypeCodeRole=\"3\">
        	<ParticipantObjectIDTypeCode code=\"12\"/>
    	</ParticipantObjectIdentification>
</AuditMessage>";



require_once('./lib/syslog.php');
        $syslog = new Syslog();
        $logSyslog=$syslog->Send($ATNA_host,$ATNA_port,$message_export);

$INSERT_atna_export = "INSERT INTO AUDITABLEEVENT (EVENTTYPE,REGISTRYOBJECT,TIME_STAMP,SOURCE) VALUES ('Export','".$uri_token[0][0]."',CURRENT_TIMESTAMP,'".$ip_consumer."')";
if($save_files){
$fp_ebxml_val =
fopen($tmp_path.$idfile."-insert_java_atna_export-".$idfile,"w+");
	fwrite($fp_ebxml_val,$INSERT_atna_export);
fclose($fp_ebxml_val);
}

}
##### PULISCO IL BUFFER DI USCITA
//ob_get_clean();//OKKIO FONDAMENTALE!!!!!
//header("Location: ".$www_REP_path.$uri_token[0][0]);
?>
