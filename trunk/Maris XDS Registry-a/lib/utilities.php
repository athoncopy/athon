<?php
# ------------------------------------------------------------------------------------
# MARIS XDS REGISTRY
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

#### UTILITY PER RENDERE LE QUERY OK EVITANDO 
#### FALSE INTERPRETAZIONI DEI COMANDI
function adjustQuery($query_string)
{
	$NEW_query_string = "";

	### ADD
	$search_add_UPP = " ADD";### OCCHIO ALLO SPAZIO CHE PRECEDE!!!
	$search_add_dow = " add";

	#### ALTRO
	$search_RegistryPackage_1 = "= 'RegistryPackage'";
	$search_RegistryPackage_2 = "='RegistryPackage'";
	$replace_RegistryPackage = " IN (SELECT id FROM ClassificationNode WHERE ClassificationNode.parent='urn:uuid:415715f1-fc0b-47c4-90e5-c180b7b82db6') ";

	$search_ExtrinsicObject_1 = "= 'ExtrinsicObject'";
	$search_ExtrinsicObject_2 = "='ExtrinsicObject'";
	$replace_ExtrinsicObject = " IN (SELECT id FROM ClassificationNode WHERE ClassificationNode.parent='urn:uuid:415715f1-fc0b-47c4-90e5-c180b7b82db6') ";

	$search_Classification = "classification ";
	$replace_Classification = "Classification ";

	#### STRINGA CON CUI SOSTITUIRE
	$replace = " xxx";### OCCHIO ALLO SPAZIO CHE PRECEDE!!!

	#### PASSO ALLA SOSTITUZIONE
	$NEW_query_string=str_replace($search_add_UPP,$replace,$query_string);
	$NEW_query_string=str_replace($search_add_dow,$replace,$NEW_query_string);
	$NEW_query_string=str_replace($search_RegistryPackage_1,$replace_RegistryPackage,$NEW_query_string);
	$NEW_query_string=str_replace($search_RegistryPackage_2,$replace_RegistryPackage,$NEW_query_string);

	$NEW_query_string=str_replace($search_ExtrinsicObject_1,$replace_ExtrinsicObject,$NEW_query_string);
	$NEW_query_string=str_replace($search_ExtrinsicObject_2,$replace_ExtrinsicObject,$NEW_query_string);

	$NEW_query_string=str_replace($search_Classification,$replace_Classification,$NEW_query_string);

	##### RITORNO LA STRINGA MODIFICATA
	return $NEW_query_string;

}//END OF adjustQuery($query_string)


//==UTILITY PER LA GESTIONE DELL'ERRORE SUL BOUNDARY==/
   function truncateString($str,$st_to_search,$offset)
   {
      $st = "";

      if($offset == 0)
      {
        $st = (substr($str,0,(strpos($str,$st_to_search))));
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

function unhtmlentities($string)  
{
   	$trans_tbl = get_html_translation_table(HTML_ENTITIES);
   	$trans_tbl = array_flip($trans_tbl);
   	$ret = strtr ($string,$trans_tbl);
		
   	return  preg_replace('/\&\#([0-9]+)\;/me',"chr('\\1')",$ret);
	
}//END OF function unhtmlentities($string)


//== PER IMBUSTARE SOAP UNA STRINGA PASSATA ==//
function makeSoapEnvelope($stringToSoap,$logentry)
{
	//$string_soap = '';
		
	$string_soap = "<SOAP-ENV:Envelope xmlns:SOAP-ENV=\"http://schemas.xmlsoap.org/soap/envelope/\">
	<SOAP-ENV:Header>
		<xdsheader SOAP-ENV:mustUnderstand=\"0\">
			<logentry url=\"".$logentry."\"/>
		</xdsheader>
	</SOAP-ENV:Header>
	<SOAP-ENV:Body>".$stringToSoap."</SOAP-ENV:Body>
	</SOAP-ENV:Envelope>";
		
	######################
	return $string_soap;
	
}//END OF function makeSoapEnvelope($stringToSoap)

//== PER IMBUSTARE SOAP UNA STRINGA PASSATA ==//

function makeSoapedFailureResponse($advertise,$logentry)
{
	$response = "<SOAP-ENV:Envelope xmlns:SOAP-ENV=\"http://schemas.xmlsoap.org/soap/envelope/\">
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

########## COMPONE LA RISPOSTA DI SUCCESS DEL REGISTRY
function makeSoapedSuccessResponse($logentry)
{
	$success_response = "<SOAP-ENV:Envelope xmlns:SOAP-ENV=\"http://schemas.xmlsoap.org/soap/envelope/\">
	<SOAP-ENV:Body>
		<RegistryResponse 
   		status=\"Success\" 
   		xmlns=\"urn:oasis:names:tc:ebxml-regrep:registry:xsd:2.1\">
		</RegistryResponse>
	</SOAP-ENV:Body>
</SOAP-ENV:Envelope>";

	return $success_response;

}//END OF makeSoapedSuccessResponse($logentry)

#### PER LE RISPOSTE ALLE QUERY
#### RICEVE IN INGRESSO <SQLQueryResult>      </SQLQueryResult>
function makeSoapedSuccessQueryResponse($logentry,$QueryResult)
{
	$success_query_response = "<SOAP-ENV:Envelope xmlns:SOAP-ENV=\"http://schemas.xmlsoap.org/soap/envelope/\">
	<SOAP-ENV:Body>
		<RegistryResponse 
   		status=\"Success\" 
   		xmlns=\"urn:oasis:names:tc:ebxml-regrep:registry:xsd:2.1\">
			<AdhocQueryResponse xmlns=\"urn:oasis:names:tc:ebxml-regrep:query:xsd:2.1\">
			<SQLQueryResult>$QueryResult</SQLQueryResult>
			</AdhocQueryResponse>
		</RegistryResponse>
	</SOAP-ENV:Body>
</SOAP-ENV:Envelope>";

	return $success_query_response;

}//END OF makeSoapedSuccessQueryResponse($logentry,$AdhocQueryResponse)


#### PER LE RISPOSTE ALLE STORED QUERY
#### RICEVE IN INGRESSO <SQLQueryResult>      </SQLQueryResult>
function makeSoapedSuccessStoredQueryResponse($logentry,$QueryResult)	
{


	$success_query_response = "<SOAP-ENV:Envelope xmlns:SOAP-ENV=\"http://schemas.xmlsoap.org/soap/envelope/\">
	<SOAP-ENV:Header>
		<xdsheader SOAP-ENV:mustUnderstand=\"0\">
			<logentry url=$logentry/>
		</xdsheader>
	</SOAP-ENV:Header>
	<SOAP-ENV:Body>
	<AdhocQueryResponse xmlns:xsi=\"http://www.w3.org/2001/XMLSchemainstance\" 
	xsi:schemaLocation=\"urn:oasis:names:tc:ebxml-regrep:xsd:query:3.0 ../schema/ebRS/query.xsd\"
		xmlns=\"urn:oasis:names:tc:ebxml-regrep:xsd:query:3.0\"
		xmlns:rim=\"urn:oasis:names:tc:ebxml-regrep:xsd:rim:3.0\"
		status=\"Success\">
		<rim:RegistryObjectList>
		$QueryResult
		</rim:RegistryObjectList>
	</AdhocQueryResponse>
	</SOAP-ENV:Body>
	</SOAP-ENV:Envelope>";

/*<?xml version=\"1.0\" encoding=\"UTF-8\"?>*/
	/*$success_query_response = "<SOAP-ENV:Envelope xmlns:SOAP-ENV=\"http://schemas.xmlsoap.org/soap/envelope/\">
	<SOAP-ENV:Header>
		<xdsheader SOAP-ENV:mustUnderstand=\"0\">
			<logentry url=$logentry/>
		</xdsheader>
	</SOAP-ENV:Header>
	<SOAP-ENV:Body>
		<RegistryResponse 
   		status=\"Success\" 
   		xmlns=\"urn:oasis:names:tc:ebxml-regrep:registry:xsd:2.1\">
			<AdhocQueryResponse xmlns=\"urn:oasis:names:tc:ebxml-regrep:query:xsd:2.1\">
			<SQLQueryResult>$QueryResult</SQLQueryResult>
			</AdhocQueryResponse>
		</RegistryResponse>
	</SOAP-ENV:Body>
	</SOAP-ENV:Envelope>";*/

	return $success_query_response;

}//END OF makeSoapedSuccessQueryResponse($logentry,$AdhocQueryResponse)


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
function idrandom_ERRATA()
{
   $stringa1 = "";
      for($i=0; $i<8; $i++) 
         {
         $lettera = chr(rand(48,122)); // carattere casuale
         while (!ereg("[a-z0-9]", $lettera))
            {
            $lettera = chr(rand(48,122));// genera un'altra
            }
         $stringa1 .= $lettera; // accoda alla stringa
         }
   $stringa2 = "";
      for($i=0; $i<4; $i++) 
         {
         $lettera = chr(rand(48,122)); // carattere casuale
         while (!ereg("[a-z0-9]", $lettera))
            {
            $lettera = chr(rand(48,122));// genera un'altra
            }
         $stringa2 .= $lettera; // accoda alla stringa
         }
   $stringa3 = "";
      for($i=0; $i<4; $i++) 
         {
         $lettera = chr(rand(48,122)); // carattere casuale
         while (!ereg("[a-z0-9]", $lettera))
            {
            $lettera = chr(rand(48,122));// genera un'altra
            }
         $stringa3 .= $lettera; // accoda alla stringa
         }
   $stringa4 = "";
      for($i=0; $i<4; $i++) 
         {
         $lettera = chr(rand(48,122)); // carattere casuale
         while (!ereg("[a-z0-9]", $lettera))
            {
            $lettera = chr(rand(48,122));// genera un'altra
            }
         $stringa4 .= $lettera; // accoda alla stringa
         }
   $stringa5 = "";
      for($i=0; $i<12; $i++) 
         {
         $lettera = chr(rand(48,122)); // carattere casuale
         while (!ereg("[a-z0-9]", $lettera))
            {
            $lettera = chr(rand(48,122));// genera un'altra
            }
         $stringa5 .= $lettera; // accoda alla stringa
         }
      $stringa=$stringa1."-".$stringa2."-".$stringa3."-".$stringa4."-".$stringa5;
      return $stringa; // restituisci alla funzione

}//END OF idrandom_ERRATA()

### true = simbolico
function isSimbolic($id_value)
{
	$boolToReturn=true;
	$pos=strpos($id_value,"urn:uuid:");
	#### Notate l'uso di ===.  Il == non avrebbe risposto come atteso
	#### poichè la posizione di 'a' è nel primo carattere.
	if($pos===0 || $pos!="")
	{
		$boolToReturn = false;
	}

	return $boolToReturn;

}//END OF isSimbolic($id_value)

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
