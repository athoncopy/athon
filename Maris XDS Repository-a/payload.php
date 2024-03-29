<?php
# ------------------------------------------------------------------------------------
# MARIS XDS REPOSITORY
# Copyright (C) 2007 - 2010  MARiS Project
# Dpt. Medical and Diagnostic Sciences, University of Padova - csaccavini@rad.unipd.it
# This program is distributed under the terms and conditions of the GPL

# Contributor(s):
# A-thon srl <info@a-thon.it>
# Alberto Castellini

# See the LICENSE files for details
# ------------------------------------------------------------------------------------
## TRUE == not empty
## FALSE == empty

function controllaPayload($dom_ebXML)
{
	$root_ebXML = $dom_ebXML->document_element();
	$dom_ebXML_node_array=$root_ebXML->get_elements_by_tagname("LeafRegistryObjectList");
	
	$node = $dom_ebXML_node_array[0];
	$payload = $node->child_nodes();

	$isNotEmpty = (count($payload)-1);

// 	if($isNotEmpty){ echo "PAYLOAD NOT EMPTY";}
// 	else{ echo "EMPTY PAYLOAD";}

	return $isNotEmpty;
	
}//END OF controllaPayload

?>