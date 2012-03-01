<?php
/* 	TwitterRSS-Enhancer - By: Craig Van Der Linden (cvanderlinden)
** 	Created February 23rd - 2012
** 	Last Modifed By: Craig Van Der Linden on Feb 23, 2012
**	Description: 
**	Imports a twitter RSS, parses the RSS into an object,
**	rewrites all the links, usernames, and hashtags to be links
** 	outputs the rest to screen and to the twitter.rss file.
*/

/******** USER CONFIGURATION ********/
// ENTER THE TWITTER USERNAME HERE
$screenname = 'test';
$length = strlen($screenname) + 2;

// Required for parsing the original RSS feed
// Download at https://github.com/collegeman/coreylib
require_once('coreylib/coreylib.php');

// Defined XML1 encoding constant
defined("ENT_XML1") or define("ENT_XML1",16);

// Get the RSS feed object
$string = 'http://api.twitter.com/1/statuses/user_timeline.rss?screen_name=' . $screenname;
$api = new clApi($string);

// Delete file if exists
$filePath = "twitter.rss";
if (file_exists($filePath)) {
    unlink($filePath);
}

// The header for the new RSS feed
$rss = '<?xml version="1.0" encoding="UTF-8" ?>
        <rss version="2.0">
            <channel>
                <title>Twitter / ' . $screenname . '</title>
                <link>http://twitter.com/' . $screenname . '</link>
                <description>Twitter updates from ' . $screenname . '</description>
                <language>en-us</language>
                <ttl>40</ttl>
            ';
// Parse the RSS feed into individual objects and manipulate them
if ($feed = $api->parse()) {
    // Now we have data...
    // Get the high level object, in this case, this is called item
    foreach ($feed->get('item') as $entry) {
        // Get the title/content of the tweet
        $title = $entry->get('title');
        $desc  = $entry->get('description');
        
        // Strip the first $length characters
        $title = substr($title, $length);
        $desc  = substr($desc, $length);
        
        // Replace all links with HTML links
		$desc = preg_replace("/([\w]+:\/\/[\w-?&;#~=\.\/\@]+[\w\/])/i","<a target=\"_blank\" href=\"$1\">$1</a>", $desc);
		// Replace all usernames with a linked username
        $desc = preg_replace('/@(\w+)\b/i', '<a target="_blank" href="http://twitter.com/$1">@$1</a>', $desc);
		// Replace all hashtags with linked hashtags
		$desc = preg_replace('/#(\w+)\b/i', '<a target="_blank" href="http://twitter.com/#!/search/%23$1">#$1</a>', $desc);
		
        // & is a bad character in XML encoding
        $title = str_replace('&', '&amp;', $title);
        $desc  = str_replace('&', '&amp;', $desc);
		
		// XML Encode the description for proper output
		$desc = htmlspecialchars($desc, ENT_XML1);
        
        // Get the link for the tweet
        $link = $entry->get('link');
        
        // Get the date the tweet as published
        $date = $entry->get('pubDate');
        
		// Build the output
        $rss .= '<item>
                    <title>' . $title . '</title>
                    ';
        $rss .= '<description>' . $desc . '</description>
                ';
        $rss .= '    <pubDate>' . $date . '</pubDate>
                ';
        $rss .= '    <guid>' . $link . '</guid>
                ';
        $rss .= '    <link>' . $link . '</link>
                </item>
                ';
    }
    
} else {
    // something went wrong
}
// Close the entire rss feed
$rss .= '</channel>
    </rss>';
	
// Print it
$fh = fopen($filePath, 'w') or die("can't open file");
fwrite($fh, $rss);
echo $rss;

// The header
header("Content-Type: application/xml; charset=ISO-8859-1");
?>
