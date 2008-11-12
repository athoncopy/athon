<?php

#### IF YOU WANT TO MAKE an INSERT or an UPDATE
function query_execute($query)
{
# IMPORT MYSQL PARAMETERS (NOTE: IT WORKS WITH ABSOLUTE PATH ONLY !!)
	include('../config/repository_mysql_db.php');
# open connection to db
    $connessione = mysql_connect($ip, $user_db, $password_db)
        or die("Connessione non riuscita: " . mysql_error());

# open  db
   mysql_select_db($db_name);

# execute the SELECT query
   $risultato = mysql_query($query);
# close connection
    mysql_close($connessione);
    $a = 1;
    return $a;

}//END OF query_execute($query)

#### PARAMETRO DI AUTORIZZAZIONE
$action = $_POST['delete_repository'];



#### ESEGUO
if($action=="database")
{
#### COMANDI
$query_DOCUMENTS = "TRUNCATE TABLE DOCUMENTS";
$query_TOKENS = "TRUNCATE TABLE TOKEN";

#### CREO L'ARRAY DEI COMANDI DA ESEGUIRE
$svuota_array =array($query_DOCUMENTS,$query_TOKENS);
	$i = 0;
	while($i<count($svuota_array))
	{
		$comando = $svuota_array[$i];

		//echo("<br><b>- ESEGUO:  $comando  </b>");
		$ris = query_execute($comando);
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