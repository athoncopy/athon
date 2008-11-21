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
// 1 --> modificabile
// 0 --> NON modificabile
### $node = IL SINGOLO NODO EXTRINSICOBJECT
function modifiable($node)
{
	$hash_bool = true;
	$size_bool = true;
	$URI_bool = true;
	$problem = "";

	$child_array_nodes = $node->child_nodes();
	for($j = 0;$j<count($child_array_nodes);$j++)
	{
		$nod = $child_array_nodes[$j];
		$nod_name = $nod->node_name();
		if ($nod->node_type() == XML_ELEMENT_NODE) 
		{
			$name = $nod->node_name();
			if($name == "Slot")
			{
				#### SLOT ATTRIBUTE
				$attribute = $nod->get_attribute("name");

				if(strtoupper($attribute) == "HASH") 
				{
					$hash_bool = false;
				}
				if(strtoupper($attribute) == "SIZE")
				{
					 $size_bool = false;
				}
				if(strtoupper($attribute) == "URI") 
				{
					$URI_bool = false;
				}

			}//END OF if($name == "Slot")
			
		}//END OF if ($nod->node_type() == XML_ELEMENT_NODE) 
		
	}//END OF for($j = 0;$j<count($child_array_nodes);$j++)

	#### COMPONGO IL VALORE BOOLEANO DA RITORNARE
	$ret = ($hash_bool && $size_bool && $URI_bool);

	#### RETURN
	return $ret;

}//END OF function modifiable($node)


function libxml_display_error($error)
{
    $return = "<br/>\n";
    switch ($error->level) {
        case LIBXML_ERR_WARNING:
            $return .= "<b>Warning $error->code</b>: ";
            break;
        case LIBXML_ERR_ERROR:
            $return .= "<b>Error $error->code</b>: ";
            break;
        case LIBXML_ERR_FATAL:
            $return .= "<b>Fatal Error $error->code</b>: ";
            break;
    }
    $return .= trim($error->message);
    if ($error->file) {
        $return .=    " in <b>$error->file</b>";
    }
    $return .= " on line <b>$error->line</b>\n";

    return $return;
}

function libxml_display_errors() {
    $error_message = "";
    $errors = libxml_get_errors();
    foreach ($errors as $error) {
	$error_message=$error_message."\n".libxml_display_error($error)."\n";
    }
    libxml_clear_errors();
return 	$error_message;
}




?>