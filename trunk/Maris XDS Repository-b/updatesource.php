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

include_once('./config/config.php');
include_once('./lib/functions_'.$database.'.php');

$REP_source_id = $_POST['source_id'];
$REP_source_action = $_POST['source_action'];
$REP_source_name = $_POST['source_name'];
$REP_source_description = $_POST['source_description'];

if($REP_source_action=="delete"){
	$deleteREP_source = "DELETE FROM KNOWN_SOUCES_IDS WHERE ID='$REP_source_id'";
	$REP_delete_source = query_execute($deleteREP_source);
}

if($REP_source_action=="add"){
	$insertREP_source = "INSERT INTO KNOWN_SOUCES_IDS (XDSSUBMISSIONSET_SOURCEID,SOURCE_DESCRIPTION) VALUES ('$REP_source_name','$REP_source_description')";
	$REP_insert_source = query_execute($insertREP_source);
}

header('location: setup.php');

?>