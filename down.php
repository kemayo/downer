<?php
require_once('config.php');
require_once('functions.php');

if(!(isset($_GET['token']) and $_GET['token'])) {
	die("No token provided.");
}
$token = $_GET['token'];

$file_info = query("SELECT f.id, f.file, f.active, (t.expires <= NOW()) as expired FROM files f LEFT JOIN tokens t ON (f.id = t.file) WHERE t.token = %s", $token, QUERY_INIT);
$file = $config['file_base'].'/'.$file_info['file'];

//print_r($file_info);die();
if(!$file_info or !file_exists($file)) {
	die("The file you tried to download is missing.");
}
if(!$file_info['active']) {
	die("This file is no longer available for download.");
}
if($file_info['uses_remaining'] < 1) {
	die("All this token's downloads have been used up.");
}
if($file_info['expired'] == 1) {
	die("This download token has expired.");
}

$file_name = basename($file);
$file_size = filesize($file);
$mime_type = get_mime_type($file);

// set headers
header("Pragma: public");
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Cache-Control: public");
header("Content-Description: File Transfer");
header("Content-Type: ".$mime_type);
header("Content-Disposition: attachment; filename=\"".$file_name."\"");
header("Content-Transfer-Encoding: binary");
header("Content-Length: ".$file_size);

// download
if ($config['xsendfile']) {
    header("X-Sendfile: ".$file);
} else {
    @readfile($file);
}

// Decrease the token's uses remaining

query("UPDATE tokens SET uses_remaining = uses_remaining - 1 WHERE token = %s", $token, QUERY_NONE);

// Log the download

query("INSERT INTO log (fileid, token, time_used, ip_address) VALUES (%d, %s, NOW(), %d)", array($file_info['id'], $token, ip2long($_SERVER["REMOTE_ADDR"])), QUERY_NONE);

