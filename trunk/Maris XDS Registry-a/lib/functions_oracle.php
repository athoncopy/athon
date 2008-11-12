<?php
# ------------------------------------------------------------------------------------
# MARIS XDS REGISTRY
# Copyright (C) 2007 - 2010  MARiS Project
# Dpt. Medical and Diagnostic Sciences, University of Padova - csaccavini@rad.unipd.it
# This program is distributed under the terms and conditions of the GPL
# See the LICENSE files for details
# ------------------------------------------------------------------------------------

#### IF YOU WANT TO MAKE an INSERT or an UPDATE
function query_exec($query)
{
# IMPORT MYSQL PARAMETERS (NOTE: IT WORKS WITH ABSOLUTE PATH ONLY !!)
include('./config/registry_oracle_db.php');
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
include('./config/registry_oracle_db.php');
//putenv("ORACLE_HOME=/usr/lib/oracle/xe/app/oracle/product/10.2.0");
# open connection to db
$conn = OCILogOn($user_db,$password_db,$db)
or die( "Could not connect to Oracle database!") or die (ocierror());;
   //$rec=array();
# execute the EXEC query
$statement = ociparse($conn, $query);
$risultato = ociexecute($statement);

# open  db
//$rec=array();
# execute the EXEC query

while (OCIFetchInto ($statement, $row, OCI_NUM+OCI_ASSOC)) {
    $rec[]=$row;
//print_r($row);

}

//OCIFetchInto ($statement, $row, OCI_ASSOC);
return $rec;
# close connection
ora_logoff($conn);
}


function query($query)
{
/*
this function execute a query on a database MySQL using a SQL statement passed in the variable $query
and returns an array $rec with the result recordset.
Use this function ONLY with SELECT statements, if you want to execute  INSERT or UPDATE
use the function query_execute().
*/
include('./config/registry_oracle_db.php');
//putenv("ORACLE_HOME=/usr/lib/oracle/xe/app/oracle/product/10.2.0");
# open connection to db
$conn = OCILogOn($user_db,$password_db,$db)
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
ora_logoff($conn);
}
//-----------------------------------------------------------------------------------//
?>
