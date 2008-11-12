<?php

######UTILITY DI SVUOTAMENTO DEL DB DEL REGISTRY
function query_exec($query) //ERA LA query_execute($query)
{
# IMPORT MYSQL PARAMETERS (NOTE: IT WORKS WITH ABSOLUTE PATH ONLY !!)
include('../config/registry_QUERY_mysql_db.php');


# open connection to db
    $connessione = mysql_connect($ip_q,$user_db_q,$password_db_q)
        or die("Connessione non riuscita: " . mysql_error());

# open  db
   mysql_select_db($db_name_q);

# execute the SELECT query
   $risultato = mysql_query($query);
# close connection
    mysql_close($connessione);
    $a = 1;
    return $a;

}//END OF query_execute($query)

#### PARAMETRO DI AUTORIZZAZIONE

$action = $_POST['delete_registry'];

#### COMANDI
$query_Association = "TRUNCATE TABLE Association";
$query_Classification = "TRUNCATE TABLE Classification";
$query_Counters = "UPDATE Counters SET Counters.id = 0";
$query_Description = "TRUNCATE TABLE Description";
$query_ExternalIdentifier = "TRUNCATE TABLE ExternalIdentifier";
$query_ExtrinsicObject = "TRUNCATE TABLE ExtrinsicObject";
$query_Name = "TRUNCATE TABLE Name";

##### ATTENZIONE NON DECOMMENTARE SE NON NECESSARIO!!!
### $query_Patient = "TRUNCATE TABLE Patient";

$query_RegistryPackage = "TRUNCATE TABLE RegistryPackage";
$query_Slot = "TRUNCATE TABLE Slot";

#### CREO L'ARRAY DEI COMANDI DA ESEGUIRE
$svuota_array =array($query_Association,$query_Classification,$query_Counters,$query_Description,$query_ExternalIdentifier,$query_ExtrinsicObject,$query_Name,$query_RegistryPackage,$query_Slot);

###### ESEGUO
if($action=="database")
{
	$i = 0;
	while($i<count($svuota_array))
	{
		$comando = $svuota_array[$i];

		//echo("<br><b>- ESEGUO:  $comando  </b>");
		$ris = query_exec($comando);
		/*if($ris==1)
		{
			echo("<b>	===>> OK -</b><br>");
		}
		echo("-----------------------------------------------------------------------------------------");*/

		$i=$i+1;

	}//END OF while($i<count($svuota_array))

	#### ATTENZIONE
	//echo("<br><br><br><b>- ATTENZIONE: SI SONO PERSE TUTTE LE INFORMAZIONI SUI DOCUMENTI !!!! -</b><br>");
	header('location: ../setup.php');
}//END OF if($truncDb=="A")

if($action=="tmp")
{

$system=PHP_OS;

$windows=substr_count(strtoupper($system),"WIN");



if ($windows>0){
	exec('del ..\\tmp\\* /q');
	exec('del ..\\tmpQuery\\* /q');
	header('location: ../setup.php');

	}
else{	
	exec('rm -f ../tmp/*');
	exec('rm -f ../tmpQuery/*');
	header('location: ../setup.php');
	}

}


?>