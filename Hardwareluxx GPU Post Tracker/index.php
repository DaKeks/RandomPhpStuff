<?php

// internal libxml errors -- turn on in production, off for debugging
libxml_use_internal_errors(true); 

// load dom; use page-1000 parameter to get always last page
$dom = new DomDocument;
$dom->loadHTMLFile("https://www.hardwareluxx.de/community/threads/die-besten-gpu-deals-aus-den-verf%C3%BCgbarkeitshinweisen.1303014/page-1000");

$xpath = new DomXPath($dom);
$nodes = $xpath->query('//article[contains(@class,"message-body")]');
$nodesDate = $xpath->query('//header[contains(@class,"message-attribution")]//time/@datetime'); //e.g. "2021-02-09T16:58:53+0100"

// if true site goes wild
$alert = false;

// how long site goes wild if alert was triggered in seconds
$alertDuration = 30;

// if script execution went as expected this will turn into "OK"
$status = "NOT OK";

// site check interval in seconds, default: 12, shorter interval will block your ip
$interval = 12;

// custom get parameters -> can be used to change values in production via url; e.g. example.com?i=15

// i => interval
if (array_key_exists("i", $_GET)) {
	$interval = (int)$_GET["i"];
}

// filter => addition text filter for name
$filter = "NO-FILTER";
if (array_key_exists("f", $_GET)) {
	$filter = (string)$_GET["f"];
}

// check latest post (script is stateless: in case there are two posts at the same time the last one will be checked only)
$lastPost = $nodes[($nodes->length)-1]->nodeValue;
$lastPostDatetime = $nodesDate[($nodesDate->length)-1]->nodeValue;

// create ISO8601 dateTime 
$lastPostDatetime = DateTime::createFromFormat(DateTime::ISO8601, $lastPostDatetime);
$lastPostDatetime -> setTimeZone(new DateTimeZone('Europe/Berlin'));

// search preparations
$searches      = ["Name", "Shop", "Anzahl", "Preis", "URL"]; // what is searched for
$results = array(); // result values will be stored here
$lines = preg_split('/\r\n|\r|\n/', $lastPost); // preparation for iteration through lines

// crazy string iteration to get values searched for
foreach ($searches as $search) {
	foreach ($lines as $noLine => $textLine)
	{
		if (strpos($textLine, $search) !== false) {
			
			// found at least one value -> script execution as expected, time to change status to "OK"
			$status = "OK";
			
			// add value to results array
			$results[$search] = str_replace($search . ': ', '', $textLine);
		}
	}
}

// remove ' *' at the end of url string
if (array_key_exists("URL",$results)) {
	$results["URL"] = str_replace(' *', '', $results["URL"]);
}

// remove euro sign and parse it
$results["Preis"] = (float)str_replace(" €", '', $results["Preis"]);


// EDIT HERE:
// alerting logic: where the magic happens

// e.g. everything from ASUS for 1k Euro or less ...
if (stripos($results["Name"], "ASUS" && $results["Preis"] <= 1000) !== false) $alert = true;
// ...  or any 3070 for 800 Euro or less
if (stripos($results["Name"], "3070" && $results["Preis"] <= 800) !== false) $alert = true;

// some other examples:

//if (stripos($results["Name"], "3080") !== false && stripos($results["Name"], "ASUS") !== false ) $alert = true;
//if (stripos($results["Name"], "3080") !== false && stripos($results["Name"], "VENTUS") !== false ) $alert = true;
//if (stripos($results["Name"], "asus") !== false) $alert = true;
//if (stripos($results["Name"], "VENTUS") !== false) $alert = true;
//if ($results["Preis"] <= 1100 && $results["Preis"] > 1) $alert = true;
//if (stripos($results["Shop"], "asus") !== false) $alert = true;
//if (stripos($results["Shop"], "saturn") !== false) $alert = true;
//if (stripos($results["Shop"], "mediamarkt") !== false) $alert = true;
//if (stripos($results["Shop"], "media markt") !== false) $alert = true;


// ... or you dont care about filters, always alert for new posts:
// $alert = true;


// filter
if (stripos($results["Name"], $filter) !== false) $alert = false;


$diff = (new DateTime())->getTimestamp() - $lastPostDatetime->getTimestamp();
if ($diff > $alertDuration) $alert = false; // no alarm if older x seconds

// test mode: if you are not sure if it works, test it with example.com?test
if (array_key_exists("test", $_GET)) {
	$alert = true;
}

?>
<html>
<head>
<title>HWLuxx GPU Check</title>
<META HTTP-EQUIV="refresh" CONTENT="<?php echo $interval; ?>; URL=index.php?i=<?php echo $interval; ?>&f=<?php echo $filter; ?>">

</head>

<?php

if ($alert) echo "<body style='background-color:red;'>";
else echo "<body>";

echo "LAST REFRESH: " . date("H:i:s", time());
echo "<br>STATUS: " . $status;


// ### ALERTS ###


if ($alert) {
	echo "<span style='font-size: 30px;'>";
	echo "<br><strong>" . $results["Name"] ."</strong>";
	echo "<br><strong>" . $results["Preis"] ."</strong>";
	echo "<br>" . $results["Shop"];
	echo "<br><a href='" . $results["URL"] . "'>" . $results["URL"] . "</a>";
	echo "</span>";
	
	?>
	
	<script type="text/javascript">
		function beep() {
		var snd = new Audio("data:audio/wav;base64,//uQRAAAAWMSLwUIYAAsYkXgoQwAEaYLWfkWgAI0wWs/ItAAAGDgYtAgAyN+QWaAAihwMWm4G8QQRDiMcCBcH3Cc+CDv/7xA4Tvh9Rz/y8QADBwMWgQAZG/ILNAARQ4GLTcDeIIIhxGOBAuD7hOfBB3/94gcJ3w+o5/5eIAIAAAVwWgQAVQ2ORaIQwEMAJiDg95G4nQL7mQVWI6GwRcfsZAcsKkJvxgxEjzFUgfHoSQ9Qq7KNwqHwuB13MA4a1q/DmBrHgPcmjiGoh//EwC5nGPEmS4RcfkVKOhJf+WOgoxJclFz3kgn//dBA+ya1GhurNn8zb//9NNutNuhz31f////9vt///z+IdAEAAAK4LQIAKobHItEIYCGAExBwe8jcToF9zIKrEdDYIuP2MgOWFSE34wYiR5iqQPj0JIeoVdlG4VD4XA67mAcNa1fhzA1jwHuTRxDUQ//iYBczjHiTJcIuPyKlHQkv/LHQUYkuSi57yQT//uggfZNajQ3Vmz+Zt//+mm3Wm3Q576v////+32///5/EOgAAADVghQAAAAA//uQZAUAB1WI0PZugAAAAAoQwAAAEk3nRd2qAAAAACiDgAAAAAAABCqEEQRLCgwpBGMlJkIz8jKhGvj4k6jzRnqasNKIeoh5gI7BJaC1A1AoNBjJgbyApVS4IDlZgDU5WUAxEKDNmmALHzZp0Fkz1FMTmGFl1FMEyodIavcCAUHDWrKAIA4aa2oCgILEBupZgHvAhEBcZ6joQBxS76AgccrFlczBvKLC0QI2cBoCFvfTDAo7eoOQInqDPBtvrDEZBNYN5xwNwxQRfw8ZQ5wQVLvO8OYU+mHvFLlDh05Mdg7BT6YrRPpCBznMB2r//xKJjyyOh+cImr2/4doscwD6neZjuZR4AgAABYAAAABy1xcdQtxYBYYZdifkUDgzzXaXn98Z0oi9ILU5mBjFANmRwlVJ3/6jYDAmxaiDG3/6xjQQCCKkRb/6kg/wW+kSJ5//rLobkLSiKmqP/0ikJuDaSaSf/6JiLYLEYnW/+kXg1WRVJL/9EmQ1YZIsv/6Qzwy5qk7/+tEU0nkls3/zIUMPKNX/6yZLf+kFgAfgGyLFAUwY//uQZAUABcd5UiNPVXAAAApAAAAAE0VZQKw9ISAAACgAAAAAVQIygIElVrFkBS+Jhi+EAuu+lKAkYUEIsmEAEoMeDmCETMvfSHTGkF5RWH7kz/ESHWPAq/kcCRhqBtMdokPdM7vil7RG98A2sc7zO6ZvTdM7pmOUAZTnJW+NXxqmd41dqJ6mLTXxrPpnV8avaIf5SvL7pndPvPpndJR9Kuu8fePvuiuhorgWjp7Mf/PRjxcFCPDkW31srioCExivv9lcwKEaHsf/7ow2Fl1T/9RkXgEhYElAoCLFtMArxwivDJJ+bR1HTKJdlEoTELCIqgEwVGSQ+hIm0NbK8WXcTEI0UPoa2NbG4y2K00JEWbZavJXkYaqo9CRHS55FcZTjKEk3NKoCYUnSQ0rWxrZbFKbKIhOKPZe1cJKzZSaQrIyULHDZmV5K4xySsDRKWOruanGtjLJXFEmwaIbDLX0hIPBUQPVFVkQkDoUNfSoDgQGKPekoxeGzA4DUvnn4bxzcZrtJyipKfPNy5w+9lnXwgqsiyHNeSVpemw4bWb9psYeq//uQZBoABQt4yMVxYAIAAAkQoAAAHvYpL5m6AAgAACXDAAAAD59jblTirQe9upFsmZbpMudy7Lz1X1DYsxOOSWpfPqNX2WqktK0DMvuGwlbNj44TleLPQ+Gsfb+GOWOKJoIrWb3cIMeeON6lz2umTqMXV8Mj30yWPpjoSa9ujK8SyeJP5y5mOW1D6hvLepeveEAEDo0mgCRClOEgANv3B9a6fikgUSu/DmAMATrGx7nng5p5iimPNZsfQLYB2sDLIkzRKZOHGAaUyDcpFBSLG9MCQALgAIgQs2YunOszLSAyQYPVC2YdGGeHD2dTdJk1pAHGAWDjnkcLKFymS3RQZTInzySoBwMG0QueC3gMsCEYxUqlrcxK6k1LQQcsmyYeQPdC2YfuGPASCBkcVMQQqpVJshui1tkXQJQV0OXGAZMXSOEEBRirXbVRQW7ugq7IM7rPWSZyDlM3IuNEkxzCOJ0ny2ThNkyRai1b6ev//3dzNGzNb//4uAvHT5sURcZCFcuKLhOFs8mLAAEAt4UWAAIABAAAAAB4qbHo0tIjVkUU//uQZAwABfSFz3ZqQAAAAAngwAAAE1HjMp2qAAAAACZDgAAAD5UkTE1UgZEUExqYynN1qZvqIOREEFmBcJQkwdxiFtw0qEOkGYfRDifBui9MQg4QAHAqWtAWHoCxu1Yf4VfWLPIM2mHDFsbQEVGwyqQoQcwnfHeIkNt9YnkiaS1oizycqJrx4KOQjahZxWbcZgztj2c49nKmkId44S71j0c8eV9yDK6uPRzx5X18eDvjvQ6yKo9ZSS6l//8elePK/Lf//IInrOF/FvDoADYAGBMGb7FtErm5MXMlmPAJQVgWta7Zx2go+8xJ0UiCb8LHHdftWyLJE0QIAIsI+UbXu67dZMjmgDGCGl1H+vpF4NSDckSIkk7Vd+sxEhBQMRU8j/12UIRhzSaUdQ+rQU5kGeFxm+hb1oh6pWWmv3uvmReDl0UnvtapVaIzo1jZbf/pD6ElLqSX+rUmOQNpJFa/r+sa4e/pBlAABoAAAAA3CUgShLdGIxsY7AUABPRrgCABdDuQ5GC7DqPQCgbbJUAoRSUj+NIEig0YfyWUho1VBBBA//uQZB4ABZx5zfMakeAAAAmwAAAAF5F3P0w9GtAAACfAAAAAwLhMDmAYWMgVEG1U0FIGCBgXBXAtfMH10000EEEEEECUBYln03TTTdNBDZopopYvrTTdNa325mImNg3TTPV9q3pmY0xoO6bv3r00y+IDGid/9aaaZTGMuj9mpu9Mpio1dXrr5HERTZSmqU36A3CumzN/9Robv/Xx4v9ijkSRSNLQhAWumap82WRSBUqXStV/YcS+XVLnSS+WLDroqArFkMEsAS+eWmrUzrO0oEmE40RlMZ5+ODIkAyKAGUwZ3mVKmcamcJnMW26MRPgUw6j+LkhyHGVGYjSUUKNpuJUQoOIAyDvEyG8S5yfK6dhZc0Tx1KI/gviKL6qvvFs1+bWtaz58uUNnryq6kt5RzOCkPWlVqVX2a/EEBUdU1KrXLf40GoiiFXK///qpoiDXrOgqDR38JB0bw7SoL+ZB9o1RCkQjQ2CBYZKd/+VJxZRRZlqSkKiws0WFxUyCwsKiMy7hUVFhIaCrNQsKkTIsLivwKKigsj8XYlwt/WKi2N4d//uQRCSAAjURNIHpMZBGYiaQPSYyAAABLAAAAAAAACWAAAAApUF/Mg+0aohSIRobBAsMlO//Kk4soosy1JSFRYWaLC4qZBYWFRGZdwqKiwkNBVmoWFSJkWFxX4FFRQWR+LsS4W/rFRb/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////VEFHAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAU291bmRib3kuZGUAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAMjAwNGh0dHA6Ly93d3cuc291bmRib3kuZGUAAAAAAAAAACU=");  
		snd.play();
		}
		beep();
		setTimeout(() => { beep(); }, 1000);
		setTimeout(() => { beep(); }, 2000);
		
	</script>
	
	<?php
}

?>

</body>
</html>