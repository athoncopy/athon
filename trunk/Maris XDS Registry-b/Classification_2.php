<?php
# ------------------------------------------------------------------------------------
# MARIS XDS REGISTRY
# Copyright (C) 2007 - 2010  MARiS Project
# Dpt. Medical and Diagnostic Sciences, University of Padova - csaccavini@rad.unipd.it
# This program is distributed under the terms and conditions of the GPL
# See the LICENSE files for details
# ------------------------------------------------------------------------------------

writeSQLQuery('-------------------------------------------------------------------------------------');
writeSQLQuery('Classification_2.php');
##### METODO PRINCIPALE
function fill_Classification_tables($dom,$RegistryPackage_id_array,$connessione)
{
	##### NODEREPRESENTATION
	$value_nodeRepresentation_assigned='';

	##### RADICE DEL DOCUMENTO ebXML
	$root_ebXML = $dom->document_element();

	##### ARRAY DEI NODI RegistryObjectList
	$dom_ebXML_RegistryObjectList_node_array=$root_ebXML->get_elements_by_tagname("RegistryObjectList");

	##### NODO RegistryObjectList
	$dom_ebXML_RegistryObjectList_node=$dom_ebXML_RegistryObjectList_node_array[0];

	##### TUTTI I NODI FIGLI DI RegistryObjectList
	$dom_ebXML_RegistryObjectList_child_nodes= $dom_ebXML_RegistryObjectList_node->child_nodes();

	for($i=0;$i<count($dom_ebXML_RegistryObjectList_child_nodes);$i++)
	{
		#### SINGOLO NODO 
		$dom_ebXML_RegistryObjectList_child_node=$dom_ebXML_RegistryObjectList_child_nodes[$i];

		##### tagname
		$dom_ebXML_RegistryObjectList_child_node_tagname=$dom_ebXML_RegistryObjectList_child_node->node_name();
		#### SOLO I NODI CLASSIFICATION
		if($dom_ebXML_RegistryObjectList_child_node_tagname=='Classification')
		{
			
			$classification_node = $dom_ebXML_RegistryObjectList_child_node;
			
			$value_id = "urn:uuid:".idrandom();
			$simbolic_Classification_id = $classification_node->get_attribute('id');

			#############################################################
			####NOTA BENE NEL CASO NON SIMBOLICO(VEDI SOLINFO)
			if(!isSimbolic($simbolic_Classification_id))
			{
				$value_id=$simbolic_Classification_id;
			}
			#############################################################
			$value_accessControlPolicy= $classification_node->get_attribute('accessControlPolicy');
			if($value_accessControlPolicy == '')
			{
				$value_accessControlPolicy = "NULL";
			}
			$value_objectType= $classification_node->get_attribute('objectType');
			if($value_objectType == '' || $value_objectType == 'urn:oasis:names:tc:ebxml-regrep:ObjectType:RegistryObject:Classification')
			{
				$value_objectType = "Classification";
			}
			$value_classificationNode= $classification_node->get_attribute('classificationNode');
			$value_classificationScheme= $classification_node->get_attribute('classificationScheme');

			$value_nodeRepresentation= $classification_node->get_attribute('nodeRepresentation');
			if($value_nodeRepresentation=='' && $value_classificationNode=='urn:uuid:d9d542f3-6cc4-48b6-8870-ea235fbc94c2')
			{
				##### VALORE DI DEFAULT CASO DI CLASSIFICATION
				##### NON FIGLIE DI EXTRINSICOBJECT E/O REGISTRYPACKAGE
				//$value_nodeRepresentation = "NULL";
				$value_nodeRepresentation=$RegistryPackage_id_array['nodeRepresentation'];
			}

			if($value_classificationNode == '')
			{
				$queryForName_value="SELECT Name_value FROM ClassificationScheme WHERE ClassificationScheme.id = '$value_classificationScheme'";
				

				$risName_value=query_select2($queryForName_value,$connessione);
				writeSQLQuery($risName_value.": ".$queryForName_value);

				$name_value=$risName_value[0][0];
				$name_value=substr($name_value,0,strpos($name_value,'.'));

				$queryForClassificationNode="SELECT id FROM ClassificationNode WHERE ClassificationNode.code = '$name_value'";
				$ris_code=query_select2($queryForClassificationNode,$connessione);
				writeSQLQuery($ris_code.": ".$queryForClassificationNode);

				$value_classificationNode=$ris_code[0][0];
			}
			if($value_classificationScheme == '')
			{
			$queryForClassificationNode="SELECT code FROM ClassificationNode WHERE ClassificationNode.id = '$value_classificationNode'";
			

			$ris_classificationNode = query_select2($queryForClassificationNode,$connessione);
			writeSQLQuery($ris_classificationNode.": ".$queryForClassificationNode);

			$code_classificationNode = $ris_classificationNode[0][0];
			#### FOLDER
			if($code_classificationNode=="XDSFolder")
			{
				$queryForClassificationScheme="SELECT id FROM ClassificationScheme WHERE ClassificationScheme.Name_value = 'XDSFolder.codeList'";
				

				$ris_ClassificationScheme=query_select2($queryForClassificationScheme,$connessione);
				writeSQLQuery($ris_ClassificationScheme.": ".$queryForClassificationScheme);

				$value_classificationScheme=$ris_ClassificationScheme[0][0];
				//$value_nodeRepresentation = "Radiology";
				
			}//END OF if($code_classificationNode=="XDSFolder")
			#### SUBMISSIONSET
			else if($code_classificationNode=="XDSSubmissionSet")
			{
				$queryForClassificationScheme="SELECT id FROM ClassificationScheme WHERE ClassificationScheme.Name_value = 'XDSSubmissionSet.contentTypeCode'";
				

				$ris_ClassificationScheme=query_select2($queryForClassificationScheme,$connessione);
				writeSQLQuery($ris_ClassificationScheme.": ".$queryForClassificationScheme);

				$value_classificationScheme=$ris_ClassificationScheme[0][0];

			}//END OF if($code_classificationNode=="XDSSubmissionSet")
			}//END OF if($value_classificationScheme == '')
			$simbolic_value_classifiedObject= $classification_node->get_attribute('classifiedObject');
			$value_classifiedObject=$RegistryPackage_id_array[$simbolic_value_classifiedObject];
// 			if($value_classifiedObject == '')
// 			{
// 				$value_classifiedObject = "NOT DECLARED";
// 			}

			$DB_array_classification_attributes['id'] = $value_id;
			$DB_array_classification_attributes['classificationScheme'] = $value_classificationScheme;
			$DB_array_classification_attributes['accessControlPolicy'] = $value_accessControlPolicy;
			$DB_array_classification_attributes['objectType'] = $value_objectType;
			$DB_array_classification_attributes['classifiedObject'] = $value_classifiedObject;
			$DB_array_classification_attributes['classificationNode'] = $value_classificationNode;
			$DB_array_classification_attributes['nodeRepresentation'] = $value_nodeRepresentation;

			if($value_classificationNode!='urn:uuid:a54d6aa5-d40d-43f9-88c5-b4633d873bdd'){
			##### SONO PRONTO A SCRIVERE NEL DB
			$INSERT_INTO_Classification = "INSERT INTO Classification (id,accessControlPolicy,objectType,classificationNode,classificationScheme,classifiedObject,nodeRepresentation) VALUES ('".trim($DB_array_classification_attributes['id'])."','".trim($DB_array_classification_attributes['accessControlPolicy'])."','".trim($DB_array_classification_attributes['objectType'])."','".trim($DB_array_classification_attributes['classificationNode'])."','".trim($DB_array_classification_attributes['classificationScheme'])."','".trim($DB_array_classification_attributes['classifiedObject'])."','".trim($DB_array_classification_attributes['nodeRepresentation'])."')";

			$ris = query_exec2($INSERT_INTO_Classification,$connessione);
			writeSQLQuery($ris.": ".$INSERT_INTO_Classification);
			
				
			}

			}//END OF if($dom_ebXML_RegistryObjectList_child_node_tagname=='Classification')

	}//END OF for($i=0;$i<count($dom_ebXML_RegistryObjectList_child_nodes);$i++)

}//END OF fill_Classification_tables($dom)

?>