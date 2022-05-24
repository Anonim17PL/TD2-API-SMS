<?php
ini_set('display_errors', 1);
$strecho;
$nrpoc;
$username_smsgateway="SMSGATEWAY_USER";
$passwd_smsgateway="SMSGATEWAY_PASSWORD";
$number = $_GET["telephone"];
$text = $_GET["text"];
$command = substr($text, 0, 4);

if (strcasecmp($command, "poc ") == 0) {
	$nrpoc = substr($text, 4);
}
if ($nrpoc != null) {
	$data = array('username' => $username_smsgateway,'password' => $passwd_smsgateway,'number' => $number, 'text' => "Brak pociągu o podanym numerze.\nThere is no train with the given number.");
	$options = array(
		'http' => array(
		'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
		'method'  => 'POST',
		'content' => http_build_query($data),
	    )
	);

	$context  = stream_context_create($options);
	$result = file_get_contents("http://192.168.1.1:80/cgi-bin/sms_send",false,$context);
	echo $result;
}else {
	$json = file_get_contents("https://api.td2.info.pl:9640/?method=readFromSWDR&value=getTimetable;".$nrpoc.";eu");
	$ja = json_decode($json,true)["message"];
	if ($ja["trainInfo"] === null) {
	
	$TInfo = $ja["trainInfo"];
	$RJ = $ja["stopPoints"];
	$TrainString = $TInfo["trainCategoryCode"].$TInfo["trainNo"];
	$TrainRouteString = $TInfo["route"];
	$hhprev=0; $mmprev=0; $offset=0; $nextday=0;
	$i = 0;
	$breakloop = 0;
	$PointName = array();
	$pointNameisBold = array();
	$ArrivalTimeWString = array();
	$DepartureTimeWString = array();
	while ($breakloop == 0) {
		$NameRAW = $RJ[$i]["pointNameRAW"];
		if (1 == 1) {
		    while (substr($NameRAW, 0, 3) == "SBL" || substr($NameRAW, 0, 3) == "sbl") {
		        $i++;
		        $offset--;
		        $NameRAW = $RJ[$i]["pointNameRAW"];
		    }
		}
		if (false === strpos($RJ[$i]["pointName"],"<strong>")) { $PointName[$i+$offset] = "".$NameRAW.""; }
		else {$PointName[$i+$offset] = $NameRAW;}
		$hh=0;
		$mm=0;
		$ss=0;
		$timeam=0;
		$timedm=0;
		if (is_null($RJ[$i]["arrivalTime"]) === false) {
		    $Arrival = $RJ[$i]["arrivalTime"];
		    $ArrivalTime = new DateTime($RJ[$i]["arrivalTime"]);
		    $ArrivalTime->setTimezone(new DateTimeZone('Europe/Warsaw'));
		    $hh = $ArrivalTime->format("H");
		    $mm = $ArrivalTime->format("i");
		    $ss = $ArrivalTime->format("s");
		    $smm;
		    $shh;

		    $shh = $hh;
		    $smm = $mm;
		    
		    $ArrivalTimeWString[$i + $offset] = ("P: " . $shh . ":" . $smm); //Godznia przyjazdu
		}
		else {
		    $ArrivalTimeWString[$i + $offset] = " ";
		    $hh = 0;
		    $mm = 0;
		    $ss = 0;
		}
		$Dhh=0; $Dmm=0; $Dss=0;
		//MessageBox(NULL, (LPCWSTR)L"dep in", (LPCWSTR)L"DEBUG", 0x00000000L);
		if (is_null($RJ[$i]["departureTime"]) === false) {
		    $Dep = $RJ[$i]["departureTime"];
		    $DepTime = new DateTime($RJ[$i]["departureTime"]);
		    $DepTime->setTimezone(new DateTimeZone('Europe/Warsaw'));
		    $Dhh = $DepTime->format("H");
		    $Dmm = $DepTime->format("i");
		    $Dss = $DepTime->format("s");
		    $smm;
		    $shh;

		    $shh = $Dhh;
		    $smm = $Dmm;

		    $DepartureTimeWString[$i + $offset] = ("O: " . $shh . ":" . $smm);
		}
		else {
		    $DepartureTimeWString[$i + $offset] = " ";
		    $Dhh = 0;
		    $Dmm = 0;
		    $Dss = 0;
		    $breakloop = 1;
		}
		$i = $i + 1;
	}

	$strecho = "";
	for ($j = 0; $j < $i+$offset; $j++) {
		$strecho .= $PointName[$j]."\n";

		$strecho .= $ArrivalTimeWString[$j]." ";
		$strecho .= $DepartureTimeWString[$j]."\n";
		$strecho .= "\n";
	}
$data = array('username' => $username_smsgateway,'password' => $passwd_smsgateway,'number' => $number, 'text' => $strecho);
$options = array(
        'http' => array(
        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
        'method'  => 'POST',
        'content' => http_build_query($data),
    )
);

$context  = stream_context_create($options);
$result = file_get_contents("http://192.168.1.1:80/cgi-bin/sms_send",false,$context);
echo $result;
}
}
die();
?>
