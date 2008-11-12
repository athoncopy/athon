<?php

include("REGISTRY_CONFIGURATION/REG_configuration.php");
#######################################

include($lib_path."utilities.php");

$get_ExtrinsicObject_ExternalIdentifier="SELECT * FROM ExternalIdentifier WHERE ExternalIdentifier.registryObject = 'urn:uuid:5a5ee0a4-2773-16d5-591f-05df2b622ac9'";

$ExtrinsicObject_ExternalIdentifier_arr=query_select($get_ExtrinsicObject_ExternalIdentifier);

print_r($ExtrinsicObject_ExternalIdentifier_arr);


for($e=0;$e<count($ExtrinsicObject_ExternalIdentifier_arr);$e++) {

if($ExtrinsicObject_ExternalIdentifier_arr[$e]['identificationScheme']=='urn:uuid:2e82c1f6-a085-4c72-9da3-8640a32e42ab'){
	echo $ExtrinsicObject_ExternalIdentifier_arr[$e]['value'];
}

if($ExtrinsicObject_ExternalIdentifier_arr[$e]['identificationScheme']=='urn:uuid:58a6f841-87b3-4a3e-92fd-a8ffeff98427'){
	echo $ExtrinsicObject_ExternalIdentifier_arr[$e]['value'];
}
}

$select_Slots = "SELECT * FROM Slot WHERE Slot.parent = 'urn:uuid:5a5ee0a4-2773-16d5-591f-05df2b622ac9'";
$Slot_arr=query_select($select_Slots);

print_r($Slot_arr);
for($s=0;$s<count($Slot_arr);$s++){

if($Slot_arr[$s]['name']=='sourcePatientInfo' && substr_count(trim(adjustString($Slot_arr[$s]['value'])),'PID-5')>0){
	echo $Slot_arr[$s]['value'];
}

}


?>