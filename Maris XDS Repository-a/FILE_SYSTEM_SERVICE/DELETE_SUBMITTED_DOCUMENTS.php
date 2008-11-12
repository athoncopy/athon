<?php

#### UTILITY CHE PERMETTE DI RIMUOVERE TUTTI I DOCUMENTI PRESENTI 
#### NELLA CARTELLA  Submitted_Documents

#### PARAMETRO DI AUTORIZZAZIONE
$delDocs = "A";

#### PARAMETRI
$fileS="*.*";
$path="/srv/www/htdocs/O3_xds/xdsServices2/repository/Submitted_Documents/";

#### PATH COMPLETO
$path_1=$path.$fileS;

#### COMPONGO IL COMANDO
$comando="rm -f $path_1";

if($delDocs=="A")
{
	
	echo("<br><b>- ESEGUO:  $comando  </b>");
	$del_result = exec($comando,$output,$error);

	if($error==0)
	{
		echo("<b>	===>> OK -</b><br>");
		echo("<br><b> - ATTENZIONE: TUTTI I DOCUMENTI SONO ANDATI PERSI - </b><br>");
	}
	else{ echo "<br><b>- [ERROR]: $error</b>";}
	
}//END OF if($delDocs=="A")

else{
		echo("<br><b>- CANCELLAZIONE DELLA DIRECTORY  $path  -- NON --  ESEGUITA: NON SI DISPONE DELL'AUTORIZZAZIONE NECESSARIA -</b><br>");

}//END OF ELSE

?>