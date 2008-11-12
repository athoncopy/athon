<?php
//----------------------------------------------------------------------------------//

#### IF YOU WANT TO MAKE an INSERT or an UPDATE
function query_execute($query)
{
# IMPORT MYSQL PARAMETERS (NOTE: IT WORKS WITH ABSOLUTE PATH ONLY !!)
	include('./config/repository_mysql_db.php');
	//include('C:\\xampp\\htdocs\\MARIS_xds\\xdsServices2\\repository\\config\\repository_mysql_db.php');
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
//include('C:\\xampp\\htdocs\\MARIS_xds\\xdsServices2\\repository\\config\\repository_mysql_db.php');
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
//-----------------------------------------------------------------------------------//
?>
