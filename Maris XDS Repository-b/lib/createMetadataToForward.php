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

//function modifyMetadata($dom_ebXML,$ExtrinsicObject_node,$file_name,$document_URI,$allegato_STRING,$idfile,$document_token)
function modifyMetadata($dom_ebXML,$ExtrinsicObject_node,$URI,$hash,$size,$rep_uniqueID,$namespacerim)
{
	#### ATTENZIONE NO include_once !!!
	include("config/REP_configuration.php");
	
	#### RICAVO ATTRIBUTO id DI ExtrinsicObject
	$ExtrinsicObject_id_attr = $ExtrinsicObject_node->get_attribute('id');

	#### FIGLI DI ExtrinsicObject
	$ExtrinsicObject_child_nodes = $ExtrinsicObject_node->child_nodes();
	$count =0;
	for($i=0;$i<(count($ExtrinsicObject_child_nodes));$i++)
	{
		### SINGOLO NODO FIGLIO
		$ExtrinsicObject_child_node=$ExtrinsicObject_child_nodes[$i];

		### TAGNAME DEL SINGOLO NODO FIGLIO
		$ExtrinsicObject_child_node_tagname=$ExtrinsicObject_child_node->node_name();

		if(($ExtrinsicObject_child_node_tagname=='Classification' || $ExtrinsicObject_child_node_tagname=='Name' || $ExtrinsicObject_child_node_tagname=='Description') && $count==0)
		{
			$next_node = $ExtrinsicObject_child_node;
			$count++;

		}//END OF if($ExtrinsicObject_child_node_tagname=='Classification')
	
		if($ExtrinsicObject_child_node_tagname=='ExternalIdentifier')
		{
			$externalidentifier_node = $ExtrinsicObject_child_node;
			$value_value= avoidHtmlEntitiesInterpretation($externalidentifier_node->get_attribute('value'));
			
			#### NODI FIGLI DI EXTERNALIDENTIFIER
			$externalidentifier_child_nodes = $externalidentifier_node->child_nodes();

			for($q = 0;$q < count($externalidentifier_child_nodes);$q++)
			{
				$externalidentifier_child_node = $externalidentifier_child_nodes[$q];
				$externalidentifier_child_node_tagname = $externalidentifier_child_node->node_name();
				if($externalidentifier_child_node_tagname=='Name')
				{
					$name_node=$externalidentifier_child_node;

					$LocalizedString_nodes = $name_node->child_nodes();

			for($p = 0;$p < count($LocalizedString_nodes);$p++)
			{
				$LocalizedString_node = $LocalizedString_nodes[$p];//->node_name();
				$LocalizedString_node_tagname = $LocalizedString_node->node_name();

				if($LocalizedString_node_tagname == 'LocalizedString')
				{
					$LocalizedString_value =$LocalizedString_node->get_attribute('value');
					if(strpos(strtolower(trim($LocalizedString_value)),strtolower('DocumentEntry.uniqueId')))
					{
						$ebxml_value = $value_value;
					}
					
				}

			}

				}
			}
		}//END OF if($ExtrinsicObject_child_node_tagname=='ExternalIdentifier')
		
	}//END OF for($i=0;$i<(count($ExtrinsicObject_child_nodes));$i++)

########## CALCOLO URL DEL DOCUMENTO

	#### CREO IL NUOVO ELEMENTO SLOT
	$new_element_Slot = $dom_ebXML->create_element_ns($namespacerim,'Slot','rim');
	$new_element_Slot->set_attribute("name", "URI");

	#### CREO IL NODO SLOT
	$new_node_Slot = $ExtrinsicObject_node->insert_before($new_element_Slot,$next_node);

	$ValueList_node = $dom_ebXML->create_element_ns($namespacerim,"ValueList",'rim');
	$new_node_Slot->append_child($ValueList_node);

	$Value_node = $dom_ebXML->create_element_ns($namespacerim,"Value",'rim');
	$ValueList_node->append_child($Value_node);
		
	#######################################################################
	
	#### MODIFICO IL DOM	
	//$new_URI_node = $dom_ebXML->create_text_node($Document_URI);#<<=== URI
	$new_URI_node = $dom_ebXML->create_text_node($URI);#<<=== URI
	$Value_node->append_child($new_URI_node);

########################## FINE BLOCCO PER L'URI ########################

################# CALCOLO L'HASH DEL DOCUMENTO

	//$hash = md5(file_get_contents($document_URI));//MD5 hash of file using the RSA Data Security, Inc. MD5 Message-Digest Algorithm

	#### CREO IL NUOVO ELEMENTO SLOT
	$new_element_Slot = $dom_ebXML->create_element_ns($namespacerim,'Slot','rim');
	$new_element_Slot->set_attribute("name","hash");

	#### CREO IL NODO SLOT
	$new_node_Slot = $ExtrinsicObject_node->insert_before($new_element_Slot,$next_node);

	$ValueList_node = $dom_ebXML->create_element_ns($namespacerim,"ValueList",'rim');
	$new_node_Slot->append_child($ValueList_node);

	$Value_node = $dom_ebXML->create_element_ns($namespacerim,"Value",'rim');
	$ValueList_node->append_child($Value_node);

	#### MODIFICO IL DOM	
	$new_HASH_node = $dom_ebXML->create_text_node($hash);#<<=== HASH
	$Value_node->append_child($new_HASH_node);

//========================= FINE BLOCCO PER L'HASH ============================//


############### CALCOLO DEL SIZE

	//$size = filesize($document_URI);

	#### CREO IL NUOVO ELEMENTO SLOT
	$new_element_Slot = $dom_ebXML->create_element_ns($namespacerim,'Slot','rim');
	$new_element_Slot->set_attribute("name","size");

	#### CREO IL NODO SLOT
	$new_node_Slot = $ExtrinsicObject_node->insert_before($new_element_Slot,$next_node);

	$ValueList_node = $dom_ebXML->create_element_ns($namespacerim,"ValueList",'rim');
	$new_node_Slot->append_child($ValueList_node);

	$Value_node = $dom_ebXML->create_element_ns($namespacerim,"Value",'rim');
	$ValueList_node->append_child($Value_node);

	#### MODIFICO IL DOM	
	$new_SIZE_node = $dom_ebXML->create_text_node($size);#<<=== SIZE
	$Value_node->append_child($new_SIZE_node);

//====================== FINE BLOCCO PER IL SIZE =========================//


############### REPOSITORY UNIQUE ID

	#### CREO IL NUOVO ELEMENTO SLOT
	$new_element_Slot = $dom_ebXML->create_element_ns($namespacerim,'Slot','rim');
	$new_element_Slot->set_attribute("name","repositoryUniqueId");

	#### CREO IL NODO SLOT
	$new_node_Slot = $ExtrinsicObject_node->insert_before($new_element_Slot,$next_node);

	$ValueList_node = $dom_ebXML->create_element_ns($namespacerim,"ValueList",'rim');
	$new_node_Slot->append_child($ValueList_node);

	$Value_node = $dom_ebXML->create_element_ns($namespacerim,"Value",'rim');
	$ValueList_node->append_child($Value_node);

	#### MODIFICO IL DOM	
	$new_rep_uniqueID_node = $dom_ebXML->create_text_node($rep_uniqueID);#<<=== rep_uniqueID
	$Value_node->append_child($new_rep_uniqueID_node);

//====================== FINE BLOCCO PER IL SIZE =========================//

############### TABELLA DOCUMENTS ##############
	#### RECUPERO DATA E ORA ATTUALI
	/*$today = date("Y-m-d");
	$cur_hour = date("H:i:s");
	$datetime = $today." ".$cur_hour;*/
	
	/*$insert_into_DOCUMENTS = "INSERT INTO DOCUMENTS (XDSDocumentEntry_uniqueId,ExtrinsicObject_id,file_system_id,size,hash,URI,TEXT,DATE) VALUES ('$ebxml_value','$ExtrinsicObject_id_attr','$file_name','$size','$hash','$Document_URI','".adjustString($allegato_STRING)."','$datetime')";*/

	
##############################################################################
	return $dom_ebXML;

}//END OF modifyMetadata($dom_ebXML,$ExtrinsicObject_node,$file_name,$document_URI,$allegato_STRING)

###### MANTIENE IL METADATA INALTERATO (CASO DI HASH-SIZE-URI GIA PRESENTI)
###### INSERISCE NEL DB
//function mantainMetadata($ExtrinsicObject_node,$file_name,$document_URI,$allegato_STRING)
function mantainMetadata($ExtrinsicObject_node,$file_name,$document_URI)
{
	include("config/REP_configuration.php");

	#### RICAVO ATTRIBUTO id DI ExtrinsicObject
	$ExtrinsicObject_id_attr = $ExtrinsicObject_node->get_attribute('id');

	#### FIGLI DI ExtrinsicObject
	$ExtrinsicObject_child_nodes = $ExtrinsicObject_node->child_nodes();
	$count =0;
	for($i=0;$i<(count($ExtrinsicObject_child_nodes));$i++)
	{
		### SINGOLO NODO FIGLIO
		$ExtrinsicObject_child_node=$ExtrinsicObject_child_nodes[$i];

		### TAGNAME DEL SINGOLO NODO FIGLIO
		$ExtrinsicObject_child_node_tagname=$ExtrinsicObject_child_node->node_name();

		if($ExtrinsicObject_child_node_tagname=='Slot')
		{
			$slot_node = $ExtrinsicObject_child_node;
			$value_name= $slot_node->get_attribute('name');

			if(strtoupper($value_name)== "HASH")
			{
				#### NODI FIGLI DI SLOT
				$slot_child_nodes = $slot_node->child_nodes();

				for($q = 0;$q < count($slot_child_nodes);$q++)
				{
					$slot_child_node = $slot_child_nodes[$q];
					$slot_child_node_tagname = $slot_child_node->node_name();
					if($slot_child_node_tagname=='ValueList')
					{
						$valuelist_node = $slot_child_node;
						$valuelist_child_nodes = $valuelist_node->child_nodes();
						## UN SOLO VALUE
						if(count($valuelist_child_nodes)==3)
						{
					 		$value_node = $valuelist_child_nodes[1];
					  		$hash = $value_node->get_content();
						}
					}

				}

			}//END OF if(strtoupper($value_name)== "HASH")

			if(strtoupper($value_name)== "SIZE")
			{
				#### NODI FIGLI DI SLOT
				$slot_child_nodes = $slot_node->child_nodes();

				for($q = 0;$q < count($slot_child_nodes);$q++)
				{
					$slot_child_node = $slot_child_nodes[$q];
					$slot_child_node_tagname = $slot_child_node->node_name();
					if($slot_child_node_tagname=='ValueList')
					{
						$valuelist_node = $slot_child_node;
						$valuelist_child_nodes = $valuelist_node->child_nodes();
						## UN SOLO VALUE
						if(count($valuelist_child_nodes)==3)
						{
					 		$value_node = $valuelist_child_nodes[1];
					  		$size = $value_node->get_content();
						}
					}

				}

			}//END OF if(strtoupper($value_name)== "SIZE")

			if(strtoupper($value_name)== "URI")
			{
				#### NODI FIGLI DI SLOT
				$slot_child_nodes = $slot_node->child_nodes();

				for($q = 0;$q < count($slot_child_nodes);$q++)
				{
					$slot_child_node = $slot_child_nodes[$q];
					$slot_child_node_tagname = $slot_child_node->node_name();
					if($slot_child_node_tagname=='ValueList')
					{
						$valuelist_node = $slot_child_node;
						$valuelist_child_nodes = $valuelist_node->child_nodes();
						## UN SOLO VALUE
						if(count($valuelist_child_nodes)==3)
						{
					 		$value_node = $valuelist_child_nodes[1];
					  		$Document_URI = $value_node->get_content();
						}
					}

				}

			}//END OF if(strtoupper($value_name)== "URI")

		}//END OF if($ExtrinsicObject_child_node_tagname=='Slot')

		if($ExtrinsicObject_child_node_tagname=='Classification' && $count==0)
		{
			$next_node = $ExtrinsicObject_child_node;
			$count++;

		}//END OF if($ExtrinsicObject_child_node_tagname=='Classification')
	
		if($ExtrinsicObject_child_node_tagname=='ExternalIdentifier')
		{
			$externalidentifier_node = $ExtrinsicObject_child_node;
			$value_value= avoidHtmlEntitiesInterpretation($externalidentifier_node->get_attribute('value'));
			
			#### NODI FIGLI DI EXTERNALIDENTIFIER
			$externalidentifier_child_nodes = $externalidentifier_node->child_nodes();

			for($q = 0;$q < count($externalidentifier_child_nodes);$q++)
			{
				$externalidentifier_child_node = $externalidentifier_child_nodes[$q];
				$externalidentifier_child_node_tagname = $externalidentifier_child_node->node_name();
				if($externalidentifier_child_node_tagname=='Name')
				{
					$name_node=$externalidentifier_child_node;

					$LocalizedString_nodes = $name_node->child_nodes();

			for($p = 0;$p < count($LocalizedString_nodes);$p++)
			{
				$LocalizedString_node = $LocalizedString_nodes[$p];//->node_name();
				$LocalizedString_node_tagname = $LocalizedString_node->node_name();

				if($LocalizedString_node_tagname == 'LocalizedString')
				{
					$LocalizedString_value =$LocalizedString_node->get_attribute('value');
					if(strpos(strtolower(trim($LocalizedString_value)),strtolower('DocumentEntry.uniqueId')))
					{
						$ebxml_value = $value_value;
					}
					
				}

			}

				}
			}
		}//END OF if($ExtrinsicObject_child_node_tagname=='ExternalIdentifier')
		
	}//END OF for($i=0;$i<(count($ExtrinsicObject_child_nodes));$i++)

	############### TABELLA DOCUMENTS ##############
	#### RECUPERO DATA E ORA ATTUALI
	/*$today = date("Y-m-d");
	$cur_hour = date("H:i:s");
	$datetime = $today." ".$cur_hour;

	$insert_into_DOCUMENTS = "INSERT INTO DOCUMENTS (XDSDocumentEntry_uniqueId,ExtrinsicObject_id,file_system_id,size,hash,URI,TEXT,DATE) VALUES ('$ebxml_value','$ExtrinsicObject_id_attr','$file_name','$size','$hash','$Document_URI','".adjustString($allegato_STRING)."','$datetime')";*/

}//END OF mantainMetadata($ExtrinsicObject_node,$file_name,$document_URI,$allegato_STRING)




function deleteMetadata($dom_ebXML,$ExtrinsicObject_node){
	include("config/REP_configuration.php");

	#### RICAVO ATTRIBUTO id DI ExtrinsicObject
	$ExtrinsicObject_id_attr = $ExtrinsicObject_node->get_attribute('id');

	#### FIGLI DI ExtrinsicObject
	$ExtrinsicObject_child_nodes = $ExtrinsicObject_node->child_nodes();
	$count =0;
	for($i=0;$i<(count($ExtrinsicObject_child_nodes));$i++)
	{
		### SINGOLO NODO FIGLIO
		$ExtrinsicObject_child_node=$ExtrinsicObject_child_nodes[$i];

		### TAGNAME DEL SINGOLO NODO FIGLIO
		$ExtrinsicObject_child_node_tagname=$ExtrinsicObject_child_node->node_name();

		if($ExtrinsicObject_child_node_tagname=='Slot')
		{
			$slot_node = $ExtrinsicObject_child_node;
			$value_name= $slot_node->get_attribute('name');

			if(strtoupper($value_name)== "HASH")
			{
				$ExtrinsicObject_node->remove_child($ExtrinsicObject_child_nodes[$i]);				
			}//END OF if(strtoupper($value_name)== "HASH")

			if(strtoupper($value_name)== "SIZE")
			{
				#### NODI FIGLI DI SLOT
				$ExtrinsicObject_node->remove_child($ExtrinsicObject_child_nodes[$i]);
			}//END OF if(strtoupper($value_name)== "SIZE")

			if(strtoupper($value_name)== "URI")
			{
				#### NODI FIGLI DI SLOT
				$ExtrinsicObject_node->remove_child($ExtrinsicObject_child_nodes[$i]);

			}//END OF if(strtoupper($value_name)== "URI")

			if(strtoupper($value_name)== "REPOSITORYUNIQUEID")
			{
				#### NODI FIGLI DI SLOT
				$ExtrinsicObject_node->remove_child($ExtrinsicObject_child_nodes[$i]);

			}//END OF if(strtoupper($value_name)== "URI")

		}//END OF if($ExtrinsicObject_child_node_tagname=='Slot')
	}
	return $dom_ebXML;

}//END OF deleteMetadata($dom_ebXML,$ExtrinsicObject_node)





?>