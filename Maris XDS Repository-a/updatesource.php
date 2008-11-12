<?php
include_once('./lib/functions_mysql.php');

$REP_source_id = $_POST['source_id'];
$REP_source_action = $_POST['source_action'];
$REP_source_name = $_POST['source_name'];
$REP_source_description = $_POST['source_description'];

if($REP_source_action=="delete"){
	$deleteREP_source = "DELETE FROM KNOWN_SOUCES_Ids WHERE ID='$REP_source_id'";
	$REP_delete_source = query_execute($deleteREP_source);
}

if($REP_source_action=="add"){
	$insertREP_source = "INSERT INTO KNOWN_SOUCES_Ids (XDSSubmissionset_sourceId,SOURCE_DESCRIPTION) VALUES ('$REP_source_name','$REP_source_description')";
	$REP_insert_source = query_execute($insertREP_source);
}

header('location: setup.php');

?>