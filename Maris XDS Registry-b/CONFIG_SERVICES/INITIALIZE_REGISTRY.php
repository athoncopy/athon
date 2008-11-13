<?php
# ------------------------------------------------------------------------------------
# MARIS XDS REGISTRY
# Copyright (C) 2007 - 2010  MARiS Project
# Dpt. Medical and Diagnostic Sciences, University of Padova - csaccavini@rad.unipd.it
# This program is distributed under the terms and conditions of the GPL
# See the LICENSE files for details
# ------------------------------------------------------------------------------------

include_once('../config/registry_QUERY_mysql_db.php');

##### CREO L'OGGETTO DOM
//$dom_initialize = domxml_open_file('../config/Initialize_05-Dec-2005_1315/initialize.xml');
$dom_initialize = domxml_open_file('../config/initialize.xml');
//$dom_initialize = domxml_open_file('config/Initialize_05-Dec-2005_1315/upgrade-from-year-1-to-year-2.xml');

##### RADICE DEL DOCUMENTO
$root_dom_initialize = $dom_initialize->document_element();

##### ARRAY DEI NODI LeafRegistryObjectList
$dom_LeafRegistryObjectList_node_array=$root_dom_initialize->get_elements_by_tagname("LeafRegistryObjectList");

#### CICLO SU OGNI LeafRegistryObjectList ####
for($i=0;$i<(count($dom_LeafRegistryObjectList_node_array));$i++)
{
	#### SINGOLO NODO LeafRegistryObjectList
	$dom_LeafRegistryObjectList_node=$dom_LeafRegistryObjectList_node_array[$i];

	#### NODI ClassificationScheme
	$dom_ClassificationScheme_node_array=$dom_LeafRegistryObjectList_node->get_elements_by_tagname("ClassificationScheme");

	for($s=0;$s<(count($dom_ClassificationScheme_node_array));$s++)
	{
		### SINGOLO NODO ClassificationScheme
		$dom_ClassificationScheme_node=$dom_ClassificationScheme_node_array[$s];

		### ATTRIBUTO id
		$id_attr = $dom_ClassificationScheme_node->get_attribute('id');

		### ATTRIBUTO isInternal
		$isInternal_at = $dom_ClassificationScheme_node->get_attribute('isInternal');
		$isInternal_attr = ($isInternal_at=='true') ? '1':'0';

		### ATTRIBUTO nodeType
		$nodeType_attr = $dom_ClassificationScheme_node->get_attribute('nodeType');

		#### NODI FIGLI
		$dom_ClassificationScheme_node_child_nodes=$dom_ClassificationScheme_node->child_nodes();

		for($k=0;$k<(count($dom_ClassificationScheme_node_child_nodes));$k++)
		{
			$dom_ClassificationScheme_node_child_node=$dom_ClassificationScheme_node_child_nodes[$k];

			$dom_ClassificationScheme_node_child_node_tagname=$dom_ClassificationScheme_node_child_node->node_name();

			#### Name
			if($dom_ClassificationScheme_node_child_node_tagname=='Name')
			{
				$name_node = $dom_ClassificationScheme_node_child_node;
				$LocalizedString_nodes = $name_node->child_nodes();
				for($p=0;$p<count($LocalizedString_nodes);$p++)
				{
					$LocalizedString_node = $LocalizedString_nodes[$p];//->node_name();
					$LocalizedString_node_tagname = $LocalizedString_node->node_name();	

					if($LocalizedString_node_tagname=='LocalizedString')
					{
						$name_LocalizedString_value =$LocalizedString_node->get_attribute('value');
					}
					
				}//END OF for($p=0;$p<count($LocalizedString_nodes);$p++)
				
			}//END OF if($dom_ClassificationScheme_node_child_node_tagname=='Name')

			#### Description
			if($dom_ClassificationScheme_node_child_node_tagname=='Description')
			{
				$description_node = $dom_ClassificationScheme_node_child_node;
				$LocalizedString_nodes = $description_node->child_nodes();
				for($p=0;$p<count($LocalizedString_nodes);$p++)
				{
					$LocalizedString_node = $LocalizedString_nodes[$p];//->node_name();
					$LocalizedString_node_tagname = $LocalizedString_node->node_name();	

					if($LocalizedString_node_tagname=='LocalizedString')
					{
						$description_LocalizedString_value =$LocalizedString_node->get_attribute('value');
					}
					
				}//END OF for($p=0;$p<count($LocalizedString_nodes);$p++)
				
			}//END OF if($dom_ClassificationScheme_node_child_node_tagname=='Description')
			
		}//END OF for($k=0;$k<(count($dom_ClassificationScheme_node_child_nodes));$k++)

		$insert_into_ClassificationScheme="INSERT INTO ClassificationScheme (id,isInternal,nodeType,Name_value,Description_value) VALUES ('$id_attr','$isInternal_attr','$nodeType_attr','$name_LocalizedString_value','$description_LocalizedString_value')";
    	
		# open connection to db
    		$connessione = mysql_connect($ip_q,$user_db_q,$password_db_q)
        	or die("Connessione non riuscita: " . mysql_error());

		# open  db
   		mysql_select_db($db_name_q);

		# execute the SELECT query
  		$risultato = mysql_query($insert_into_ClassificationScheme);
		# close connection
    		mysql_close($connessione);

		if(!$risultato)
		{
			echo "<b>INSERIMENTO NON RIUSCITO ClassificationScheme :</b> <br>";
			echo $insert_into_ClassificationScheme."<br><br>";
			exit;	

		}//END OF if(!$risultato)
	
	}//END OF for($s=0;$s<(count($dom_ClassificationScheme_node_array));$s++)
	

	#### NODI ClassificationNode
	$dom_ClassificationNode_node_array=$dom_LeafRegistryObjectList_node->get_elements_by_tagname("ClassificationNode");

	//print_r($dom_ClassificationNode_node_array);

	for($n=0;$n<(count($dom_ClassificationNode_node_array));$n++)
	{
		### SINGOLO NODO ClassificationNode
		$dom_ClassificationNode_node=$dom_ClassificationNode_node_array[$n];

		### ATTRIBUTO id
		$id_attr = $dom_ClassificationNode_node->get_attribute('id');

		### ATTRIBUTO parent
		$parent_attr = $dom_ClassificationNode_node->get_attribute('parent');
		//echo $parent_attr;
		### ATTRIBUTO code
		$code_attr = $dom_ClassificationNode_node->get_attribute('code');

 		if($dom_ClassificationNode_node->has_attribute('parent'))
 		{
		    #### NODI FIGLI
		    $dom_ClassificationNode_node_child_nodes=$dom_ClassificationNode_node->child_nodes();

		    for($k=0;$k<(count($dom_ClassificationNode_node_child_nodes));$k++)
		    {
			### SINGOLO NODO FIGLIO
			$dom_ClassificationNode_node_child_node=$dom_ClassificationNode_node_child_nodes[$k];

			### TAGNAME DEL NODO FIGLIO
			$dom_ClassificationNode_node_child_node_tagname=$dom_ClassificationNode_node_child_node->node_name();

			#### Name
			if($dom_ClassificationNode_node_child_node_tagname=='Name')
			{
				$name_node = $dom_ClassificationNode_node_child_node;
				$LocalizedString_nodes = $name_node->child_nodes();
				for($p=0;$p<count($LocalizedString_nodes);$p++)
				{
					$LocalizedString_node = $LocalizedString_nodes[$p];//->node_name();
					$LocalizedString_node_tagname = $LocalizedString_node->node_name();	

					if($LocalizedString_node_tagname=='LocalizedString')
					{
						$name_LocalizedString_value =$LocalizedString_node->get_attribute('value');
					}
					
				}//END OF for($p=0;$p<count($LocalizedString_nodes);$p++)

				### COMPONGO IL PATH
				$path = "/$parent_attr/$code_attr";

				$insert_into_ClassificationNode_1 = "INSERT INTO ClassificationNode (id,parent,path,code,Name_value) VALUES ('$id_attr','$parent_attr','$path','$code_attr','$name_LocalizedString_value')";
    	
				# open connection to db
    				$connessione = mysql_connect($ip_q,$user_db_q,$password_db_q)
        			or die("Connessione non riuscita: " . mysql_error());

				# open  db
   				mysql_select_db($db_name_q);

				# execute the SELECT query
				$risultato = mysql_query($insert_into_ClassificationNode_1);
				# close connection
    				mysql_close($connessione);
				
				if(!$risultato)
				{
					echo "<b>INSERIMENTO NON RIUSCITO ClassificationNode:</b> <br>";
					echo $insert_into_ClassificationNode_1."<br><br>";
					exit;	

				}//END OF if(!$risultato)

			}//END OF if($dom_ClassificationScheme_node_child_node_tagname=='Name')
		
			else if($dom_ClassificationNode_node_child_node_tagname=='ClassificationNode')
			{
				### ATTRIBUTO id
				$id_attr_2 = $dom_ClassificationNode_node_child_node->get_attribute('id');

				### ATTRIBUTO parent==id del precedente
				$parent_attr_2 = $dom_ClassificationNode_node->get_attribute('id');

				### ATTRIBUTO code
				$code_attr_2 = $dom_ClassificationNode_node_child_node->get_attribute('code');

				#### NODI FIGLI
		$dom_ClassificationNode_node_child_nodes_2=$dom_ClassificationNode_node_child_node->child_nodes();

		for($u=0;$u<(count($dom_ClassificationNode_node_child_nodes_2));$u++)
		{
			### SINGOLO NODO FIGLIO
			$dom_ClassificationNode_node_child_node_2=$dom_ClassificationNode_node_child_nodes_2[$u];

			### TAGNAME DEL NODO FIGLIO
			$dom_ClassificationNode_node_child_node_tagname_2=$dom_ClassificationNode_node_child_node_2->node_name();

			#### Name
			if($dom_ClassificationNode_node_child_node_tagname_2=='Name')
			{
				$name_node = $dom_ClassificationNode_node_child_node_2;
				$LocalizedString_nodes = $name_node->child_nodes();
				for($z=0;$z<count($LocalizedString_nodes);$z++)
				{
					$LocalizedString_node = $LocalizedString_nodes[$z];//->node_name();
					$LocalizedString_node_tagname = $LocalizedString_node->node_name();	

					if($LocalizedString_node_tagname=='LocalizedString')
					{
						$name_LocalizedString_value =$LocalizedString_node->get_attribute('value');
					}
					
				}//END OF for($p=0;$p<count($LocalizedString_nodes);$p++)

				### COMPONGO IL PATH
				$path_2 = "/$parent_attr/$code_attr/$code_attr_2";

				$insert_into_ClassificationNode_2 = "INSERT INTO ClassificationNode (id,parent,path,code,Name_value) VALUES ('$id_attr_2','$parent_attr_2','$path_2','$code_attr_2','$name_LocalizedString_value')";

    	
				# open connection to db
    				$connessione = mysql_connect($ip_q,$user_db_q,$password_db_q)
        			or die("Connessione non riuscita: " . mysql_error());

				# open  db
   				mysql_select_db($db_name_q);

				# execute the SELECT query
				$risultato = mysql_query($insert_into_ClassificationNode_2);
				# close connection
    				mysql_close($connessione);
				
				if(!$risultato)
				{
					echo "<b>INSERIMENTO NON RIUSCITO ClassificationNode2:</b> <br>";
					echo $insert_into_ClassificationNode_2."<br><br>";
					exit;	

				}//END OF if(!$risultato)

				}
			}

			}//END OF ELSE

		}//END OF for($k=0;$k<(count($dom_ClassificationNode_node_child_nodes));$k++)

 		}

	}//END OF for($n=0;$n<(count($dom_ClassificationNode_node_array));$n++)	


}//END OF for($i=0;$i<(count($dom_LeafRegistryObjectList_node_array));$i++)

#### CONFERMA DEGLI INSERIMENTI OK
echo "<b>- IL REGISTRY E' STATO INIZIALIZZATO CORRETTAMENTE -</b>";

?>