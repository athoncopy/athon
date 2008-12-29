<?php
# ------------------------------------------------------------------------------------
# MARIS XDS REPOSITORY
# Copyright (C) 2007 - 2010  MARiS Project
# Dpt. Medical and Diagnostic Sciences, University of Padova - csaccavini@rad.unipd.it
# This program is distributed under the terms and conditions of the GPL

# Contributor(s):
# A-thon srl <info@a-thon.it>
# Alberto Castellini

# See the LICENSE files for details
# ------------------------------------------------------------------------------------

#### IF YOU WANT TO MAKE an INSERT or an UPDATE
function query_execute($query)
{
# IMPORT MYSQL PARAMETERS (NOTE: IT WORKS WITH ABSOLUTE PATH ONLY !!)
include('./config/repository_oracle_db.php');
# open connection to db
//putenv("ORACLE_HOME=/usr/lib/oracle/xe/app/oracle/product/10.2.0");
$conn = oci_connect($user_db,$password_db,$db)
or die( "Could not connect to Oracle database!") or die (ocierror());;

# execute the EXEC query
$statement = ociparse($conn, $query);
$risultato = ociexecute($statement);


# close connection
oci_close($conn);

    $a = 1;
    return $a;

}//END OF query_execute($query)


function query_execute2($query,$conn)
{
# execute the EXEC query
$statement = ociparse($conn, $query);
$risultato = ociexecute($statement);

    if($risultato){
   	$a = 1;
    	return $a;
    }
    // Se non riesce ad eseguire la query prova a riconnettersi
    else {
	include('./config/repository_oracle_db.php');
	$conn = oci_connect($user_db,$password_db,$db);
	if(!$conn){

		$errorcode=array();
		$error_message=array();
	
		$errorcode[]="XDSRepositoryError";
		$err=ocierror();
		$error_message[] = $err['message'];
		$database_error_response = makeSoapedFailureResponse($error_message,$errorcode);
		writeTimeFile($_SESSION['idfile']."--Repository: database_error_response");
			
		$file_input=$_SESSION['idfile']."-database_error_response-".$_SESSION['idfile'];
		writeTmpFiles($database_error_response,$file_input);
	
		SendResponse($database_error_response);
		exit;
	}

	$statement = ociparse($conn, $query);
	$risultato = ociexecute($statement);
	
	if($risultato){
   	 	$a = 1;
    		return $a;
		}
	// Se non riesce nemmeno adesso ritorna un errore
	else {
		
		return "FALSE";
	}
     }
	
	

}//END OF query_exec2($query)


#### IF YOU WANT TO MAKE a SELECT command
####  RETURN: A BIDIMENSIONAL ARRAY $rec[..][..]
function query_select($query)
{
/*
this function execute a query on a database MySQL using a SQL statement passed in the variable $query
and returns an array $rec with the result recordset.
Use this function ONLY with SELECT statements, if you want to execute  INSERT or UPDATE
use the function query_execute().
*/
$rec=array();
include('./config/repository_oracle_db.php');
//putenv("ORACLE_HOME=/usr/lib/oracle/xe/app/oracle/product/10.2.0");
# open connection to db
$conn = oci_connect($user_db,$password_db,$db)
or die( "Could not connect to Oracle database!") or die (ocierror());;
   //$rec=array();
# execute the EXEC query
$statement = ociparse($conn, $query);
$risultato = ociexecute($statement);

# open  db
//$rec=array();
# execute the EXEC query

while (OCIFetchInto ($statement, $row)) {
    $rec[]=$row;
//print_r($row);

}

//OCIFetchInto ($statement, $row, OCI_ASSOC);
return $rec;
# close connection
oci_close($conn);
}


function query_select2($query,$conn)
{
$rec=array();
$statement = ociparse($conn, $query);
$risultato = ociexecute($statement);

if($risultato){
	while (OCIFetchInto ($statement, $row, OCI_NUM+OCI_ASSOC)) {
    		$rec[]=$row;
	}
	return $rec;
}
// Se non riesce ad eseguire la query prova a riconnettersi
else {
	include('./config/repository_oracle_db.php');
	$conn = oci_connect($user_db,$password_db,$db);
	if(!$conn){

		$errorcode=array();
		$error_message=array();
	
		$errorcode[]="XDSRepositoryError";
		$err=ocierror();
		$error_message[] = $err['message'];
		$database_error_response = makeSoapedFailureResponse($error_message,$errorcode);
		writeTimeFile($_SESSION['idfile']."--Repository: database_error_response");
			
		$file_input=$_SESSION['idfile']."-database_error_response-".$_SESSION['idfile'];
		writeTmpFiles($database_error_response,$file_input);
	
		SendResponse($database_error_response);
		exit;
	}

	$statement = ociparse($conn, $query);
	$risultato = ociexecute($statement);
	
	if($risultato){
		while (OCIFetchInto ($statement, $row, OCI_NUM+OCI_ASSOC)) {
    		$rec[]=$row;
		}
	return $rec;
	}
	
	// Se non riesce nemmeno adesso ritorna un errore
	else {
		
	return "FALSE";
	}

}

}


//Per pagina di verifica
function query_select3($query,$table)
{

$flag_green="img/flag-green.png";
$flag_red="img/flag-red.png";

$rec=array();
include('./config/repository_oracle_db.php');
# open connection to db
$conn = OCILogOn($user_db,$password_db,$db)
or die( "Could not connect to Oracle database!") or die (ocierror());;
   //$rec=array();
# execute the EXEC query
$statement = ociparse($conn, $query);
$risultato = ociexecute($statement);

if ($risultato){
?>
    <tr class="patient">
        <td class="valore" width="50"><img src="<?php echo $flag_green; ?>" width="32" height="32"/></td>
        <td class="valore"><?php echo "$table: OK";?></td>
    </tr>
<?php

}
else {
$err=array();
$err=ocierror($statement);
$error_message = $err['message'];

?>
    <tr class="patient">
        <td class="valore" width="50"><img src="<?php echo $flag_red; ?>" width="32" height="32"/></td>
        <td class="valore"><?php echo $table.": ".$error_message; ?></td>
    </tr>
<?php
}

}//END OF query_select3($query)

function connectDB(){

include('./config/repository_oracle_db.php');
//putenv("ORACLE_HOME=/usr/lib/oracle/xe/app/oracle/product/10.2.0");
# open connection to db
$conn = oci_connect($user_db,$password_db,$db);
	if(!$conn){

		$errorcode=array();
		$error_message=array();
	
		$errorcode[]="XDSRepositoryError";
		$err=ocierror();
		$error_message[] = $err['message'];
		$database_error_response = makeSoapedFailureResponse($error_message,$errorcode);
		writeTimeFile($_SESSION['idfile']."--Repository: database_error_response");
			
		$file_input=$_SESSION['idfile']."-database_error_response-".$_SESSION['idfile'];
		writeTmpFiles($database_error_response,$file_input);
	
		SendResponse($database_error_response);
		exit;
	}

return $conn;
}


function disconnectDB($conn){

oci_close($conn);

}


?>
