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
require_once('./lib/functions_'.$database.'.php');



$REP_repository_action = $_POST['repository_action'];
$REP_repository_ip = $_POST['repository_ip'];
$REP_repository_uniqueid = $_POST['repository_uniqueid'];

if($REP_repository_action=="delete"){
	$deleteREP_repository = "DELETE FROM REPOSITORY WHERE REP_HOST='$REP_repository_ip'";
	$REP_delete_repository = query_exec($deleteREP_repository);
}

if($REP_repository_action=="add"){
	$insertREP_repository = "INSERT INTO REPOSITORY (REP_HOST,REP_UNIQUEID) VALUES ('$REP_repository_ip','$REP_repository_uniqueid')";
	$REP_insert_repository = query_exec($insertREP_repository);
}

header('location: setup.php');

?>