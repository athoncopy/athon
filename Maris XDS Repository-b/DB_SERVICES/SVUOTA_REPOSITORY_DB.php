<?php
# ------------------------------------------------------------------------------------
# MARIS XDS REPOSITORY
# Copyright (C) 2007 - 2010  MARiS Project
# Dpt. Medical and Diagnostic Sciences, University of Padova - csaccavini@rad.unipd.it
# This program is distributed under the terms and conditions of the GPL
# See the LICENSE files for details
# ------------------------------------------------------------------------------------

#### IF YOU WANT TO MAKE an INSERT or an UPDATE
include_once('../config/config.php');
if($database=="mysql"){
function query_execute($query)
{
# IMPORT MYSQL PARAMETERS (NOTE: IT WORKS WITH ABSOLUTE PATH ONLY !!)
	include('../config/repository_mysql_db.php');
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
}
else if($database=="oracle"){
function query_execute($query)
{
# IMPORT MYSQL PARAMETERS (NOTE: IT WORKS WITH ABSOLUTE PATH ONLY !!)
include('../config/repository_oracle_db.php');
# open connection to db
//putenv("ORACLE_HOME=/usr/lib/oracle/xe/app/oracle/product/10.2.0");
$conn = OCILogOn($user_db,$password_db,$db)
or die( "Could not connect to Oracle database!") or die (ocierror());;

# execute the EXEC query
$statement = ociparse($conn, $query);
$risultato = ociexecute($statement);


# close connection
ocilogoff($conn);

    $a = 1;
    return $a;

}//END OF query_exec($query)
}


#### PARAMETRO DI AUTORIZZAZIONE
$action = $_POST['delete_repository'];



#### ESEGUO
if($action=="database")
{
#### COMANDI
$query_DOCUMENTS = "TRUNCATE TABLE DOCUMENTS";
$query_AuditableEvent = "TRUNCATE TABLE AUDITABLEEVENT";

#### CREO L'ARRAY DEI COMANDI DA ESEGUIRE
$svuota_array =array($query_DOCUMENTS,$query_AuditableEvent);
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

$windows=substr_count(strtoupper($system),"WINDOWS");



if ($windows>0){
	exec('del ..\\tmp\\* /q');
	exec('del ..\\tmpQuery\\* /q');
	exec('del ..\\log\\* /q');
	exec('del ..\\tmp_retrive\\* /q');
	header('location: ../setup.php');

	}
else{	
	exec('rm -f ../tmp/*');
	exec('rm -f ../tmpQuery/*');
	exec('rm -f ../log/*');
	exec('rm -f ../tmp_retrive/*');
	header('location: ../setup.php');
	}

}

if($action=="documents")
{

$system=PHP_OS;

$windows=substr_count(strtoupper($system),"WINDOWS");



if ($windows>0){
	exec('del ..\\Submitted_Documents\\* /q');
	header('location: ../setup.php');

	}
else{	
	exec('rm -f -R ../Submitted_Documents/*');
	header('location: ../setup.php');
	}

}

?>