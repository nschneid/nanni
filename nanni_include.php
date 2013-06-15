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
	
	$outF=fopen($f, 'c');	// open for writing, but don't truncate (yet)
	if (flock($outF, LOCK_EX)) { // do an exclusive lock
		$t=tempnam('/tmp', 'NANNI');
		if (copy($f, $t)===false) {
			die("couldn't copy $f to $t");
		}
		$tempF=fopen($t, 'r');
		
		ftruncate($outF, 0);	// now truncate the output file (to prevent trailing data in case it shrinks in size)
		
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


/*
Given a space-tokenized string and two strings of annotations containing those tokens,
    optionally with multiword joiners _ and/or ~, produce a string containing material  
    common to the two strings but indicating discrepancies with a # symbol.
    
2013-06-06 (ported from Python)
*/
function reconcile($T,$A,$B) {
	$T = preg_split('/\s+/', $T);
	assert(count($T)>0);
    $A = trim($A) . ' ';
    $B = trim($B) . ' ';
    
    if (preg_match('/[\$\|]\d+ /', $A.$B)!==0)
    	return '';	// can't handle explicitly indexed groupings
    
    $j = 0;
    $k = 0;
    $s = '';
    
    foreach ($T as $i=>$t) {    # plain tokenized string
        assert(substr($A, $j, strlen($t))==$t);
        assert(substr($B, $k, strlen($t))==$t);
        $j += strlen($t);
        $k += strlen($t);
        $s .= $t;
        assert(strchr(' ~_', $A[$j])!==false);
        assert(strchr(' ~_', $B[$k])!==false);
        
        if ($A[$j]==' ' && $B[$k]==' ') { # both 'x '
            $s .= ' ';
            $j += 1;
            $k += 1;
            if ($i<count($T)-1) { # check for 'x _y' or 'x ~y'
                $endgapA = strpos($A, $T[$i+1], $j)!==$j;    # end of a gap in A
                $endgapB = strpos($B, $T[$i+1], $k)!==$k;    # end of a gap in B
                if ($endgapA && $endgapB && $A[$j]==$B[$k])
                    $s .= $A[$j];
                else if ($endgapA || $endgapB)
                    $s .= '#';
                
                if ($endgapA)
                    $j += 1;
                if ($endgapB)
                    $k += 1;
            }
        }
        else if ($A[$j]==$B[$k]) { # both 'x_' or 'x~'
            assert($A[$j]=='~' || $A[$j]=='_');
            
            if ($A[$j+1]==' ' && $B[$k+1]==' ') # both 'x_ y' or 'x~ y'
                $s .= substr($A, $j, 2);
            else if ($A[$j+1]==' ' || $B[$k+1]==' ')
                $s .= '#';
            else
                $s .= $A[$j];
            
            $j += 1;
            $k += 1;
            if ($A[$j]==' ') # A is 'x_ y' or 'x~ y'
                $j += 1;
            if ($B[$k]==' ') # B is 'x_ y' or 'x~ y'
                $k += 1;
        }
        else { # mismatch
            $s .= '#';
            if ($A[$j]==' ') {   # A is of the form 'x ', B is of the form 'x~' or 'x_'
                $j += 1;  # eat the space in A
                $k += 1;  # eat the joiner in B
            
                if ($B[$k]==' ') {   # A is of the form 'x ', B is of the form 'x~ y' or 'x_ y'
                    $s .= ' ';
                    $k += 1;  # eat the space in B
                }
                if ($i<count($T)-1 && strpos($A, $T[$i+1], $j)!==$j) # A of the form 'x _y' or 'x ~y', B of the form 'x~y' or 'x_y' or 'x~ y' or 'x_ y'
                    $j += 1;  # eat the leading joiner in A
            }
            else if ($B[$k]==' ') { # same as above, swapping A and B
                $j += 1;
                $k += 1;
                
                if ($A[$j]==' ') {
                    $s .= ' ';
                    $j += 1;
                }
                if ($i<count($T)-1 && strpos($B, $T[$i+1], $k)!==$k)
                    $k += 1;
            }
            else {   # mismatched joiners
                assert(($A[$j].$B[$k])=='_~' || ($A[$j].$B[$k])=='~_');
                $j += 1;
                $k += 1;
                if ($A[$j]==' ' && $B[$k]==' ')
                    $s .= ' ';
                if ($A[$j]==' ')   # beginning of a gap in A
                    $j += 1;  # eat the space in A
                if ($B[$k]==' ')   # beginning of a gap in B
                    $k += 1;  # eat the space in B
            }
        }
    }
    assert($j==strlen($A));
    assert($k==strlen($B));
    return trim($s);
}



    //include_once("json.php");

// user handling
$user = $_SERVER['REMOTE_USER'];
$authenticated_user = preg_replace('/\s+/', '-', preg_replace('/@.*/', '',  $user));	// user alias (no @domain.edu)

$issuperuser = ($authenticated_user=='nschneid');

if ($authenticated_user=='guest128') { $u = 'mmordo'; }
else if ($authenticated_user=='guest300') { $u = 'nkazour'; }
else { $u = $authenticated_user; }
$authenticated_alias = $u;

if (isset($_REQUEST['u'])) {	// impersonating another user
	$u = $_REQUEST['u'];
	if (strpos($u, '@')!==false) { die("Invalid user ID: $u"); }
	else if (!$issuperuser && strpos("+$u+", "+$authenticated_alias+")===false && !($u=='consensus' && $authenticated_alias=='sonuffer')) {
		die("Authenticated user $authenticated_user ($authenticated_alias) cannot log in as $u");
	}
}


function get_user_dir($usr) {
	return "users/$usr";
}
$udir = get_user_dir($u);	// user directory
$ddir = "data";	// data directory
$edir = "extra";	// extras directory

$lang = "EN";

?>