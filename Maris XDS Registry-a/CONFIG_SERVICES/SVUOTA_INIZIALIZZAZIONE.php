<?php
# ------------------------------------------------------------------------------------
# MARIS XDS REGISTRY
# Copyright (C) 2007 - 2010  MARiS Project
# Dpt. Medical and Diagnostic Sciences, University of Padova - csaccavini@rad.unipd.it
# This program is distributed under the terms and conditions of the GPL
# See the LICENSE files for details
# ------------------------------------------------------------------------------------

######UTILITY DI SVUOTAMENTO DELLA INIZIALIZZAZIONE DEL REGISTRY
require('../config/config.php');
require('../lib/functions_'.$database.'.php');

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