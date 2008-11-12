<?php
# ------------------------------------------------------------------------------------
# MARIS XDS REGISTRY
# Copyright (C) 2007 - 2010  MARiS Project
# Dpt. Medical and Diagnostic Sciences, University of Padova - csaccavini@rad.unipd.it
# This program is distributed under the terms and conditions of the GPL
# See the LICENSE files for details
# ------------------------------------------------------------------------------------

function query_select($query)
{
/*
this function execute a query on a database MySQL using a SQL statement passed in the variable $query
and returns an array $rec with the result recordset.
Use this function ONLY with SELECT statements, if you want to execute  INSERT or UPDATE
use the function query_execute().
*/
include('./config/registry_mysql_db.php');
# open connection to db
	// echo $ip." ".$user_db." ".$password_db;
    $rec=array();
    $connessione = mysql_connect($ip_q,$user_db_q,$password_db_q)
        or die("Connessione non riuscita: " . mysql_error());
# open  db
   mysql_select_db($db_name_q);
# execute the SELECT query
   $risultato = mysql_query($query);

# put the result recordset into $rec
   $i = 0;
if (! empty($risultato)){
while($tmp = mysql_fetch_array($risultato, MYSQL_BOTH))//INDICIZZA L'ARRAY DI RITORNO
  {
   $rec[]=$tmp;
   $i = $i +1;
  }
  return $rec;
};
# close connection
    mysql_close($connessione);

}//END OF query_select($query)

//IF YOU WANT TO MAKE a SELECT command
//RETURN: A BIDIMENSIONAL ARRAY $rec[..][..]
function query($query) //ERA LA query_select($query)
{
/*
this function execute a query on a database MySQL using a SQL statement passed in the variable $query
and returns an array $rec with the result recordset.
Use this function ONLY with SELECT statements, if you want to execute  INSERT or UPDATE
use the function query_execute().
*/
//echo getcwd();
include('./config/registry_mysql_db.php');
# open connection to db
    $connessione = mysql_connect($ip_q,$user_db_q,$password_db_q)
        or die("Connessione non riuscita: " . mysql_error());
# open  db
   mysql_select_db($db_name_q);
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

# close connection
    mysql_close($connessione);

}//END OF query($query)

//IF YOU WANT TO MAKE an INSERT or an UPDATE
function query_exec($query) //ERA LA query_execute($query)
{
# IMPORT MYSQL PARAMETERS (NOTE: IT WORKS WITH ABSOLUTE PATH ONLY !!)
include('./config/registry_mysql_db.php');

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

?>
