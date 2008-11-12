<?php
 echo "pippo";
include('../lib/functions_mysql_oracle.php');

$insert_into_PROVA = "INSERT INTO PROVA (NUMERO,CARATTERE) VALUES ('5','pippo')";
 query_execute($insert_into_PROVA);
 
 echo "pippo";
 ciao();
?>