<?php
# ------------------------------------------------------------------------------------
# MARIS XDS REPOSITORY
# Copyright (C) 2007 - 2010  MARiS Project
# Dpt. Medical and Diagnostic Sciences, University of Padova - csaccavini@rad.unipd.it
# This program is distributed under the terms and conditions of the GPL
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

function makeSoapEnvelope($stringToSoap)
{
	$stringSoaped = "<SOAP-ENV:Envelope xmlns:SOAP-ENV=\"http://schemas.xmlsoap.org/soap/envelope/\"><SOAP-ENV:Header> </SOAP-ENV:Header><SOAP-ENV:Body>
			$stringToSoap
		</SOAP-ENV:Body>
        </SOAP-ENV:Envelope>";

	return $stringSoaped;

}//END OF makeSoapEnvelope($stringToSoap)

//======== PER RISPONDERE SOAP NEL CASO DI FAILURE ========//
function makeSoapedFailureResponse($advertise,$logentry)
{
	$response = "<SOAP-ENV:Envelope xmlns:SOAP-ENV=\"http://schemas.xmlsoap.org/soap/envelope/\">
	<SOAP-ENV:Header>
		<xdsheader SOAP-ENV:mustUnderstand=\"0\"> 
	      		<logentry url = $logentry/>
	      </xdsheader>
	</SOAP-ENV:Header>
	<SOAP-ENV:Body>
	      <RegistryResponse status=\"Failure\" xmlns=\"urn:oasis:names:tc:ebxml-regrep:registry:xsd:2.1\">
	      		<RegistryErrorList>
				<RegistryError codeContext=\"\" errorCode=\"Unknown\" severity=\"Error\">
				$advertise
	      			</RegistryError> 
	      		</RegistryErrorList>
	      </RegistryResponse>
	</SOAP-ENV:Body>
</SOAP-ENV:Envelope>";

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

?>
