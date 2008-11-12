<?php
//----------------------------------------------------------------------------------//

#### IF YOU WANT TO MAKE an INSERT or an UPDATE
function query_execute($query)
{
# IMPORT MYSQL PARAMETERS (NOTE: IT WORKS WITH ABSOLUTE PATH ONLY !!)
//include('/var/www/MARIS_xds/xdsServices2/repository/config/repository_mysql_db.php');
include('/var/www/MARIS_xds3/xdsServices2/repository/config/repository_oracle_db.php');
# open connection to db

$conn = OCILogOn($user_db,$password_db,$db);
    if (!$conn) {
    $e = oci_error();
    print htmlentities($e['message']);
    exit;   }
# execute the EXEC query
$analizza = ora_parse($my_cursor, $query, 0);
$risultato = ora_exec($my_cursor);


# close connection
ora_logoff($connessione);

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

include('/var/www/MARIS_xds3/xdsServices2/repository/config/repository_oracle_db.php');
# open connection to db
$connessione = ora_logon($user_sid,$password_db) or die ('error'.ora_error());

# open  db
$my_cursor = ora_open($connessione) or die ('Could not connect.'.ora_error());
$rec=array();
# execute the EXEC query
$analizza = ora_parse($my_cursor, $query, 0);
$risultato = ora_exec($my_cursor);
    if($risultato){
# put the result recordset into $rec

   ora_fetch_into($my_cursor, $rec);
    }
  		return $rec;
# close connection
ora_logoff($connessione);
}
//-----------------------------------------------------------------------------------//
?>
