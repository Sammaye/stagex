<?php
/**
 * All this script does is cURL Google telling them we have changed our sitemap once every hour
 */
$ch = curl_init("www.google.com/webmasters/tools/ping?sitemap=".'http://www.stagex.co.uk/site_map.xml');
curl_setopt($ch, CURLOPT_HEADER, 0);
//curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$output = curl_exec($ch);
curl_close($ch);
echo $output;
