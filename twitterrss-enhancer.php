<?php
/* 	TwitterRSS-Enhancer - By: Craig Van Der Linden (cvanderlinden)
** 	Created February 23rd - 2012
** 	Last Modifed By: Craig Van Der Linden on Feb 23, 2012
**	Description: 
**	Imports a twitter RSS, parses the RSS into an object,
**	rewrites all the links, usernames, and hashtags to be links
** 	outputs the rest to screen and to the twitter.rss file.
**  Uses OAuth
*/

/******** USER CONFIGURATION ********/
$screenName = "";
$tweetCount = 5;
$oauth_access_token = "";
$oauth_access_token_secret = "";
$consumer_key = "";
$consumer_secret = "";
/******** USER CONFIGURATION ********/

/******** OAUTH CONNECTION ********/
function buildBaseString($baseURI, $method, $params) {
    $r = array();
    ksort($params);
    foreach($params as $key=>$value){
        $r[] = "$key=" . rawurlencode($value);
    }
    return $method."&" . rawurlencode($baseURI) . '&' . rawurlencode(implode('&', $r));
}

function buildAuthorizationHeader($oauth) {
    $r = 'Authorization: OAuth ';
    $values = array();
    foreach($oauth as $key=>$value)
        $values[] = "$key=\"" . rawurlencode($value) . "\"";
    $r .= implode(', ', $values);
    return $r;
}

$oauth = array( 'oauth_consumer_key' => $consumer_key,
                'oauth_nonce' => time(),
                'oauth_signature_method' => 'HMAC-SHA1',
                'oauth_token' => $oauth_access_token,
                'oauth_timestamp' => time(),
                'oauth_version' => '1.0');
/******** OAUTH CONNECTION ********/

/****** TIMELINE START ******/

// API URL for requesting user's timeline
$url = "https://api.twitter.com/1.1/statuses/user_timeline.json";

//	Build OAuth string for request
$base_info = buildBaseString($url, 'GET', $oauth);
$composite_key = rawurlencode($consumer_secret) . '&' . rawurlencode($oauth_access_token_secret);
$oauth_signature = base64_encode(hash_hmac('sha1', $base_info, $composite_key, true));
$oauth['oauth_signature'] = $oauth_signature;

// Make request
$header = array(buildAuthorizationHeader($oauth), 'Expect:');
$options = array( CURLOPT_HTTPHEADER => $header,
				  //CURLOPT_POSTFIELDS => $postfields,
                  CURLOPT_HEADER => false,
                  CURLOPT_URL => $url,
                  CURLOPT_RETURNTRANSFER => true,
                  CURLOPT_SSL_VERIFYPEER => false);

$feed = curl_init();
curl_setopt_array($feed, $options);
$json = curl_exec($feed);
curl_close($feed);

// Decode JSON Object
$twitter_data = json_decode($json);

// $rss is the HTML output string
$rss = '<ul>';
$count = 0;

for ($i = 0; $i <= $tweetCount; $i++) {
	// Tweet Text
	$desc  = $twitter_data[$i]->text;
	// Build link back
	$link  = $twitter_data[$i]->id_str;
	$link  = "https://twitter.com/RoyalRoads/status/" . $link;
	// Date tweet posted
	$date = $twitter_data[$i]->created_at;
	
	// Replace all links with HTML links
	$desc = preg_replace("/([\w]+:\/\/[\w-?&;#~=\.\/\@]+[\w\/])/i","<a target=\"_blank\" href=\"$1\">$1</a>", $desc);
	// Replace all usernames with a linked username
	$desc = preg_replace('/@(\w+)\b/i', '<a target="_blank" href="http://twitter.com/$1">@$1</a>', $desc);
	// Replace all hashtags with linked hashtags
	$desc = preg_replace('/#(\w+)\b/i', '<a target="_blank" href="http://twitter.com/#!/search/%23$1">#$1</a>', $desc);
	
	// Get the current date/time
	$curDate = date("r");
	
	// Subtract the times to get timeAgo
	$diff = strtotime($curDate) - strtotime($date);
	
	// Initialize and clean the textAgo varible
	$textAgo = "";
	
	// Convert date timestap into years, months, days, hours, minutes
	$years   = floor($diff / (365*60*60*24)); 
	$months  = floor(($diff - $years * 365*60*60*24) / (30*60*60*24)); 
	$days    = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24)/ (60*60*24));
	$hours   = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24 - $days*60*60*24)/ (60*60));
	$minutes = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24 - $days*60*60*24 - $hours*60*60)/ 60);
	
	// Add in time denominations to textAgo
	if ($years > 0) {
		if ($years > 1) {
			$textAgo .= $years . " years ";
		}
		else {
			$textAgo .= $years . " year ";
		}
	}
	if ($months > 0) {
		if ($months > 1) {
			$textAgo .= $months . " months ";
		}
		else {
			$textAgo .= $months . " month ";
		}
	}
	if ($days > 0) {
		if ($days > 1) {
			$textAgo .= $days . " days ";
		}
		else {
			$textAgo .= $days . " day ";
		}
	}
	if ($hours > 0) {
		if ($hours > 1) {
			$textAgo .= $hours . " hours ";
		}
		else {
			$textAgo .= $hours . " hour ";
		}
	}
	if ($minutes > 0) {
		if ($minutes > 1) {
			$textAgo .= $minutes . " minutes ";
		}
		else {
			$textAgo .= $minutes . " minute ";
		}
	}
	if ($minutes == 0 && $hours == 0 && $days == 0 && $months == 0 && $years == 0) {
		$textAgo .= " a few seconds ";
	}
	// Build final output
	$rss .= '<li>' . $desc . '<span><a href="' . $link . '"> ' . $textAgo . '</a>ago by <a target="blank" href="https://twitter.com/#!/' . $screenName . '">' . $screenName . '</a></span></li>';
	$count++;
}

// Close the list with a </ul> tag
$rss .= '</ul>';

echo $rss;

/* 
** FILE WRITE	
** Delete file if exists
** Please uncomment below if you wish for the file to be written
** Also specifiy the path.
** Normal functionality will display text to screen
*/

//$filePath = "../../files/twitter.html";
//if (file_exists($filePath)) {
//	unlink($filePath);
//}	
// Send to file, and echo to screen
//$fh = fopen($filePath, 'w') or die("can't open file");
//fwrite($fh, $rss);

/****** TIMELINE END ******/
?>