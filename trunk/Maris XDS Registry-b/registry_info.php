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

include("REGISTRY_CONFIGURATION/REG_configuration.php");
########################################################

print("<pre>\t----------------------------------------\n</pre>");
print("<pre>\n\t*** THIS IS REGISTRY SETUP INFO FILE ***</pre>");
print("<pre>\t----------------------------------------\n</pre>");

print("<pre>\n\n\t1 - LOCAL REGISTRY HOST INFOS:\n</pre>");
print("<pre>\t\thost:     $reg_host\n</pre>");
$port=($http=="NORMAL"? "<pre>\t\t\t(http port LISTENING)</pre>":"<pre>\t\t\t(https port LISTENING)</pre>");
print("<pre>\t\tport:     $reg_port    $port\n</pre>");

print("<pre>\n\t2 - THIS REGISTRY ANSWERS TO QUERIES ON:\n</pre>");
print("<pre>\t\thost:     $reg_QUERY_host\n</pre>");
print("<pre>\t\tport:     $reg_QUERY_port      $port\n</pre>");

print("<pre>\n\t3 - THIS REGISTRY SENDS ATNA MESSAGES TO:\n</pre>");
print("<pre>\t\thost:     $ATNA_host\n</pre>");
print("<pre>\t\tport:     $ATNA_port\n</pre>");
print("<pre>\n\t\t\t\tATNA SERVER NODE\n</pre>");

?>