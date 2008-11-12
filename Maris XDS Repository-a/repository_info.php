<?php

include("config/REP_configuration.php");
############################################

print("<pre>\t------------------------------------------\n</pre>");
print("<pre>\n\t*** THIS IS REPOSITORY SETUP INFO FILE ***</pre>");
print("<pre>\t------------------------------------------\n</pre>");

print("<pre>\n\n\t1 - LOCAL REPOSITORY HOST INFOS:\n</pre>");
print("<pre>\t\thost:     $rep_host\n</pre>");
$port=($http=="NORMAL"? "<pre>\t\t\t(http port LISTENING)</pre>":"<pre>\t\t\t(https port LISTENING)</pre>");
print("<pre>\t\tport:     $rep_port    $port\n</pre>");

print("<pre>\n\t2 - THIS REPOSITORY IS SET TO MAKE REGISTER TRANSACTION TO THE REGISTRY:\n</pre>");
print("<pre>\t\thost:     $reg_host       ($reg_description)\n</pre>");
print("<pre>\t\tport:     $reg_port\n</pre>");
print("<pre>\t\tON THE PATH:    $reg_path\n</pre>");

$num= ($http=="NORMAL"? 1:2);
if($num==1)
{
	$protoc = "<pre>\t\t\t\tHTTP POST PROTOCOL</pre>";
}
else if($num==2)
{
	$protoc = "<pre>\t\t\t\tHTTPS POST PROTOCOL</pre>";
}
print("<pre>\t\tPROTOCOL USED --> $protoc</pre>");

print("<pre>\n\n\t3 - DOCUMENTS ARE STORED IN:\n</pre>");
print("<pre>\t\t$absolute_docs_path DIRECTORY OF LOCAL FILE SYSTEM\n</pre>");

print("<pre>\n\n\t4 - THIS REPOSITORY SENDS ATNA MESSAGES TO:\n</pre>");
print("<pre>\t\thost:     $ATNA_host\n</pre>");
print("<pre>\t\tport:     $ATNA_port\n</pre>");
print("<pre>\n\t\t\t\tATNA SERVER NODE\n</pre>");

?>