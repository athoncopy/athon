<?php
# ------------------------------------------------------------------------------------
# MARIS XDS REGISTRY
# Copyright (C) 2007 - 2010  MARiS Project
# Dpt. Medical and Diagnostic Sciences, University of Padova - csaccavini@rad.unipd.it
# This program is distributed under the terms and conditions of the GPL
# See the LICENSE files for details
# ------------------------------------------------------------------------------------

## TRUE == not empty
## FALSE == empty
####CONTROLLA SE E' PRESENTE L'ebXML
function controllaPayload($dom_ebXML)
{
   $root_ebXML = $dom_ebXML->document_element();
   $dom_ebXML_node_array=$root_ebXML->get_elements_by_tagname("RegistryObjectList");
	
   $node = $dom_ebXML_node_array[0];
   $payload = $node->child_nodes();

   $isNotEmpty = (count($payload)-1);

// 	if($isNotEmpty){ echo "PAYLOAD NOT EMPTY";}
// 	else{ echo "EMPTY PAYLOAD";}

   return $isNotEmpty;
	
}//END OF controllaPayload

?>