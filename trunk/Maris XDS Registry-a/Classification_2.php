<?php

##### METODO PRINCIPALE
function fill_Classification_tables($dom,$RegistryPackage_id_array)
{
	##### NODEREPRESENTATION
	$value_nodeRepresentation_assigned='';

	##### RADICE DEL DOCUMENTO ebXML
	$root_ebXML = $dom->document_element();

	##### ARRAY DEI NODI LeafRegistryObjectList
	$dom_ebXML_LeafRegistryObjectList_node_array=$root_ebXML->get_elements_by_tagname("LeafRegistryObjectList");

	##### NODO LeafRegistryObjectList
	$dom_ebXML_LeafRegistryObjectList_node=$dom_ebXML_LeafRegistryObjectList_node_array[0];

	##### TUTTI I NODI FIGLI DI LeafRegistryObjectList
	$dom_ebXML_LeafRegistryObjectList_child_nodes= $dom_ebXML_LeafRegistryObjectList_node->child_nodes();

	for($i=0;$i<count($dom_ebXML_LeafRegistryObjectList_child_nodes);$i++)
	{
		#### SINGOLO NODO 
		$dom_ebXML_LeafRegistryObjectList_child_node=$dom_ebXML_LeafRegistryObjectList_child_nodes[$i];

		##### tagname
		$dom_ebXML_LeafRegistryObjectList_child_node_tagname=$dom_ebXML_LeafRegistryObjectList_child_node->node_name();

		#### SOLO I NODI CLASSIFICATION
		if($dom_ebXML_LeafRegistryObjectList_child_node_tagname=='Classification')
		{
			$classification_node = $dom_ebXML_LeafRegistryObjectList_child_node;
			$DB_array_classification_attributes = array();

			$value_id= $classification_node->get_attribute('id');
			if($value_id == '')
			{
				$value_id = "urn:uuid:".idrandom();
			}
			$value_accessControlPolicy= $classification_node->get_attribute('accessControlPolicy');
			if($value_accessControlPolicy == '')
			{
				$value_accessControlPolicy = "NULL";
			}
			$value_objectType= $classification_node->get_attribute('objectType');
			if($value_objectType == '')
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
				$queryForName_value="SELECT  Name_value FROM ClassificationScheme WHERE ClassificationScheme.id = '$value_classificationScheme'";

				$risName_value=query_select($queryForName_value);
				$name_value=$risName_value[0]['Name_value'];
				$name_value=substr($name_value,0,strpos($name_value,'.'));

				$queryForClassificationNode="SELECT id FROM ClassificationNode WHERE ClassificationNode.code = '$name_value'";
				$ris_code=query_select($queryForClassificationNode);

				$value_classificationNode=$ris_code[0]['id'];
			}
			if($value_classificationScheme == '')
			{
			$queryForClassificationNode="SELECT code FROM ClassificationNode WHERE ClassificationNode.id = '$value_classificationNode'";

			$ris_classificationNode = query_select($queryForClassificationNode);
			$code_classificationNode = $ris_classificationNode[0]['code'];
			#### FOLDER
			if($code_classificationNode=="XDSFolder")
			{
				$queryForClassificationScheme="SELECT id FROM ClassificationScheme WHERE ClassificationScheme. Name_value = 'XDSFolder.codeList'";

				$ris_ClassificationScheme=query_select($queryForClassificationScheme);

				$value_classificationScheme=$ris_ClassificationScheme[0]['id'];
				//$value_nodeRepresentation = "Radiology";
				
			}//END OF if($code_classificationNode=="XDSFolder")
			#### SUBMISSIONSET
			else if($code_classificationNode=="XDSSubmissionSet")
			{
				$queryForClassificationScheme="SELECT id FROM ClassificationScheme WHERE ClassificationScheme. Name_value = 'XDSSubmissionSet.contentTypeCode'";

				$ris_ClassificationScheme=query_select($queryForClassificationScheme);

				$value_classificationScheme=$ris_ClassificationScheme[0]['id'];

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

			##### SONO PRONTO A SCRIVERE NEL DB
			$INSERT_INTO_Classification = "INSERT INTO Classification (id,accessControlPolicy,objectType,classificationNode,classificationScheme,classifiedObject,nodeRepresentation) VALUES ('".trim($DB_array_classification_attributes['id'])."','".trim($DB_array_classification_attributes['accessControlPolicy'])."','".trim($DB_array_classification_attributes['objectType'])."','".trim($DB_array_classification_attributes['classificationNode'])."','".trim($DB_array_classification_attributes['classificationScheme'])."','".trim($DB_array_classification_attributes['classifiedObject'])."','".trim($DB_array_classification_attributes['nodeRepresentation'])."')";

 			$fp = fopen("tmpQuery/INSERT_INTO_Classification","w+");
     			fwrite($fp,$INSERT_INTO_Classification);
 			fclose($fp);
			
			//include_once("lib/functions_QUERY_mysql.php");
				$ris = query_exec($INSERT_INTO_Classification);

			}//END OF if($dom_ebXML_LeafRegistryObjectList_child_node_tagname=='Classification')

	}//END OF for($i=0;$i<count($dom_ebXML_LeafRegistryObjectList_child_nodes);$i++)

}//END OF fill_Classification_tables($dom)

?>