<?php
/** Entry Point for Crypto key - Utilises browser caching so only have to send to the client once per key session
*
* Originally had to send twice because expiry was tied to the user's CredLocker session, 
* meaning Chrome requested twice (expiry of 0 epoch was sent by the login form). Firefox exhibited an odd behaviour and
* ignored the expiry date, so only requested once.
*
*
* Although not currently implemented, this file will eventually define the following
*
*  - Encryption key for received data
*  - Encryption key for sent data
*  - Delimiter to use for API requests
*  - API terminology to use (allowing us to replace known calls such as retCred with a random string)
* 
*
*
* Copyright (C) 2012 B Tasker
* Released under GNU AGPL V3
* See LICENSE
*
*/


// Using gzhandler broke on a few systems. A lot seem to automatically gzip where possible though, so removed gzhandler call
ob_start();
error_reporting(0);

session_start();
define('_CREDLOCK',1);

// Change the current working dir
chdir(dirname(__FILE__)."/../");

// Load the framework
require_once 'lib/Framework/main.php';


if (isset($_COOKIE['PHPCredLockerKeySet']) && BTMain::getVar('destSession') == 'Y'){

$expires = strtotime("-2 days");

// See PHPCRED-28
$conf = BTMain::getConf();
$host = (!empty($conf->CredlockerHost) && ($conf->CredlockerHost != 'DEFAULT'))? $conf->CredlockerHost : $_SERVER['HTTP_HOST'];

setcookie("PHPCredLockerKeySet", 1, $expires, dirname($_SERVER["REQUEST_URI"]), $host, $conf->forceSSL);
BTMain::unsetSessVar('tls');
BTMain::unsetSessVar('KeyExpiry');
BTMain::unsetSessVar('apiterms');

$_COOKIE['PHPCredLockerKeySet'] = 0;

}




$tls = BTMain::getSessVar('tls');
$expiry = BTMain::getSessVar('KeyExpiry');



$apiterms = array(
			"retCred",
			"checkSess",
			"delCred",
			"delUser",
			"delCredType",
			"delCust",
			"delGroup"
				    );




// We only to do key generation if  we're not on a SSL connection or if the system is configured to force use
if (!BTMain::getConnTypeSSL() || BTMain::getConf()->forceTLS){


// If the key is still valid and we know the browser has already retrieved it, just tell the browser to use the cache
if  ((BTMain::getVar('forceload') != 'y') && (time() < $expiry) && ($_COOKIE['PHPCredLockerKeySet'] == 1) && ($expiry) && (!empty($tls))){
header("HTTP/1.1 304 Not Modified");
die;
}

// Would actually prefer not to include this in an unauthenticated session, but want to put key generation in the most logical place.
require_once 'lib/crypto.php';



// Set MIME-Header
header("Content-Type: text/javascript");





if (isset($_COOKIE['PHPCredLocker'])):

	  foreach ($apiterms as $term){

		$x = 0;
		$new = '';
		$termlength = mt_rand(4,15);


		while ($x <= $termlength){
		      $new .= chr(mt_rand(97,122));
			  if (($x == $termlength) && in_array($new,$usedterms)){
			  // Make sure the termcode isn't already in used, if so, start again
			  $x = 0;
			  $new = '';
		      }
		  $x++;
		}	

		$usedterms[] = $new;
		$terms[$new] = $term;
      }


	  $expiry = strtotime('+10 minutes');
	  $seconds_to_cache = $expiry - time();
	  $gmt = gmdate("D, d M Y H:i:s", $expiry) . " GMT";

	  // Set caching headers
	  header("Expires: $gmt");
	  header("Pragma: cache");
	  header("Cache-Control: Private, max-age=$seconds_to_cache");

	  // Add the key and it's expiry to the session
	  BTMain::setSessVar('KeyExpiry',$expiry);
	  BTMain::setSessVar('tls',Crypto::genxorekey());
	  BTMain::setSessVar('apiterms',$terms);

	  // By setting a cookie, we provide an easy mechanism for allowing the API to force a key refresh
	  setcookie("PHPCredLockerKeySet", 1, $expiry, dirname($_SERVER["REQUEST_URI"]), $_SERVER['HTTP_HOST'], BTMain::getConf()->forceSSL);

      endif;

      // We use a different method to generate Auth keys - in case a pattern does somehow appear in the TLS generation stuff we don't want anyone to be
      // able to view those keys without a valid login (at which point they won't really need to do key analysis!)

      $x = 0;
      $str = '';
      $str2 = '';

      while ($x < 40){

      $str .= chr(rand(33,126)) .mt_rand(16,45);
      $str2 .= chr(rand(33,126)) .mt_rand(16,45);
      $x++;

      }


      BTMain::setSessVar('AuthKey',rtrim(base64_encode($str),"=") . ":" . rtrim(base64_encode($str2),"="));
      $enabled = 'true';



}else{
// We don't need to generate keys as we're on a SSL connection

     foreach ($apiterms as $value){
	$terms[$value] = $value;
      }

     BTMain::setSessVar('apiterms',$terms);
     BTMain::setSessVar('tls',' ');
     BTMain::setSessVar('AuthKey',' ');
     $enabled = 'false';



    $expiry = strtotime('+1 day');
    $seconds_to_cache = $expiry - time();
    $gmt = gmdate("D, d M Y H:i:s", $expiry) . " GMT";

    // Set caching headers
    header("Expires: $gmt");
    header("Pragma: cache");
    header("Cache-Control: Private, max-age=$seconds_to_cache");

}



ob_start();
?> 
function getTLSKey(){ return '<?php echo base64_encode(BTMain::getSessVar('tls'));?>'; }


function getDelimiter(){ return "|..|";}


function getTerminology(a){ 

if (a == 'undefined' || a == 'null' || a == ''){
return;}



return this[a];
 }



function getAuthKey(){
return '<?php echo base64_encode(BTMain::getSessVar('AuthKey')); ?>';
}


function destroyKeys(){
window.getKey = '';
window.getDelimiter = '';
window.getTerminology = '';
window.getAuthKey = '';
return window.destroyKeys = '';
}

function enabledTLSEncryption(){
return <?php echo $enabled;?>;
}

function setStorage(){
  if(typeof(Storage)!=="undefined"){
	sessionStorage.setItem('key', getTLSKey());
	sessionStorage.setItem('Terminology','{<?php foreach ($terms as $key=>$value){ echo "\"$value\":\"".base64_encode($key) ."\","; }?>"null":"null"}');
	sessionStorage.setItem('CryptEnabled',enabledTLSEncryption());
	sessionStorage.setItem('Delimiter',getDelimiter());
    }else{
    alert("You're using an out of date browser, this has a serious impact on security. Please use at least IE8, Firefox, Chrome or Safari");
    }
}




setStorage();
new getTerminology();
<?php



echo str_replace("\n","",ob_get_clean());

ob_end_flush();