#!/usr/bin/php
<?
error_reporting(0);

$pluginName ="Election";
$myPid = getmypid();

$messageQueue_Plugin = "MessageQueue";
$MESSAGE_QUEUE_PLUGIN_ENABLED=false;

$DEBUG=true;

$skipJSsettings = 1;
require_once("/opt/fpp/www/config.php");
require_once("/opt/fpp/www/common.php");
//include_once("/opt/fpp/www/plugin.php");

include_once("functions.inc.php");
include_once "electionData.inc.php";
require ("lock.helper.php");

define('LOCK_DIR', '/tmp/');
define('LOCK_SUFFIX', '.lock');



$pluginConfigFile = $settings['configDirectory'] . "/plugin." .$pluginName;
if (file_exists($pluginConfigFile))
	$pluginSettings = parse_ini_file($pluginConfigFile);


//print_r($pluginSettings);


$logFile = $settings['logDirectory']."/".$pluginName.".log";

$messageQueuePluginPath = $pluginDirectory."/".$messageQueue_Plugin."/";

$messageQueueFile = urldecode(ReadSettingFromFile("MESSAGE_FILE",$messageQueue_Plugin));

if(($pid = lockHelper::lock()) === FALSE) {
	exit(0);

}

if(file_exists($messageQueuePluginPath."functions.inc.php"))
	{
		include $messageQueuePluginPath."functions.inc.php";
		$MESSAGE_QUEUE_PLUGIN_ENABLED=true;

	} else {
		logEntry("Message Queue Plugin not installed, some features will be disabled");
	}	


//if($MESSAGE_QUEUE_PLUGIN_ENABLED) {
//	$queueMessages = getNewPluginMessages("SMS");
//	print_r($queueMessages);
//} else {
//	logEntry("MessageQueue plugin is not enabled/installed");
//}	

//	print_r($pluginSettings);
//	echo "\n";
	
	//$SPORTS = urldecode(ReadSettingFromFile("SPORTS",$pluginName));
	$VOTES = urldecode($pluginSettings['VOTES']);
	
	//$ENABLED = urldecode(ReadSettingFromFile("ENABLED",$pluginName));
	$ENABLED = urldecode($pluginSettings['ENABLED']);
	
	//$SEPARATOR = urldecode(ReadSettingFromFile("SEPARATOR",$pluginName));
	$SEPARATOR = urldecode($pluginSettings['SEPARATOR']);
	
	//$LAST_READ = urldecode(ReadSettingFromFile("LAST_READ",$pluginName));
	$LAST_READ = $pluginSettings['LAST_READ'];

	//echo "enabled: ".$ENABLED."\n";
	
//echo "ENABLED: ".$ENABLED."\n";
if($ENABLED != "1") {
	logEntry("Plugin Status: DISABLED Please enable in Plugin Setup to use & Restart FPPD Daemon");
	lockHelper::unlock();
	exit(0);
	
}

//$SEPARATOR = urldecode(ReadSettingFromFile("SEPARATOR",$pluginName));

$VOTES_READ = explode(",",$VOTES);

//echo "Incoming sports reading: \n";
//print_r($SPORTS_READ);
//print_r($SPORTS_DATA_ARRAY);

$messageText="";


for($i=0;$i<=count($VOTES_READ)-1;$i++) {
	//echo "Retrieving data for: ".$SPORTS_READ[$i]."\n";
	
	if( search_in_array($VOTES_READ[$i],$VOTES_DATA_ARRAY) > 0) {
		
		//echo $SPORTS_READ[$i]. " is in Sports data array\n";
			
		//fetch the information
		$votesData = file_get_contents($VOTES_DATA_ARRAY[$i][1]);
		
		if($DEBUG)
			print_r($votesData);
		
		


	$new = str_replace('&',"|",$votesData);
	//$new = str_replace('&',"|",$output);

	$votes = explode('&',$votesData);

//print_r($stats);

	foreach($votes as $item) {

		$split = explode("=",$item);

		$left = $split[0];
		$right = (string)urldecode($split[1]);


		if(substr($left,0,10) == strtolower($VOTES_READ[$i]."_s_left")) {

		//echo $right."<br/>";
	//echo $split[0]." --- ".$right."<br/>";

		if(substr($right,0,1) == "^") {
			$right = substr($right,1);
		}

		if(trim($right) !="") {
			$messageText .= " ".$SEPARATOR." ".$right;
		}
	}
	}
	
	//there gets some ^ in the output.. erase them!
	$messageText = preg_replace('/\^/', '', $messageText);
	$messageText = preg_replace('/\s[a]t\s/', ' @ ', $messageText);
	
	if(trim($messageText) == "" ) {
		$messageLine = $VOTES_READ[$i]." - No Election Data Available";
	} else {
	
		$messageLine = $VOTES_READ[$i]." ".$messageText;
	}
	addNewMessage($messageLine,$pluginName,$pluginData=$VOTES_READ[$i]);
	$messageText="";
	$messageLine="";
	}
	
}

function search_in_array($value, $arr){

	$num = 0;
	for ($i = 0; $i < count($arr); ) {
		if($arr[$i][0] == $value) {
			$num++;
		}
		$i++;
	}
	return $num ;
}
lockHelper::unlock();
?>
