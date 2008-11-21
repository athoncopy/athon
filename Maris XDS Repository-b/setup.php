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

require_once('./config/config.php');
require_once('./lib/utilities.php');
require_once('./lib/log.php');
require_once('./lib/functions_'.$database.'.php');
ob_start();
$autenticato = false;
$connessione=connectDB();

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
</script>

<SCRIPT LANGUAGE="JavaScript">
function conferma()
{
var sicuro = confirm('!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! Attention !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!\nAre you sure you want delete all references of Documents in the Repository database? \nIf you press OK all references of documents in the Repository Database will be lost\n!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!');
if (sicuro == true)
return true;
else
return false;
}
//  End -->
</script>


<title>
<?php

$v_maris_repository="3.1";

echo "MARIS XDS REPOSITORY-B v$v_maris_repository SETUP"; //."   (".$_SESSION["idcode"].")";
	?></title>

</HEAD>


<?php

$delete_active = $_GET['delete'];
echo '<table width="100%" border=0 cellpadding="10" cellspacing="0">
<tr bgcolor="black"><td><img src="./img/logo+scritta.jpg"></td>
<td><div align="right"><img src="./img/logo-A-thon-XDS-repository.jpg"></div></td></tr>';
echo '<tr bgcolor="#FF8F10"><td colspan="2">';


$ip = $_SERVER['SERVER_NAME'];
$script = $_SERVER['PHP_SELF'];
$root = $_SERVER['DOCUMENT_ROOT'];


if($ip=="127.0.0.1" || $ip=="localhost"){
$repository_link="http://repository.ip".str_replace('setup.php', 'repository.php',$script);
$repository_get="http://repository.ip".str_replace('setup.php', 'getDocumentb.php',$script);
$repository_getxop="http://repository.ip".str_replace('setup.php', 'getDocumentbopt.php',$script);
$ip_new="repository.ip";
}
else {
$repository_link="http://".$ip.str_replace('setup.php', 'repository.php',$script);
$repository_get="http://".$ip.str_replace('setup.php', 'getDocumentb.php',$script);
$repository_getxop="http://".$ip.str_replace('setup.php', 'getDocumentbopt.php',$script);
$ip_new=$ip;
}

#################### REPOSITORY ###################

echo "<FORM name=\"myForm\" action=\"updatesetup.php\" method=\"POST\">";


$get_REP="SELECT HOST,PORT,HTTP FROM REPOSITORY WHERE ACTIVE = 'A'";


$res_REP = query_select2($get_REP,$connessione);


$REP_host = $res_REP[0][0];
$REP_port = $res_REP[0][1];
$REP_http = $res_REP[0][2];


echo "<h2>Repository-b v$v_maris_repository Setup</h2>";

echo "This version of MARiS XDS Repository is not certified as a commercial medical device (FDA or CE)<br><br>";

echo "The link to the repository you have to set in your software (XDS Source) is:";
echo "<br><b>".$repository_link."</b>";
echo "<br><br>";
echo "The link to the repository you have to set in your consumer (MTOM) is:";
echo "<br><b>".$repository_get."</b>";
echo "<br><br>";
echo "The link to the repository you have to set in your consumer (MTOM/XOP) is:";
echo "<br><b>".$repository_getxop."</b>";
echo "<br><br>";



$get_REP_config="SELECT LOG,CACHE,FILES,UNIQUEID,STATUS FROM CONFIG_B";
$res_REP_config = query_select2($get_REP_config,$connessione);

$REP_www = str_replace('setup.php','',$script);
$REP_log = $res_REP_config[0][0];
$REP_cache = $res_REP_config[0][1];
$REP_files = $res_REP_config[0][2];
$REP_uniqueID = $res_REP_config[0][3];
$REP_status = $res_REP_config[0][4];


echo "<h3>Repository Status</h3>";

if($REP_status=="A"){

	echo "Repository Status: <select name=\"repository_status\">
  	<option value=\"A\" selected=\"selected\">ON</option>
   	<option value=\"O\">OFF</option>
  	</select><br></br>";
	}
else {

	echo "Repository Status: <select name=\"repository_status\">
   	<option value=\"A\">ON</option>
  	<option value=\"O\" selected=\"selected\">OFF</option>
  	</select><br></br>";
	}

echo "<h3>Repository parameters</h3>";
echo "Repository Host: (".$ip_new.") <INPUT type=\"text\" name=\"repository_host\" value=\"$REP_host\" size=\"20\" maxlength=\"30\"><br></br>";
echo "Repository Port: (80 default) <INPUT type=\"text\" name=\"repository_port\" value=\"$REP_port\" size=\"4\" maxlength=\"10\"><br></br>";






echo "Repository UniqueID: <INPUT type=\"text\" name=\"repository_uniqueid\" value=\"$REP_uniqueID\" size=\"30\" maxlength=\"50\"><br></br>";

echo "<INPUT type=\"hidden\" name=\"repository_www\" value=\"$REP_www\" size=\"50\" maxlength=\"100\">";

if($REP_http=="NORMAL"){
	echo "Repository HTTP: <select name=\"repository_http\">
  	<option value=\"NORMAL\" selected=\"selected\">NORMAL</option>
   	<option value=\"TLS\">TLS</option>
  	</select><br></br>";
	}
else {
	echo "Repository HTTP: <select name=\"repository_http\">
   	<option value=\"NORMAL\">NORMAL</option>
  	<option value=\"TLS\" selected=\"selected\">TLS</option>
  	</select><br></br>";
	}

if($REP_log=="A"){
	echo "Log: <select name=\"repository_log\">
  	<option value=\"A\" selected=\"selected\">ON</option>
   	<option value=\"O\">OFF</option>
  	</select><br></br>";
	}
else {
	echo "Log: <select name=\"repository_log\">
   	<option value=\"A\">ON</option>
  	<option value=\"O\" selected=\"selected\">OFF</option>
  	</select><br></br>";
	}

if($REP_cache=="A"){
	echo "Delete tmp file: <select name=\"repository_cache\">
  	<option value=\"A\" selected=\"selected\">ON</option>
   	<option value=\"O\">OFF</option>
  	</select><br></br>";
	}
else {
	echo "Delete tmp file: <select name=\"repository_cache\">
   	<option value=\"A\">ON</option>
  	<option value=\"O\" selected=\"selected\">OFF</option>
  	</select><br></br>";
	}


if($REP_files=="L"){
	echo "Tmp Log Files: <select name=\"repository_files\">
  	<option value=\"L\" selected=\"selected\">LOW</option>
   	<option value=\"M\">MIDDLE</option>
	<option value=\"H\">HIGH</option>
  	</select><br></br>";
	}
else if($REP_files=="M"){
	echo "Tmp Log Files: <select name=\"repository_files\">
   	<option value=\"L\">LOW</option>
  	<option value=\"M\" selected=\"selected\">MIDDLE</option>
	<option value=\"H\">HIGH</option>
  	</select><br></br>";
	}

else {
	echo "Tmp Log Files: <select name=\"repository_files\">
   	<option value=\"L\">LOW</option>
  	<option value=\"M\">MIDDLE</option>
	<option value=\"H\" selected=\"selected\">HIGH</option>
  	</select><br></br>";
	}






#################### ATNA ####################

$get_ATNA="SELECT * FROM ATNA";

$res_ATNA = query_select2($get_ATNA,$connessione);

$ATNA_host = $res_ATNA[0][1];
$ATNA_port = $res_ATNA[0][2];
$ATNA_active = $res_ATNA[0][3];

echo "<h3>ATNA parameters</h3>";


if($ATNA_active=="A"){
echo "ATNA: <select name=\"repository_atna_status\">
   <option value=\"A\" selected=\"selected\">ON</option>
   <option value=\"O\">OFF</option>
  </select><br></br>";}
else {
echo "ATNA: <select name=\"repository_atna_status\">
   <option value=\"A\" >ON</option>
   <option value=\"O\" selected=\"selected\">OFF</option>
  </select><br></br>";}


echo "Repository ATNA Host: <INPUT type=\"text\" name=\"repository_atna_host\" value=\"$ATNA_host\" size=\"20\" maxlength=\"30\"><br></br>";
echo "Repository ATNA Port: <INPUT type=\"text\" name=\"repository_atna_port\" value=\"$ATNA_port\" size=\"4\" maxlength=\"10\"><br></br>";





#################### REGISTRY ####################

$get_REG="SELECT HOST,PORT,PATH,HTTP FROM REGISTRY_B WHERE ACTIVE = 'A'";


$res_REG = query_select($get_REG);

$REG_host = $res_REG[0][0];
$REG_port = $res_REG[0][1];
$REG_path = $res_REG[0][2];
$REG_http = $res_REG[0][3];

echo "<h3>Registry parameters</h3>";

echo "Registry Host: <INPUT type=\"text\" name=\"registry_host\" value=\"$REG_host\" size=\"20\" maxlength=\"30\"><br></br>";
echo "Registry Port: <INPUT type=\"text\" name=\"registry_port\" value=\"$REG_port\" size=\"4\" maxlength=\"10\"><br></br>";
echo "Registry Path: <INPUT type=\"text\" name=\"registry_path\" value=\"$REG_path\" size=\"50\" maxlength=\"100\"><br></br>";


if($REG_http=="NORMAL"){
echo "Registry HTTP: <select name=\"registry_http\">
   <option value=\"NORMAL\" selected=\"selected\">NORMAL</option>
   <option value=\"TLS\">TLS</option>
  </select><br></br>";}
else {
echo "Registry HTTP: <select name=\"registry_http\">
   <option value=\"NORMAL\">NORMAL</option>
   <option value=\"TLS\" selected=\"selected\">TLS</option>
  </select><br></br>";}

/*
#################### JAVA_PATH ###################

echo "<h3>JAVA HOME</h3>";
echo "JAVA_HOME could be /usr/lib/jvm/jre/bin/ or /usr/lib/jvm/java-xxx/bin/ or C:\\\\Programmi\\Java\\jre.xxx\\bin\\<br>";
echo "<INPUT type=\"text\" name=\"repository_java_home\" value=\"$REP_java\" size=\"50\" maxlength=\"100\"><br></br>";

*/







echo "<INPUT type=\"Submit\" value=\"Update\"><br></br>";

echo "</FORM>";

if($delete_active=="on"){
echo "<h3>Delete Repository</h3>";
echo "<FORM action=\"./DB_SERVICES/SVUOTA_REPOSITORY_DB.php\" method=\"POST\"> ";
echo "If you press button \"Delete TMP Files\" all tmp files will be lost";
echo "<INPUT type=\"hidden\" name=\"delete_repository\" value=\"tmp\"";
echo "<br>";
echo "<br>";
echo "<INPUT type=\"Submit\" value=\"Delete TMP Files\"><br></br>";
echo "</FORM>";


echo "<FORM action=\"./DB_SERVICES/SVUOTA_REPOSITORY_DB.php\" method=\"POST\" onSubmit=\"return conferma()\">";
echo "If you press button \"Delete Database\" all references of documents in the Repository Database will be lost";
echo "<INPUT type=\"hidden\" name=\"delete_repository\" value=\"database\"";
echo "<br>";
echo "<br>";
echo "<INPUT type=\"Submit\" value=\"Delete Database\"><br></br>";
echo "</FORM>";


echo "<FORM action=\"./DB_SERVICES/SVUOTA_REPOSITORY_DB.php\" method=\"POST\" >";
echo "If you press button \"Delete Submitted Documents\" all documents in the Repository will be deleted";
echo "<INPUT type=\"hidden\" name=\"delete_repository\" value=\"documents\"";
echo "<br>";
echo "<br>";
echo "<INPUT type=\"Submit\" value=\"Delete Submitted Documents\"><br></br>";
echo "</FORM>";
}

#################### KNOWN SOURCES ###################

$get_SOURCES="SELECT * FROM KNOWN_SOUCES_IDS";


$res_REP_SOURCES = query_select2($get_SOURCES,$connessione);

echo "<h3>Known Sources</h3>";

echo "<table>";
echo "<tr><td></td><td><b>Source ID</b></td><td><b>Source Description</b></td></tr>";
for($s=0;$s<count($res_REP_SOURCES);$s++)
{
echo "<form action=\"updatesource.php\" method=\"POST\">";
echo "<tr><td><INPUT type=\"image\" title=\"Delete Known Source\"  src=\"./img/delete.png\">
<INPUT type=\"hidden\" name=\"source_action\" value=\"delete\">
<INPUT type=\"hidden\" name=\"source_id\" value=\"".$res_REP_SOURCES[$s][0]."\"></td><td>
<INPUT type=\"text\" name=\"source_name\" value=\"".$res_REP_SOURCES[$s][1]."\" size=\"50\" maxlength=\"100\"></td><td>
<INPUT type=\"text\" name=\"source_description\" value=\"".$res_REP_SOURCES[$s][2]."\" size=\"50\" maxlength=\"100\"></td></tr>";
echo "</form>";
}
echo "<form action=\"updatesource.php\" method=\"POST\">";
echo "<tr><td><INPUT type=\"image\" title=\"Insert Known Source\" src=\"./img/add.png\">
<INPUT type=\"hidden\" name=\"source_action\" value=\"add\">
<INPUT type=\"hidden\" name=\"source_id\" value=\"\"></td><td>
<INPUT type=\"text\" name=\"source_name\" value=\"\" size=\"50\" maxlength=\"100\"></td><td>
<INPUT type=\"text\" name=\"source_description\" value=\"\" size=\"50\" maxlength=\"100\"></td></tr>";
echo "</form>";

echo "</table>";




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
?>