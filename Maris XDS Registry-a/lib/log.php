<?php

# ------------------------------------------------------------------------------------
# MARIS XDS REGISTRY
# Copyright (C) 2007 - 2010  MARiS Project
# Dpt. Medical and Diagnostic Sciences, University of Padova - csaccavini@rad.unipd.it
# This program is distributed under the terms and conditions of the GPL
# See the LICENSE files for details
# ------------------------------------------------------------------------------------

##### CLASSE PER LA CREAZIONE DEI LOGs #####
/*class Log_REG
{
	#### VARIABILI INTERNE
	var $current_log_path;

	var $isActive;
	var $isCleanCacheActive;

	var $user = null;
	var $tmp_path;
	var $idfile;
	### COSTRUTTORE
	function Log($user)
	{
		### NON ATTIVO PER DEFAULT
		$this->isActive = "O";

######### UNICO LOG
		### CURRENT
		$this->current_log_path = '';
		if($user=="REP")
		{
			### DEFAULT LOG IN FILESYSTEM /usr/tmp/
			$this->default_current_log_path = '/usr/tmp/REPOSITORY_log.out';
		}
		else if($user=="REG")
		{
			### DEFAULT LOG IN FILESYSTEM /usr/tmp/
			$this->default_current_log_path = '/usr/tmp/REGISTRY_log.out';
		}

######### LOGS SU PIU' FILES
		### CURRENT
		$this->current_files_log_path = '';
		### DEFAULT LOG IN FILESYSTEM /usr/tmp/
		$this->default_current_files_log_path = '/usr/tmp/';
	
	}//END OF CONSTRUCTOR

	function set_tmp_path($tmp_path)
	{
	$this->tmp_path = $tmp_path;
	}

	function set_idfile($idfile)
	{
	$this->idfile = $idfile;
	}
	#### SETTA IL PATH DI SCRITTURA DEL LOG
	#### INPUT:  $current    COMPLETO CON IL NOME DEL FILE
	function setCurrentLogPath($current)
	{
		$this->current_log_path = $current;

	}//END OF setCurrentLogPath($current)
	### DEFAULT


	#### PER SETTARE ATTIVO O MENO IL LOGGING
	function setLogActive($active)
	{
		$this->isActive = $active;

	}//END OF setLogActive($active)

	function setCleanCache($active)
	{
		$this->isCleanCacheActive = $active;

	}//END OF setCleanCache($active)

	#### METODO DI LOGGING
	## INPUT: $log_text   TESTO DI LOG
	## INPUT: $hour	1->SI ORA	0->NO ORA

	function writeTimeFile($tempotxt)
	{
			### CASO DI LOGGING ATTIVO
			if ($this->isActive=='A'){
			### CONTROLLO CHE IL PATH SIA SETTATO
			
			### APERTURA DEL FILE IN FORMA TAIL ED IN SOLA SCRITTURA
			$handler_log_time = fopen($this->current_log_path."time_of_operation",'ab+');

			#### RECUPERO DATA E ORA ATTUALI
			$today_t = date("d-M-Y");
			$cur_hour_t = date("H:i:s");

			#### FORMA:  [gg-MMM-AAAA hh:mm:ss] -
			$datetime_t = "\n[$today_t $cur_hour_t] -";

			//$log_tempo = $tempotxt;
			## CASO DI DATO TIPO ARRAY
			if(is_array($tempotxt))
			{
				$txt = "";
				### IMPOSTA L'ARRAY NELLA FORMA [etichetta] = valore
				foreach($log_text as $element => $value) 
				{
   					$txt = $txt."$element = $value\n";
				}//END OF foreach
				$tempotxt = $txt;
			}//END OF if(is_array($log_text))			

			### SCRIVO IL LOG

			fwrite($handler_log_time,"$datetime_t $tempotxt");

			#### CHIUDO L'HANDLER
			fclose($handler_log_time);
		}
	}//END OF makeLog($log_text)

########################################



}//END OF CLASS Log*/

function writeSQLQuery($tempotxt)
	{
			### CASO DI LOGGING ATTIVO
			if ($_SESSION['logActive']=='A'){
			### CONTROLLO CHE IL PATH SIA SETTATO
			### APERTURA DEL FILE IN FORMA TAIL ED IN SOLA SCRITTURA
			$handler_log_time = fopen($_SESSION['log_path']."log-".$_SESSION['idfile']."-"."SQLSubmission",'ab+');

			#### RECUPERO DATA E ORA ATTUALI
			$today_t = date("d-M-Y");
			$cur_hour_t = date("H:i:s");

			#### FORMA:  [gg-MMM-AAAA hh:mm:ss] -
			$datetime_t = "\n[$today_t $cur_hour_t] -";

			//$log_tempo = $tempotxt;
			## CASO DI DATO TIPO ARRAY
			if(is_array($tempotxt))
			{
				$txt = "";
				### IMPOSTA L'ARRAY NELLA FORMA [etichetta] = valore
				foreach($log_text as $element => $value) 
				{
   					$txt = $txt."$element = $value\n";
				}//END OF foreach
				$tempotxt = $txt;
			}//END OF if(is_array($log_text))			

			### SCRIVO IL LOG

			fwrite($handler_log_time,"$tempotxt\n");

			#### CHIUDO L'HANDLER
			fclose($handler_log_time);
		}
	}//END OF makeLog($log_text)

function writeSQLQueryService($tempotxt)
	{
			### CASO DI LOGGING ATTIVO
			if ($_SESSION['logActive']=='A'){
			### CONTROLLO CHE IL PATH SIA SETTATO
			### APERTURA DEL FILE IN FORMA TAIL ED IN SOLA SCRITTURA
			$handler_log_time = fopen($_SESSION['log_path']."log-".$_SESSION['idfile']."-"."SQLQueryService",'ab+');

			#### RECUPERO DATA E ORA ATTUALI
			$today_t = date("d-M-Y");
			$cur_hour_t = date("H:i:s");

			#### FORMA:  [gg-MMM-AAAA hh:mm:ss] -
			$datetime_t = "\n[$today_t $cur_hour_t] -";

			//$log_tempo = $tempotxt;
			## CASO DI DATO TIPO ARRAY
			if(is_array($tempotxt))
			{
				$txt = "";
				### IMPOSTA L'ARRAY NELLA FORMA [etichetta] = valore
				foreach($log_text as $element => $value) 
				{
   					$txt = $txt."$element = $value\n";
				}//END OF foreach
				$tempotxt = $txt;
			}//END OF if(is_array($log_text))			

			### SCRIVO IL LOG

			fwrite($handler_log_time,"$tempotxt\n");

			#### CHIUDO L'HANDLER
			fclose($handler_log_time);
		}
	}//END OF makeLog($log_text)


function writeTimeFile($tempotxt)
	{
			### CASO DI LOGGING ATTIVO
			if ($_SESSION['logActive']=='A'){
			### CONTROLLO CHE IL PATH SIA SETTATO
			
			### APERTURA DEL FILE IN FORMA TAIL ED IN SOLA SCRITTURA
			$handler_log_time = fopen($_SESSION['log_path']."time_of_operation",'ab+');

			#### RECUPERO DATA E ORA ATTUALI
			$today_t = date("d-M-Y");
			$cur_hour_t = date("H:i:s");

			#### FORMA:  [gg-MMM-AAAA hh:mm:ss] -
			$datetime_t = "\n[$today_t $cur_hour_t] -";

			//$log_tempo = $tempotxt;
			## CASO DI DATO TIPO ARRAY
			if(is_array($tempotxt))
			{
				$txt = "";
				### IMPOSTA L'ARRAY NELLA FORMA [etichetta] = valore
				foreach($log_text as $element => $value) 
				{
   					$txt = $txt."$element = $value\n";
				}//END OF foreach
				$tempotxt = $txt;
			}//END OF if(is_array($log_text))			

			### SCRIVO IL LOG

			fwrite($handler_log_time,"$datetime_t $tempotxt");

			#### CHIUDO L'HANDLER
			fclose($handler_log_time);
		}
	}//END OF makeLog($log_text)

?>