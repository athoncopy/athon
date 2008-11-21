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

#### IF YOU WANT TO MAKE an INSERT or an UPDATE
function query_execute($query)
{
# IMPORT MYSQL PARAMETERS (NOTE: IT WORKS WITH ABSOLUTE PATH ONLY !!)
	include('./config/repository_mysql_db.php');
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



function query_execute2($query,$connessione) //ERA LA query_execute($query)
{
# execute the SELECT query
   $risultato = mysql_query($query);
	if($risultato){
    		$a = 1;
    		return $a;
	}
	// Se non riesce ad eseguire la query prova a riconnettersi
	else {
		include('./config/repository_mysql_db.php');
    		$connessione = mysql_connect($ip,$user_db,$password_db)
        	or die("Connessione non riuscita: " . mysql_error());

		# open  db
   		mysql_select_db($db_name);

		# execute the SELECT query
  		$risultato = mysql_query($query);
	
		if($risultato){
   	 		$a = 1;
    			return $a;
		}
	// Se non riesce nemmeno adesso ritorna un errore
		else {
			return "FALSE";
		}
     	}

}//END OF query_execute($query)






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

include('./config/repository_mysql_db.php');
# open connection to db
   $rec=array();
    $connessione = mysql_connect($ip,$user_db,$password_db)
        or die("Connessione non riuscita: " . mysql_error());
# open  db
   mysql_select_db($db_name);
# execute the SELECT query
   $risultato = mysql_query($query);

# put the result recordset into $rec
   $i = 0;
	if (! empty($risultato))
	{
		while($tmp = mysql_fetch_row($risultato))
   	{
   		$rec[]=$tmp;
   		$i = $i +1;
  		}
  		return $rec;
	}

	//else
	//{return array();}

# close connection
    mysql_close($connessione);
}



function query_select2($query,$connessione)
{
   $risultato = mysql_query($query);

# put the result recordset into $rec
   $i = 0;
if ($risultato){
	$rec=array();
	while($tmp = mysql_fetch_array($risultato, MYSQL_BOTH))//INDICIZZA L'ARRAY DI RITORNO
  	{
  	 $rec[]=$tmp;
  	 $i = $i +1;
  	}
  return $rec;
}

// Se non riesce ad eseguire la query prova a riconnettersi
else {
	include('./config/repository_mysql_db.php');
    	$connessione = mysql_connect($ip,$user_db,$password_db)
        or die("Connessione non riuscita: " . mysql_error());
	# open  db
   	mysql_select_db($db_name);
	# execute the SELECT query
   	$risultato = mysql_query($query);
	
	if ($risultato){
		$rec=array();
		while($tmp = mysql_fetch_array($risultato, MYSQL_BOTH))//INDICIZZA L'ARRAY DI RITORNO
  		{
  	 	$rec[]=$tmp;
  		$i = $i +1;
  		}
  	return $rec;
	}
	
	// Se non riesce nemmeno adesso ritorna un errore
	else {
		
	return "FALSE";
	}

}
# close connection
}//END OF query_select($query)



function connectDB(){

# IMPORT MYSQL PARAMETERS (NOTE: IT WORKS WITH ABSOLUTE PATH ONLY !!)
include('./config/repository_mysql_db.php');

# open connection to db
    $connessione = mysql_connect($ip,$user_db,$password_db)
        or die("Connessione non riuscita: " . mysql_error());

# open  db
   mysql_select_db($db_name);

return $connessione;
}


function disconnectDB($conn){

    mysql_close();

}


?>
