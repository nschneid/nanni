<?php
// session_start();	// has started hanging... :(

/* Search file $f for key $key and return its value (separated by a tab).
 * If $key is not found, return null;
 */
function get_key_value($f, $key) {
	$DELIM = "\t";
	$k = "$key$DELIM";
	$kLen = strlen($k);
	
	$inF=fopen($f, 'r');	// open for writing, but don't truncate
	if (flock($inF, LOCK_SH)) { // do an exclusive lock
		$val = null;
		while (!feof($inF)) {
			$line=fgets($inF);
			if (substr($line, 0, $kLen)===$k) {
				$val = substr($line, $kLen);
				break;
			}
		}
		flock($inF, LOCK_UN); // release the lock
	} else {
		die("Couldn't lock the file: $f");
	}
	fclose($inF);

	if ($val===null) return $val;
	
	return substr($val, 0, strlen($val)-1);	// strip newline
}

/* Search file(s) matching $fpat for key $key and add each of its values (separated by a tab) to an array.
 */
function get_key_values($fpat, $key) {
	$DELIM = "\t";
	$k = "$key$DELIM";
	$kLen = strlen($k);
	
	$vals = array();
	
	foreach (glob($fpat) as $f) {
		$inF=fopen($f, 'r');	// open for writing, but don't truncate
		if (flock($inF, LOCK_SH)) { // do an exclusive lock
			$val = null;
			while (!feof($inF)) {
				$line=fgets($inF);
				if (substr($line, 0, $kLen)===$k) {
					$val = substr($line, $kLen);
					$val = substr($val, 0, strlen($val)-1);	// strip newline
					array_push($vals, $val);
				}
			}
			flock($inF, LOCK_UN); // release the lock
		} else {
			die("Couldn't lock the file: $f");
		}
		fclose($inF);
	}
	return $vals;
}

/* Search file $f for key $key, and set its value (following a tab) to $newval, replacing the rest of the line. 
 * If $key is not already present in $f, append to the end of the file.
 * Return the old value (null if key was not found).
 */
function update_key_value($f, $key, $newval) {
	$DELIM = "\t";
	$k = "$key$DELIM";
	$kLen = strlen($k);
	
	$outF=fopen($f, 'c');	// open for writing, but don't truncate
	if (flock($outF, LOCK_EX)) { // do an exclusive lock
		$t=tempnam('/tmp', 'NANNI');
		if (copy($f, $t)===false) {
			die("couldn't copy $f to $t");
		}
		$tempF=fopen($t, 'r');
		
		$oldval = null;
		while (!feof($tempF)) {
			$line=fgets($tempF);
			if (substr($line, 0, $kLen)===$k) {
				if ($oldval!==null)
					die("key seen twice in input: $key");
				$oldval = substr($line, $kLen);
				$line = "$k$newval";
			}
			fwrite($outF, $line);
		}
		if ($oldval===null)
			fwrite($outF, "$k$newval");	// append
		
		fclose($tempF);
		fflush($outF);
		
		flock($outF, LOCK_UN); // release the lock
	} else {
		die("Couldn't lock the file: $f");
	}
	fclose($outF);
	
	return $oldval;
}

/* See if array $arr defines key. If it does, return the corresponding value.
 * Otherwise, set the value to $default and return that value.
 */
function set_default($arr, $key, $default) {
	if (isset($arr[$key]))
		return $arr[$key];
	$arr[$key] = $default;
	return $default;
}



    //include_once("json.php");

// user handling
$user = $_SERVER['REMOTE_USER'];
$authenticated_user = preg_replace('/\s+/', '-', preg_replace('/@.*/', '',  $user));	// user alias (no @domain.edu)

if (isset($_REQUEST['u'])) {	// impersonating another user
	$u = $_REQUEST['u'];
	if (strpos($u, '@')!==false) { die("Invalid user ID: $u"); }
	else if ($authenticated_user!='nschneid' && strpos("+$u+", "+$authenticated_user+")===false) {
		die("Authenticated user $authenticated_user cannot log in as $u");
	}
}
else { $u = $authenticated_user; }

$udir = "users/$u";	// user directory
$ddir = "data";	// data directory
$edir = "extra";	// extras directory

$lang = "EN";

?>