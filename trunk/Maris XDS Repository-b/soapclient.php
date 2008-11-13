<?php

$filename="./tmp/fmqrodfa-forwarded-fmqrodfa";
$fp_XML = fopen($filename,"r");
$ebxml_STRING =	fread($fp_XML, filesize($filename));

$client = new SoapClient("./wsdl/XDS.b_DocumentRegistry.wsdl");

$response=$client->doRequest($ebxml_STRING,"http://129.6.24.109:9080/axis2/services/xdsregistryb","urn:ihe:iti:2007:RegisterDocumentSet-b",SOAP_1_2);
echo $response;

?>
