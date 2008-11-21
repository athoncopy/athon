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
	var $create_folder;
        var $localtmp;
	var $header_data;
	//var $action;

//Constructor, timeout 30s
function HTTP_Client($host,$port,$timeout)
{
	$this->host = $host;
	$this->port = $port;
	$this->timeout = $timeout;

}

//apre connessione
function connect()
{
		$this->socket = fsockopen($this->host,$this->port,$this->errno,$this->errstr,$this->timeout);
									 
$fp_HTTP_Client_CONNECTION_STATUS = fopen($this->localtmp."HTTP_Client_CONNECTION_STATUS","w+");
    	
	if(!$this->socket)
	{
			fwrite($fp_HTTP_Client_CONNECTION_STATUS,"CONNESSIONE CON IL REpository $this->host *** NON RIUSCITA ***");
		fclose($fp_HTTP_Client_CONNECTION_STATUS);
		return false;
	}
	else
	{
			fwrite($fp_HTTP_Client_CONNECTION_STATUS,"CONNESSIONE CON IL REpository $this->host *** AVVENUTA REGOLARMENTE ***");
		fclose($fp_HTTP_Client_CONNECTION_STATUS);
		return true;
	}
}

//setta il path
function set_post_data($data)
{
	$this->post_data = $data;
}
function set_data_length($len)
{
	$this->dataLength = $len;
}

function set_local_tmp_path($local_tmp)
{
        $this->localtmp = $local_tmp;
}
function set_header($header_data)
{
        $this->header_data = $header_data;
}

function set_action($action)
{
	$this->action = $action;
}

//spedisce
function send_request()
{
	if(!$this->connect())
	{
		return false;
	}
	else
	{
		$this->result = $this->request($this->post_data,$this->header_data);
		return $this->result;
	}
}

function request($post_data,$header_data)
{
	$this->buf = "";
	$post = $this->header_data;
	//$post .= $this->post_data;

	
        $fp_HTTP_Client_POSTED = fopen($this->localtmp."HTTP_Client_POSTED","w+");
    	fwrite($fp_HTTP_Client_POSTED,$post);
	fclose($fp_HTTP_Client_POSTED);
	
	fwrite($this->socket,$post);

	while(!feof($this->socket))
	{
		$this->buf .= fgets($this->socket);
	}
	$this->close();
	return $this->buf;
}

function close()
{
	fclose($this->socket);
}

}//END OF CLASS
 
?>
