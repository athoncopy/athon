<?php
# ------------------------------------------------------------------------------------
# MARIS XDS REPOSITORY
# Copyright (C) 2007 - 2010  MARiS Project
# Dpt. Medical and Diagnostic Sciences, University of Padova - csaccavini@rad.unipd.it
# This program is distributed under the terms and conditions of the GPL
# See the LICENSE files for details
# ------------------------------------------------------------------------------------
##### CLASSE PER LA CREAZIONE DEI LOGs #####
class Log
{
	#### VARIABILI INTERNE
	var $current_log_path;
	var $default_current_log_path;
	var $current_files_log_path;
	var $default_current_files_log_path;
	var $cur_num;

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
		$this->current_log_path = $current.'log.out';

	}//END OF setCurrentLogPath($current)
	### DEFAULT
	###  /usr/tmp/REPOSITORY_log.out
	### /usr/tmp/REGISTRY_log.out
	function setDefaultCurrentLogPath($default_current) ##UNICO FILE DI LOG
	{
		$this->default_current_log_path = $default_current;

	}//END OF setDefaultCurrentLogPath($current)

	#### SETTA IL PATH DI SCRITTURA DEI FILES DI LOG SEPARATI
	function setCurrentFileSLogPath($currentf)
	{
		$this->current_files_log_path = $currentf;

	}//END OF setCurrentFileSLogPath($currentf)
	#### DEFAULT
	### /usr/tmp/
	function setDefaultCurrentFileSLogPath($default_currentf)
	{
		$this->default_current_files_log_path = $default_currentf;

	}//END OF setDefaultCurrentFileSLogPath($currentf)

	### PER SETTARE IL NUMERO DI SESSIONE (RANDOM)
	/*function setCurrentNumFile()
	{
		$stringa5 = "";
      		for($i=0; $i<12; $i++)
         	{
         		$lettera = chr(rand(48,122)); // carattere casuale
         		while (!ereg("[a-z0-9]", $lettera))
            		{
            			$lettera = chr(rand(48,122));// genera un'altra
            		}
         		$stringa5 .= $lettera; // accoda alla stringa

         	}//END OF for($i=0; $i<12; $i++)

		#### NELLA FORMA    __if60igcqyc0f
		$this->cur_num = "__$stringa5";

	}//EOND OF setCurrentNumFile()*/

	#### PER SETTARE ATTIVO O MENO IL LOGGING
	function setLogActive($active)
	{
		$this->isActive = $active;

	}//END OF setLogActive($active)

	#### METODO DI LOGGING
	## INPUT: $log_text   TESTO DI LOG
	## INPUT: $hour	1->SI ORA	0->NO ORA
	function writeLogFile($log_text,$hour)
	{
		### CASO DI LOGGING ATTIVO
		if($this->isActive=="A")
		{
			### CONTROLLO CHE IL PATH SIA SETTATO
			if($this->current_log_path=='')
			{
				$this->current_log_path = $this->default_current_log_path;
			}
			### APERTURA DEL FILE IN FORMA TAIL ED IN SOLA SCRITTURA
			$handler_log = fopen($this->current_log_path,'ab');

			#### RECUPERO DATA E ORA ATTUALI
			$today = date("d-M-Y");
			$cur_hour = date("H:i:s");

			#### FORMA:  [gg-MMM-AAAA hh:mm:ss] -
			$datetime = "\n[$today $cur_hour] -";

			## CASO DI DATO TIPO ARRAY
			if(is_array($log_text))
			{
				$txt = "";
				### IMPOSTA L'ARRAY NELLA FORMA [etichetta] = valore
				foreach($log_text as $element => $value) 
				{
   					$txt = $txt."$element = $value\n";
				}//END OF foreach
				$log_text = $txt;
			}//END OF if(is_array($log_text))

			### SCRIVO IL LOG
			if($hour)### CASO DI SI ORA
			{
				fwrite($handler_log,"$datetime $log_text \n");
			}
			else ###CASO DI NO ORA
			{
				fwrite($handler_log,"$log_text \n");
			}
			#### CHIUDO L'HANDLER
			fclose($handler_log);

		}//END OF if($isActive=="A")

		else {return;}

	}//END OF makeLog($log_text)

function writeLogFileS($log_text,$file_name,$mandatory)
	{
		$pathToFile = '';

		### CONTROLLO CHE IL PATH SIA SETTATO
		if($this->current_files_log_path=='')
		{
			$this->current_files_log_path = $this->default_current_files_log_path;
		}

			### PATH COMPLETO AL FILE 
			$pathToFile = $this->current_files_log_path.$file_name;

			### APERTURA DEL FILE IN FORMA TAIL ED IN SOLA SCRITTURA
			$handler_log = fopen($pathToFile,"wb+");

			## CASO DI DATO TIPO ARRAY
			if(is_array($log_text))
			{
				$txt = "";
				### IMPOSTA L'ARRAY NELLA FORMA [etichetta] = valore
				foreach($log_text as $element => $value) 
				{
   					$txt = $txt."$element = $value\n";
				}//END OF foreach
				$log_text = $txt;
			}//END OF if(is_array($log_text))

			fwrite($handler_log,$log_text );

			#### CHIUDO L'HANDLER
			fclose($handler_log);
		
		
		#### RITORNO IL PATH AL FILE SCRITTO
		return $pathToFile;

	}//END OF writeLogInFiles($log_text)

	#### SCRIVE I LOGS NEL DATABASE DI LOG
	function writeLogDatabase($log_text)
	{
		

	}//END OF writeLogDatabase



}//END OF CLASS Log



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