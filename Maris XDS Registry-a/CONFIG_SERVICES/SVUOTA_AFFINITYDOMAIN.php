<?php
# ------------------------------------------------------------------------------------
# MARIS XDS REGISTRY
# Copyright (C) 2007 - 2010  MARiS Project
# Dpt. Medical and Diagnostic Sciences, University of Padova - csaccavini@rad.unipd.it
# This program is distributed under the terms and conditions of the GPL
# See the LICENSE files for details
# ------------------------------------------------------------------------------------

######UTILITY DI SVUOTAMENTO AFFINITY DOMAIN
include_once('../config/config.php');
if($database=="MYSQL"){
include_once('../lib/functions_QUERY_mysql.php');
}
else if($database=="ORACLE"){
include_once('../lib/functions_oracle.php');
}

$query_classCode = "TRUNCATE TABLE classCode";
$query_classScheme = "TRUNCATE TABLE classScheme";
$query_codeList = "TRUNCATE TABLE codeList";
$query_confidentialityCode = "TRUNCATE TABLE confidentialityCode";
$query_contentTypeCode = "TRUNCATE TABLE contentTypeCode";
$query_formatCode = "TRUNCATE TABLE formatCode";
$query_healthcareFacilityTypeCode = "TRUNCATE TABLE healthcareFacilityTypeCode";
$query_mimeType = "TRUNCATE TABLE mimeType";
$query_practiceSettingCode = "TRUNCATE TABLE practiceSettingCode";
$query_typeCode = "TRUNCATE TABLE typeCode";

$svuota_array =array($query_classCode,$query_classScheme,$query_codeList,$query_confidentialityCode,$query_contentTypeCode,$query_formatCode,$query_healthcareFacilityTypeCode,$query_mimeType,$query_practiceSettingCode,$query_typeCode);

$i = 0;
while($i<count($svuota_array))
{
	$comando = $svuota_array[$i];

	echo("<br><b>- ESEGUO:  $comando  </b>");
	$ris = query_exec($comando);
	if($ris==1)
	{
		echo("<b>	===>> OK -<br></b>");
	}
	echo("-------------------------------------------------------");

	$i=$i+1;

}//END OF while($i<count($svuota_array))

?>