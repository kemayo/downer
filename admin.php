<?php
require_once('config.php');
require_once('functions.php');
session_start();
ob_start();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
	<title>Downloads</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
</head>
<body>
	<h1>Downloads</h1>
<?php
if($_SESSION['message']) {
	print '<p id="message">'.$_SESSION['message'].'</p>';
	unset($_SESSION['message']);
}
if(!$_SESSION['ADMIN']) {
	if(isset($_POST['username']) and isset($_POST['password'])) {
		if($config['admin_name'] == $_POST['username'] and $config['admin_pass'] == $_POST['password']) {
			$_SESSION['ADMIN'] = true;
		} else {
			$_SESSION['message'] = "Incorrect login information.";
		}
		header("Location: admin.php");
		exit;
	}
?>
	<p>You need to log in first.</p>
	<form action="admin.php" method="POST">
		<label for="username">Username</label>
		<input name="username" id="username"><br>
		<label for="password">Password</label>
		<input name="password" id="password" type="password"><br>
		<input type="submit" name="submit" id="submit" value="Log in">
	</form>
<?php
} else {
	switch($_GET['p']) {
		case 'file':
			$id = $_GET['id'];
			if(isset($_POST['submit'])) {
				// save!
				if(!file_exists($config['file_base'].'/'.$_POST['file'])) {
					$_SESSION['message'] = "That file doesn't exist.";
				} else {
					if($id=='new') {
						$id = query("INSERT INTO files (file, active) VALUES (%s, %d)", array($_POST['file'], $_POST['active'] == 1 ? 1 : 0), QUERY_ID);
					} else {
						query("UPDATE files SET file=%s, active=%d WHERE id = %d", array($_POST['file'], $_POST['active'] == 1 ? 1 : 0, $id), QUERY_NONE);
					}
				}
				header("Location: admin.php?p=file&id=".$id);
				exit;
			}
			if($id == 'new') {
				$file = array('file'=>'', 'active'=>1);
			} else {
				$file = query("SELECT * FROM files WHERE id = %d", $id, QUERY_INIT);
			}
?>
	<form method="POST">
		<label for="file">File</label>
		<input name="file" id="file"<?if($file['file']) {?> value="<?=$file['file']?>"<?}?>><br>
		<label for="active">Active?</label>
		<input name="active" id="active" type="checkbox" value="1"<?if($file['active']){?> checked="checked"<?}?>><br>
		<input name="submit" id="submit" type="submit" value="save">
	</form>
<?php
			break;
		case 'csv':
			ob_end_clean();
			// set headers
			header("Pragma: public");
			header("Expires: 0");
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header("Cache-Control: public");
			header("Content-Description: File Transfer");
			header("Content-Disposition: attachment; filename=\"tokens.csv\"");
			header("Content-Transfer-Encoding: binary");
			print get_tokens_as_csv($_SESSION['tokens']);
			exit;
			break;
		case 'tokenlist':
			$file = $_GET['file'];
			if(isset($_POST['submit'])) {
				if(is_numeric($_POST['howmany'])) {
					$_SESSION['tokens'] = create_tokens($file, $_POST['howmany'], $_POST['uses'], $_POST['expires']);
					$_SESSION['message'] = "Created ".count($_SESSION['tokens'])." tokens.  <a href=\"admin.php?p=csv\">Download as CSV</a>?";
				} else {
					$_SESSION['message'] = "'How many?' should be a number";
				}
				header("Location: admin.php?p=tokenlist&file=".$file);
				exit;
			}
			$tokens = query("SELECT * FROM tokens WHERE file = %d ORDER BY created", $file);
?>
	<h2>Generate new tokens</h2>
	<form method="POST">
		<label for="howmany">How many?</label><input id="howmany" name="howmany"><br>
		<label for="uses">Uses</label><input id="uses" name="uses" value="1"><br>
		<label for="expires">Expires</label><input id="expires" name="expires" value="2029-12-31 00:00:00"><br>
		<input name="submit" id="submit" type="submit" value="Generate!">
	</form>
	<h2>Existing tokens</h2>
	<table>
		<tr><th>token</th><th>uses remaining</th><th>initial uses</th><th>created</th><th>expires</th></tr>
<?php
			foreach($tokens as $t) {
?>
		<tr>
			<td><a href="admin.php?p=token&token=<?=$t['token']?>"><?=$t['token']?></a></td>
			<td><?=$t['uses_remaining']?></td>
			<td><?=$t['initial_uses']?></td>
			<td><?=$t['created']?></td>
			<td><?=$t['expires']?></td>
		</tr>
<?php
			}
			break;
		case 'token':
			if(isset($_POST['submit'])) {
				$token = $_GET['token'];
				query("UPDATE tokens SET uses_remaining = %d, expires = %s WHERE token = %s", array($_POST['uses_remaining'], $_POST['expries'], $token), QUERY_NONE);
				header("Location: admin.php?p=token&token=".$token);
				exit;
			}
			$token = query("SELECT * FROM tokens WHERE token = %s", $_GET['token'], QUERY_INIT);
?>
	<form method="POST">
		Token: <?=$token['token']?><br>
		Initial uses: <?=$token['initial_uses']?><br>
		<label for="uses_remaining">Uses remaining</label><input name="uses_remaining" id="uses_remaining" value="<?=$token['uses_remaining']?>"><br>
		Created: <?=$token['created']?><br>
		<label for="expires">Expires</label><input name="expires" id="expires" value="<?=$token['expires']?>"><br>
		<input name="submit" id="submit" type="submit" value="Save">
	</form>
<?php
			break;
		case 'log':
			break;
		case 'index':
		default:
			$files = query("SELECT * FROM files ORDER BY file");
?>
	<h2>All files</h2>
	<table>
		<tr><th>file</th><th>size</th><th>type</th><th>active</th><th>tokens</th><th>downloads so far</th><th>downloads unclaimed</th></tr>
<?php
			foreach($files as $f) {
				$tokens = query("SELECT COUNT(*) as count, SUM(uses_remaining) as remaining FROM tokens WHERE file = %d", $f['id'], QUERY_INIT);
				$downloads = query("SELECT COUNT(*) FROM log l LEFT JOIN tokens t ON (l.token = t.token) LEFT JOIN files f ON (t.file = f.id) WHERE f.id = %d", $f['id'], QUERY_SINGLEVALUE);
?>
		<tr>
			<td><a href="admin.php?p=file&id=<?=$f['id']?>"><?=$f['file']?></a></td>
			<td><?=filesize($config['file_base'].'/'.$f['file'])?></td>
			<td><?=get_mime_type($config['file_base'].'/'.$f['file'])?></td>
			<td><?=($f['active'] ? 'yes' : 'no')?></td>
			<td><a href="admin.php?p=tokenlist&file=<?=$f['id']?>"><?=$tokens['count']?></a></td>
			<td><?=$downloads?></td>
			<td><?=$tokens['remaining']?></td>
		</tr>
<?php } ?>
	</table>
	<a href="admin.php?p=file&id=new">New</a>
<?php
			break;
	}
}
?>
</body>
</html>
