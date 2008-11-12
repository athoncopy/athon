<?php

# ------------------------------------------------------------------------------------
# MARIS XDS REGISTRY
# Copyright (C) 2007 - 2010  MARiS Project
# Dpt. Medical and Diagnostic Sciences, University of Padova - csaccavini@rad.unipd.it
# This program is distributed under the terms and conditions of the GPL
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

?>