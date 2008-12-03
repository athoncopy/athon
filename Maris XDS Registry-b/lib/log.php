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

			fwrite($handler_log_time,"$datetime_t $tempotxt\n");

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
			$microtime = microtime();

			#### FORMA:  [gg-MMM-AAAA hh:mm:ss] -
			$datetime_t = "\n[$today_t $cur_hour_t] -";

			//$log_tempo = $tempotxt;
			## CASO DI DATO TIPO ARRAY
			if(is_array($tempotxt))
			{
				$txt = "";
				### IMPOSTA L'ARRAY NELLA FORMA [etichetta] = valore
				foreach($tempotxt as $element => $value) 
				{
   					$txt = $txt."$element = $value\n";
				}//END OF foreach
				$tempotxt = $txt;
			}//END OF if(is_array($log_text))			

			### SCRIVO IL LOG

			fwrite($handler_log_time,"$datetime_t $tempotxt\n");

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
				foreach($tempotxt as $element => $value) 
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
            $handler_log = fopen($pathToFile,"wb+");
			if($handler_log){
	
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
			$errorcode[]="XDSRegistryError";
			$error_message[] = "Registry can't create tmp file. ";
			$tmp_response = makeSoapedFailureResponse($error_message,$errorcode);
			writeTimeFile($_SESSION['idfile']."--Registry: Tmp File error");
		
			$file_input=$idfile."-tmp_failure_response.xml";
			writeTmpFiles($tmp_response,$file_input);
			SendResponse($tmp_response);
			//SendResponseFile($_SESSION['tmp_path'].$file_input);
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



function writeTmpQueryFiles($log_text,$file_name,$mandatory=false)
	{
	//$mandatory indica se il file deve essere salvato.
	### PATH COMPLETO AL FILE 
	if(!isset($_SESSION['tmpQueryService_path'])){
			$pathToFile = "./tmpQueryService/".$file_name;
		}
	else {
		$pathToFile = $_SESSION['tmpQueryService_path'].$file_name;
	}
	$writef=false;
	$nfile=0;
	//Se il file è obbligatorio devo accertarmi che venga salvato
	if($mandatory){
		while(!$writef && $nfile<10){
			### APERTURA DEL FILE IN FORMA TAIL ED IN SOLA SCRITTURA
            $handler_log = fopen($pathToFile,"wb+");
			if($handler_log){
	
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
			$errorcode[]="XDSRegistryError";
			$error_message[] = "Registry can't create tmp file. ";
			$tmp_response = makeSoapedFailureResponse($error_message,$errorcode);
			writeTimeFile($_SESSION['idfile']."--Registry: Tmp File error");
		
			$file_input=$idfile."-tmp_failure_response.xml";
			writeTmpQueryFiles($tmp_response,$file_input);
			//SendResponseFile($_SESSION['tmpQueryService_path'].$file_input);
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

	}//END OF writeTmpQueryFiles($log_text)


?>