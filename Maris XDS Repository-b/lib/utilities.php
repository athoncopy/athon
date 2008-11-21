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

//==UTILITY PER LA GESTIONE DELLE STRINGHE CON SINGOLI APICI DA INSERIRE NEL DB==/

function adjustString($stringToAdjust)
{
	$string_to_return = str_replace("'","''",$stringToAdjust);
	return $string_to_return;
	
}//END OF adjustString($stringToAdjust)
	
//==UTILITY PER LA GESTIONE DELLE STRINGHE CON SINGOLI APICI DA INSERIRE NEL DB==/

//ESEGUE LA SOSTITUZION URN:UUID: ---> URN-UUID-
function adjustURN_UUIDs($ebxml_string)
{
	$NEW_ebxml_string="";
	$search = "urn:uuid:";
	$replace = "urn-uuid-";
	$NEW_ebxml_string = str_replace($search,$replace,$ebxml_string);

	return $NEW_ebxml_string;

}//END OF adjustURN_UUIDs($ebxml_string)

//==UTILITY PER LA GESTIONE DELL'ERRORE SUL BOUNDARY==/
   function truncateString($str,$st_to_search,$offset)
   {
      $st = "";
      $tempString = "";

      if($offset == 0)//CASO DI OGGETTO DICOM: NO TRIM !!!
      {
	$tempString = (substr($str,0,(strpos($str,$st_to_search))));
        $tempString_2 = rtrim($tempString,"\n");//NOTA BENE PRIMA n POI r !!!!
        $st = rtrim($tempString_2,"\r");
      }
      else
      {
        $st = trim(substr($str,0,(strpos($str,$st_to_search,$offset))));
      }

		#################
      return($st);
		 
   }//END OF truncateString($str,$st_to_search,$offset)
	
//==UTILITY PER LA GESTIONE DELL'ERRORE SUL BOUNDARY==/


//==IMPEDISCE CHE PHP INTERPRETI LE HTMLENTITIES SULLA STRINGA DI INGRESSO $str==/
	//UTILE PER IL PATIENTID
function avoidHtmlEntitiesInterpretation($str)
{
	$trans = get_html_translation_table(HTML_ENTITIES);
	$encoded = strtr($str,$trans);
		
	#################
	return $encoded;
	
}//END OF avoidHtmlEntitiesInterpretation($str)
	
//==IMPEDISCE CHE PHP INTERPRETI LE HTMLENTITIES==/


// 1 --> modificabile
// 0 --> NON modificabile
### $node = IL SINGOLO NODO EXTRINSICOBJECT
function modifiable($node)
{
	$hash_bool = true;
	$size_bool = true;
	$URI_bool = true;
	
	$problem = "";

	$child_array_nodes = $node->child_nodes();
	for($j = 0;$j<count($child_array_nodes);$j++)
	{
		$nod = $child_array_nodes[$j];
		$nod_name = $nod->node_name();
		if ($nod->node_type() == XML_ELEMENT_NODE) 
		{
			$name = $nod->node_name();
			if($name == "Slot")
			{
				#### SLOT ATTRIBUTE
				$attribute = $nod->get_attribute("name");

				if(strtoupper($attribute) == "HASH") 
				{
					$hash_bool = false;
				}
				if(strtoupper($attribute) == "SIZE")
				{
					 $size_bool = false;
				}
				if(strtoupper($attribute) == "URI") 
				{
					$URI_bool = false;
				}

			}//END OF if($name == "Slot")
			
		}//END OF if ($nod->node_type() == XML_ELEMENT_NODE) 
		
	}//END OF for($j = 0;$j<count($child_array_nodes);$j++)

	#### COMPONGO IL VALORE BOOLEANO DA RITORNARE
	$ret = ($hash_bool && $size_bool && $URI_bool);

	#### RETURN
	return $ret;

}//END OF function modifiable($node)



function makeSoapEnvelope($stringToSoap,$action)
{

	$stringSoaped = "<?xml version='1.0' encoding='UTF-8'?>\r\n<SOAP-ENV:Envelope xmlns:SOAP-ENV=\"http://www.w3.org/2003/05/soap-envelope\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\" xmlns:wsa=\"http://www.w3.org/2005/08/addressing\"><SOAP-ENV:Header>
	<wsa:To>http://".$_SESSION['reg_host'].":".$_SESSION['reg_port'].$_SESSION['reg_path']."</wsa:To>
	<wsa:MessageID>".$_SESSION['messageID']."</wsa:MessageID>
	<wsa:Action>urn:ihe:iti:2007:".$action."</wsa:Action>
	</SOAP-ENV:Header>
	<SOAP-ENV:Body>
			$stringToSoap
	</SOAP-ENV:Body>
	</SOAP-ENV:Envelope>
	";

	return $stringSoaped;

}//END OF makeSoapEnvelope($stringToSoap)

//======== PER RISPONDERE SOAP NEL CASO DI FAILURE ========//
function makeSoapedFailureResponse($advertise,$errorcode,$action="urn:ihe:iti:2007:ProvideAndRegisterDocumentSet-b")
{
	$response = "<?xml version='1.0' encoding='UTF-8'?>\r\n<soapenv:Envelope xmlns:soapenv=\"http://www.w3.org/2003/05/soap-envelope\" xmlns:wsa=\"http://www.w3.org/2005/08/addressing\">
	<soapenv:Header>
	<wsa:MessageID>".$_SESSION['messageID']."</wsa:MessageID>
	<wsa:Action>".$action."Response</wsa:Action>
	</soapenv:Header>
	<soapenv:Body>
		 <rs:RegistryResponse xmlns:rs=\"urn:oasis:names:tc:ebxml-regrep:xsd:rs:3.0\" status=\"urn:oasis:names:tc:ebxml-regrep:ResponseStatusType:Failure\">
	      		<rs:RegistryErrorList>";
			for($i=0;$i<count($errorcode);$i++){
			$response .= "\r<rs:RegistryError codeContext=\"".$advertise[$i]."\" errorCode=\"".$errorcode[$i]."\" severity=\"Error\"/>";
			}
	  $response .="</rs:RegistryErrorList>
	      </rs:RegistryResponse>
	</soapenv:Body>
</soapenv:Envelope>";

	return $response;


}//END OF makeSoapedFailureResponse

####### GENERA URN UUIDs
function idrandom()
{
   if(function_exists('com_create_guid'))
   {
       return com_create_guid();
   }else{

       mt_srand((double)microtime()*10000);
       $charid = strtolower(md5(uniqid(rand(), true)));
       $hyphen = chr(45);
       $uuid = substr($charid, 0, 8).$hyphen
               .substr($charid, 8, 4).$hyphen
               .substr($charid,12, 4).$hyphen
               .substr($charid,16, 4).$hyphen
               .substr($charid,20,12);

       return $uuid;
   }

}//END OF idrandom()

###################################################

###### GENERA UN ID RANDOM
function idrandom_file()
{
   $stringa = "";
      for($i=0; $i<8; $i++) 
         {
         $lettera = chr(rand(48,122)); // carattere casuale
         while (!ereg("[a-z0-9]", $lettera))
            {
            $lettera = chr(rand(48,122));// genera un'altra
            }
         $stringa .= $lettera; // accoda alla stringa
         }

      return $stringa; // restituisci alla funzione

}//END OF idrandom_ERRATA()

function givenamescape($link,$input){

	preg_match('/((xmlns:)?([^\t\n\r\f\v ";<]+)?(="'.$link.'"))/i',$input,$matches);

	$namespace=$matches[3];

return $namespace;

}


function giveboundary($headers){

	if(stripos($headers["Content-Type"],"boundary")){
		writeTimeFile($_SESSION['idfile']."--Repository: Il boundary e' presente");

		if (preg_match('(boundary="[^\t\n\r\f\v";]+")',$headers["Content-Type"])) {
			writeTimeFile($_SESSION['idfile']."--Repository: Ho trovato il boundary di tipo boundary=\"bvdwetrct637crtv\"");

			$content_type = stristr($headers["Content-Type"],'boundary');
			$pre_boundary = substr($content_type,strpos($content_type,'"')+1);

			$fine_boundary = strpos($pre_boundary,'"')+1;
			//BOUNDARY ESATTO
			$boundary = '';
			$boundary = substr($pre_boundary,0,$fine_boundary-1);

			writeTimeFile($idfile."--Repository: Il boundary ".$boundary);
		}

		else if (preg_match('(boundary=[^\t\n\r\f\v";]+[;])',$headers["Content-Type"])) {
			writeTimeFile($_SESSION['idfile']."--Repository: Ho trovato il boundary di tipo boundary=bvdwetrct637crtv;");
			$content_type = stristr($headers["Content-Type"],'boundary');
			$pre_boundary = substr($content_type,strpos($content_type,'=')+1);
			$fine_boundary = strpos($pre_boundary,';');
			//BOUNDARY ESATTO
			$boundary = '';
			$boundary = substr($pre_boundary,0,$fine_boundary);

			writeTimeFile($_SESSION['idfile']."--Repository: Il boundary ".$boundary);

		}

		else {
			writeTimeFile($_SESSION['idfile']."--Repository: Il boundary non e' del tipo boundary=\"bvdwetrct637crtv\" o boundary=bvdwetrct637crtv;");
	
 		}

		$MTOM=false;
	}
	//Caso MTOM
	else {
		writeTimeFile($_SESSION['idfile']."--Repository: non e' dichiarato il boundary");
		$MTOM=true;
		$boundary = "--boundary_per_MTOM";
	}

	$ret=array($boundary,$MTOM);
 return $ret;
}


function SendError($file_input){

	ob_get_clean();//OKKIO FONDAMENTALE!!!!!

	//HEADERS
	header("HTTP/1.1 200 OK");
	header("Path: ".$_SESSION['www_REP_path']);
	header("Content-Type: application/soap+xml;charset=UTF-8");
	header("Content-Length: ".(string)filesize($file_input));
		//CONTENUTO DEL FILE DI RISPOSTA
	if($file = fopen($file_input,'rb'))
	{
   		while((!feof($file)) && (connection_status()==0))
   		{
     			print(fread($file, 1024*8));
      			flush();//NOTA BENE!!!!!!!!!
   		}

   		fclose($file);
	}

	//SPEDISCO E PULISCO IL BUFFER DI USCITA
	ob_end_flush();
	//BLOCCO L'ESECUZIONE DELLO SCRIPT
	exit;

}


?>
