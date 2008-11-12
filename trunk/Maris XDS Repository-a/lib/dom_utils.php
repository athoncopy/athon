<?php
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

?>