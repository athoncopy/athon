<?php

######UTILITY DI SVUOTAMENTO DELLA INIZIALIZZAZIONE DEL REGISTRY
include("../lib/functions_QUERY_mysql.php");

$query_ClassificationNode = "TRUNCATE TABLE ClassificationNode";
$query_ClassificationScheme = "TRUNCATE TABLE ClassificationScheme";

$svuota_array =array($query_ClassificationNode,$query_ClassificationScheme);

$i = 0;
while($i<count($svuota_array))
{
	$comando = $svuota_array[$i];

	echo("<br><b>- ESEGUO:  $comando  </b>");
	$ris = query_exec($comando);
	if($ris==1)
	{
		echo("<b>	===>> OK -</b><br>");
	}
	echo("-----------------------------------------------------------------------------");

	$i=$i+1;

}//END OF while($i<count($svuota_array))

?>