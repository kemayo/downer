<?php
if(!function_exists('file_get_contents')) {
	//Making sure file handling works pre-4.3.0
	function file_get_contents($file) {
		$file = file($file);
		return !$file ? false : implode('', $file);
	}
}

define('QUERY_ALL', 1);
define('QUERY_INIT', 2);
define('QUERY_SINGLEVALUE', 3);
define('QUERY_SINGLEVALUE_ARRAY', 4);
define('QUERY_ID', 5);
define('QUERY_NONE', 6);

function db_connect($dbhost, $dbname, $dbuser, $dbpass) {
	$result = mysqli_connect($dbhost,$dbuser, $dbpass, $dbname); 
	if (!$result) return false;
	mysqli_set_charset($result, 'utf8');
	return $result;
}

function quoter($s) {
	global $conn;
	$s = mysqli_real_escape_string($conn, $s);
	if(!is_numeric($s) or $s[0] == '0') {
		$s = "'".$s."'";
	}
	return $s;
}

function query($sql, $values = null, $howmany = QUERY_ALL, $result_type = MYSQLI_ASSOC) {
	global $conn;
	if (!$conn) {
		global $config;
		$conn = db_connect($config['dbhost'], $config['dbname'], $config['dbuser'], $config['dbpass']);
		if(!$conn) {
			echo "Database connection refused.  Dying horribly.  Ow.";
			exit;
		}
	}
	if($values !== null) {
		if(!is_array($values)) { $values = array($values); }
		$values = array_map("quoter", $values);
		$sql = vsprintf($sql, $values);
	}
	$q = mysqli_query($conn, $sql);
	//print $sql."<br />\n";
	if($q) {
		$results = false;
		if($howmany == QUERY_ALL) {
			$results = array();
			while($r = mysqli_fetch_array($q, $result_type)) {
				$results[] = $r;
			}
		} elseif($howmany == QUERY_INIT) {
			$results = mysqli_fetch_array($q, $result_type);
		} elseif($howmany == QUERY_SINGLEVALUE) {
			if($r = mysqli_fetch_array($q, $result_type)) {
				$results = current($r);
			}
		} elseif($howmany == QUERY_SINGLEVALUE_ARRAY) {
			$results = array();
			while($r = mysqli_fetch_array($q, $result_type)) {
				$results[] = current($r);
			}
		} elseif($howmany == QUERY_ID) {
			$results = mysqli_insert_id($conn);
		} else {
			$results = true;
		}
		return $results;
	}
	return false;
}

function get_mime_type($filename) {
	$mime_type = '';
	// mime type is not set, get from server settings
	if (function_exists('mime_content_type')) {
		$mime_type = mime_content_type($filename);
	} else if (function_exists('finfo_file')) {
		$finfo = finfo_open(FILEINFO_MIME); // return mime type
		$mime_type = finfo_file($finfo, $filename);
		finfo_close($finfo);  
	}
	if ($mime_type == '') {
		$mime_type = "application/force-download";
	}
	return $mime_type;
}

function random_token() {
	return sprintf('%06x', mt_rand(0, 0xffffff));
}

function create_tokens($file, $howmany, $uses, $expire) {
	$tokens = array();
	for($i=1; $i <= $howmany; $i++) {
		do {
			$token = random_token();
			$skip = is_numeric($token) or query("SELECT token FROM tokens WHERE token = %s", $token, QUERY_SINGLEVALUE);
		} while($skip);
		query("INSERT INTO tokens (token, file, uses_remaining, initial_uses, created, expires) VALUES (%s, %d, %d, %d, NOW(), %s)",
			array($token, $file, $uses, $uses, $expire), QUERY_NONE);
		$tokens[] = array($token, $uses, $expire);
	}
	return $tokens;
}

function get_tokens_as_csv($tokens) {
	ob_start();
	foreach($tokens as $t) {
		print $t[0].','.$t[1].','.$t[2]."\n";
	}
	return ob_get_clean();
}
?>
