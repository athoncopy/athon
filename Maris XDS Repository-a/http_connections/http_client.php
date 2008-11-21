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

//include_once("./lib/log.php");


class HTTP_Client
{
	var $host;
	var $port;
	var $socket;
	var $errno;
	var $errstr;
	var $timeout;
	var $buf;
	var $result;
	var $post_data;
	var $path;
	var $dataLength;
	var $agent_name = "MyAgent";
	var $idfile;
	var $tmp_path;
        
//Constructor, timeout 30s
function HTTP_Client($host,$port,$timeout)
{
	$this->host = $host;
	$this->port = $port;
	$this->timeout = $timeout;

}

//apre connessione
function connect()
{ //$log = new Log("REP");

	$this->socket = fsockopen($this->host,$this->port,$this->errno,$this->errstr,$this->timeout);


	if(!$this->socket)
	{
	if($this->save_files){
		$connection_response="CONNESSIONE CON IL REGISTRY $this->host *** NON RIUSCITA ***";
		writeTmpFiles($connection_response,$this->idfile."-HTTP_Client_CONNECTION_STATUS-".$this->idfile);
		}

		return false;
	}
	else
	{
	if($this->save_files){
		$connection_response="CONNESSIONE CON IL REGISTRY $this->host *** AVVENUTA REGOLARMENTE ***";
		writeTmpFiles($connection_response,$this->idfile."-HTTP_Client_CONNECTION_STATUS-".$this->idfile);
		}
		return true;
	}

}//END OF connect()

#### SPEDISCE: METODO PRINCIPALE
function send_request()
{ 
//$log = new Log("REP");

	if(!$this->connect())
	{
		$ret = array(false,"\nERROR IN HTTP CONNECTION\n");

		return $ret;
	}
	else	#####CASO SENZA ERRORI
	{

		$this->result = $this->request($this->post_data);

		##### COMPONGO L'ARRAY DA RITORNARE
		$ret = array($this->result,"");
		return $ret;
	}

}//END OF send_request()

function request($post_data)
{

//$log = new Log("REP");
	$this->buf = "";
	$post = "POST ".$this->path." HTTP/1.1\r\n".
		"Host: ".$this->host.":".$this->port."\r\n".
		"Accept: text/html, text/xml, image/gif, image/jpeg, *; q=.2, */*; q=.2\r\n".
		"Cache-Control: no-cache\r\n".
		"Connection: Close\r\n".
		"Content-Length: $this->dataLength \r\n".
		"Pragma: no-cache\r\n".
		"SOAPAction: \"\"\r\n".
		"User-Agent: myAgent\r\n".
		"Content-Type: text/xml; charset=\"utf-8\"\r\n".
		"\r\n".$post_data;
	
	if($this->save_files){
	writeTmpFiles($post,$this->idfile."-HTTP_Client_POSTED-".$this->idfile);
	}
	
	fwrite($this->socket,$post);

	while(!feof($this->socket))
	{
		#$this->buf .= fgets($this->socket, 2048);
		$this->buf .= fgets($this->socket);

	}
	$this->close();


	return $this->buf;


}

############ METODI DI SERVIZIO

function set_path($path)
{
	$this->path = $path;
}
function set_post_data($data)
{
	$this->post_data = $data;
}
function set_data_length($len)
{
	$this->dataLength = $len;
}

function set_tmp_path($tmp_path)
{
	$this->tmp_path = $tmp_path;
}

function set_protocol($prot)
{}
function set_host($host)
{}
function set_port($port)
{}
function set_idfile($idfile)
{
	$this->idfile = $idfile;
}

function set_save_files($save_files)
{
	$this->save_files = $save_files;
}




function close()
{
	fclose($this->socket);
}

############## FINE METODI DI SERVIZIO

}//END OF CLASS
 
?>
