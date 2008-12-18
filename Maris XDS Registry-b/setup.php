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


require('./config/config.php');
require('./lib/utilities.php');
require('./lib/log.php');
require('./lib/functions_'.$database.'.php');
ob_start();
$connessione=connectDB();
$autenticato = false;

$users = "SELECT * FROM USERS";
$res_users = query_select2($users,$connessione);

if (isset($_SERVER['PHP_AUTH_USER']) &&
    isset($_SERVER['PHP_AUTH_PW']))
  {

  $user = $_SERVER['PHP_AUTH_USER'];
  $password = $_SERVER['PHP_AUTH_PW'];

if ($user == $res_users[0][0] && crypt($password,'xds') == $res_users[0][1]){

  $autenticato = true;
  }
 }
if (!$autenticato){
  header('WWW-Authenticate: Basic realm="Pagina di accesso"');
  header('HTTP/1.0 401 Unauthorized');
  echo "<h1>Authentication failed.</h1>";
}
  else {

?>
<HEAD>

<SCRIPT LANGUAGE="JavaScript">
<!-- Begin
function validatePwd() {
var invalid = " "; // Invalid character is a space
var minLength = 6; // Minimum length
var pw1 = document.myForm.password.value;
var pw2 = document.myForm.password2.value;
// check for a value in both fields.
if (pw1 == '' || pw2 == '') {
alert('Please enter your password twice.');
return false;
}
// check for minimum length
if (document.myForm.password.value.length < minLength) {
alert('Your password must be at least ' + minLength + ' characters long. Try again.');
return false;
}
// check for spaces
if (document.myForm.password.value.indexOf(invalid) > -1) {
alert("Sorry, spaces are not allowed.");
return false;
}
else {
if (pw1 != pw2) {
alert ("You did not enter the same new password twice. Please re-enter your password.");
return false;
}
else {
return true;
      }
   }
}
//  End -->
</SCRIPT>


<SCRIPT LANGUAGE="JavaScript">
function conferma()
{
var sicuro = confirm('!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! Attention !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!\nAre you sure you want delete all references of Documents in the Repository database? \nIf you press OK all references of documents in the Registry Database will be lost\n!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!');
if (sicuro == true)
return true;
else
return false;
}
//  End -->
</SCRIPT>


<title>
<?php

$v_maris_registry="3.2";

echo "MARIS XDS REGISTRY-B v$v_maris_registry SETUP"; //."   (".$_SESSION["idcode"].")";
	?></title>

</HEAD>


<?php
$delete_active = $_GET['delete'];

$ip = $_SERVER['SERVER_NAME'];
$script = $_SERVER['PHP_SELF'];
$root = $_SERVER['DOCUMENT_ROOT'];


$registry_link="http://".$ip.str_replace('setup.php', 'registry.php',$script);
$query_link="http://".$ip.str_replace('setup.php', 'query.php',$script);
$stored_query_link="http://".$ip.str_replace('setup.php', 'storedquery.php',$script);




echo '<table width="100%" border=0 cellpadding="10" cellspacing="0">
<tr bgcolor="black"><td><img src="./img/logo+scritta.jpg"></td>
</tr>';
echo '<tr bgcolor="#FF8F10"><td colspan="2">';
echo "<h2>Registry-b v$v_maris_registry Setup</h2>";

echo "This version of MARiS XDS Registry is not certified as a commercial medical device (FDA or CE)<br><br>";



echo "The link to the registry you have to set in your software (XDS Repository) is:";
echo "<br><b>".$registry_link."</b><br><br>";
//echo "The link to the registry you have to set in your software for query (XDS Consumer) is:";
//echo "<br><b>".$query_link."</b><br><br>";

echo "The link to the registry you have to set in your software for stored query (XDS Consumer) is:";
echo "<br><b>".$stored_query_link."</b>";





$get_REG_config="SELECT CACHE,PATIENTID,LOG,STAT,FOLDER,STATUS FROM CONFIG_B";
$res_REG_config = query_select2($get_REG_config,$connessione);

$REG_cache = $res_REG_config[0][0];
$REG_PatientID = $res_REG_config[0][1];
$REG_log= $res_REG_config[0][2];
$REG_stat= $res_REG_config[0][3];
$REG_folder= $res_REG_config[0][4];
$REG_status = $res_REG_config[0][5];

echo "<FORM action=\"updatesetup.php\" method=\"POST\">";

############## REGISTRY STATUS ################

echo "<h3>Registry Status</h3>";

if($REG_status=="A"){

	echo "Registry Status: <select name=\"registry_status\">
  	<option value=\"A\" selected=\"selected\">ON</option>
   	<option value=\"O\">OFF</option>
  	</select><br></br>";
	}
else {

	echo "Registry Status: <select name=\"registry_status\">
   	<option value=\"A\">ON</option>
  	<option value=\"O\" selected=\"selected\">OFF</option>
  	</select><br></br>";
	}


############## HTTP ################
/*
$get_HTTP="SELECT * FROM HTTP";

$res_REG_HTTP = query_select2($get_HTTP,$connessione);
$REG_HTTP = $res_REG_HTTP[0][0];
echo "<h3>Registry connection</h3>";

if($REG_HTTP=="NORMAL"){

	echo "Registry HTTP: <select name=\"registry_http\">
  	<option value=\"NORMAL\" selected=\"selected\">NORMAL</option>
   	<option value=\"TLS\">TLS</option>
  	</select><br></br>";
	}
else {

	echo "Registry HTTP: <select name=\"registry_http\">
   	<option value=\"NORMAL\">NORMAL</option>
  	<option value=\"TLS\" selected=\"selected\">TLS</option>
  	</select><br></br>";
	}
*/

echo "<h3>Registry parameters</h3>";
if($REG_cache=="O"){
	echo "Save tmp files: <select name=\"registry_cache\">
  	<option value=\"O\" selected=\"selected\">OFF</option>
   	<option value=\"L\">LOW</option>
	<option value=\"H\">HIGH</option>
  	</select><br></br>";
	}
else if($REG_cache=="L"){
	echo "Save tmp files: <select name=\"registry_cache\">
   	<option value=\"O\">OFF</option>
  	<option value=\"L\" selected=\"selected\">LOW</option>
	<option value=\"H\">HIGH</option>
  	</select><br></br>";
	}
else {
	echo "Save tmp files: <select name=\"registry_cache\">
   	<option value=\"O\">OFF</option>
	<option value=\"L\">LOW</option>
  	<option value=\"H\" selected=\"selected\">HIGH</option>
  	</select><br></br>";
	}


echo "<h3>Registry Patient ID Control</h3>";
echo "If you set \"<b>ON</b>\", when the Registry receives a document with an unknown PatientID, it rejects the submission.<br>";
echo "If you set \"<b>OFF</b>\", when the Registry receives a document with an unknown PatientID, it inserts the Patient ID in the database and accept the submission.<br><br>";
if($REG_PatientID=="A"){
	echo "Validate PatientID: <select name=\"registry_patient\">
  	<option value=\"A\" selected=\"selected\">ON</option>
   	<option value=\"O\">OFF</option>
  	</select><br></br>";
	}
else {
	echo "Validate PatientID: <select name=\"registry_patient\">
   	<option value=\"A\">ON</option>
  	<option value=\"O\" selected=\"selected\">OFF</option>
  	</select><br></br>";
	}


echo "<h3>Registry Folder uniqueID Control</h3>";
echo "If you set \"<b>ON</b>\", when the Registry receives a document with an existing Folder.uniqueID, it rejects the submission.<br>";
echo "If you set \"<b>OFF</b>\", when the Registry receives a document with an existing Folder.uniqueID, it adds the documet to folder and accept the submission.<br><br>";
if($REG_folder=="A"){
	echo "Validate Folder.uniqueID: <select name=\"registry_folder\">
  	<option value=\"A\" selected=\"selected\">ON</option>
   	<option value=\"O\">OFF</option>
  	</select><br></br>";
	}
else {
	echo "Validate Folder.uniqueID: <select name=\"registry_folder\">
   	<option value=\"A\">ON</option>
  	<option value=\"O\" selected=\"selected\">OFF</option>
  	</select><br></br>";
	}


echo "<h3>Registry Log</h3>";
if($REG_log=="A"){
	echo "Log: <select name=\"registry_log\">
  	<option value=\"A\" selected=\"selected\">ON</option>
   	<option value=\"O\">OFF</option>
  	</select><br></br>";
	}
else {
	echo "Log: <select name=\"registry_log\">
   	<option value=\"A\">ON</option>
  	<option value=\"O\" selected=\"selected\">OFF</option>
  	</select><br></br>";
	}


echo "<h3>Registry STATS</h3>";
if($REG_stat=="A"){
	echo "Statistics: <select name=\"registry_stat\">
  	<option value=\"A\" selected=\"selected\">ON</option>
   	<option value=\"O\">OFF</option>
  	</select><br></br>";
	}
else {
	echo "Statistics: <select name=\"registry_stat\">
   	<option value=\"A\">ON</option>
  	<option value=\"O\" selected=\"selected\">OFF</option>
  	</select><br></br>";
	}

############### ATNA ###############
$get_ATNA="SELECT * FROM ATNA";


$res_REG_ATNA = query_select2($get_ATNA,$connessione);

$REG_ATNA_host = $res_REG_ATNA[0][1];
$REG_ATNA_port = $res_REG_ATNA[0][2];
$REG_ATNA_active = $res_REG_ATNA[0][3];


echo "<h3>ATNA parameters</h3>";


if($REG_ATNA_active=="A"){
echo "ATNA: <select name=\"registry_atna_status\">
   <option value=\"A\" selected=\"selected\">ON</option>
   <option value=\"O\">OFF</option>
  </select><br></br>";}
else {
echo "ATNA: <select name=\"registry_atna_status\">
   <option value=\"A\" >ON</option>
   <option value=\"O\" selected=\"selected\">OFF</option>
  </select><br></br>";}

echo "Registry Host: <INPUT type=\"text\" name=\"registry_host_atna\" value=\"$REG_ATNA_host\" size=\"20\" maxlength=\"30\"><br></br>";
echo "Registry Port: <INPUT type=\"text\" name=\"registry_port_atna\" value=\"$REG_ATNA_port\" size=\"4\" maxlength=\"10\"><br></br>";


#################### NAV ####################

$get_REG_NAV="SELECT * FROM NAV";
$res_REG_NAV = query_select2($get_REG_NAV,$connessione);

$REG_NAV = $res_REG_NAV[0][0];
$REG_NAV_from = $res_REG_NAV[0][1];
$REG_NAV_to = $res_REG_NAV[0][2];

echo "<h3>Registry NAV</h3>";
if($REG_NAV=="A"){
	echo "NAV: <select name=\"registry_nav\">
  	<option value=\"A\" selected=\"selected\">ON</option>
   	<option value=\"O\">OFF</option>
  	</select><br></br>";
	}
else {
	echo "NAV: <select name=\"registry_nav\">
   	<option value=\"A\">ON</option>
  	<option value=\"O\" selected=\"selected\">OFF</option>
  	</select><br></br>";
	}

echo "NAV e-mail from: <INPUT type=\"text\" name=\"registry_nav_from\" value=\"$REG_NAV_from\" size=\"20\" maxlength=\"50\"><br></br>";
echo "NAV e-mail to: <INPUT type=\"text\" name=\"registry_nav_to\" value=\"$REG_NAV_to\" size=\"20\" maxlength=\"50\"><br></br>";


#################### JAVA_PATH ###################
/*
echo "<h3>JAVA HOME</h3>";
echo "JAVA_HOME could be /usr/lib/jvm/jre/bin/ or /usr/lib/jvm/java-xxx/bin/ or C:\\\\Programmi\\Java\\jre.xxx\\bin\\<br>";
echo "<INPUT type=\"text\" name=\"registry_java_home\" value=\"$REG_java\" size=\"50\" maxlength=\"100\"><br></br>";
*/

echo "<INPUT type=\"Submit\" value=\"Update\"><br></br>";

echo "</FORM>";

if($delete_active=="on"){
echo "<h3>Delete Registry</h3>";

echo "<FORM action=\"./DB_SERVICES/SVUOTA_REGISTRY_DB.php\" method=\"POST\"> ";
echo "If you press button \"Delete TMP Files\" all tmp files will be lost";
echo "<INPUT type=\"hidden\" name=\"delete_registry\" value=\"tmp\"";
echo "<br>";
echo "<br>";
echo "<INPUT type=\"Submit\" value=\"Delete TMP Files\"><br></br>";
echo "</FORM>";

echo "<FORM name=\"svuota_registry\"  action=\"./DB_SERVICES/SVUOTA_REGISTRY_DB.php\" method=\"POST\"onSubmit=\"return conferma()\">";
echo "If you press button \"Delete Database\" all references of documents in the Registry Database will be lost";
echo "<INPUT type=\"hidden\" name=\"delete_registry\" value=\"database\"";
echo "<br>";
echo "<br>";
echo "<INPUT type=\"Submit\" value=\"Delete Registry\"><br></br>";
echo "</FORM>";
}

#################### PASSWORD ####################

$get_USER="SELECT * FROM USERS";

$res_USER = query_select2($get_USER,$connessione);

$USER_login = $res_USER[0][0];


echo "<h3>Setup User and password</h3>";


echo "If you change login or password <br>you must fill the new login e the new password <br>immediately after you press update<br><br>";

echo "<FORM name=\"myForm\" action=\"updateuser.php\" method=\"POST\"onSubmit=\"return validatePwd()\">";
echo "Login: <INPUT type=\"text\" name=\"login\" value=\"$USER_login\" size=\"20\" maxlength=\"30\"><br></br>";
echo "Password: <INPUT type=\"password\" name=\"password\" value=\"\" size=\"10\" maxlength=\"20\"><br></br>";
echo "Verify Password: <INPUT type=\"password\" name=\"password2\" value=\"\" size=\"10\" maxlength=\"20\"><br></br>";
echo "<INPUT type=\"Submit\" value=\"Update User\"><br></br>";
echo "</FORM>";


echo "</td></tr>";
echo '<tr bgcolor="black"><td colspan="2"><br><br></td></tr></table>';
}

disconnectDB($connessione);


?>