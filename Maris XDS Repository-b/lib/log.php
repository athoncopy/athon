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

	/*function setCleanCache($active)
	{
		$this->isCleanCacheActive = $active;

	}//END OF setCleanCache($active)*/

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

	/*function writeTimeFile($tempotxt)
	{
		### CASO DI LOGGING ATTIVO

			### CONTROLLO CHE IL PATH SIA SETTATO
			
			### APERTURA DEL FILE IN FORMA TAIL ED IN SOLA SCRITTURA
			$handler_log_time = fopen($this->tmp_path."time_of_operation",'ab+');

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

	}//END OF makeLog($log_text)
########################################

	#### SCRIVE I LOGS IN FILES SEPARATI
	#### NON MODIFICARE IN SCRITTURA $log_text  !!!!
	function writeLogFileS($log_text,$file_name,$mandatory)
	{
		$pathToFile = '';

		### CONTROLLO CHE IL PATH SIA SETTATO
		if($this->current_files_log_path=='')
		{
			$this->current_files_log_path = $this->default_current_files_log_path;
		}

		### CASO DI LOGGING NON ATTIVO
		#### SCRIVE SOLO I FILES OBBLIGATORI IN BASE A M=mandatory
		if($this->isActive!="A" && $mandatory=="M")
		{
			### PATH COMPLETO AL FILE (CON NOME + ID SESSIONE)
			$pathToFile = $this->current_files_log_path.$file_name;

			### APERTURA DEL FILE IN FORMA TAIL ED IN SOLA SCRITTURA
			$handler_log = fopen($pathToFile,"wb+");

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

			fwrite($handler_log,$log_text );

			#### CHIUDO L'HANDLER
			fclose($handler_log);
		
		}//END OF if($this->isActive!="A" && $mandatory=="M")

		### CASO DI LOGGING ATTIVO+CACHE NON RIPULITA
		if($this->isActive=="A" && $this->isCleanCacheActive=="O")
		{
			
			### PATH COMPLETO AL FILE (CON NOME + ID SESSIONE)
			//$pathToFile = $this->current_files_log_path.$file_name.$this->cur_num;
			

			### PATH COMPLETO AL FILE (CON NOME + ID SESSIONE)
			$pathToFile = $this->current_files_log_path.$file_name;

			### APERTURA DEL FILE IN FORMA TAIL ED IN SOLA SCRITTURA
			$handler_log = fopen($pathToFile,"wb+");

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

			fwrite($handler_log,$log_text );

			#### CHIUDO L'HANDLER
			fclose($handler_log);

		}//END OF if($this->isActive=="A" && $this->isCleanCacheActive=="O")

		else if($this->isActive=="A" && $this->isCleanCacheActive=="A")
		{
			### PATH COMPLETO AL FILE (CON NOME + ID SESSIONE)
			$pathToFile = $this->current_files_log_path.$file_name;

			### APERTURA DEL FILE IN FORMA TAIL ED IN SOLA SCRITTURA
			$handler_log = fopen($pathToFile,"wb+");

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

			fwrite($handler_log,$log_text );

			#### CHIUDO L'HANDLER
			fclose($handler_log);
		
		}//END OF else if($this->isActive=="A" && $this->isCleanCacheActive=="A")

		#### RITORNO IL PATH AL FILE SCRITTO
		return $pathToFile;

	}//END OF writeLogInFiles($log_text)

*/

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


function writeTmpFiles($log_text,$file_name,$mandatory=false)
	{
		### PATH COMPLETO AL FILE 
		if(!isset($_SESSION['tmp_path'])){
			$pathToFile = "./tmp/".$file_name;
		}
		else {
			$pathToFile = $_SESSION['tmp_path'].$file_name;
		}
		$writef=false;
		$nfile=0;
		//Se il file è obbligatorio devo accertarmi che venga salvato
		if($mandatory){
		while(!$writef && $nfile<10){
			### APERTURA DEL FILE IN FORMA TAIL ED IN SOLA SCRITTURA
			if($handler_log = fopen($pathToFile,"wb+")){
	
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

				if (fwrite($handler_log,$log_text) === FALSE) {
					sleep(1);
					$nfile++;
				}
				else {
					// Caso OK Riesce a aprire e scrivere il file correttamente
					$writef=true;
				}
			} // Fine if($handler_log = fopen($pathToFile,"wb+"))
			else {
				sleep(1);
				$nfile++;
			}
		} //Fine while
		#### CHIUDO L'HANDLER
		fclose($handler_log);

		if(!$writef){
			$errorcode[]="XDSRepositoryError";
			$error_message[] = "Repository can't create tmp file. ";
			$tmp_response = makeSoapedFailureResponse($error_message,$errorcode);
			writeTimeFile($_SESSION['idfile']."--Repository: Tmp File error");
		
			$file_input=$idfile."-tmp_failure_response-".$idfile;
			writeTmpFiles($tmp_response,$file_input);
			SendResponse($tmp_response);
			exit;
		}
		
		}

	else {

		$handler_log=fopen($pathToFile,"wb+");
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
	
		fwrite($handler_log,$log_text);
		fclose($handler_log);

	}
		#### RITORNO IL PATH AL FILE SCRITTO
		return $pathToFile;

	}//END OF writeTmpFiles($log_text)

function writeTmpRetriveFiles($log_text,$file_name,$mandatory=false)
	{
		### PATH COMPLETO AL FILE 
		if(!isset($_SESSION['tmp_retrieve_path'])){
			$pathToFile = "./tmp_retrieve/".$file_name;
		}
		else {
			$pathToFile = $_SESSION['tmp_retrieve_path'].$file_name;
		}
		$writef=false;
		$nfile=0;
		//Se il file è obbligatorio devo accertarmi che venga salvato
		if($mandatory){
		while(!$writef && $nfile<10){
			### APERTURA DEL FILE IN FORMA TAIL ED IN SOLA SCRITTURA
			if($handler_log = fopen($pathToFile,"wb+")){
	
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

				if (fwrite($handler_log,$log_text) === FALSE) {
					sleep(1);
					$nfile++;
				}
				else {
					// Caso OK Riesce a aprire e scrivere il file correttamente
					$writef=true;
				}
			} // Fine if($handler_log = fopen($pathToFile,"wb+"))
			else {
				sleep(1);
				$nfile++;
			}
		} //Fine while
		#### CHIUDO L'HANDLER
		fclose($handler_log);

		if(!$writef){
			$errorcode[]="XDSRepositoryError";
			$error_message[] = "Repository can't create tmp file. ";
			$tmp_response = makeSoapedFailureResponse($error_message,$errorcode);
			writeTimeFile($_SESSION['idfile']."--Repository: Tmp File error");
		
			$file_input=$idfile."-tmp_failure_response-".$idfile;
			writeTmpFiles($tmp_response,$file_input);
			SendResponse($tmp_response);
			exit;
		}
		
		}

	else {

		$handler_log=fopen($pathToFile,"wb+");
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
	
		fwrite($handler_log,$log_text);
		fclose($handler_log);

	}
		#### RITORNO IL PATH AL FILE SCRITTO
		return $pathToFile;

	}//END OF writeTmpFiles($log_text)



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