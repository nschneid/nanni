<?php
// session_start();	// has started hanging... :(
 echo '<!DOCTYPE html>'; 


/**
Nanni: Nice Annotation Interface

Item Index
----------
For each item in a dataset, shows
(a) the raw sentence
(b) its status as color-coding of the sentence: 
    * black if unannotated by the current user
    * green if annotated by the current user in the last day
    * blue otherwise
(c) title text: the user's latest annotation, time it was produced
(d) the user's note text
(e) for each other user that has annotated the sentence, color-coded user ID 
	(bold if there is a note) with their latest annotation/time/note in title text. 

Clicking on a row sentence opens it for annotation (new tab?) (version history available but collapsed by default?)

TODO:
 - link the sentences


[[
other possible features:

search for items containing 2 (or more) specified words

lexical index
- for each word, list all containing lexical expressions & link to each of their instances
- mark gappy instances
- mark disputed lexemes? (from only a subset of annotators)
- decorate gold POS tags (& syntactic info?).

]]

@author: Nathan Schneider (nschneid@cs.cmu.edu)
@since: 2013-02-13
*/

?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link rel="stylesheet" href="http://code.jquery.com/ui/1.9.2/themes/base/jquery-ui.css" />
<style type="text/css">
body,tbody { font-family: "Helvetica Neue",helvetica,arial,sans-serif; }
div.item { max-width: 40em; margin-left: auto; margin-right: auto; }
.rawitem.sUnann,.rawitem.sUnann a { color: #000; }
.rawitem.sRecent,.rawitem.sRecent a { color: #6a0; }
.rawitem.sAnn,.rawitem.sAnn a { color: #33c; }
.rawitem > a { text-decoration: none; }
#items { border-collapse: collapse; }
#items th,#items td { padding: 2px; }
#items th.num { text-align: right; padding-right: 0.5em; color: #aaa; font-weight: normal; }
#items td.note:not(:empty) { border: solid 1px #000; }
#items tr:hover { background-color: #eee; }
.usr0 { color: #c3f; background-color: #c3f; }
.usr1 { color: #09f; background-color: #09f; }
.usr2 { color: #e70; background-color: #e70; }
.usr3 { color: #f0c; background-color: #f0c; }
.usr4 { color: #f00; background-color: #f00; }
.usr5 { color: #093; background-color: #093; }
.user:not(.hasnote) { background-color: transparent; }
.user.hasnote { color: #fff; }
.user.sRecent { font-weight: bold; }

.wlu7 { border-bottom: dotted 2px #09f; }
.wlu6 { border-bottom: dotted 2px #f00; }
.wlu3 { border-bottom: solid 2px #e70; }
.wlu4 { border-bottom: dotted 2px #6a0; }
.wlu5 { border-bottom: dotted 2px #f0c; }
.wlu2 { border-bottom: solid 2px #c3f; }
.wlu1 { border-bottom: solid 2px #093; }
.wlu0 { border-bottom: solid 2px #33c; }
.note:default { color: #ccc; }
input[type=submit] { height: 3em; width: 65%; }
input.btnprev,input.btnnext { height: 3em; width: 15%; }
input[type=submit]:hover:not([disabled]),input.btnprev:hover:not([disabled]),input.btnnext:hover:not([disabled]) { background-color: #333; color: #eee; }
input[type=submit]:focus:not([disabled]),input.btnprev:focus:not([disabled]),input.btnnext:focus:not([disabled]) { border: solid 2px #ff0; }

textarea[readonly],input[readonly] { border: none; }

.outdated { color: #ccc; }

body.embedded p { margin-top: 0; margin-bottom: 0; }
body.embedded textarea { background-color: transparent; }

.w { position: relative; display: inline-block; vertical-align: top; margin-bottom: 1.5em; }
.w input:-moz-placeholder { font-style: italic; }
.w input::-webkit-input-placeholder { font-style: italic; }
.w input:-ms-input-placeholder { font-style: italic; }
.tokenlabel { width: 6em; border: solid 1px #ccc; position: absolute; top: 2.1em; left: 0; }
.ui-menu-item { font-size: 10pt; }
.ui-menu .ui-menu-item a { padding-top: 0; padding-bottom: 0; }
</style>

<?
/* Search file $f for key $key and return its value (separated by a tab).
 * If $key is not found, return null;
 */
include_once('nanni_include.php');


$init_stage = (array_key_exists('initialStage', $_REQUEST)) ? $_REQUEST['initialStage'] : '0';
$prep = array_key_exists('prep', $_REQUEST);
$new = array_key_exists('new', $_REQUEST);
$nonav = array_key_exists('nonav', $_REQUEST);
$nosubmit = array_key_exists('nosubmit', $_REQUEST);
$vv = isset($_REQUEST['v']) ? $_REQUEST['v'] : null;	// invoke version browser(s) in an iframe
$versions = array_key_exists('versions', $_REQUEST);	// the version browser itself
$instructionsCode = (array_key_exists('inst', $_REQUEST)) ? $_REQUEST['inst'] : '';
$instructions = "mwe_ann_instructions$instructionsCode.md";
$noinstr = array_key_exists('noinstr', $_REQUEST);
$nooutdated = array_key_exists('nooutdated', $_REQUEST);
$readonly = array_key_exists('readonly', $_REQUEST);
$embedded = array_key_exists('embedded', $_REQUEST);

// arbitrarily maps each user that has been encountered (other than $u) to a unique integer
$otherusers = array();


?>

<script type="text/javascript" language="javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
<script type="text/javascript" language="javascript" src="https://jquery-json.googlecode.com/files/jquery.json-2.3.min.js"></script>
<script type="text/javascript" language="javascript" src="http://code.jquery.com/ui/1.9.2/jquery-ui.js"></script>
<script type="text/javascript" language="javascript" src="sisyphus.min.js"></script>
<script type="text/javascript">

// Escape special HTML characters, including quotes
function htmlspecialchars(s) {
	return $("<div>").text(s).html().replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}


//Cookie-handling functions
//Source: http://www.quirksmode.org/js/cookies.html, 24 Aug 2008

function createCookie(name,value,days) {
	if (days) {
		var date = new Date();
		date.setTime(date.getTime()+(days*24*60*60*1000));
		var expires = "; expires="+date.toGMTString();
	}
	else var expires = "";
	document.cookie = name+"="+value+expires+"; path=/";
}

function readCookie(name) {
	var nameEQ = name + "=";
	var ca = document.cookie.split(';');
	for(var i=0;i < ca.length;i++) {
		var c = ca[i];
		while (c.charAt(0)==' ') c = c.substring(1,c.length);
		if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
	}
	return null;
}
function getName(){
	var name = readCookie("annotator");
	if (name != null) return name;
	name = prompt("Enter annotator name (saved as cookie)");
	if (name!=null)
	{
		createCookie("annotator", name, 7);
		return name;
	}
}

function eraseCookie(name) {
	createCookie(name,"",-1);
}

function equivArrays(x, y) {
	if (x.length!=y.length) return false;
	for (var i=0; i<x.length; i++) {
		if (x[i]!=y[i]) return false;
	}
	return true;
}

// The provided string will be conjoined with other fields to form a CSV record, and should thus be appropriately quoted
function logString(s) {
	var logEntry = new String(new Date());
	if (s!=null)
		logEntry += "," + s;
	$("#results").val($("#results").val() + logEntry + "\n");
}
function logEvent(targetId, att, val) {	// 'val', in particular, should be appropriately quoted
	logString(getName() + ',' + targetId + ',' + att + ',' + val);
}
// Record a user's decision to add or remove a segmentation point
// segmentationPoint: e.g. s85c5 (where character indices to the tokenized sentence are 0-based and include spaces)
// addition: true if the segmentation is being added, false otherwise
function recordDecision(segmentationPosition, addition) {
	logString(((addition) ? '+' : '-') + segmentationPosition);
}
// Record that the user clicked to see more of the instructions, suggesting that they're paying attention
function recordClickedMore() {
	logString("expandInstructionDetails");
}

function shuffleList(listId) {
	// Randomize the ordering of the items in the given list, and return the list item IDs in their new order as an Array
	// - Remove sentences from the DOM
	var lis = $("#" + listId + " > li").detach();

	// - Generate a permuation of the integers from 0 to the number of sentences
	var numsSorted = new Array();
	for (var h=0; h<lis.length; h++)
		numsSorted[h] = h;
	var numsRandomized = new Array();
	for (var q=0; numsSorted.length>0; q++) {
		var r = Math.floor(Math.random()*numsSorted.length);
		// Remove the rth number and put it in the randomized array
		var rem = numsSorted.splice(r,1);
		numsRandomized.push(rem[0]);
	}
	
	// - Add sentences back to the DOM in the randomized order
	newOrder = new Array();
	for (var qq=0; qq<numsRandomized.length; qq++) {
		var liId = lis.eq(numsRandomized[qq]).appendTo("#"+listId).attr("id");
		newOrder.push(liId);
	}

	return newOrder;
}





KEY_LEFT_ARROW = 37;
KEY_UP_ARROW = 38;
KEY_RIGHT_ARROW = 39;
KEY_DOWN_ARROW = 40;


function arraysEq(x, y) {
	if (x.length!=y.length) return false;
	for (var i=0; i<x.length; i++) {
		if (x[i]!==y[i]) return false;
	}
	return true;
}



</script>
<title>Item Index (<?= $_SERVER['REMOTE_USER'] ?> as <?= $u ?>)</title>
</head>
<body<?= ($embedded) ? ' class="embedded"' : '' ?>>

<?

if (!isset($_REQUEST['split'])) {
	// list all splits under this user
?><ul id="splits">
<?
	foreach (glob("$udir/*.nanni") as $split) {
		preg_match('/([^\\/\\\\]+)\.nanni$/', $split, $matches);
		$spl = $matches[1];
?>		<li><a href="?split=<?= $spl ?>"><?= $spl ?></a></li>
<?	}
	$iFrom = -1;
}
else {
	$split = $_REQUEST['split'];

	if (!array_key_exists('from', $_REQUEST)) {	// demo mode
		$iFrom = 0;
		$iTo = -1;
	}
	else {
		$iFrom = intval($_REQUEST['from']);
		$iTo = (array_key_exists('to', $_REQUEST)) ? intval($_REQUEST['to']) : -1;
	}
}


if ($iFrom>-1) {
	
	// TODO: pagination
	/*
	$perpage = intval($_REQUEST['perpage']);
	if ($perpage<=0)
		$perpage = 10;
	$perpage = 1;
	*/
	
	$kv = "$udir/$split.nanni";
	
	
	($iFrom>=0 && ($iTo>$iFrom || $iTo==-1)) or die("You have finished annotating the current batch. Thanks!");
	
	$IN_FILE = "$ddir/$split";
	$f = fopen($IN_FILE, 'r');
	
	
	if ($f) {
?>
<table id="items">
<?

		$l = 0;
		while (($entry = fgets($f)) !== false) {
			if ($l >= $iFrom) {
				if (($iTo>-1 && $l >= $iTo)) break;	// || $l >= ($iFrom+$perpage)) break;
	
				$entry = htmlspecialchars($entry, ENT_QUOTES);	
				$entry = explode("\t", $entry);
				$sentId = $entry[0];
				$tokenizedS = $entry[1];
				//$tagsS = $entry[2];
				$tokens = explode(' ', $tokenizedS);
				//$tags = explode(' ', $tagsS);
				//$taggedS = "";
				//for ($i=0; $i<count($tokens); $i++)
				//	$taggedS .= "$tokens[$i]/$tags[$i] ";
				
				$sent = trim($tokenizedS);
				
				// build URL for going to the annotation of this item. keep all $_GET params 
				// except the 'from' index.
				$qsarray = $_GET;
				$qsarray['from'] = $l;
				$annurl = htmlentities('nanni.php?' . http_build_query($qsarray));
				
				
				// load user's current version of the sentence, if available
				$v = get_key_value($kv, $sentId);
				if ($v!==null) {
					$parts = explode("\t", $v);
					$timestamp = intval($parts[1]);
					$time = date('r', $timestamp);
					$anno = htmlspecialchars($parts[count($parts)-2]);
					$note = htmlspecialchars($parts[count($parts)-1]);
					$tip = $anno . "\n" . $time;
					$status = ($timestamp<(mktime()-24*60*60)) ? 'sAnn' : 'sRecent';
				}
				else {
					$time = '';
					$anno = '';
					$note = '';
					$tip = '';
					$status = 'sUnann';
				}
				
				// extra data for the sentence (if available)
				if        (file_exists("$edir/$split.extra")) {
					$e = get_key_value("$edir/$split.extra", $sentId);
					if ($e===null) $e = '';
				}
				else $e = '';
				
?><tr id="_<?= $sentId ?>"><th class="num" title="<?= $sentId ?>" id="n<?= $l ?>"><?= $l ?></th><td class="rawitem <?= $status ?>" title="<?= $tip ?>"><a href="<?= $annurl ?>"><?= $sent ?></a></td><td class="note"><?= $note ?></td><td class="extra"><?= $e ?></td><td class="users"><?
					foreach (get_key_values("users/*/$split.nanni", $sentId) as $j => $data) {
						$parts = explode("\t", $data);
						$timestamp = intval($parts[1]);
						$userId = $parts[2];
						if ($userId=="@$u") continue;
						$userOffset = set_default(&$otherusers, $userId, count($otherusers));
						$time = date('r', $timestamp);
						$anno = htmlspecialchars($parts[count($parts)-2]);
						$note = htmlspecialchars($parts[count($parts)-1]);
						$tip = $anno . "\n" . $time;
						if ($note)
							$tip .= "\n" . $note;
						$status = ($timestamp<(mktime()-24*60*60)) ? 'sAnn' : 'sRecent';
?><span class="user usr<?= $userOffset ?> <?= $status ?><?= ($note) ? ' hasnote' : '' ?>" title="<?= $tip ?>"><?= $userId ?></span> <?
					}
?></td></tr>
<?
			}
			$l++;
		}
		fclose($f);
?>
</table>
		
<?
	}
}


?>




</div>
</form>
</body>
</html>