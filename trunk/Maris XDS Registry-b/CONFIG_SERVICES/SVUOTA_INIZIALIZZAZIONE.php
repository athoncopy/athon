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

######UTILITY DI SVUOTAMENTO DELLA INIZIALIZZAZIONE DEL REGISTRY
include_once('../config/config.php');
if($database=="MYSQL"){
include_once('../lib/functions_QUERY_mysql.php');
}
else if($database=="ORACLE"){
include_once('../lib/functions_oracle.php');
}

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