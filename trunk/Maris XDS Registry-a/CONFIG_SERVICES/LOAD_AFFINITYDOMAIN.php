<?php

include_once('../config/registry_QUERY_mysql_db.php');

$dom_affinity_domain = domxml_open_file ('../config/AffinityDomain_17-Nov-2005_1303/codes.xml');

##### RADICE DEL DOCUMENTO
$root_dom_affinity_domain = $dom_affinity_domain->document_element();
	
##### ARRAY DEI NODI CodeType
$dom_CodeType_node_array=$root_dom_affinity_domain->get_elements_by_tagname("CodeType");

#### CICLO SU OGNI CodeType ####
for($index=0;$index<(count($dom_CodeType_node_array));$index++)
{
	##### SINGOLO NODO CodeType
	$dom_CodeType_node=$dom_CodeType_node_array[$index];

	#### name ATTRIBUTE
	$CodeType_name_attr = $dom_CodeType_node->get_attribute('name');

	#### classScheme ATTRIBUTE
	$CodeType_classScheme_attr = $dom_CodeType_node->get_attribute('classScheme');

	#### INSERISCO NELLA TABELLA classScheme
	$insert_classScheme="INSERT INTO classScheme (class_Scheme,name) VALUES ('$CodeType_classScheme_attr','$CodeType_name_attr')";



	# open connection to db
	$connessione = mysql_connect($ip_q,$user_db_q,$password_db_q)
	or die("Connessione non riuscita: " . mysql_error());

	# open  db
	mysql_select_db($db_name_q);

	$risultato = mysql_query($insert_classScheme);
    	if(!$risultato)
	{
		# close connection
    		mysql_close($connessione);
		echo "<b>INSERIMENTO NON RIUSCITO classScheme:</b> <br>";
		echo $insert_classScheme."<br><br>";
		exit;	

	}//END OF if(!$risultato)

	##### FIGLI DEL SINGOLO NODO CodeType
	$dom_Code_node_array=$dom_CodeType_node->child_nodes();

	#### CICLO SU OGNI Code ####
	for($i=0;$i<(count($dom_Code_node_array));$i++)
	{
		#### SINGOLO NODO Code
		$dom_Code_node = $dom_Code_node_array[$i];

		#### TAGNAME DEL SINGOLO NODO Code
		$dom_Code_node_tagname=$dom_Code_node->node_name();

		if($dom_Code_node_tagname=='Code')
		{
		#### code
		$code_attr = $dom_Code_node->get_attribute('code');
		
		if($dom_Code_node->has_attribute('display'))
		{
			#### display
			$display_attr = $dom_Code_node->get_attribute('display');

			#### codingScheme
			$codingScheme_attr = $dom_Code_node->get_attribute('codingScheme');

			### QUERY DI INSERIMENTO
			$insert = "INSERT INTO $CodeType_name_attr (code,display,codingScheme) VALUES ('$code_attr','$display_attr','$codingScheme_attr')";

			//echo $INSERT_INTO_Name;
    	
			# open connection to db
    			$connessione = mysql_connect($ip_q,$user_db_q,$password_db_q)
        		or die("Connessione non riuscita: " . mysql_error());

			# open  db
   			mysql_select_db($db_name_q);

			# execute the SELECT query
  			$risultato = mysql_query($insert);
			if(!$risultato)
			{
				# close connection
    				mysql_close($connessione);
				echo "<b>INSERIMENTO NON RIUSCITO $CodeType_name_attr:</b> <br>";
				echo $insert."<br><br>";
				exit;	

			}//END OF if(!$risultato)
			# close connection
    			mysql_close($connessione);

		}

		else
		{
			### QUERY DI INSERIMENTO
			$insert = "INSERT INTO $CodeType_name_attr (code) VALUES ('$code_attr')";

			//echo $INSERT_INTO_Name;
    	
			# open connection to db
    			$connessione = mysql_connect($ip_q,$user_db_q,$password_db_q)
        		or die("Connessione non riuscita: " . mysql_error());

			# open  db
   			mysql_select_db($db_name_q);

			# execute the SELECT query
  			$risultato = mysql_query($insert);
			if(!$risultato)
			{
				# close connection
    				mysql_close($connessione);
				echo "<b>INSERIMENTO NON RIUSCITO:</b> <br>";
				echo $insert."<br><br>";
				exit;	

			}//END OF if(!$risultato)
			# close connection
    			mysql_close($connessione);
		
		}//END OF ELSE

		}

	}//END OF for($i=0;$i<(count($dom_Code_node_array));$i++)

}//END OF for($index=0;$index<(count($dom_CodeType_node_array));$index++)

#### CONFERMA DEGLI INSERIMENTI OK
echo "<b>- IL REGISTRY E' STATO AGGIORNATO CORRETTAMENTE CON TUTTI I CODICI DELL'AFFINITY DOMAIN -</b>";

#### RECUPERO DATA E ORA ATTUALI
$today = date("Y-m-d");
$cur_hour = date("H:i:s");
$datetime = $today." ".$cur_hour;

$text = "$datetime, INFO affinity_domain.php: AffinityDomain from file codes.xml for the 2006 Connectathon loaded";

$fp_affinity_domain = fopen("../tmp/load_affinity_domain.txt","w+");
    fwrite($fp_affinity_domain,$text);
fclose($fp_affinity_domain);

###11701
##2006-02-22 10:38:58,901 INFO com.sib.ged.affdom.AffinityDomainXMLLoader[http-18086-Processor25] - loadAffinityDomain() : AffinityDomain file C:Documents and SettingsTREPOSGinstallomarv1.2.0-post-alpha2-devaffinity_domain.xml loade

###11702
##2006-02-22 10:38:53,526 INFO com.sib.ged.affdom.AffinityDomainXMLLoader[http-18086-Processor25] - loadAffinityDomain() : Mime Types from file C:Documents and SettingsTREPOSGinstallomarv1.2.0-post-alpha2-devaffinity_domain.xml well loaded

###11703
##2006-02-22 10:38:53,573 INFO com.sib.ged.affdom.AffinityDomainXMLLoader[http-18086-Processor25] - loadAffinityDomain() : Code Tables from file C:Documents and SettingsTREPOSGinstallomarv1.2.0-post-alpha2-devaffinity_domain.xml well loaded

?>