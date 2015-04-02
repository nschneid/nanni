<?php
// session_start();	// has started hanging... :(
 echo '<!DOCTYPE html>'; 

error_reporting(0);
ini_set('display_errors', 'On');

//header("Content-Security-Policy: default-src 'self' ; script-src 'self' ; style-src 'self'");

/**
Nanni: Nice Annotation Interface

A customizable, Javascript-based interface for text annotation. 
Different forms of annotation may be defined and combined; see ANNOTATION PROTOCOL below.

Currently the interface is configured for annotation of:
  Strong and weak multiword expressions grouping together multiple tokens (MWEAnnotator)
  Preposition functions (PrepTokenAnnotator)
  An optional user comment for each sentence (ItemNoteAnnotator)

@author: Nathan Schneider (nschneid@cs.cmu.edu)
@since: 2012-12-17
*/

?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link rel="stylesheet" href="jquery-ui_1.9.2.css" />
<? $icontype = ($_SERVER['REMOTE_USER']=='nschneid@cs.cmu.edu') ? 'png' : 'gif'; ?>
<link rel="icon" type="image/<?= $icontype ?>" href="img/cupcake-icon.<?= $icontype ?>" />
<style type="text/css">
body,tbody { font-family: "Helvetica Neue",helvetica,arial,sans-serif; }
div.item { max-width: 40em; margin-left: auto; margin-right: auto; }
.slu0 { color: #09f; }
.slu1 { color: #f00; }
.slu2 { color: #e70; }
.slu3 { color: #6a0; }
.slu4 { color: #f0c; }
.slu5 { color: #c3f; }
.slu6 { color: #093; }
.slu7 { color: #33c; }
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
.toplabel { color: #09f; }

.indexlinks { position: fixed; bottom: 10px; right: 10px; }
.indexlinks a { color: #aaa; font-variant: small-caps; }

textarea[readonly].comment { background-color: #ffc !important; }
</style>

<?
/* Search file $f for key $key and return its value (separated by a tab).
 * If $key is not found, return null;
 */
include_once('nanni_include.php');

$init_stage = (array_key_exists('initialStage', $_REQUEST)) ? $_REQUEST['initialStage'] : '0';

// extra forms of annotation
$prep = array_key_exists('prep', $_REQUEST);	// preposition tokens
$psst = array_key_exists('psst', $_REQUEST);	// preposition supersenses
$nsst = array_key_exists('nsst', $_REQUEST);	// noun supersenses
$vsst = array_key_exists('vsst', $_REQUEST);	// verb supersenses


$nonav = array_key_exists('nonav', $_REQUEST);
$nosubmit = array_key_exists('nosubmit', $_REQUEST);
$vv = isset($_REQUEST['v']) ? $_REQUEST['v'] : null;	// invoke version browser(s) in an iframe
$embedded = array_key_exists('embedded', $_REQUEST);
$new = $_REQUEST['new'];
if (!$new)
	$new = array_key_exists('new', $_REQUEST);
if ($embedded) $new = false;
$reconcile = (isset($_REQUEST['reconcile']) && !$embedded) ? $_REQUEST['reconcile'] : null;	// user whose annotations should be imported, or 2 users whose annotations should be reconciled
if ($reconcile!==null && !is_array($reconcile))
	$reconcile = array(0 => $reconcile);
$versions = array_key_exists('versions', $_REQUEST);	// the version browser itself
$instructionsCode = (array_key_exists('inst', $_REQUEST)) ? $_REQUEST['inst'] : '';
$instructions = "mwe_ann_instructions$instructionsCode.md";
$noinstr = array_key_exists('noinstr', $_REQUEST);
$nooutdated = array_key_exists('nooutdated', $_REQUEST);
$readonly = array_key_exists('readonly', $_REQUEST);


if (!array_key_exists('from', $_REQUEST)) {	// demo mode
	$iFrom = -1;
	$iTo = -1;
}
else {
	$iFrom = intval($_REQUEST['from']);
	$iTo = (array_key_exists('to', $_REQUEST)) ? intval($_REQUEST['to']) : -1;
}



if ($iFrom>-1) {
	if (isset($_REQUEST['q']))
		$query = $_REQUEST['q'];
	else if (isset($_REQUEST['split']))
		$split = $_REQUEST['split'];
	else
		die("missing: query or split");
	
	/*
	$perpage = intval($_REQUEST['perpage']);
	if ($perpage<=0)
		$perpage = 10;
	*/
	$perpage = 1;	// TODO: allow multiple sentences per page

	
	
	if (array_key_exists("submit", $_REQUEST)) {	// save data from a page of annotation
		if (!isset($_REQUEST['split']))
			die("missing: query or split");
		$split = $_REQUEST['split'][0];
		$TAG_FILE = "$udir/$split.nanni.all";
		$tagF = fopen($TAG_FILE, 'a');
		if (!$tagF) die("Unable to save annotations: " . getcwd() . "/$TAG_FILE");
		
		$ann = $_REQUEST['annotation'];
		//$ann = htmlspecialchars_decode(stripslashes($ann), ENT_QUOTES);
		$ann = trim(preg_replace('/\s+/', ' ', $ann));
		//$note = htmlspecialchars_decode(stripslashes($note), ENT_QUOTES);
		
		for ($I=0; $I<count($_REQUEST['sentid']); $I++) {
			$sentId = $_REQUEST['sentid'][$I];
			$mwe = trim(preg_replace('/\s+/', ' ', $_REQUEST['mwe'][$I]));
			$note = trim(preg_replace('/\s+/', ' ', $_REQUEST['note'][$I]));
			$key = $_REQUEST['sentid'][$I];
			$avals = '"initval": "' . addslashes(trim(preg_replace('/\s+/', ' ', $_REQUEST['initval'][$I]))) . '",';
			if (array_key_exists('beforeVExpand', $_REQUEST))	// annotation prior to clicking the "versions" link
				$avals .= '  "beforeVExpand": "' .  preg_replace('/\s+/', ' ', trim(addslashes($_REQUEST['beforeVExpand'][$I]))) . '",';
			if (isset($_REQUEST['reconciled'][$I])) {
				if ($reconcile[0]=='^') {	
					// most recent of the source versions being reconciled
					$maxtstamp = -1;
					foreach ($_REQUEST['reconciledtime'][$I] as $tstamp) {
						if (intval($tstamp) > $maxtstamp)
							$maxtstamp = intval($tstamp);
					}
					
					// find user with most recent annotation for this sentence
					$Atstamp = -1;
					foreach (get_key_values(get_user_dir('*') . "/$split.nanni", $sentId) as $data) {
						$parts = explode("\t", $data);
						$tstamp = intval($parts[1]);
						if ($tstamp > $Atstamp) {	// this is the newest so far
							$A = substr($parts[2], 1);	// @user --[strip @]--> user
							$Atstamp = $tstamp;
						}
					}
					if ($Atstamp > $maxtstamp) {
						die("EDIT CONFLICT: There is a new annotation for this sentence ($sentId) from user $A. Go back, hit refresh, and make sure your annotation takes theirs into account.");
					}
				}
				if (true) {	// also ensure that for each user being reconciled, they have not submitted a more recent version
					foreach ($_REQUEST['reconciled'][$I] as $k=>$A) {
						$Adata = get_key_value(get_user_dir($A) . "/$split.nanni", $sentId);
						$Aparts = explode("\t", $Adata);
						$Atstamp = intval($Aparts[1]);
						if ($Atstamp > intval($_REQUEST['reconciledtime'][$I][$k])) {
							die("EDIT CONFLICT: There is a new annotation for this sentence ($sentId) from user $A. Go back, hit refresh, and make sure your annotation takes theirs into account.");
						}
					}
				}
				$avals .= '  "reconciled": {"users": ' . json_encode($_REQUEST['reconciled'][$I]) . ', "times": ' . json_encode(array_map(intval, $_REQUEST['reconciledtime'][$I])) . '},';
			}
			if (isset($query))
				$avals .= '  "query": "' . addslashes($query) . '",';
			$chklbls = ($_REQUEST['chklbls']) ? $_REQUEST['chklbls'][$I] : '{}';
			$val = $_REQUEST['loadtime'] . "\t" . mktime() . "\t@$u\t$ann\t{{$avals}   \"sgroups\": " . $_REQUEST['sgroups'][$I] . ",   \"wgroups\": " . $_REQUEST['wgroups'][$I] . (($prep) ? ",   \"preps\": " . $_REQUEST['preps'][$I] : '') . (($nsst || $vsst || $psst) ? ",   \"chklbls\": $chklbls" : '') . ",   \"url\": \"" . $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"] . "\",   \"session\": \"" . session_id() . "\",  \"authuser\": \"$user\"}\t$mwe\t$note\n";
			
			// check if a new split
			if ($_REQUEST['split'][$I]!=$split) {
				fclose($tagF);
				$split = $_REQUEST['split'][$I];
				$TAG_FILE = "$udir/$split.nanni.all";
				$tagF = fopen($TAG_FILE, 'a');
				if (!$tagF) die("Unable to save annotations: " . getcwd() . "/$TAG_FILE");
			}
			fwrite($tagF, "$key\t$val");
			update_key_value("$udir/$split.nanni", $key, $val);
		}
		fclose($tagF);
		
		/*
		$LOG_FILE = "$TAG_FILE.log";
		$logF = fopen($LOG_FILE, 'a');
		if (!$logF) die("Unable to save log: " . getcwd() . "/$LOG_FILE");		
		fwrite($logF, htmlspecialchars_decode(stripslashes($_REQUEST['resultsLog']), ENT_QUOTES));
		fclose($logF);
		*/
		$qsarray = $_GET;
		foreach ($_GET as $k=>$v) {
			if (isset($_POST[$k])) {	// allow POST parameter (from form) to override GET (from current page URL)
				$qsarray[$k] = $_POST[$k];
			}
		}
		// exception: split (array in POST, single value in GET)
		$qsarray['split'] = $_GET['split'];
		header('Location: ?' . http_build_query($qsarray));
	}
	
	($iFrom>=0 && ($iTo>$iFrom || $iTo==-1)) or die("You have finished annotating the current batch. Thanks!");
	
	$IN_FILE = (isset($query)) ? "$qdir/$query.query" : "$ddir/$split";
	$f = fopen($IN_FILE, 'r');
	
	// Load the input sentences
	$SENTENCES = array();
	
	if ($f) {
		$l = 0;
		while (($entry = fgets($f)) !== false) {
			if ($l >= $iFrom) {
				if (($iTo>-1 && $l >= $iTo) || $l >= ($iFrom+$perpage)) break;
				
				//$entryRaw = explode("\t", $entry);
				//$tokenizedSRaw = $entry[1];	// no HTML escaping (# in entities screws things up)
				
				$entry = htmlspecialchars($entry, ENT_QUOTES);	
				$entry = explode("\t", $entry);
				$sentId = $entry[0];
				
				$tokenizedS = (isset($query)) ? $entry[2] : $entry[1];
				if (isset($query))
					$split = $entry[1];
				//$tagsS = $entry[2];
				$tokens = explode(' ', $tokenizedS);
				//$tags = explode(' ', $tagsS);
				//$taggedS = "";
				//for ($i=0; $i<count($tokens); $i++)
				//	$taggedS .= "$tokens[$i]/$tags[$i] ";
	
				$sentdata = array('sentence' => trim($tokenizedS), 'sentenceId' => $sentId, 'split' => $split);


				if ($reconcile!==null) {
					if (in_array('^', array_slice($reconcile, 1)))
						die("'^' or '^USER-GROUP-PREFIX' must be first reconcile argument");
					if (substr($reconcile[0],0,1)=='^') {	// find user with most recent annotation for this sentence
						$ugFilter = substr($reconcile[0],1);	// ...filtered by user group prefix
						$Atstamp = -1;
						foreach (get_key_values(get_user_dir('*') . "/$split.nanni", $sentId) as $data) {
							$parts = explode("\t", $data);
							$tstamp = intval($parts[1]);
							if ($tstamp > $Atstamp) {	// this is the newest so far
								$provisionalA = substr($parts[2], 1);	// @user --[strip @]--> user
								if (strlen($ugFilter)>0) {
									if (!isset($UGROUPS[$provisionalA]))
										die("No user group for: $provisionalA");
									if (strrpos($UGROUPS[$provisionalA], $ugFilter, -strlen($haystack)) === FALSE) {
										// user group name does not start with $ugFilter
										continue;
									}
								}
								$A = $provisionalA;
								$Adata = $data;
								$Aparts = $parts;
								$Atstamp = $tstamp;
							}
						}
						if ($Atstamp<0)
							die("No annotation to reconcile for: " . $reconcile[0]);
						$reconcile[0] = $A;	// record which user was newest
					}
					else {
						$A = $reconcile[0];
						$Adata = get_key_value(get_user_dir($A) . "/$split.nanni", $sentId);
						$Aparts = explode("\t", $Adata);
						$Atstamp = intval($Aparts[1]);
					}
					
					$B = $reconcile[count($reconcile)-1];
					if (substr($B,0,1)=='^')
						die("'^' or '^USER-GROUP-PREFIX' must be first reconcile argument");
					$Bdata = get_key_value(get_user_dir($B) . "/$split.nanni", $sentId);
					// possibly the same user as A, in which case that user's annotations will simply be imported
					$Bparts = explode("\t", $Bdata);
					$Btstamp = intval($Bparts[1]);
					
					$sentdata['reconciled'] = $reconcile;	// users whose annotations were actually used/reconciled
					$sentdata['reconciledtime'] = array($Atstamp, $Btstamp);
				}

				if (!$new || $new==='^') {	// load user's current version of the sentence, if applicable
					if ($readonly) {
						if ($versions && false) {
							// TODO: what is this line supposed to do?? may have been vestigial and failing silently because $_REQUEST['versions'] was empty
							// (git blame points to the commit "show comments in versions box", but such comments seem to appear fine without it.)
							$sentdata['versions'] = get_key_values(get_user_dir($_REQUEST['versions'])."/$split.nanni", $sentId);
						}
					}
					else {
						$v = get_key_value("$udir/$split.nanni", $sentId);
						if ($v!==null) {
							$parts = explode("\t", $v);
							$tstamp = intval($parts[1]);
							if (!$new || $reconcile===null || $tstamp >= $Atstamp || $tstamp >= $Btstamp) {
								// use this user's current saved version
								$sentdata['initval'] = htmlspecialchars($parts[count($parts)-2]);
								$sentdata['note'] = htmlspecialchars($parts[count($parts)-1]);
								$sentJ = json_decode(str_replace("\\'", "'", $parts[count($parts)-3]), true);
								if ($sentJ['chklbls']) {
									$sentdata['chklbls'] = htmlspecialchars(json_encode($sentJ['chklbls']), ENT_QUOTES);
									if (!array_key_exists('initialStage', $_REQUEST)) // unless a particular start stage is forced, make it '1' because there are already chunk labels
										$init_stage = '1';
								}
								if ($reconcile===null) {
									$sentdata['reconciled'] = array(0=>$u);
									$sentdata['reconciledtime'] = array(0=>$tstamp);
								}
							}
						}
					}
				}

				
				if ($nsst || $vsst || $psst) {	// load POS tags
					$posJS = get_key_value("$pdir/$split.pos.json", $sentId, false);
					$sentdata['pos'] = htmlspecialchars($posJS, ENT_QUOTES);
				}
				
				if ($reconcile!==null && !isset($sentdata['initval'])) {
					
					$A = htmlspecialchars($Aparts[count($Aparts)-2], ENT_QUOTES);
					$B = htmlspecialchars($Bparts[count($Bparts)-2], ENT_QUOTES);
					
					$AJ = json_decode(str_replace("\\'", "'", $Aparts[count($Aparts)-3]), true);
					$BJ = json_decode(str_replace("\\'", "'", $Bparts[count($Bparts)-3]), true);
					
					if ($AJ['chklbls'] || $BJ['chklbls']) {
						if (!($nsst || $vsst || $psst)) {
							die("At least one of the annotations to be reconciled contains tags, so an appropriate URL parameter should be specified.");
						}
						$reconciledLbls = reconcile_tags(htmlspecialchars_decode($tokenizedS, ENT_QUOTES), $AJ['chklbls'], $BJ['chklbls']);
						$sentdata['chklbls'] = htmlspecialchars(json_encode($reconciledLbls), ENT_QUOTES);
					}
					
					$sentdata['reconcile'] = array($A, $B);
					$sentdata['recon'] = reconcile_mwes($tokenizedS, $A, $B);
					$sentdata['initval'] = reconcile_mwes($tokenizedS, $A, $B);
					if ($sentdata['initval']==='') 
						$sentdata['initval'] = '#cannot-auto-reconcile#';
				}
				
				if ($versions || $vv!==null) {	// load all versions of this sentence
					if ($versions && strlen($_REQUEST['versions'])>0) {
						// filter to a specified user
						$sentdata['versions'] = get_key_values(get_user_dir($_REQUEST['versions']) . "/$split.nanni.all", $sentId);
					}
					else {	// all users
						$sentdata['versions'] = get_key_values(get_user_dir('*') . "/$split.nanni.all", $sentId);
					}
				}
				array_push($SENTENCES, $sentdata);
			}
			$l++;
		}
		fclose($f);
	}
}
else if ($iFrom==-1) {	// demo mode
	$SENTENCES = array(array('sentenceId' => 'test0', 'sentence' => 'It even got a little worse during a business trip to the city , so on the advice of a friend I set up an appointment with True Massage .'));
} else if ($iFrom==-2) {
	$SENTENCES = array(array('sentenceId' => 'tortureTest', 'sentence' => "~ TORTURE TEST $ & &c. \" $ | a w x y1 y2 z q b ~ c ~ _ _"));
}

?>
<script type="text/javascript" language="javascript" src="js/jquery_1.8.2.min.js"></script>
<script type="text/javascript" language="javascript" src="js/jquery.json-2.3.min.js"></script>
<script type="text/javascript" language="javascript" src="js/jquery-ui_1.9.2.js"></script>
<script type="text/javascript" language="javascript" src="js/jquery.ui.autocomplete.html.js"></script>
<script type="text/javascript" language="javascript" src="js/sisyphus.min.js"></script>
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



/************ ANNOTATION PROTOCOL ************/
/*
This protocol attempts to standardize several key components of the annotation process 
so that kinds of annotation, or "annotators", can be defined and applied in a modular way. 

Some terminology:
items: independent units to be presented for annotation, such as sentences. 
       Defined by the input and common to all annotators. Must not be empty.
annotator:
       interface that manages a particular kind of annotation for an item, 
       mediating among the item's input, the current configuration, the interface 
       (rendering), user actions, and the output.
passive annotator: 
       annotator used without the opportunity for human interaction. 
       e.g. could be used to submit metadata along with an item, or to display previous 
       annotations that are not modifiable at present.
targets:
       annotation sites identified by an annotator for a particular item. 
       e.g., specific tokens in the item, or the item as a whole. 
       Targets for different annotators can be at different levels of granularity. 
       Each target must be identified relative to unmodifiable characteristics the 
       original input (e.g., tokenization if this is not subject to change, or 
       non-whitespace character offsets if whitespace can change, etc.). 
       At any given time, the space of valid targets for one annotator (kind of annotation) 
       may be defined by a lower-level kind of annotation (earlier annotator). 
       "Communication" between different levels of annotation is performed with the 
       updateTargets() method.
       N.B.: In the code, 'target' refers to the control created by a particular annotator 
       that holds the annotation.
actor: inferface that encapsulates an annotator's functionality with respect to a single 
       target. ASSUMPTION: each actor will render and control at least one interface 
       element, known in the code as the 'target', and this element will not be shared 
       with any other actor.
value: the annotation for a given annotator and target.
default value: 
       value determined automatically, not provided or reviewed by a human annotator. 
       possibly empty or a special flag (e.g. UNANN).
system value: 
       default value determined by running some automatic tool, such as a tagger.
initial value: 
       value populated at page load. could be a default or a previous annotation for the target.
final value: 
       value when the annotation is submitted (part of the item output).
candidates: 
       possible values. an annotator may specify a global candidate set which is filtered 
       down depending on the target, other annotations (of the same or different kinds) 
       in the same item as the target, etc. The candidate set may be open-ended 
       (e.g., all strings) or confined to a whitelist.
legal analysis: 
       complete set of annotations for an item that is "acceptable" to all the annotators 
       (meets any validity conditions).
control: 
       interface element(s) allowing the user to specify an annotation value for a target.
structural annotation {update}: 
       potentially affects {changes} the space of possible targets for another level of 
       annotation. e.g. changing the tokenization or grouping tokens into chunks affects 
       the targets available for labeling those tokens/chunks.

Classes for actors and annotators are defined below, followed by the guts of the 
annotation execution model in the global variables/functions:
  annotators
  II
  AA
  ann_init()
  ann_setup()
  ann_update()
  ann_submit()
*/


function Actor(ann) {
	this.ann = ann;
}
Actor.prototype.initValue = function() {
	this.firstrender();
	this.validate();
	return {'initialization': true, 'isStructural': false};
}
Actor.prototype.setValue = function() {
	this.validate();
	var oldvalue = null;
	this.rerender();
	return {'oldval': oldvalue, 'isStructural': false};
}
Actor.prototype.validate = function() { }
Actor.prototype.rerender = function() { }
Actor.prototype.listenForInteraction = function() { /* nothing to do */ }
Actor.prototype.remove = function() {
	var j = this.ann.actors.indexOf(this);
	if (j<0)
		return false;	// already removed
	$(this.target).remove();
	this.ann.actors.splice(j,1);
	return true;
}

function Annotator(self, I, itemId) {
	self.I = I;
	self.item = II[I];
	self.itemId = itemId;
	self.submittable = false;
	self.constructor.started = false;
	self.constructor.stopped = false;
}
Annotator.prototype.start = function() { this.constructor.started=true; }	// make annotation controls available to the user
Annotator.prototype.stop = function() { this.constructor.stopped=true; }	// make annotation controls unavailable to the user

function ItemNoteAnnotator(I, itemId) {
	this._name = 'ItemNoteAnnotator';
	Annotator(this, I, itemId);
	this.actors = [];
}
ItemNoteAnnotator.prototype.identifyTargets = function() {
	if ($(this.item).find('p.sent').find('span.w').length==0) {
		// no sentence. e.g., in versions browser
		return;
	}
	
	var a = new Actor(this);
	this.actors.push(a);
	a.getValue = function () {
		return this.target.value;
	}
	a.firstrender = function () {
		var item = this.ann.item;
		var itemId = this.ann.itemId;
		this.initval = $(item).find('input.initnote').val();
		var $control = $('<textarea/>').attr({"id": "note_"+itemId, "name": "note[]", 
										  "placeholder": "Note for sentence "+itemId+" (optional)",
										  "rows": "1", "cols": "80"}).addClass("comment").val(this.initval);
		this.target = $control.get(0);
		$('<p/>').append($control).insertAfter($(item).find('p.buttons'));
		this.ann.submittable = true;
	}
	a.rerender = function () {	// if a value may have changed, decide whether to show/hide the control
		$control = $(this.target);
		if ($control.prop("readonly") && $control.val()==="") $control.hide();
		else $control.show();
	}
}
ItemNoteAnnotator.prototype.validate = function() { }


function MWEAnnotator(I, itemId) {
	this._name = 'MWEAnnotator';
	Annotator(this, I, itemId);
	this.actors = [];
}
MWEAnnotator.prototype.identifyTargets = function() {
	if ($(this.item).find('p.sent').find('span.w').length==0) {
		// no sentence. e.g., in versions browser
		return;
	}
	
	var a = new Actor(this);
	this.actors.push(a);
	
	a.getValue = function() {
		return this.target.value;
	};
	a.firstrender = function() {
		var item = this.ann.item;
		var itemId = this.ann.itemId;
		var $sentence = $(item).find('p.sent');
		this.sentence = $sentence.text();
		this.initval = $(item).find('input.initval').val();
		if (!this.initval)
			this.initval = this.sentence;
		var $control = $('<textarea/>').attr({"id": "mwe_"+itemId, "name": "mwe[]", 
										  "rows": "3", "cols": "80"}).addClass("input").val(this.initval);
		var $out1 = $('<input type="hidden" name="sgroups[]" class="sgroups" value=""/>').attr({"id": "sgroups_"+itemId});
		var $out2 = $('<input type="hidden" name="wgroups[]" class="wgroups" value=""/>').attr({"id": "wgroups_"+itemId});
		this.target = $control.get(0);
		$('<p/>').append($control).append($out1).append($out2).insertAfter($sentence);
		
		this.ann.submittable = true;
	};
	a.listenForInteraction = function() {
		var theactor = this;
		$(this.target).bind('input', function (evt) {
			ann_update(theactor, theactor.getValue());
		});
		// update now with preloaded/default data
		ann_update(theactor, theactor.getValue());
	};
	a.setValue = function() {
		return {'isStructural': true};
	};
}


MWEAnnotator.prototype.updateTargets = function(updateInfo) { }
MWEAnnotator.prototype.isGrouped = function(tknOffset, strength) {
	if (arguments.length<2) strength = 'both';
	// strength is one of ('strong', 'weak', 'both')
	var strong = (strength=='strong' || strength=='both');
	var weak = (strength=='weak' || strength=='both');
	var classes = ' '+$(this.item).find('.sent .w[data-w='+tknOffset+']').attr("class");
	return ((strong && classes.indexOf(' slu')>-1) || (weak && classes.indexOf(' wlu')>-1));
}

/* poses (optional): list of POSes for the sentence; 
filterFxn (optional): function that must be true for some (word, POS) pair in the chunk 
for the chunk to be included 
firstOnlyPlusWhitelist (optional): if an array, return false unless the first token in the chunk 
is matched by filterFxn OR (when lowercased) by one of the words in the array. 
In either case, filterFxn must return true for some word in the chunk.
*/
MWEAnnotator.prototype.isChunkBeginner = function(tknOffset, strength, poses, filterFxn, firstOnlyPlusWhitelist) {
	if (arguments.length<2) strength = 'both';
	if (arguments.length<3) poses = null;
	if (arguments.length<4) filterFxn = function (word, pos) { return true; };
	if (arguments.length<5) firstOnlyPlusWhitelist = false;
	var $thisitem = $(this.item);
	var getTok = function (toffset) {
		return $thisitem.find('.sent .w[data-w='+toffset+']');
	};
	var thistok = getTok(tknOffset);
	var w = thistok.text();
	if (!this.isGrouped(tknOffset, strength)) {
		return (poses===null || filterFxn(w, poses[tknOffset]));
	}
	// strength is one of ('strong', 'weak', 'both')
	var strong = (strength=='strong' || strength=='both');
	var weak = (strength=='weak' || strength=='both');
	
	var classes = ' '+thistok.attr("class");
	var sb = wb = false;
	if (strong && classes.indexOf(' slu')>-1) {
		sb = $thisitem.find('.sent .w.slu'+thistok.data("slu")).eq(0).data("w");
		if (sb!=tknOffset) return false;
		sb = ($thisitem.find('.sent .w.slu'+thistok.data("slu")).filter(function (j) {
				return (poses===null || filterFxn(getTok($(this).data("w")).text(), poses[$(this).data("w")]));
			}).length>0);
	}
	if (weak && classes.indexOf(' wlu')>-1) {
		wb = $thisitem.find('.sent .w.wlu'+thistok.data("wlu")).eq(0).data("w");
		if (wb!=tknOffset) return false;
		wb = ($thisitem.find('.sent .w.wlu'+thistok.data("wlu")).filter(function (j) {
				return (poses===null || filterFxn(getTok($(this).data("w")).text(), poses[$(this).data("w")]));
			}).length>0);
	}
	if (firstOnlyPlusWhitelist)	// note that an empty array is truthy in Javascript
		return (sb || wb) && (poses===null || filterFxn(w, poses[tknOffset]) || firstOnlyPlusWhitelist.indexOf(w.toLowerCase())>-1);
	return (sb || wb);
}
MWEAnnotator.prototype.nextInChunk = function(tknOffset, strength) {
	if (arguments.length<2) strength = 'both';
	if (!this.isGrouped(tknOffset, strength))
		return true;
	// strength is one of ('strong', 'weak', 'both')
	var strong = (strength=='strong' || strength=='both');
	var weak = (strength=='weak' || strength=='both');
	var thistok = $(this.item).find('.sent .w[data-w='+tknOffset+']');
	var classes = ' '+thistok.attr("class");
	var sb = wb = -1;
	if (strong && classes.indexOf(' slu')>-1) {
		var sgroups = $(this.item).find('.sent .w.slu'+thistok.data("slu")).filter(function (j) {
			return $(this).data("w")>tknOffset;
		}).eq(0).data("w");
	}
	if (weak && classes.indexOf(' wlu')>-1) {
		wb = $(this.item).find('.sent .w.wlu'+thistok.data("wlu")).filter(function (j) {
			return $(this).data("w")>tknOffset;
		}).eq(0).data("w");
	}
	return (sb==tknOffset && wb==tknOffset);
}
MWEAnnotator.prototype.isChunkSegmentEnder = function(tknOffset, strength) {
	if (arguments.length<2) strength = 'both';
	if (!this.isGrouped(tknOffset, strength))
		return true;
	// strength is one of ('strong', 'weak', 'both')
	var strong = (strength=='strong' || strength=='both');
	var weak = (strength=='weak' || strength=='both');
	var thistok = $(this.item).find('.sent .w[data-w='+tknOffset+']');
	var classes = ' '+thistok.attr("class");
	var nexttok = $(this.item).find('.sent .w[data-w='+(tknOffset+1)+']');
	if (strong && classes.indexOf(' slu')>-1) {
		if (nexttok.data("slu")==thistok.data("slu"))
			return false;
	}
	if (weak && classes.indexOf(' wlu')>-1) {
		if (nexttok.data("wlu")==thistok.data("wlu"))
			return false;
	}
	return true;
}
MWEAnnotator.prototype.isChunkEnder = function(tknOffset, strength) {
	if (arguments.length<2) strength = 'both';
	if (!this.isGrouped(tknOffset, strength))
		return true;
	// strength is one of ('strong', 'weak', 'both')
	var strong = (strength=='strong' || strength=='both');
	var weak = (strength=='weak' || strength=='both');
	var thistok = $(this.item).find('.sent .w[data-w='+tknOffset+']');
	var classes = ' '+thistok.attr("class");
	var sb = wb = tknOffset;
	if (strong && classes.indexOf(' slu')>-1) {
		sb = $(this.item).find('.sent .w.slu'+thistok.data("slu")).eq(-1).data("w");
	}
	if (weak && classes.indexOf(' wlu')>-1) {
		wb = $(this.item).find('.sent .w.wlu'+thistok.data("wlu")).eq(-1).data("w");
	}
	return (sb==tknOffset && wb==tknOffset);
}
MWEAnnotator.prototype.validate = function() {
	var x = parseMWEMarkup($('.item .input').val());
	if (typeof x==="string") {
		$('.input').get(0).setCustomValidity(x);
	}
	else {
		$('.input').get(0).setCustomValidity("");
		//console.log(x);
		
		var sgroups = x[0];
		var wgroups = x[1];
//console.log(sgroups);
//console.log(wgroups);
		
		var sGroups = [];	// indices of *multiword* groups
		var sSets = [];	// sets of token offsets for *multiword* groups
		
		// clear existing groups
		for (var j=0; j<8; j++) $('.item .sent .w').removeClass('slu'+j)
		$('.item .sent .w').removeData("slu");
		
		for (var i=0; i<sgroups.length; i++) {
			var g = sgroups[i];
			if (sgroups.indexOf(g)!=sgroups.lastIndexOf(g)) {	// multiword group
				if (sGroups.indexOf(g)==-1) {	// new multiword group
					sGroups.push(g);
					sSets.push([]);
				}
				sSets[sGroups.indexOf(g)].push(i);
				$('.item .sent .w[data-w='+i+']').addClass('slu'+sGroups.indexOf(g)%8).data("slu", sGroups.indexOf(g));
			}
		}
		$('.item .sgroups').val($.toJSON(sSets));
//console.log(sSets);

		var wGroups = [];	// indices of *multiword* groups
		var wSets = [];	// sets of token offsets for *multiword* groups

		// clear existing groups
		for (var j=0; j<8; j++) $('.item .sent .w').removeClass('wlu'+j)
		$('.item .sent .w').removeData("wlu");
		
		for (var i=0; i<wgroups.length; i++) {
			var g = wgroups[i];
			if (wgroups.indexOf(g)!=wgroups.lastIndexOf(g)) {	// multiword group
				if (wGroups.indexOf(g)==-1) {	// new multiword group
					wGroups.push(g);
					wSets.push([]);
				}
				wSets[wGroups.indexOf(g)].push(i);
				
			}
		}
		// transitive closure over strong sets belonging to each weak set
		for (var j=0; j<wSets.length; j++) {
			for (var i=0; i<wSets[j].length; i++) {
				var itok = wSets[j][i];
				var g = sgroups[itok];
				if (sGroups.indexOf(g)>-1) {	// this token belongs to a strong multiword group
					var sset = sSets[sGroups.indexOf(g)];
					for (var k=0; k<sset.length; k++) {
						if (wSets[j].indexOf(sset[k])==-1)
							wSets[j].push(sset[k]);	// grab another member of that same multiword group
					}
				}
			}
		}
		// merge parts of gappy weak sets in case they contain strong sets
		// test case: It even got a~little_worse~ during a ~business trip to the city , so on the advice of a friend I set up an appointment with True Massage .
		var merged = [];
		for (var j=1; j<wSets.length; j++) {
			for (var i=0; i<wSets[j].length; i++) {
				var itok = wSets[j][i];
				for (var j2=0; j2<j; j2++) {
					if (merged.indexOf(j2)==-1 && wSets[j2].indexOf(itok)>-1) {	// this token belongs to another weak multiword group (that has not already been merged)
						for (var k=0; k<wSets[j].length; k++) {
							if (wSets[j2].indexOf(wSets[j][k])==-1)
								wSets[j2].push(wSets[j][k]);
						}
						merged.push(j);
						break;
					}
				}
				if (merged.indexOf(j)>-1) break;
			}
		}
		for (var k=merged.length-1; k>=0; k--) {
			wSets.splice(merged[k],1);
		}
		// filter list of weak sets to exclude exactly matching strong sets
		for (var j=0; j<wSets.length; j++) {
			wSets[j].sort();
		
			var removingeq = true;
			while (removingeq && j<wSets.length) {
				removingeq = false;
				for (var k=0; k<sSets.length; k++) {
					if (arraysEq(sSets[k], wSets[j])) {	// this exactly matches a strong grouping, so don't include it as a weak grouping
						wSets.splice(j,1);
						removingeq = true;
						break;
					}
				}
			}
		}
		
		// display weak sets
		for (var j=0; j<wSets.length; j++) {
			for (var i=0; i<wSets[j].length; i++)
				$('.item .sent .w[data-w='+wSets[j][i]+']').addClass('wlu'+j%8).data("wlu", j);
		}
		$('.item .wgroups').val($.toJSON(wSets));
//console.log('weak');
//console.log(wSets);
	}
}

/** Creates a text box for inline annotation of tokens in the sentence. 
    A predefined list of labels may be used for suggesting and/or constraining the annotations. */
N_LABEL_SHORTCUTS = {
		'LOCATION': 'L', 
		'PERSON': 'P', 
		'TIME': 'T', 
		'GROUP': 'G', 
		'ANIMAL': 'N', 
		'FOOD': 'D', 
		'PLANT': 'Y', 
		'BODY': 'B', 
		'FEELING': 'F', 
		'ARTIFACT': 'A', 
		'NATURAL OBJECT': 'O',
		'SUBSTANCE': '$', 
		'COGNITION': '^', 
		'COMMUNICATION': 'C', 
		'PHENOMENON': 'X', 
		'PROCESS': 'R', 
		'ATTRIBUTE': '@', 
		'RELATION': '=', 
		'MOTIVE': 'M', 
		'POSSESSION': 'H', 
		'SHAPE': '+', 
		'QUANTITY': 'Q', 
		'ACT': '!', 
		'EVENT': 'E', 
		'STATE': 'S', 
		'OTHER': '_'
};
V_LABEL_SHORTCUTS = {
		'body': 'b',
		'change': 'x',
		'cognition': 'g',
		'communication': 'c',
		'competition': 'v',
		'consumption': 'd',
		'contact': 'n',
		'creation': 'r',
		'emotion': 'e',
		'motion': 'm',
		'perception': 'p',
		'possession': 'h',
		'social': 'l',
		'stative': 's',
		'weather': 'w',
		'`a': '`a',
		'`j': '`j'
};
PREPS_MASTER = ["2", "4", "a", "abaft", "aboard", "about", "above", "abreast", "abroad", "absent", "across", 
	"adrift", "afore", "aft", "after", "afterward", "afterwards", "against", "agin", "ago", 
	"aground", "ahead", "aloft", "along", "alongside", "amid", "amidst", "among", "amongst", 
	"an", "anent", "anti", "apart", "apropos", "apud", "around", "as", "ashore", "aside", 
	"aslant", "astraddle", "astride", "asunder", "at", "athwart", "atop", "away", "back", 
	"backward", "backwards", "bar", "barring", "before", "beforehand", "behind", "below", 
	"beneath", "beside", "besides", "between", "betwixt", "beyond", "but", "by", "c.", "cept", 
	"chez", "circa", "come", "concerning", "considering", "counting", "cum", "dehors", "despite", 
	"down", "downhill", "downstage", "downstairs", "downstream", "downward", "downwards", 
	"downwind", "during", "eastward", "eastwards", "ere", "ex", "except", "excepting", "excluding", 
	"failing", "following", "for", "forbye", "fore", "fornent", "forth", "forward", "forwards", 
	"frae", "from", "gainst", "given", "gone", "granted", "heavenward", "heavenwards", "hence", 
	"henceforth", "home", "homeward", "homewards", "in", "including", "indoors", "inside", "into", 
	"inward", "inwards", "leftward", "leftwards", "less", "like", "mid", "midst", "minus", 
	"mod", "modulo", "mongst", "near", "nearby", "neath", "next", "nigh", "northward", "northwards", 
	"notwithstanding", "o'", "o'er", "of", "off", "on", "onto", "onward", "onwards", "opposite", 
	"out", "outdoors", "outside", "outta", "outward", "outwards", "outwith", "over", "overboard", 
	"overhead", "overland", "overseas", "overtop", "pace", "past", "pending", "per", "plus", "pon", 
	"post", "pro", "qua", "re", "regarding", "respecting", "rightward", "rightwards", "round", 
	"sans", "save", "saving", "seaward", "seawards", "since", "skyward", "skywards", "southward", 
	"southwards", "than", "thenceforth", "thro'", "through", "throughout", "thru", "thruout", 
	"thwart", "'til", "till", "times", "to", "together", "touching", "toward", "towards", "under", 
	"underfoot", "underground", "underneath", "unlike", "until", "unto", "up", "uphill", "upon", 
	"upside", "upstage", "upstairs", "upstream", "upward", "upwards", "upwind", "v.", "versus", 
	"via", "vice", "vis-a-vis", "vis-à-vis", "vs.", "w/", "w/i", "w/in", "w/o", "westward", 
	"westwards", "with", "withal", "within", "without", 
	"a cut above", "a la", "à la", "according to", "after the fashion of", "ahead of", "all for", 
	"all over", "along with", "apart from", "as far as", "as for", "as from", "as of", 
	"as opposed to", "as regards", 
	"as to", "as well as", "aside from", "at a range of", "at the hand of", "at the hands of", 
	"at the heels of", "back of", "bare of", "because of", "but for", "by courtesy of", 
	"by dint of", "by force of", "by means of", "by reason of", "by the hand of", 
	"by the hands of", "by the name of", "by virtue of", "by way of", "care of", "complete with", 
	"contrary to", "courtesy of", "depending on", "due to", "except for", "exclusive of", "for all", 
	"for the benefit of", "give or take", "having regard to", "in accord with", "in addition to", 
	"in advance of", "in aid of", "in back of", "in bed with", "in behalf of", "in case of", 
	"in common with", "in company with", "in connection with", "in consideration of", 
	"in contravention of", "in default of", "in excess of", "in face of", "in favor of", 
	"in favour of", "in front of", "in honor of", "in honour of", "in keeping with", "in lieu of", 
	"in light of", "in line with", "in memoriam", "in need of", "in peril of", "in place of", 
	"in proportion to", "in re", "in reference to", "in regard to", "in relation to", 
	"in respect of", "in sight of", "in spite of", "in terms of", "in the course of", 
	"in the face of", "in the fashion of", "in the grip of", "in the light of", "in the matter of", 
	"in the midst of", "in the name of", "in the pay of", "in the person of", "in the shape of", 
	"in the teeth of", "in the throes of", "in token of", "in view of", "in virtue of", 
	"inclusive of", "inside of", "instead of", "irrespective of", "little short of", "more like", 
	"near to", "next door to", "next to", "nothing short of", "of the name of", "of the order of", 
	"on a level with", "on a par with", "on account of", "on behalf of", "on pain of", 
	"on the order of", "on the part of", "on the point of", "on the score of", "on the strength of", 
	"on the stroke of", "on top of", "other than", "out of", "out of keeping with", 
	"out of line with", "outboard of", "outside of", "over against", "over and above", "owing to", 
	"preparatory to", "previous to", "prior to", "pursuant to", "regardless of", "relative to", 
	"round about", "short for", "short of", "such as", "subsequent to", "thanks to", "this side of", 
	"to the accompaniment of", "to the tune of", "together with", "under cover of", "under pain of", 
	"under sentence of", "under the heel of", "up against", "up and down", "up before", "up for", 
	"up to", "upward of", "upwards of", "vis a vis", "vis à vis", "vis - a - vis", "vis - à - vis", 
	"with reference to", "with regard to", "with respect to", "with the exception of", 
	"within sight of"];
PREP_SPECIAL_MW_BEGINNERS = ["a", "according", "all", "bare", "because", "but", "care", "complete", 
"contrary", "courtesy", "depending", "due", "exclusive", "inclusive", "instead", 
"irrespective", "little", "more", "next", "nothing", "other", "outboard", "owing", 
"preparatory", "previous", "prior", "pursuant", "regardless", "relative", "short", 
"subsequent", "such", "thanks", "this"];
// removed "having" because it turns up false positives with "have to" (quasimodal)
/* // Do not contain any single-word prepositions, therefore will not be matched:
a la
à la
give or take
vis a vis
vis à vis
vis - a - vis
vis - à - vis
*/

PSST_LIST_OF_LABELS = ['1DTrajectory', '2DArea', '3DMedium', 'Accompanier', 'Activity',
    'Age', 'Agent', 'Approximator', 'Attribute', 'Beneficiary',
    'Causer', 'Circumstance', 'ClockTimeCxn', 'Co-Agent',
    'Co-Patient', 'Co-Theme', 'Comparison/Contrast', 'Contour',
    'Course', 'Creator', 'DeicticTime', 'Destination', 'Direction',
    'Donor/Speaker', 'Duration', 'Elements', 'EndState', 'EndTime',
    'Experiencer', 'Explanation', 'Extent', 'Frequency', 'Function',
    'Goal', 'InitialLocation', 'Instrument', 'Location', 'Locus',
    'Manner', 'Material', 'Means', 'Other', 'Partitive', 'Patient',
    'Possessor', 'ProfessionalAspect', 'Purpose', 'Recipient',
    'Reciprocation', 'RelativeTime', 'Scalar/Rank', 'Source',
    'Species', 'StartState', 'StartTime', 'State', 'Stimulus',
    'Superset', 'Temporal', 'Theme', 'Time', 'Topic', 'Transit',
    'Value', 'ValueComparison', 'Via', 'Whole', '`', '`d', '`i', '`a',
    '`j', '`o', '?'];

PSST_SHORT_DEFS = {
    "1DTrajectory": "One-dimensional path that is the location traversed. {past, through, between, down, by, above, out of, over, around, about, along, up, across, round}", 
    "2DArea": "Two-dimensional area that is the location traversed. {around, about, all over, round}", 
    "3DMedium": "Three-dimensional medium that is traversed through. {through, with}", 
    "Accompanier": "Entity that another entity is together with. {with}", 
    "Activity": "Action that an agent undertakes for a period of time. {into, at, from, on}", 
    "Actor": "Abstract macro-role. {}", 
    "Age": "Something's age. {at, in, of}", 
    "Agent": "Animate/volitional/intentional instigator of an action. {by}", 
    "Approximator": "Measurement, quantity, or range being &quot;transformed&quot; by the preposition. {between, under, over, around, about}", 
    "Attribute": "Characteristic, feature, part, or possessum of an entity. {as, in, by, with, of}", 
    "Beneficiary": "Animate or personified undergoer that is (potentially) advantaged or disadvantaged by the event or state. {by, against, to, towards, on, with, for}", 
    "Causer": "Force/party that precipitates an event (and is a central participant of it). {by, from, with, of}", 
    "Circumstance": "Macro-role. Used directly for occasions and contextualizations. {in, with, for}", 
    "ClockTimeCxn": "In constructions for telling time, the hour part. {past, to, after, of}", 
    "Co-Agent": "Participant acting symmetrically with the agent. {between, by, against, with}", 
    "Co-Patient": "Undergoer symmetrical to the Patient. {between}", 
    "Co-Theme": "Undergoer symmetrical to the Theme. {between, from, to, with, for}", 
    "Comparison/Contrast": "Point of comparison, analogy, imitation, or contrast/distinction. {near, between, as opposed to, from, against, to, than, beside, after, instead of, like}", 
    "Configuration": "Abstract macro-role for static configurational relationships typically between nominals. {}", 
    "Contour": "Path shape. {in}", 
    "Course": "Pathway used for travel. {via, through, down, by, up}", 
    "Creator": "Participant that has created something new. {by, of}", 
    "DeicticTime": "Earlier or later time expressed by the length of an interval relative to an implicit reference time. {in, before, for, inside}", 
    "Destination": "Endpoint (actual or intended) of physical motion. {into, through, at, in, down, against, to, under, out, over, on, for, inside, up, onto, round}", 
    "Direction": "Orientation or heading of a figure, without implying a specific starting or ending point of the path. {into, at, in, out, towards, after, off, away, back}", 
    "Donor/Speaker": "Animate starting point in a transfer scenario; counterpart to Recipient. {from}", 
    "Duration": "Temporal length of an event. {into, through, in, down, over, during, for, across}", 
    "Elements": "Member or non-member of a set of discrete items. {except for, except, on top of, such as, apart from, in addition to, but, besides, like, of, aside from}", 
    "EndState": "Result of a change or endpoint of a qualitative range. {into, to}", 
    "EndTime": "When an event ends. {through, to, until}", 
    "Experiencer": "Patient that is aware of the event undergone (specific to events of perception). {to, for}", 
    "Explanation": "Secondary event that is asserted as the reason for the main event. {on account of, because of, owing to, as, thanks to, by virtue of, after, for, due to}", 
    "Extent": "Size of a path. {through, by, for, of}", 
    "Frequency": "Temporal rate or repetitions. {at, by, after, for}", 
    "Function": "The function that an entity serves, or for which it was made. {by, to, towards, for}", 
    "Goal": "Abstract intended endpoint or outcome. {at, towards, on}", 
    "InitialLocation": "Locative origin or starting point of physical motion. {from, out of, off}", 
    "Instrument": "Thing that enables or facilitates achieving some purpose. {at, with}", 
    "Location": "Concrete location. {among, within, near, past, through, at, in, between, down, before, in front of, by, from, against, to, beside, outside, on top of, above, under, out of, over, around, behind, about, on, below, all over, off, of, inside, up, across, without, beneath, round}", 
    "Locus": "Abstract location. {among, at, in, on}", 
    "Manner": "How something happens. {according to, in, by, with}", 
    "Material": "Ingredients/material from which something is made. {from, of}", 
    "Means": "An activity that facilitates achieving some purpose. {via, through, by, with}", 
    "Other": "Miscellaneous. {before, by, on}", 
    "Partitive": "Quantified kind or mass. {of}", 
    "Path": "Abstract region between an initial position and a final position. {}", 
    "Patient": "Undergoer in an event that experiences a change of state, location or condition, that is causally involved or directly affected by other participants, and exists independently of the event. {to, over}", 
    "Place": "Abstract macro-role. {}", 
    "Possessor": "Possessor of an entity (including kin) or attribute. {behind, about, on, of}", 
    "ProfessionalAspect": "Entity with which an individual is formally associated in a social/professional scenario such as employment or membership. {at, in, to, outside, under, on, with, for}", 
    "Purpose": "Something that somebody wants to bring about. {to, for}", 
    "Recipient": "Animate intended recipient of something concrete or abstract. Includes addressees. {to}", 
    "Reciprocation": "Action provoking a judgment-laden response/reaction that is the main event. {for}", 
    "RelativeTime": "Time before or after some reference point. {past, between, before, by, since, towards, after}", 
    "Scalar/Rank": "Point of comparison on a continuous or discrete scale (such as an ordering or ranking). {before, above, under, over, behind, after, below, for, beneath}", 
    "Source": "Abstract starting point. {from, off}", 
    "Species": "Instance of a kind or content of a shell noun. {of}", 
    "StartState": "Initial state or beginning of a qualitative range. {from}", 
    "StartTime": "Time an event begins. {from, since}", 
    "State": "State of affairs. {near, at, in, under, on, all over, off, in need of}", 
    "Stimulus": "That which is perceived or experienced. {at, by, with, of}", 
    "Superset": "Discrete containing set. {among, in, out of, of}", 
    "Temporal": "Macro-role for temporal characteristics. {by}", 
    "Theme": "Central undergoer that is not in control over the event, not structurally changed by the event, and/or is characterized as being in a certain position or condition. {to, on, with, for, of}", 
    "Time": "Point in time. {near, as, at, in, around, on, for}", 
    "Topic": "Information content. {into, in, as to, re, over, towards, around, concerning, about, on, with, regarding, for, of, as regards, round}", 
    "Transit": "Conveyance/vehicle that the entity occupies in order to get from one place to another. {by}", 
    "Traversed": "Location traversed in a path. {}", 
    "Undergoer": "Abstract macro-role. {}", 
    "Value": "Point on a formal scale. {into, at, in, by, to, per, for, of}", 
    "ValueComparison": "Value being compared to some other value on a scale. {near, above, below}", 
    "Via": "Path/instrument that enables concrete or abstract entities (including information, etc.) to get from one place to another. {via, through, over, on}", 
    "Whole": "Complete or complex whole entity. {of}"
};

PSST_LABELS = {"about": {"1DTrajectory": [18, "They skipped about my feet, a flock of lambs", "rotated 90 degrees clockwise about its lowest corner", "wrapped an arm about Emilia's shoulders"], 
    "2DArea": [90, "She wore her dark hair in plaits about her head.", "she looked about the room."], 
    "Approximator": [0, "It will take about an hour."], 
    "Location": [1, "rugs were strewn about the hall", "he produced a knife from somewhere about his person."], 
    "Possessor": [6, "there was a look about her that said everything."], 
    "Topic": [967, "I was thinking about you", "a book about ancient Greece", "it's all about having fun.", "there's nothing we can do about it."]},
"above": {"1DTrajectory": [3, "a cable runs above the duct."], 
    "Location": [54, "from his flat above the corner shop", "bruises above both eyes.", "a display of fireworks above the town", "in the hills above the capital", "on the wall above the altar.", "she held her arms above her head."], 
    "Scalar/Rank": [17, "the firm cynically chose profit above car safety.", "the food was well above average", "she married above her.", "at a level above the common people.", "he seldom spoke above a whisper", "the doorbell went unheard above the din."], 
    "ValueComparison": [8, "above sea level."]},
"according to": {"Manner": [109, "Nationwide's business is not going quite according to plan.", "cook the spaghetti according to the instructions on the pack.", "The degree of distress experienced varies according to what people expect.", "We are told that it is for a health service available to all according to their needs"], 
    "`d": [141, "A somewhat unusual system one might think : but not according to the papal encyclical Quadragesimo Anno of 1931.", "Both sides won , according to statements when the case was called off .", "We are already well on the way to being addicted to internet gambling, according to some press reports."]},
"across": {"1DTrajectory": [469, "the bridge (that goes) across the river", "he looked across at me", "halfway across, Jenny jumped.", "I ran across the street", "travelling across Europe", "he had swum across."], 
    "Duration": [1], 
    "Location": [17, "they lived across the street from one another"]},
"after": {"ClockTimeCxn": [0, "I strolled in about ten minutes after two."], 
    "Comparison/Contrast": [7, "a drawing after Millet's The Reapers.", "they named her Pauline, after Barbara's mother."], 
    "Direction": [9, "she stared after him."], 
    "Explanation": [19, "Both were crestfallen after Stevie's erratic performance", "The sergeant thought Blanche would be tired after the tension of the previous hour or two"], 
    "Frequency": [0, "day after day we kept studying."], 
    "RelativeTime": [60, "shortly after Christmas", "after a while he returned", "he'd gone out with his secretary for an after-work drink."], 
    "Scalar/Rank": [0, "in their order of priorities health comes after housing."], 
    "`": [18]},
"against": {"Beneficiary": [21, "the first victim gave evidence against him."], 
    "Co-Agent": [14, "the championship match against Somerset."], 
    "Comparison/Contrast": [0, "the benefits must be weighed against the costs."], 
    "Destination": [124, "frustration made him bang his head against the wall."], 
    "Location": [124, "she stood with her back against the door"]},
"all over": {"2DArea": [0, "I traveled all over France"], 
    "Location": [0, "there are crumbs all over the table"], 
    "State": [0, "I went to the party and I had women all over me."]},
"along": {"1DTrajectory": [535, "cars were parked along the grass verge", "the path along the cliff.", "soon we were driving along a narrow road", "he saw Gray run along the top of the wall", "we continued to plod along."]},
"among": {"Location": [56, "flowers hidden among the roots of the trees", "you're among friends."], 
    "Locus": [58, "a drop in tooth decay among children", "members of the government bickered among themselves."], 
    "Superset": [35, "a British woman was among the 54 victims of the disaster", "snakes are among the animals most feared by man."]},
"apart from": {"Elements": [85, "Quite apart from the aforementioned lovely inhabitants, the city itself is great.", "I have substantial loans, apart from my home loan, a large chunk of which is credit card debt.", "One admits that apart from his papers, his briefcase also contains a pair of swimming trunks.", "We went to hospital to get checked out but apart from bruises we were fine.", "He had been smoking since he was 12, apart from a few years when he managed to stop while in the Army.", "According to these diagnoses the patient seems quite normal apart from having mad ideas."]},
"around": {"1DTrajectory": [161, "he put his arm around her.", "They zigzagged around tree trunks", "Lily peeped around the open curtain", "he walked around the airfield", "it can drill around corners."], 
    "2DArea": [235, "cycling around the village", "a number of large depots around the country."], 
    "Approximator": [0, "It will take around an hour."], 
    "Location": [106, "the palazzo is built around a courtyard", "the hills around the city.", "He must have parked around the front of the motel"], 
    "Time": [21, "They left around midnight."], 
    "Topic": [3, "our entire culture is built around those loyalties."]},
"as": {"Attribute": [257, "it came as a shock", "she got a job as a cook."], 
    "Explanation": [0, "I will appoint him as he is most qualified for the job."], 
    "Time": [0, "Everyone stood as the president entered."], 
    "`": [49, "\"You know, you're AS bad AS he is\"", "\"Enough is AS good AS a feast\"", "He's AS kind AS she is funny", "There are AS few AS 3200 tigers in the wild today"]},
"as opposed to": {"Comparison/Contrast": [0, "His choice of Indonesia , as opposed to the United Kingdom or the USA , as his first foreign destination was controversial", "a radial as opposed to a concentric circular structure"]},
"as regards": {"Topic": [250, "As regards the survey data, the proof of the pudding will be clear when we see how the Home Office presents the conclusions.", "She notes that not a lot has changed in 30 years as regards the ratio of women in politics in Carlow."]},
"as to": {"Topic": [138, "So now I'm back home and a bit confused as to whether my visit was effective or not.", "Police are mystified as to how the thief managed to open the vehicle without using force.", "collect all relevant information from teachers as to academic ability and character"]},
"aside from": {"Elements": [57, "The good thing is, aside from some privacy, you don't have to hear a roommate's snore.", "The exhibit spans multiple rooms and displays many works aside from the pop art he is most famous for."]},
"at": {"Activity": [42, "boxing was the only sport I was any good at", "she was getting much better at hiding her reactions."], 
    "Age": [1, "at fourteen he began to work as a postman."], 
    "Destination": [445, "they stopped at a small trattoria."], 
    "Direction": [41, "she clutched at the thin gown", "he hit at her face with the gun."], 
    "Frequency": [44, "driving at 50 mph."], 
    "Goal": [278, "I looked at my watch", "Leslie pointed at him", "policies aimed at reducing taxation."], 
    "Instrument": [19, "holding a prison officer at knifepoint", "her pride had taken a beating at his hands."], 
    "Location": [445, "they live at Conway House"], 
    "Locus": [0, "You can find testimonials at my website."], 
    "ProfessionalAspect": [0, "it was at university that he first began to perform.", "baristas at Starbucks (= Starbucks baristas)"], 
    "State": [2, "his ready smile put her at ease", "they were at a disadvantage."], 
    "Stimulus": [183, "shaking their heads at her foolishness", "admiration at her own acumen"], 
    "Time": [43, "the sea is cooler at night.", "the children go to bed at nine o'clock", "his death came at a time when the movement was split."], 
    "Value": [44, "prices start at \u00a318,500", "chanting at full volume"]},
"away": {"Direction": [0, "She swam away (from the shore). Charlie tried to grab the football but Lucy kicked it away."]},
"back": {"Direction": [0, "She swam back (to the shore).", "The dog brought the ball back."]},
"because of": {"Explanation": [250, "Some would even say the only reason they go to watch Tranmere is because of Iain alone.", "we closed down because of lack of interest by adults in the village.", "The loss of a handful of email messages because of a technical glitch is an irritation."]},
"before": {"DeicticTime": [36, "his playing days had ended six years before", "it's never happened to me before."], 
    "Location": [22, "Matilda stood before her, panting", "trotting through the city with guards running before and behind."], 
    "Other": [9, "he could be taken before a magistrate for punishment."], 
    "RelativeTime": [36, "she had to rest before dinner", "the day before yesterday", "they lived rough for four days before they were arrested"], 
    "Scalar/Rank": [0, "a woman who placed duty before all else", "they would die before they would cooperate with each other."]},
"behind": {"Location": [182, "we were stuck behind a slow-moving tractor.", "the recording machinery was kept behind screens", "the sun came out from behind a cloud."], 
    "Possessor": [0, "the agony behind his decision to retire."], 
    "Scalar/Rank": [0, "Woosnam moved to ten under par, five shots behind Fred Couples.", "the government admitted it is ten years behind the West in PC technology."]},
"below": {"Location": [27, "Males and females look the same, with white chins extending up just below the eyes and gray-brown caps.", "Obvious examples are the excavation of building foundation and tunnels that extend below the water-table.", "Most older houses will have a crawl space below the boards.", "The acromion is hook-like and extends below the glenoid as in modern mammals.", "They extend both above and below the waistline, are wider in the center and taper to points at both ends.", "A deep cut could penetrate to the fatty layer below the skin.", "Surrounded by water on all sides, the edifice is 11 stories high but also goes 18 metres below sea level.", "One of the exhibits consisted simply of a couple of Post-It notes stuck to each other, hanging from the ceiling by a thread, a little bit below eye level.", "Malingerer's arm was low, but it never fell below shoulder level.", "The ground floor, which would be partially set below pavement level, would accommodate two single bedroom residential units."], 
    "Scalar/Rank": [121, "I have a hard time accepting that we rank below Lithuania, Estonia, Croatia and Slovakia.", "Too many are doing work which is at least a grade below their skills.", "That which transpired last midweek was probably the heaviest loss Scotland have suffered against a team ranked below them.", "Apollyon was his Second Lieutenant, ranking below only Lady Alysia and the Prince himself.", "Italy ranks below Botswana, while Namibia scores equal with Greece.", "There are countless examples of conduct by high profile sports stars that fall well below community standards."], 
    "ValueComparison": [115, "below 50 mph"]},
"beneath": {"Location": [83, "the ancient city has lain hidden beneath the sea for 2,000 years.", "beneath this floor there's a cellar", "her eyes had dark shadows beneath them.", "the labyrinths beneath central Moscow."], 
    "Scalar/Rank": [0, "he was relegated to the rank beneath theirs.", "she's in love with a man who is rather beneath her."]},
"beside": {"Comparison/Contrast": [0, "beside Paula she always felt clumsy."], 
    "Location": [91, "he sat beside me in the front seat", "the table beside the bed."]},
"besides": {"Elements": [247, "She couldn't remember a time where anyone she knew besides her family had seen her in a dress.", "Candidates were asked which nation, besides Canada, concerns them the most."]},
"between": {"1DTrajectory": [129, "the dog crawled between us and lay down at our feet", "those who travel by train between London and Paris."], 
    "Approximator": [7, "a man aged between 18 and 30", "between 25 and 40 per cent off children's clothes."], 
    "Co-Agent": [148, "negotiations between Russia, Ukraine, and Romania.", "the wars between Russia and Poland."], 
    "Co-Patient": [64, "a collision in mid-air between two light aircraft"], 
    "Co-Theme": [148, "links between science and industry", "such policies would create division between the two communities"], 
    "Comparison/Contrast": [9, "the difference between income and expenditure."], 
    "Location": [129, "the border between Mexico and the United States"], 
    "RelativeTime": [1, "they snack between meals", "the long, cold nights between autumn and spring."]},
"but": {"Elements": [0, "In fact, I have no alternative but to fine her a crisp crunchie for being just too good to be true.", "She was too shaken and frightened to do anything else but feel safe in the arms of Peter Grayson.", "So I didn't have any alternative then but to get up in front of everyone and attempt to play the thing.", "If we want vehicles to be less polluting, then we have no choice but to find an alternative fuel.", "On some London high streets it is becoming difficult to go food shopping anywhere but Tesco."]},
"by": {"1DTrajectory": [0, "I drove by our house."], 
    "Agent": [406, "the door was opened by my cousin Annie", "a clear decision by the electorate", "years of hard fund-raising work by local people."], 
    "Attribute": [20, "a breakdown of employment figures by age and occupation."], 
    "Beneficiary": [0, "she had done her duty by him."], 
    "Causer": [98, "damage caused by fire.", "rights guaranteed by law"], 
    "Co-Agent": [0, "Richard is his son by his third wife."], 
    "Course": [37, "travelling by road to Aylsham."], 
    "Creator": [53, "a book by Ernest Hemingway."], 
    "Extent": [69, "the shot missed her by miles", "the raising of VAT by 2.5%."], 
    "Frequency": [0, "colours changing minute by minute", "the risk becomes worse by the day."], 
    "Function": [2, "what is meant by \"fair\"?"], 
    "Location": [40, "remains were discovered by the roadside", "the pram was by the dresser."], 
    "Manner": [0, "I heard by chance that she has married again", "Anderson, by contrast, rejects this view", "she ate by candlelight.", "it was the least he could do, by God", "I swear by Almighty God."], 
    "Means": [127, "malaria can be controlled by attacking the parasite", "they substantiate their opinions by the use of precise textual reference", "they plan to provide further working capital by means of borrowing."], 
    "Other": [0, "anything you do is all right by me"], 
    "RelativeTime": [1, "I've got to do this report by Monday", "by now Kelly needed extensive physiotherapy."], 
    "Stimulus": [98, "we were surprised by her comments"], 
    "Temporal": [1, "this animal always hunts by night."], 
    "Transit": [37, "the cost of travelling by bus"], 
    "Value": [0, "billing is by the minute", "the drunken yobbos who turned up by the cartload.", "a map measuring 400 by 600 mm", "she multiplied it by 89."]},
"by virtue of": {"Explanation": [250, "The 64 runs that took him to 103 came by virtue of 14 fours and a six and a six and two singles.", "We felt kinda out of place here by virtue of not wearing polished shoes, smart pants and a designer shirt.", "Diana was a non-entity who achieved greatness through marriage, and by virtue of her beauty."]},
"concerning": {"Topic": [250, "He writes extensively on subjects concerning cross-strait relations and Chinese politics."]},
"down": {"1DTrajectory": [230, "up and down the stairs", "tears streaming down her face."], 
    "Course": [254, "They will then set off down the mighty River Mersey on the start of their adventure.", "I wandered down the road."], 
    "Destination": [0, "she was tired of going down the pub every night."], 
    "Duration": [0, "astrologers down the ages."], 
    "Location": [254, "I often see him down the pub", "(he lives) a dozen miles or so down the Thames", "he lived down the street"]},
"due to": {"Explanation": [20, "The delay was due to a lack of scaffolding.", "She also found signs of hypoxic damage to nerve cells due to lack of oxygen before death.", "It too was cancelled, this time on the previous day, due to the lack of a full panel.", "It refers to a blue tinge seen on the surface of the whole or part of the body, due to lack of oxygen in the blood.", "As it is, their jokes fall flat and it is not due to any lack of talent by the artists involved."]},
"during": {"Duration": [120, "the restaurant is open during the day", "the period during which he grew to adulthood.", "the stabbing took place during a row at a party."]},
"except": {"Elements": [105, "In 2000, Al Gore. did not win a single one of these states except New Mexico."]},
"except for": {"Elements": [250, "Everything is very quiet, except for the occasional floomfing sound of snow falling off pine trees and cedars.", "The media coverage is always sensationalized here in the US except for possibly Public Radio."]},
"for": {"Beneficiary": [337, "Eating healthy food is good/expensive/important for students ((semantically, adj. takes a predicate AND a participant as arguments))", "troops who had fought for Napoleon", "the High Court found for the Commissioner", "I got a present for you", "these parents aren't speaking for everyone."], 
    "Circumstance": [385, "I had peanut butter for lunch", "she wants a bike for Christmas"], 
    "Co-Theme": [17, "swap these two bottles for that one."], 
    "DeicticTime": [65, "I haven't seen him for some time."], 
    "Destination": [8, "they are leaving for London tomorrow."], 
    "Duration": [65, "he was jailed for 12 years"], 
    "Experiencer": [204, "I want to see for myself", "It is difficult/enjoyable for students to eat gourmet food ((students experience difficulty/joy))", "it was a significant victory for Hussein"], 
    "Explanation": [385, "Aileen is proud of her family for their support", "I could dance and sing for joy.", "He criticized Bevin for lack of imagination", "grief for a friend killed by right-wing vigilantes"], 
    "Extent": [0, "he crawled for 300 yards."], 
    "Frequency": [0, "the camcorder failed for the third time."], 
    "Function": [395, "the \"F\" is for Fascinating.", "networks for the exchange of information", "a coupon for peanut butter"], 
    "ProfessionalAspect": [28, "she is a tutor for the Open University."], 
    "Purpose": [766, "Blackbeard seemed ready to murder us for a wallet with a few fivers in it", "I went to the store for eggs", "We hired a caterer for the party", "Friday is a good time for a party", "physical therapy for a leg injury", "the necessary tools for making a picture frame."], 
    "Reciprocation": [385, "Miss Parker said she thanked God for high cheek bones"], 
    "Scalar/Rank": [37, "she was tall for her age", "warm weather for the time of year."], 
    "Theme": [442, "you have a great desire for solitude", "charge higher prices for goods bought by credit card", "Sheldukher fumbled for his laser pistol", "phone for a taxi", "be undeterred in your search for the possible dream"], 
    "Time": [8, "schedule it for 5:00"], 
    "Topic": [44, "they voted for independence in a referendum.", "they are arguing for a delay"], 
    "Value": [23, "copies are available for \u00a31.20.", "they will repair it for $100"], 
    "`": [160, "It is rare/foolish for students to eat gourmet food ((semantically, adj. characterizes event as a whole, though a participant may be syntactically extracted))"]},
"from": {"Activity": [11, "the story of how he was saved from death."], 
    "Causer": [71, "a child suffering from asthma."], 
    "Co-Theme": [351, "estranged from their families"], 
    "Comparison/Contrast": [23, "these fees are quite distinct from expenses."], 
    "Donor/Speaker": [555, "an admission from the defendant", "the comment from Bertrand Russell", "she demanded the keys from her husband.", "the court sought briefs from the parties"], 
    "InitialLocation": [1272, "she began to walk away from him", "I leapt from my bed", "I'm from Hackney", "recent emigrants from southern India", "split a bough from a tree", "such foods should be totally eliminated from a healthy eating plan"], 
    "Location": [467, "the ambush occurred 50 metres from a checkpoint.", "you can see the island from here", "the ability to see things from another's point of view.", "she rang him from the hotel", "the railway from Albert Street"], 
    "Material": [16, "a paint made from a natural resin."], 
    "Source": [606, "information obtained from papers, books, and presentations.", "someone I know from work", "everyone is from the same team", "the representative from my district", "men who ranged in age from seventeen to eighty-four."], 
    "StartState": [878, "he was turning the Chamberlain government away from appeasement.", "Pat is recovering from scarlet fever.", "A tug at his trouser leg awakened him from his reverie", "the party was ousted from power after sixteen years.", "escape from poverty", "take land from indigenous people", "East German departure from the Warsaw Pact"], 
    "StartTime": [12, "the show will run from 10 a.m. to 2 p.m.", "a document dating from the thirteenth century."]},
"in": {"Age": [142, "a woman in her thirties."], 
    "Attribute": [319, "Mozart's Piano Concerto in E flat.", "a fairly new satin blouse in kingfisher-blue", "a chestnut-brown sweater in fine wool", "Trouserless men looked absurd in socks.", "no discernible difference in quality.", "say it in French", "put it in writing."], 
    "Circumstance": [16, "In announcing the program, Computershare pointed out the environmental benefits of reducing the use of valuable resources such as trees."], 
    "Contour": [124, "If at any point they lose the scent they fly in zig-zags", "Planets move in ellipses around the sun", "shaking his thumb in the direction of the sleeping animals"], 
    "DeicticTime": [100, "I hadn't seen him in years.", "I'll see you in fifteen minutes."], 
    "Destination": [251, "don't put coal in the bath", "he got in his car and drove off.", "The bird flew in (=inside).", "cover the well so Timmy won't fall in"], 
    "Direction": [0, "the fog rolled in (from the sea) overnight", "the boat drifted in toward the shore", "hold your stomach in"], 
    "Duration": [0, "John was here in 45 minutes after I called"], 
    "Location": [768, "I'm living in London", "she saw the bus in the rear-view mirror."], 
    "Locus": [331, "I read it in a book", "acting in a film."], 
    "Manner": [124, "cruel in the extreme", "he frowned in what he hoped was a manly, intent manner", "He smiled in a benevolent sort of way"], 
    "ProfessionalAspect": [28, "she works in publishing."], 
    "State": [142, "to be in love", "I've got to put my affairs in order"], 
    "Superset": [331, "everyone in our lunch group plays golf", "Perhaps only one in twenty of the city's adult residents had been born there."], 
    "Time": [98, "they met in 1885", "at one o'clock in the morning", "In my life I have never seen so many cats in one place", "the leaders met for the 8th time in 6 weeks"], 
    "Topic": [211, "an increased public concern and interest in issues of defence , disarmament and peace"], 
    "Value": [0, "a local income tax running at six pence in the pound."]},
"in addition to": {"Elements": [250, "Those explosions are in addition to a number of bomb threats in the city each week.", "He received a two-year sentence which he will serve in addition to his current jail term."]},
"in front of": {"Location": [88, "Christie noticed a sleek black car parked in front of her house.", "I found that I liked to program sitting in front of a computer"]},
"in need of": {"State": [0, "The three-bedroom house comprises 102 square metres of accommodation and is in need of refurbishment.", "Yet I feel in need of a cool drink at the end of an unbelievably stressful week."]},
"inside": {"DeicticTime": [0, "the oven will have paid for itself inside 18 months."], 
    "Destination": [47, "Anatoly reached inside his shirt and brought out a map", "we walked inside."], 
    "Location": [39, "he went inside Graves and scored near the post.", "a radio was playing inside the flat", "Mr Jackson is waiting for you inside."]},
"instead of": {"Comparison/Contrast": [0, "Take the stairs instead of the lift to add a few extra steps to your daily regime.", "He also has to show that he can be a useful member of society instead of being regarded as a parasite."]},
"into": {"Activity": [0, "he's into surfing and jet-skiing."], 
    "Destination": [896, "the narrow road which led down into the village.", "cover the bowl and put it into the fridge", "Sara got into her car and shut the door.", "he crashed into a parked car."], 
    "Direction": [35, "with the wind blowing into your face", "sobbing into her skirt."], 
    "Duration": [3, "shopping goes on into the evening"], 
    "EndState": [137, "they forced the club into a humiliating special general meeting.", "a peaceful protest which turned into a violent confrontation", "the fruit can be made into jam."], 
    "Topic": [10, "a clearer insight into what is involved", "an inquiry into the squad's practices."], 
    "Value": [0, "three into twelve goes four."]},
"like": {"Comparison/Contrast": [366, "he used to have a car like mine", "they were like brothers", "she looked nothing like Audrey Hepburn.", "he was screaming like a banshee.", "students were angry at being treated like children."], 
    "Elements": [2, "the cautionary vision of works like Animal Farm and 1984."]},
"near": {"Comparison/Contrast": [0], 
    "Location": [205, "Far away in the distance somewhere back near the Cork road, hooters blared angrily as road rage mounted.", "We had a home made wooden sledge and we took the crates and put it on the sledge and took them round to the houses near where we lived.", "Lita asked and pointed to a figure in the distance, near the exit of the park."], 
    "State": [0, "The other two photographs are closer, and shows many birds dead, some near death and very few still standing.", "He was losing weight at a dramatic rate and many of his closest friends feared he might be near death.", "The rest were alive, but affected by the low temperatures and were near dead due to freezing."], 
    "Time": [5, "In early 1973 there were signs that this gloomy period was near its end.", "For a brief period near the end of the half, Ireland exerted pressure.", "Annoying for anyone that has recently paid for it, but not so bad for those of us who are near the end of our subscription period.", "Kelly could not keep out the Redskins for much longer and Wayne Trunchion scored a fourth goal near the end of the second period.", "Below were essay questions that were to be filled out near the end of the quarter."], 
    "ValueComparison": [4, "Agar-agar gels are unique in withstanding temperatures near boiling point.", "We here in Seattle had record temperatures,, somewhere near 100 degrees,, whew!", "All of these showed saturation at PPFD levels near or below those listed in Table 1.", "No other European power came near this degree of commitment to overseas expansion and empire in the nineteenth century.", "Where possible try to take landmarks that intercept at as near ninety degrees as possible."]},
"of": {"Age": [6, "a boy of 15."], 
    "Attribute": [29, "A faint frown of doubt brought Lucenzo 's brows together .", "He is a gentleman of complete integrity"], 
    "Causer": [67, "he died of cancer."], 
    "ClockTimeCxn": [0, "it would be just a quarter of three in New York."], 
    "Creator": [18, "the plays of Shakespeare", "the paintings of Rembrandt."], 
    "Elements": [86, "our allies consist of the NATO countries, Canada, and Australia"], 
    "Extent": [12, "an increase of 5%"], 
    "Location": [0, "north of Watford."], 
    "Material": [86, "the house was built of bricks", "walls of stone."], 
    "Partitive": [1222, "a bag of popcorn", "a series of programmes", "a piece of cake", "a sheet of paper"], 
    "Possessor": [935, "it was kind of you to ask", "the son of a friend", "the government of India", "a former colleague of John's.", "The smug foolishness of the Western leaders was typified by the British participants."], 
    "Species": [559, "the city of Prague", "the idea of a just society", "the population of interbreeding individuals", "this type of book."], 
    "Stimulus": [67, "fear of bears", "proud of our accomplishments"], 
    "Superset": [1135, "nine/all/lots/a bunch of the children came to the show", "10% of individuals are left-handed", "hundreds of dollars", "the days of the week."], 
    "Theme": [91, "He cleared it of leaves ((removal verbs))"], 
    "Topic": [1060, "I am certain of that.", "Let bards sing now of barbiturates as bright as violets . ((statement verbs))", "These she would learn of only through other people . ((hearing/learning verbs))", "a photograph of the bride", "I don't know of anything that would be suitable.", "Hobbes conceived of what he called a state of nature.", "All three were convinced of his innocence."], 
    "Value": [12, "a height of 10 metres."], 
    "Whole": [1135, "a third/none of the pie is left", "the sleeve of his coat", "in the back of the car", "the headquarters of the company"]},
"off": {"Direction": [176, "rinse off detergent/rinse detergent off (intr. particle)"], 
    "InitialLocation": [223, "he rolled off the bed", "the coat slipped off his arms", "trying to get us off the stage.", "single wires leading off the main lines", "threatening to tear the door off its hinges", "it's a huge burden off my shoulders.", "pluck him off the mountain"], 
    "Location": [19, "anchoring off Blue Bay", "six miles off Dunkirk.", "in a little street off Whitehall."], 
    "Source": [176, "they knocked $2,000 off the price"], 
    "State": [0, "I took a couple of days off work.", "he managed to stay off alcohol."]},
"on": {"Activity": [3, "his attendant was out on errands."], 
    "Beneficiary": [49, "But is this fair on clients who are vulnerable and in need?", "it would be all too convenient to blame everything on the absent Miss Philimore", "the courts have been too lenient on Dr Courtney"], 
    "Destination": [200, "we got on the train.", "put it on the table.", "put your ideas down on paper"], 
    "Goal": [86, "five air raids on Schweinfurt", "thousands marching on Washington", "her eyes were fixed on his dark profile."], 
    "Location": [365, "a scratch on her arm", "a smile on her face.", "an internment camp on the island", "the house on the corner.", "on the table was a water jug", "she was lying on the floor", "a sign on the front gate.", "John got some sleep on the plane."], 
    "Locus": [30, "he was on his way to see his mother.", "we met on a trip/vacation/journey/expedition/mission to Paris", "stored on the client's own computer."], 
    "Other": [41, "he was lying on his back.", "the box fell on its side", "she kissed me on the cheek"], 
    "Possessor": [1, "she only had a few pounds on her."], 
    "ProfessionalAspect": [0, "they would be allowed to serve on committees."], 
    "State": [6, "he is on morphine to relieve the pain."], 
    "Theme": [86, "the mechanic worked on the engine", "spend a lot of money on textbooks", "nosh on snacks"], 
    "Time": [48, "she was booed on arriving home.", "reported on September 26", "on a very hot evening in July."], 
    "Topic": [362, "a book on careers.", "a constitution modelled on America's."], 
    "Via": [10, "a new twelve-part TV series on Channel 4."]},
"on account of": {"Explanation": [242, "It is understood her sentence was cut to 12 years on account of her guilty plea.", "Traffic accidents continue to claim a large number of lives on account of several factors.", "As a result of this, I have not been able to take any time off on account of not feeling well.", "We had decided to go down the river first, on account of car parking.", "The need for the Thames Estuary sea forts arose in the last war on account of the mining of our waters with magnetic mines."]},
"on top of": {"Elements": [36, "And you may be charged an additional fee on top of the room rates.", "On top of this benefit, recent evidence suggests that eating rye bread can lower cholesterol levels too."], 
    "Location": [46, "Like most families, they wanted enough space so they wouldn't feel they were living on top of each other.", "Finally on the way home we saw a shopping trolley perched on top of a stop sign.", "Normally, asphalt road surfaces are built on top of a bed of concrete"]},
"onto": {"Destination": [175, "an onto mapping.", "a small strip of tape designed to stick firmly onto skin"]},
"out": {"Destination": [0, "With this weather, it isn't safe to go out (=outside)."], 
    "Direction": [0, "I put a nickel into the machine and a gumball came out.", "He took a good drag off the Arturo Fuente and let the smoke waft out from between pursed lips."]},
"out of": {"1DTrajectory": [0, "He climbed back out of the window"], 
    "InitialLocation": [0, "Then, without a word, he stormed out of the room, slamming the door shut as he went.", "I grabbed a piece of toast and let myself out of the house.", "the single narrow road that led out of the forest."], 
    "Location": [0, "We picked up a hitchhiker about ten miles out of town."], 
    "Superset": [19, "Nine times out of ten this is a big mistake.", "On a day when the fixture list was badly hit by the weather, only nine matches were played out of 19 scheduled.", "To pass the examinations a score of 6 out of ten had to be achieved."]},
"outside": {"Location": [0, "The only home score came when full-back Leigh Hinton came up outside his winger to make the extra man.", "The same boy is standing outside a school waiting to be collected.", "He was running towards a little boy outside an open door, down the hall.", "I should have said that all the kids who either went into the sewer or stood outside the sewer were boys.", "My friend had a car stolen by a gang of boys outside Starbucks.", "The crowd was now situated right outside her door, with the Duke's coach not too far away."], 
    "ProfessionalAspect": [0, "During the holidays the rooms can also be booked by people outside the university for conferences and meetings.", "She warmly thanked the many members both in and outside the committee who have given sterling service over the year.", "Whether members comment on persons outside the House is a matter of taste.", "This is an experience that cannot be replicated outside a university or something at least akin to it.", "The rise in reading groups outside the university is perhaps significant in this regard."]},
"over": {"1DTrajectory": [56, "he toppled over the side of the boat.", "she trudged over the lawn.", "glance over her shoulder"], 
    "Approximator": [0, "over 40 degrees C", "they've been married for over a year."], 
    "Destination": [2, "They broke a chair over me ."], 
    "Duration": [4, "you've given us a lot of heartache over the years", "she told me over coffee."], 
    "Location": [115, "I saw flames over Berlin", "cook the sauce over a moderate heat.", "over the hill is a small village.", "his flat was over the shop.", "an oxygen tent over the bed", "ladle this sauce over fresh pasta.", "views over Hyde Park."], 
    "Patient": [24, "editorial control over what is included."], 
    "Scalar/Rank": [1, "the predominance of Asian over African managers in the sample.", "he shouted over the noise of the taxis.", "over him is the financial director.", "I'd choose the well-known brand over that one."], 
    "Topic": [110, "a heated debate over unemployment."], 
    "Via": [0, "a voice came over the loudspeaker."]},
"owing to": {"Explanation": [240, "However, this was put on the backburner owing to difficult market conditions at that time.", "One of the buses on Wednesday night, owing to engine trouble, got held up at Mountfalcon.", "However, throughout the world, the cost of fish has increased owing to reduced yields.", "We only ever got as far as a turkey farm six miles down the road, and it took over four hours to get there owing to the fact that neither of us knew how to drive a lorry.", "This sentiment will seem less of a cliche to us than to other classes, owing to the tragic events of the past year."]},
"past": {"1DTrajectory": [66, "the horse and rider rode past the town's gates.", "the track climbed through forest and past a small waterfall.", "he decided to drive past my house"], 
    "ClockTimeCxn": [30, "I got back from Lucca at half past midnight this morning", "we started at 20 to 5 and finished at 10 past 5."], 
    "Location": [0, "The large bathroom lies further along the hall, past a sizable storage cupboard."], 
    "RelativeTime": [30, "He kept us up until well past 4am", "I intend to continue practicing law past my 65th birthday"]},
"per": {"Value": [243, "the cost of repainting was expressed in terms of a rate per square metre."]},
"re": {"Topic": [14]},
"regarding": {"Topic": [209, "He raised several concerns regarding the proposed tuition hikes"]},
"round": {"1DTrajectory": [154, "he wrapped the blanket round him", "she drew a red circle round his name.", "a bus appeared round the corner.", "Angus put an arm round Flora and kissed her.", "The car swung round a corner into the dusty main road", "I stepped round him and went back to the house"], 
    "2DArea": [90, "she went round the house and saw that all the windows were barred."], 
    "Destination": [0, "if he didn't shut up he might get a clip round the ear."], 
    "Location": [131, "camel drivers squatting round their early morning fires", "the area round the school", "with shifting sands all round me.", "Steven parked the car round the corner."], 
    "Topic": [4, "the text is built round real practical examples."]},
"since": {"RelativeTime": [242, "It was the British who suffered the worst single incident since the end of the war."], 
    "StartTime": [242, "They are on a mission, a task that they have been training for since they entered the Army.", "For that reason clarinets have been built since their early days in different keys."]},
"such as": {"Elements": [0, "institutions such as the family and marriage"]},
"than": {"Comparison/Contrast": [0, "Our council tax is much higher than in larger towns", "It turned out to be better in looks than taste", "everything is worse than it was", "He likes getting in on the act too and has appeared in more productions than he cares to count.", "On one occasion a female passenger leaned on him for more than just a friendly chat.", "They just seem to do what they are told rather than have their own personal opinion.", "we would rather be able to see than be blind."]},
"thanks to": {"Explanation": [0, "Not for much longer , however , thanks to those statisticians .", "Tobacco profits jumped 16 % , thanks to the growth in smoking in less well-off parts of the world .", "Here , however , I 've got no complaint , thanks to the mad Steely and Clevie dub on the B-side ."]},
"through": {"1DTrajectory": [368, "the lorry smashed through a brick wall", "a cucumber, slit, but not right through.", "the sun was streaming in through the window", "the glass in the front door where the moonlight streamed through.", "stepping boldly through the doorway", "as soon as we opened the gate they came streaming through.", "flipping through the pages of a notebook", "she read the letter through carefully.", "he'd hooked his umbrella through the handles of the handbag and pulled it out"], 
    "3DMedium": [210, "The sap flowed easily through her veins .", "making my way through the guests."], 
    "Course": [27, "Westbourne estimated the chances of escape through the kitchen.", "dioxins get into mothers' milk through contaminated food."], 
    "Destination": [6, "Josh tucked his arm companionably through hers"], 
    "Duration": [9, "the goal came midway through the second half", "to struggle through until pay day.", "we sat through some very boring speeches", "she's been through a bad time", "Karl will see you through, Ingrid."], 
    "EndTime": [0, "they will be in London from March 24 through May 7."], 
    "Extent": [3, "each joint can move through an angle within fixed limits.", "rotate them through 90 degrees"], 
    "Location": [11, "the approach to the church is through a gate."], 
    "Means": [12, "rehabilitating older public-sector estates through a programme of co-operation with local authorities"], 
    "Via": [26, "freedom of expression through words", "seeking justice through the proper channels."]},
"to": {"Beneficiary": [703, "you were terribly unkind to her", "priced to be affordable to domestic users", "She was rude to Larry Hagman"], 
    "ClockTimeCxn": [0, "it's five to ten."], 
    "Co-Theme": [148, "he is married to his cousin Emma", "he had left his dog tied to a drainpipe", "they are inextricably linked to this island."], 
    "Comparison/Contrast": [0, "the club's nothing to what it once was."], 
    "Destination": [595, "place the cursor to the left of the first word.", "walking down to the shops", "my first visit to Africa.", "every driveway to the castle was crowded", "emigrants to South Africa"], 
    "EndState": [160, "Christopher's expression changed from amazement to joy", "she was close to tears.", "smashed to smithereens.", "a drop in profits from \u00a3105 m to around \u00a375 m", "to her astonishment, he smiled."], 
    "EndTime": [0, "from 1938 to 1945."], 
    "Experiencer": [132, "speed altogether surprising to Miss Buckley"], 
    "Function": [0, "a shoulder to cry on"], 
    "Location": [8, "(He lives) forty miles to the south of the site"], 
    "Patient": [132, "made a repair to the air conditioning"], 
    "ProfessionalAspect": [41, "he's economic adviser to the president."], 
    "Purpose": [0, "He rose to make a speech"], 
    "Recipient": [571, "they donated \u00a3400 to the hospice.", "John told the news to Mary"], 
    "Theme": [112, "a threat to world peace", "a reference to Psalm 22:18."], 
    "Value": [0, "my car only does ten miles to the gallon.", "ten to the minus thirty-three."]},
"towards": {"Beneficiary": [14, "he was warm and tender towards her"], 
    "Direction": [276, "they drove towards the German frontier."], 
    "Function": [0, "the council provided a grant towards the cost of new buses."], 
    "Goal": [2, "moves towards EU political and monetary union."], 
    "RelativeTime": [0, "towards the end of April."], 
    "Topic": [14, "our attitude towards death."]},
"under": {"Approximator": [0, "Jacques Chirac, the right wing president, scraped just under 20 percent of the vote.", "The majority of cases in Angola have occurred in children under the age of five years.", "Which meant if I ran the second half at the same speed I would have finished in under four hours."], 
    "Destination": [0, "He recently began to put gold and silver metal leaf under the paint", "The cat thought it was great fun and climbed under the duvet with me."], 
    "Location": [0, "Pity the display was directly under the departures board which had a long list of delayed and cancelled trains.", "Richey is looking forward to experiencing the things we take for granted, such as feeling grass under his feet.", "I had three layers on under my thick winter coat.", "Another had lain on a chaise lounge for four days, his stockinged feet poking out from under a quilt.", "Fruit cell turgors were obtained by probing the mesocarp cells directly under the fruit epidermis.", "He landed in an old storage room somewhere under the museum, where he was he had no idea.", "Dropping forward onto the walkway around the hold it is possible to swim into the crew's quarters under the chart room.", "The luggage compartment floor is on two levels, under which the rear seat base can be stowed."], 
    "ProfessionalAspect": [0, "They have both been studying for five years under Academy founder Richard Smith."], 
    "Scalar/Rank": [0, "I learned long ago that I had to give the managers under me the ability to make mistakes or I'd lose good people."], 
    "State": [9, "a program that allows you to run windows apps under linux", "In both the US and UK the novel will be published under the name Michael Marshall.", "You'll find it in your filing cabinet under \"A\" for \"anal\".", "Petipa ran the company during the late 19th century, when Russia was under the rule of the Czars.", "under British rule", "Those living under an oppressive regime may have good reason to seek this level of online privacy.", "The US occupation authority has placed the city under what amounts to martial law.", "The right to anonymity on Internet bulletin boards is under threat again.", "Nursing and support staff do a wonderful job but are constantly under pressure from ever dwindling resources.", "It will work by using steam, fed under pressure, to break down organic waste and sterilise metals and plastics.", "The process put us under a lot of stress", "These trees can start producing fruit within about six years under the right conditions.", "I don't think of us are under any illusions about those elections.", "Ponten confirmed the organization's web site has been under attack since the raids.", "There is a new rollercoaster under construction at Disney's Animal Kingdom.", "Employees who are under investigation for alleged breaches of conduct are not allowed union representation at interviews with management.", "What was the Citizen's Charter if it was not public service reform under a different badge?", "All four youths were arrested under the Explosives Act and taken to Fulford Police Station.", "Smith is under contract but probably will be asked to take a pay cut or face being released."]},
"until": {"EndTime": [194, "As soon as the flames go out, add the cider and boil until the liquid has reduced by half.", "You never quite know how a poem sounds until your hear it coming out of a pair of speakers.", "By the way you only have until the end of September to lend your support to this idea.", "If you want to book a seat in it you'll have to wait until April as they're all booked up.", "The rules are due to change but until then it still pays you to keep your mileage up."]},
"up": {"1DTrajectory": [0, "She pushed her glasses further up the bridge of her nose.", "I don't know where we were, but I wanted to climb up a really steep hill - which seemed to take ages.", "We picked our way up one side of the ridge, and I found a spot where we could spend the long night ahead.", "She shrieked with laughter as they raced up the stairs.", "Calleri is one of several Argentines moving steadily up the rankings."], 
    "Course": [1, "In 1866 the U.S.S. General Sherman sailed up the Taedong River to Pyongyang.", "Last summer I made a trip up the Amazon basin in Peru.", "Then one of them led him up the garden path to a shed ."], 
    "Destination": [0, "Then we went up the pub and stayed there until midnight.", "Fancy going up the shops?"], 
    "Location": [1, "Gould 's brother-in-law 's station , 160 miles from Newcastle on the coast up the Hunter River at Yarrundi , far exceeded Gould 's expectations both in size and in beauty .", "I lived just up the street from them.", "He was such a lovely lad, would often see him up the pub"]},
"via": {"Course": [73, "Tomorrow we are travelling home via Harrogate where we will call to see Daisy's other sister Janet.", "Entry to the field is via a forestry road and will be sign-posted from the A170."], 
    "Means": [122, "And you have condemned countless numbers of your own citizens to death via the death penalty."], 
    "Via": [171, "Daily now, postwoman Julie Harris is delivering a handful of cards for him, via his minders on the ground.", "Messages can also be sent via email by completing an online form here."]},
"with": {"3DMedium": [0, "marine mammals generally swim with the current."], 
    "Accompanier": [274, "a nice steak with a bottle of red wine."], 
    "Attribute": [112, "he's in bed with the flu.", "a small man with thick glasses.", "a man with a knife", "I returned with the receipt", "a flower-sprigged blouse with a white collar."], 
    "Beneficiary": [286, "my father will be angry with me.", "you have been patient with me"], 
    "Causer": [16, "wisdom comes with age."], 
    "Circumstance": [0, "Ferns look wonderful with diffused sunlight shining through their foliage .", "With markets on the rise, investors are optimistic."], 
    "Co-Agent": [124, "a row broke out with another man.", "I often chat with strangers on the Internet"], 
    "Co-Theme": [0, "replace the old tires with new ones", "they confused my order with someone else's"], 
    "Instrument": [261, "cut the fish with a knife", "treatment with acid before analysis."], 
    "Manner": [73, "the people shouted with pleasure."], 
    "Means": [261, "It also made him want to heal her wounds with a kiss .", "retaliate with vicious shootings"], 
    "ProfessionalAspect": [11, "I bank with the TSB.", "she's with the Inland Revenue now."], 
    "Stimulus": [146, "he was trembling with fear."], 
    "Theme": [431, "fill the bowl with water."], 
    "Topic": [17, "Irina had been brisk when she called with the news and told him to telephone Rakovsky at his hotel ."]},
"within": {"Location": [0, "It'll be nice to think living within strolling distance of both a multiplex and an arthouse will perk up my film-going.", "This is no European event: this race is within spitting distance of Bonn and is the German Grand Prix in all but name.", "Last year, I lived within walking distance of the carnival route, and I went in both days to soak up the atmosphere.", "If it's within reasonable walking distance, try doing this next Sunday.", "Good to see a local hangout that's within staggering distance home if you're totally mangled."]},
"without": {"Location": [0]}
};



function buildTooltipStrings() {
	result = {};
	Object.keys(PSST_LABELS).forEach(function (p) {
		Object.keys(PSST_LABELS[p]).forEach(function (sst) {
			if (result[sst]===undefined) {
				result[sst] = {};
			}
			var examplesL = PSST_LABELS[p][sst].slice(1);
			for (var i=0; i<examplesL.length; i++) {	// for each example, capitalize the target preposition
				// (heuristically identified as the last matching word)
				examplesL[i] = examplesL[i].replace(new RegExp("(.*)\\b"+p+"\\b", "i"), "$1"+p.toUpperCase());
			}
			result[sst][p] = examplesL.join(' | ');
			if (PSST_SHORT_DEFS[sst]!==undefined)
				result[sst][p] += '\n\n'+PSST_SHORT_DEFS[sst];
		});
	});
	return result;
}
PSST_LEX_LABEL_DESCRIPTIONS = buildTooltipStrings();
// normalization/aliases
// after buildTooltipStrings(), so OUT OF is correctly capitalized in examples whether filed under 'out' or 'out of'
PSST_LABELS["2"] = PSST_LABELS["to"];
Object.keys(PSST_LEX_LABEL_DESCRIPTIONS).forEach(function (sst) {
	if (PSST_LEX_LABEL_DESCRIPTIONS[sst]["to"]) 
		PSST_LEX_LABEL_DESCRIPTIONS[sst]["2"] = PSST_LEX_LABEL_DESCRIPTIONS[sst]["to"];
	if (PSST_LEX_LABEL_DESCRIPTIONS[sst]["for"])
		PSST_LEX_LABEL_DESCRIPTIONS[sst]["4"] = PSST_LEX_LABEL_DESCRIPTIONS[sst]["for"];
	if (PSST_LEX_LABEL_DESCRIPTIONS[sst]["out of"])
		PSST_LEX_LABEL_DESCRIPTIONS[sst]["out"] = PSST_LEX_LABEL_DESCRIPTIONS[sst]["out of"];
});

PSST_TOP_LABELS = {"about": ["Topic", "2DArea", "1DTrajectory", "Possessor", "Location", "Approximator"],
    "above": ["Location", "Scalar/Rank", "ValueComparison", "1DTrajectory"],
    "according to": ["`d", "Manner"],
    "across": ["1DTrajectory", "Location", "Duration"],
    "after": ["RelativeTime", "Explanation", "`", "Direction", "Comparison/Contrast", "Scalar/Rank", "Frequency", "ClockTimeCxn"],
    "against": ["Location", "Destination", "Beneficiary", "Co-Agent", "Comparison/Contrast"],
    "all over": ["State", "Location", "2DArea"],
    "along": ["1DTrajectory"],
    "among": ["Locus", "Location", "Superset"],
    "apart from": ["Elements"],
    "around": ["2DArea", "1DTrajectory", "Location", "Time", "Topic", "Approximator"],
    "as": ["Attribute", "`", "Time", "Explanation"],
    "as opposed to": ["Comparison/Contrast"],
    "as regards": ["Topic"],
    "as to": ["Topic"],
    "aside from": ["Elements"],
    "at": ["Location", "Destination", "Goal", "Stimulus", "Value", "Frequency", "Time", "Activity", "Direction", "Instrument", "State", "Age", "ProfessionalAspect", "Locus"],
    "away": ["Direction"],
    "back": ["Direction"],
    "because of": ["Explanation"],
    "before": ["RelativeTime", "DeicticTime", "Location", "Other", "Scalar/Rank"],
    "behind": ["Location", "Scalar/Rank", "Possessor"],
    "below": ["Scalar/Rank", "ValueComparison", "Location"],
    "beneath": ["Location", "Scalar/Rank"],
    "beside": ["Location", "Comparison/Contrast"],
    "besides": ["Elements"],
    "between": ["Co-Theme", "Co-Agent", "Location", "1DTrajectory", "Co-Patient", "Comparison/Contrast", "Approximator", "RelativeTime"],
    "but": ["Elements"],
    "by": ["Agent", "Means", "Stimulus", "Causer", "Extent", "Creator", "Location", "Transit", "Course", "Attribute", "Function", "Temporal", "RelativeTime", "Value", "Other", "Manner", "Frequency", "Co-Agent", "Beneficiary", "1DTrajectory"],
    "by virtue of": ["Explanation"],
    "concerning": ["Topic"],
    "down": ["Location", "Course", "1DTrajectory", "Duration", "Destination"],
    "due to": ["Explanation"],
    "during": ["Duration"],
    "except": ["Elements"],
    "except for": ["Elements"],
    "for": ["Purpose", "Theme", "Function", "Reciprocation", "Explanation", "Circumstance", "Beneficiary", "Experiencer", "`", "Duration", "DeicticTime", "Topic", "Scalar/Rank", "ProfessionalAspect", "Value", "Co-Theme", "Time", "Destination", "Frequency", "Extent"],
    "from": ["InitialLocation", "StartState", "Source", "Donor/Speaker", "Location", "Co-Theme", "Causer", "Comparison/Contrast", "Material", "StartTime", "Activity"],
    "in": ["Location", "Superset", "Locus", "Attribute", "Destination", "Topic", "State", "Age", "Manner", "Contour", "DeicticTime", "Time", "ProfessionalAspect", "Circumstance", "Value", "Duration", "Direction"],
    "in addition to": ["Elements"],
    "in front of": ["Location"],
    "in need of": ["State"],
    "inside": ["Destination", "Location", "DeicticTime"],
    "instead of": ["Comparison/Contrast"],
    "into": ["Destination", "EndState", "Direction", "Topic", "Duration", "Value", "Activity"],
    "like": ["Comparison/Contrast", "Elements"],
    "near": ["Location", "Time", "ValueComparison", "State", "Comparison/Contrast"],
    "of": ["Partitive", "Whole", "Superset", "Topic", "Possessor", "Species", "Theme", "Material", "Elements", "Stimulus", "Causer", "Attribute", "Creator", "Value", "Extent", "Age", "Location", "ClockTimeCxn"],
    "off": ["InitialLocation", "Source", "Direction", "Location", "State"],
    "on": ["Location", "Topic", "Destination", "Theme", "Goal", "Beneficiary", "Time", "Other", "Locus", "Via", "State", "Activity", "Possessor", "ProfessionalAspect"],
    "on account of": ["Explanation"],
    "on top of": ["Location", "Elements"],
    "onto": ["Destination"],
    "out": ["Direction", "Destination"],
    "out of": ["Superset", "Location", "InitialLocation", "1DTrajectory"],
    "outside": ["ProfessionalAspect", "Location"],
    "over": ["Location", "Topic", "1DTrajectory", "Patient", "Duration", "Destination", "Scalar/Rank", "Via", "Approximator"],
    "owing to": ["Explanation"],
    "past": ["1DTrajectory", "RelativeTime", "ClockTimeCxn", "Location"],
    "per": ["Value"],
    "re": ["Topic"],
    "regarding": ["Topic"],
    "round": ["1DTrajectory", "Location", "2DArea", "Topic", "Destination"],
    "since": ["StartTime", "RelativeTime"],
    "such as": ["Elements"],
    "than": ["Comparison/Contrast"],
    "thanks to": ["Explanation"],
    "through": ["1DTrajectory", "3DMedium", "Course", "Via", "Means", "Location", "Duration", "Destination", "Extent", "EndTime"],
    "to": ["Beneficiary", "Destination", "Recipient", "EndState", "Co-Theme", "Patient", "Experiencer", "Theme", "ProfessionalAspect", "Location", "Value", "Purpose", "Function", "EndTime", "Comparison/Contrast", "ClockTimeCxn"],
    "towards": ["Direction", "Topic", "Beneficiary", "Goal", "RelativeTime", "Function"],
    "under": ["State", "Scalar/Rank", "ProfessionalAspect", "Location", "Destination", "Approximator"],
    "until": ["EndTime"],
    "up": ["Location", "Course", "Destination", "1DTrajectory"],
    "via": ["Via", "Means", "Course"],
    "with": ["Theme", "Beneficiary", "Accompanier", "Means", "Instrument", "Stimulus", "Co-Agent", "Attribute", "Manner", "Topic", "Causer", "ProfessionalAspect", "Co-Theme", "Circumstance", "3DMedium"],
    "within": ["Location"],
    "without": ["Location"]
};

// normalization
PSST_TOP_LABELS["2"] = PSST_TOP_LABELS["to"];
PSST_TOP_LABELS["4"] = PSST_TOP_LABELS["for"];
PSST_TOP_LABELS["out"] = PSST_TOP_LABELS["out"].concat(PSST_TOP_LABELS["out of"]);

SRIKUMAR_LABELS = ['Activity','Age','Agent','Attribute','Beneficiary','Cause','ClockTimeCxn','Co-Particiants','DeicticTime','Destination','Direction',
				   'Duration','EndState','EndTime','Experiencer','Frequency','Instrument','Location','Manner','MediumOfCommunication','Numeric',
				   'ObjectOfVerb','Opponent/Contrast','Other','PartWhole','Participant/Accompanier','PhysicalSupport',
				   'Possessor','ProfessionalAspect','Purpose','RelativeTime','Recipient',
				   'Separation','Source','Species','StartState','StartTime',
				   'Temporal','Time','Topic','Via','`','`i','?'];
SRIKUMAR_TOP_LABELS = {
'about':['Location','Possessor','Time','Topic'],
'above':['Location','Other'],
'across':['Duration','Location'],
'after':['Beneficiary','Cause','ClockTimeCxn','Direction','Frequency','ObjectOfVerb','Other','RelativeTime'],
'against':['Opponent/Contrast','Other','PhysicalSupport'],
'along':['Direction','Location','Manner'],
'among':['Choices','Location','PartWhole'],
'around':['Location','Time','Topic'],
'as':['Attribute','Other'],
'at':['Activity','Age','Attribute','Cause','Frequency','Instrument','Location','Numeric','ObjectOfVerb','ProfessionalAspect','Time'],
'before':['Location','Other','RelativeTime'],
'behind':['Beneficiary','Cause','Direction','Location','Other','Temporal'],
'beneath':['Location','Other'],
'beside':['Location','Other'],
'between':['Choices','Location','Numeric','Opponent/Contrast','RelativeTime'],
'by':['Age','Agent','Attribute','Direction','Frequency','Instrument','Location','Numeric','Other','Possessor','RelativeTime','Source','Temporal','Via'],
'down':['Direction','Location','Temporal'],
'during':['Duration'],
'for':['Beneficiary','Cause','Destination','DeicticTime','Duration','Numeric','ObjectOfVerb','Other','ProfessionalAspect','Purpose'],
'from':['Agent','Attribute','Cause','Location','ObjectOfVerb','Opponent/Contrast','Separation','Source','StartState','StartTime','Temporal'],
'in':['Activity','Attribute','Destination','DeicticTime','Duration','Location','Manner','MediumOfCommunication','PartWhole','ProfessionalAspect','Time'],
'inside':['DeicticTime','Destination','Location','Other','PartWhole'],
'into':['Activity','Destination','Duration','EndState','Location','Numeric','Topic'],
'like':['Manner','Other'],
'of':['Age','Attribute','Cause','ClockTimeCxn','Location','Numeric','ObjectOfVerb','PartWhole','Possessor','Source','Species'],
'off':['Direction','Location','Separation'],
'on':['Activity','Experiencer','Instrument','Location','MediumOfCommunication','Numeric','Other','PhysicalSupport','Possessor','ProfessionalAspect','Time','Topic','Via'],
'onto':['Location','PhysicalSupport','Via'],
'over':['Duration','Instrument','Location','ObjectOfVerb','Other','Topic'],
'round':['Location','ObjectOfVerb','Topic'],
'through':['Activity','Duration','EndTime','Instrument','Location','Manner','ObjectOfVerb','Via'],
'to':['Activity','Beneficiary','ClockTimeCxn','Destination','EndState','EndTime','Location','Numeric','ObjectOfVerb','Other','Participant/Accompanier','Recipient'],
'toward':['Direction','EndState','Experiencer','ObjectOfVerb','RelativeTime'],
'towards':['Direction','EndState','Experiencer','ObjectOfVerb','RelativeTime'],
'with':['Attribute','Cause','Direction','Instrument','Manner','ObjectOfVerb','Opponent/Contrast','Other','Participant/Accompanier','ProfessionalAspect','Separation']
};
SRIKUMAR_LABEL_DESCRIPTIONS = {"PhysicalSupport": "The object of the preposition is physically in contact with and supports the governor or a subject of the governor if the governor is a verb or nominalization. \"stood with her back against the wall\", \"a water jug on the table\"",
"Temporal": "Supercategory for all temporal senses (refer to Temporal Hierarchy). The object of the preposition specifies the time of when an event occurs, either as an explicit temporal expression or by indicating another event as a reference. \"shortly after Christmas\", \"cooler at night\", \"met in 1985\"",
"Duration": "Period or length of time over which something takes place: \"slept for hours\", \"ate in 20 minutes\", \"during/throughout the party\", \"into/over/across/through the years\"",
"Frequency": "Rate of repetition or progress: \"at 25mph/a steady clip\", \"day by/after day\"",
"Age": "How old someone or something is: \"at/by 40\", \"a child of 5\"", 
"Time": "Time or event marking the time when something happens: \"at noon\", \"on Friday\", \"on/upon arrival\", \"in the morning\", \"around/about/near midnight\"", 
"RelativeTime": "Time or event marking the time relative to which something happens: \"I ate before/after/since noon\", \"dozens of competitions will take place between the opening and closing ceremonies\", \"towards/by 8:00\"",
"StartTime": "Time or event marking the time when something begins: \"from 8 a.m.\", \"I have been depressed (ever) since the election\"",
"EndTime": "Time or event marking the time when something ends: \"to 5 p.m.\", \"until noon\", \"The festival runs through (the end of the day) Thursday\"",
"DeicticTime": "Time interval relative to the present time when something takes place: \"20 minutes ago/hence\"; \"within/inside 3 months (from now)\"; \"in 20 minutes (from now)\"; \"haven't eaten in/for 3 hours (before now)\"",
"ClockTimeCxn": "Offset of minutes to hour when telling time: \"10 of/after/to/till noon\"",
 "Destination": "The object of the preposition is the destination of the motion indicated by the governor. \"put coal in the bath\", \"Sara got into her car\", \"walking to the shops\"",
 "Beneficiary": "The object of the preposition is a beneficiary of the action indicated by the preposition's governor. \"fought for Napoleon\", \"be charming to them\", \"clean up after him\"",
 "Instrument": "The object of the preposition indicates the means or the instrument for performing an action that is typically the governor. \"hold at knifepoint\", \"banged his head on the beam\", \"fill the bowl with water\"",
 "Other": "This is the catch-all category. A preposition labeled as Other has relation, but this sense does not appear frequently enough to support creation of a new category. \"married above her\", \"years behind them\", \"tall for her age\"",
 "PartWhole": "This relation indicates that one argument is a part or member of another. \"see a friend among them\", \"a slice of cake\"",
 "Cause": "The objects indicates a cause for the governor. \"in bed with the flu\", \"died of cancer\", \"tired after work\"",
 "ObjectOfVerb": "The object of the preposition is an object of the verb or the nominalization that is the governor of the preposition. This includes cases like \"construction of the library\", where the object of the preposition is an object of the underlying verb. \"saved from death\", \"cross with her\"", 
 "Direction": "The prepositional phrase (that is, the preposition along with the object) indicates a direction that modifies the action which is expressed by the governor. \"crept up behind him\", \"drive by the house\", \"roll off the bed\"",
 "Possessor": "The governor of the preposition is something belonging to the object or an inherent quality of the object . This relation includes familial relations. \"a look about her\", \"son of a friend\", \"his son by his third wife\"",
 "Experiencer": "The object of the preposition denotes the entity that is target or subject of the action, thought or feeling that is usually indicated by the governor. \"focus attention on her\", \"he was warm toward her\"",
 "Separation": "The relation indicates separation or removal. The object of the preposition is the entity that is removed. \"tear the door off its hinges\", \"part with possessions\", \"the party was ousted from power\"",
 "Purpose": "The object of the preposition speci\ufb01es the purpose (i.e., a result that is desired, intention or reason for existence) of the governor. \"networks for the exchange of information\", \"tools for making the picture frame\"",
 "Recipient": "The object of the preposition identi\ufb01es the person or thing receiving something. \"unkind to her\" \"donated to the hospital\"",
 "Opponent/Contrast": "This relation indicates a collision, con\ufb02ict or contrast and the object of the preposition refers to one or more entities involved. \"fight against crime\", \"the wars between Russia and Poland\", \"fought with another man\"",
 "Participant/Accompanier": "The object of the preposition indicates an entity which accompanies another entity, which is typically indicated by either the governor of the preposition or the subject of the governor. \"he is married to Emma\", \"a map pinned to the wall\"",
 "ProfessionalAspect": "This relation signi\ufb01es a professional relationship between the governor (or the subject of the governor, if the governor is a verb) and the object of preposition, which is an employer, a profession, an institution or a business establishment. \"tutor for the University\", \"bank with TSB\", \"works in publishing\"",
 "StartState": "The object of the preposition indicates the state or condition that an entity has left. \"recovered from the disease\", \"a growth from $2.2 billion to $2.4 billion\"",
 "EndState": "The object of the preposition indicates the resultant state of an entity that is undergoing change. The governor of the preposition is usually a verb or noun denoting transformation. \"protest turned into a violent confrontation\", \"she was moved to tears\", \"return towards traditional Keynesianism\"",
 "Activity": "Describes the relationship between some entity and an activity, an ordeal or a process that can be a verbal noun or a gerund. Note that the object of the preposition is the activity here. \"He is into jet-skiing\", \"out on errands\", \"good at boxing\"",
 "Co-Participants": "The preposition indicates a choice, sharing or differentiation and involves multiple participants, represented by the object. \"drop in tooth decay among children\", \"links between science and industry\", \"choose between two options\"",
 "Numeric": "The object of the preposition indicates a numeric quantity (age, price, percentage, etc). \"driving at 50mph\", \"crawled for 300 yards\", \"a boy of 15\"",
 "Via": "This is an infrequent relation where the object of the preposition indicates a mode of transportation or a path for travel. The governor can be action indicating movement or travel or a noun denoting passengers. \"traveling by bus\", \"he is on his way\", \"sleep on the plane\"",
 "Source": "The object of the preposition indicates the provenance of the governor or the subject of the governor. This includes cases where the object is the place of origin, the source of information or the creator of an artifact. \"I am from Hackeney\", \"book by Hemmingway\", \"paintings of Rembrandt\"",
 "Attribute": "The object of the preposition indicates some attribute of either the governor or the subject/object of the governor in case of verb-attached prepositions. \"sell at a loss\", \"paint made from resin\", \"Mozart's Piano Concerto in E flat\"",
 "MediumOfCommunication": "The prepositional phrase indicates the medium or language of some form of communication or idea. The object is, in a general sense, a 'mode of communication'. This includes languages \"say it in French\" media like TV or internet \"saw it on a website\" or specific instances of these \"saw it on the Sopranos\".",
 "Agent": "The object of the preposition is the agent of the action indicated by the attachment point. This primarily covers the use of the preposition 'by' in a passive construction. \"Understood by the customers\", \"allegations from the FDA\", \"the address by the officer\"",
 "Topic": "The object of the preposition denotes the subject or topic under consideration. In many cases, the preposition can be replaced by the phrase 'on the subject of'. The governor of the preposition can be a verb or nominalization that implies an action or analysis or a noun that indicates an information store. \"thinking about you\", \"book on careers\", \"debate over unemployment\"",
 "Manner": "The prepositional phrase indicates the manner in which an action is performed. The action is typically the governor of the preposition. \"frame the definition along those lines\", \"disappear in a flash\", \"shout with pleasure\"",
 "Species": "This expresses the relationship between a general category or type and the thing being specified which belongs to the category. The governor is a noun indicating the general category and the object is an instance of that category. \"the city of Prague\", \"this type of book\"",
 "Location": "The prepositional phrase indicates a locative meaning. This relation includes both physical and figurative aspects of location. \"live at Conway house\", \"left it in the cupboard\", \"left it in her will\", \"bruises above both eyes\"",
 '`': '`: No semantic function; purely collocational or syntactic',
 '`i': "Infinitival TO or FOR (with no additional semantics)",
 '?': 'Unsure. You may be more specific by listing one or more predefined category names (separated by commas), followed by "?".'};
GENERAL_LABEL_SHORTCUTS = {'`': '`', '?': '?'};
ALL_LABEL_SHORTCUTS = $.extend({}, N_LABEL_SHORTCUTS, V_LABEL_SHORTCUTS, GENERAL_LABEL_SHORTCUTS);

PSEUDOLABEL_DESCRIPTIONS = {'?': '(unsure; you can also tentatively specify one or more possibilities by following them with ?)', 
		'`': '(skip for now)',
		'`i': "infinitival TO or FOR (with no additional semantics)",
		'`d': 'discourse',
		'`o': 'pronoun',
		'`s': 'non-infinitival subordinator',
		'`a': 'auxiliary',
		'`j': 'adjectival'}

function TokenLabelAnnotator(I, itemId) {
	this._name = 'TokenLabelAnnotator';
	this.labelShortcuts = $.extend({}, 
		<? if ($nsst) { ?>N_LABEL_SHORTCUTS, <? } ?>
		<? if ($vsst) { ?>V_LABEL_SHORTCUTS, <? } ?>
		GENERAL_LABEL_SHORTCUTS);
	<? if ($psst) { ?>
	this.labels = PSST_LIST_OF_LABELS;
	this.lexlabeldescriptions = PSST_LEX_LABEL_DESCRIPTIONS;
	this.labeldescriptions = PSST_SHORT_DEFS;
	<? } else { ?>
	this.labels = Object.keys(this.labelShortcuts); //['LOCATION', 'PERSON', 'TIME', 'GROUP', 'OTHER', '?', '`'];
	this.labeldescriptions = {'OTHER': 'miscellaneous tag'};
	<? } ?>
	this.labeldescriptions = $.extend(this.labeldescriptions, PSEUDOLABEL_DESCRIPTIONS);
	this.toplabels = [];
	if (arguments.length == 0) return; // stop -- necessary for inheritance
	
	Annotator(this, I, itemId);
	this.actors = [];
}
TokenLabelAnnotator.prototype._makeTarget = function (wordelt, wordOffset) {
	var a = new Actor(this);
	a.word = wordelt;
	var w = $(a.word).text().toLowerCase();
	a.url = 'http://www.ark.cs.cmu.edu/PrepWiki/index.php/Category:'+w;
	
	a.tokenOffset = wordOffset;
	a.labels = this.labels;
	a.toplabels = this.toplabels;
	<? if ($psst) { ?>
	var prepw = w; 
	// possibly a multiword prep
	// TODO: this logic doesn't get executed when updating MWE analysis.
	if (AA[a.ann.I][MWEAnnotator.annotatorTypeIndex].isGrouped(a.tokenOffset, 'strong')) {
		// get all words in the chunk
		var classes = ' '+$(a.word).attr("class")+' ';
		var islu = classes.indexOf(' slu');
		var sluNum = classes.match(/\bslu(\d+)\b/)[1];
		prepw = $(a.word).parent().children('.slu'+sluNum).map(function () { return $(this).text(); }).toArray().join(' ');
	}
	if (PSST_TOP_LABELS[prepw])	// prepw may be an MWE
		a.toplabels = PSST_TOP_LABELS[prepw];
	else if (PSST_TOP_LABELS[w])	// back off to first word if MWE is not listed
		a.toplabels = PSST_TOP_LABELS[w];
	<? } ?>
	a.labelShortcuts = this.labelShortcuts;
	a.lexlabeldescriptions = this.lexlabeldescriptions;
	a.labeldescriptions = this.labeldescriptions;
	this.actors.push(a);
	a.getValue = function () {
		return this.target.value;
	};
	a.populateLabels = function (labelShortcuts) {
		/* Refreshes the dropdown menu from the contents of this.labels and this.labelShortcuts 
		 * (and optionally, this.toplabels).
		 * If labelShortcuts is provided, first assigns that to this.labelShortcuts 
		 * and its keys to this.labels.
		 */
		
		if (arguments.length>0) {
			this.labelShortcuts = labelShortcuts;
			this.labels = Object.keys(this.labelShortcuts);
		}
		
		// sort the labels, putting the top ones first (the subset that are in the top 
		// can differ for each dropdown instance)
		var toplabels = (this.toplabels); // ? this.toplabels : [];
		var orderedLabels = [];
		for (var i=0; i<toplabels.length; i++) {
			orderedLabels.push(this.toplabels[i]);
		}
		for (var i=0; i<this.labels.length; i++) {
			if (orderedLabels.indexOf(this.labels[i])==-1)
				orderedLabels.push(this.labels[i]);
		}

		var labelsWithShortcuts = [];
		
		for (var i=0; i<orderedLabels.length; i++) {
			var shortcut = this.labelShortcuts[orderedLabels[i]];
			var thelabel = orderedLabels[i];
			if (i<toplabels.length)	// we are still in the top labels
				thelabel = '<span class="toplabel">'+thelabel+'</span>'
			labelsWithShortcuts.push({"label": (shortcut) ? '<tt style="background-color: #555; margin-left: -5px; padding: 2px; border-radius: 4px; color: #fff;">'+shortcut+'</tt>' + ' ' + thelabel : thelabel, 
									  "value": orderedLabels[i],
									  "shortcut": shortcut});
		}
		
		// update control
		$(this.target).autocomplete("option", "source", labelsWithShortcuts);
	};
	a.firstrender = function () {
		var theactor = this;
		var word = this.word;
		var $word = $(word);
		var wordId = $(this.word).attr("id");
		
		if (theactor.url!==undefined) {
			$word.dblclick(function (evt, ui) {
				window.open(theactor.url, '_blank');
			});
		}
		
		// http://api.jqueryui.com/autocomplete/
		// TODO: depending on defaults/preannotations, set placeholder for preps belonging to an MWE
		var $control = $('<input class="tokenlabel" />').attr({"id": "toktag_"+this.ann.itemId+"_"+wordId.replace(/^i\d+/, ''), 
			"name": "tag_"+this.ann.itemId+"_"+wordId.replace(/^i\d+/, '')}).autocomplete({ 
			source: [], // will be populated by populateLabels()
			/*source: function( request, response ) {
				var matcher = new RegExp( "^" + $.ui.autocomplete.escapeRegex( request.term ), "i" );
				response( $.grep( tags, function( item ){
					return matcher.test( item );
				}) );
			}*/
			response: function (event, ui) {
				var v = event.target.value;
				// if entered text exactly matches the shortcut of one of the labels (case-sensitive), move it to the top of the list
				for (var i=0; i<ui.content.length; i++) {
					if (ui.content[i].shortcut==v) {
						ui.content.unshift(ui.content.splice(i,1)[0]);
						//ui.content[0].label = $('<div></div>').append(ui.content[0].label).find('tt').css("color", '#0ff').parentsUntil().eq(-1).html();
						break;
					}
				}
			},
			autoFocus: true, minLength: 0, html: true, 	// the html option uses an extension script
			focus: function (evt, ui) {	// apply tooltip to focused menu item
				var v = ui.item.value;
				var w = $word.text().trim().toLowerCase();
				if (theactor.lexlabeldescriptions && theactor.lexlabeldescriptions[v]!==undefined && theactor.lexlabeldescriptions[v][w]!==undefined)
					$(evt.currentTarget).attr("title", theactor.lexlabeldescriptions[v][w]);
				else if (theactor.labeldescriptions && theactor.labeldescriptions[v]!==undefined)
					$(evt.currentTarget).attr("title", theactor.labeldescriptions[v]);
				else
					$(evt.currentTarget).attr("title", "");
			},
			// don't handle 'select' event because the value won't be updated yet!
			change: function (evt, ui) {
				// apply tooltip to input box to reflect selection
				var v = theactor.getValue();
				var w = $word.text().trim().toLowerCase();
				if (theactor.lexlabeldescriptions && theactor.lexlabeldescriptions[v]!==undefined && theactor.lexlabeldescriptions[v][w]!==undefined)
					$(evt.currentTarget).attr("title", theactor.lexlabeldescriptions[v][w]);
				else if (theactor.labeldescriptions && theactor.labeldescriptions[v]!==undefined)
					$(this).attr("title", theactor.labeldescriptions[v]);
				else if (v.trim()!="")	// tooltip in case text overflows the box (is clipped)
					$(this).attr("title", v);
				else
					$(this).attr("title", "");

				ann_update(theactor, v);
			},
			close: function (evt, ui) {
				if (evt.originalEvent===undefined)	// invoked directly, not from a UI event
					return;	// do nothing
				if (evt.keyCode===undefined || evt.keyCode==13)	// clicked or pressed Enter
					$('input').eq($('input').index($(this))+1).focus();	// advance focus to next <input> element
			} }).keyup(function (evt) { if ($(this).val().charAt(0)=='?') $(this).autocomplete("close"); });
			
		if (this.initval)
			$control.val(this.initval);
		
		this.target = $control.get(0);
		
		this.populateLabels();
		
		//var width = this.ann.computeVisibleWidth(this, $control, $word);
		$word.append($control);

		this.submittable = true;
		
		// prevent double-click on preposition label text box from hiding it
		$control.dblclick(function (evt) { evt.stopPropagation(); });
		
		this.aparecium = function () {
			if (this.rerender())	// show unless readonly and empty
				$word.css("width", this.ann.computeVisibleWidth(this, $control, $word));
			else
				$word.css("width", "inherit");
		}
		this.evanesco = function () {
			$control.hide();
			$word.css("width", "inherit");
		}
		
		this.evanesco();
		
		this.start = function () {
			if (this.submittable)
				this.aparecium();
			this.ann.constructor.started = true;
		}
		this.stop = function () {
			this.evanesco();
			this.ann.constructor.stopped = true;
		}
	};
	a.rerender = function () {	// if a value may have changed, decide whether to show/hide the control
		$control = $(this.target);
		if ($control.prop("readonly") && $control.val()==="") {
			$control.hide();
			return false;
		}
		else {
			$control.show();
//console.log("rerender true");
			return true;
		}
	};
	return a;
}
/* The width that the *word* should have when the control is visible */
TokenLabelAnnotator.prototype.computeVisibleWidth = function($control, $word) {
	return Math.max($control.outerWidth(),$word.outerWidth());
}
TokenLabelAnnotator.prototype.identifyTargets = function() {
	var theann = this;
	$(this.item).find('.sent .w').each(function (w) {
		theann._makeTarget(this,w);
	});
	theann.submittable = true;
}
TokenLabelAnnotator.prototype.start = function() {
	for (var j=0; j<this.actors.length; j++) {
		this.actors[j].start();
	}
	this.started = true;
}
TokenLabelAnnotator.prototype.stop = function() {
	for (var j=0; j<this.actors.length; j++) {
		this.actors[j].stop();
	}
	this.constructor.stopped = true;
}

// extends TokenLabelAnnotator
function PrepTokenAnnotator(I, itemId) {
	TokenLabelAnnotator.call(this, I, itemId);
	this._name = 'PrepTokenAnnotator';
	this.PREPS = ['of','to','for','by','with','from','at','over','out','about','in','on','off','as','down','under','above','across','after','against','ago','among','during','before','behind','below','beneath','beside','besides','between','beyond','away','back','into','near','since','until','together','toward','towards','apart','within','without'];
	var tentative_labels = ['Agent', 'Audience', 'Cause/Reason', 'Comitative', 'Communicator', 'Comparison', 'Cost/Compensation/Price', 
 					'Destination/Direction', 'Duration', 'Giver', 'GOAL', 'Instrument', 'Location', 'Manner', 'Means', 'Medium', 
					'Origin', 'OTHER', 'PATH', 'Patient/Affected', 'Purpose', 'Recipient/Beneficiary/Maleficiary', 'Reciprocal/Co-participant',
					'Result', 'SOURCE', 'State', 'Time', 'Topic/Content', 'Trajectory', 'X', '?'];
	var tentative_labeldescriptions = {'X': 'X: No semantic function; purely collocational or syntactic',
					     'Duration': 'Duration: span of time of an event, e.g. "leave FOR a few days", or interval, "once IN a million years"',
						 'Manner': 'Manner: e.g. "communicate IN a clear and comprehensible way" [FrameNet]',
						 'Means': 'Means: e.g. "communicate BY signalling in some such subliminal manner" [FrameNet]',
						 'Medium': 'Medium: e.g. "communicate OVER the telephone", "mumbled IN French" [FrameNet]',
						 'Time': 'Time: point in time, e.g. "AT the beginning of the month", "ON Friday", start and end points in "work FROM 9:00 TO 5:00"',
						 'GOAL': 'GOAL: Miscellaneous goal (not Destination, Recipient/Beneficiary/Maleficiary, or Purpose)',
						 'SOURCE': 'SOURCE: Miscellaneous source (not Origin, Giver, or Communicator)',
						 'PATH': 'PATH: Miscellaneous path (not Trajectory, Manner, Means, or Medium)',
						 'OTHER': 'OTHER: Semantic function not otherwise listed. You may instead enter "?" followed by an ad hoc category name.',
						 '?': 'Unsure. You may be more specific by listing one or more predefined category names (separated by commas), followed by "?".'};
	var srikumar_labels = ['Activity','Agent','Attribute','Beneficiary','Cause','Co-Particiants','Destination','Direction',
						   'EndState','Experiencer','Instrument','Location','Manner','MediumOfCommunication','Numeric',
						   'ObjectOfVerb','Opponent/Contrast','Other','PartWhole','Participant/Accompanier','PhysicalSupport',
						   'Possessor','ProfessionalAspect','Purpose','Recipient','Separation','Source','Species','StartState',
						   'Temporal','Topic','Via','`','?'];
	var srikumar_labeldescriptions = {'`': '`: No semantic function; purely collocational or syntactic',
					     'Other': 'Other: Semantic function not otherwise listed. You may instead enter "?" followed by an ad hoc category name.',
						 '?': 'Unsure. You may be more specific by listing one or more predefined category names (separated by commas), followed by "?".'};
	this.labels = srikumar_labels;
	this.labeldescriptions = srikumar_labeldescriptions;
}
PrepTokenAnnotator.prototype = new TokenLabelAnnotator();
PrepTokenAnnotator.prototype.constructor = PrepTokenAnnotator;
PrepTokenAnnotator.prototype.isPrep = function (s) {
	return this.PREPS.indexOf(s.trim().toLowerCase())>-1;
}
PrepTokenAnnotator.prototype.identifyTargets = function() {
	var theann = this;
	
	// create a hidden field for aggregating all the values of individual actors
	var item = this.item;
	var $sentence = $(item).find('p.sent');
	var $out = $('<input type="hidden" name="preps[]" class="preps" value=""/>').attr({"id": "preps_"+this.itemId});
	$out.insertAfter($sentence);
	this.target = $out.get(0);
	
	// create individual actors
	decorateActor = function (a) {
		/*a.listenForInteraction = function() {
			var theann = this;
			$(this.target).change(function (evt) {
				ann_update(theann, theann.getValue());
			});
		};*/ // handled by change event callback in .autocomplete
		a.validate = function (finalization) {
			if (arguments.length<1) finalization = false;
			var v = this.getValue();
//console.log("validating",v);
			var theactor = this;
			var errstatus = function() {
				if (v.trim()==="") {
					/*if (!finalization)
						return '';	// don't complain about empty values until submission
					if (theactor.target.placeholder===undefined || theactor.target.placeholder=="")
						return 'A non-empty value is required.';*/
					return '';	// don't worry about empty values. The "required" attribute is used to block submission of illegally empty fields.
				}
				else if (theactor.labels.indexOf(v)==-1 && v.charAt(0)!='?') {
					if (v.charAt(v.length-1)!='?')
						return 'Tag must be drawn from the predefined label list, or begin or end with "?"';
					else {	// LABEL? or LABEL1,LABEL2? etc.
						var parts = v.substring(0,v.length-1).split(',');
						for (var p=0; p<parts.length; p++) {
							var part = parts[p];
							if (theactor.labels.indexOf(part)==-1)
								return 'Invalid label in tag: "'+part+'" (tags ending in "?" must contain 0 or more comma-separated labels from the predefined list)';
						}
					}
				}
				// OK
				return '';
			}();
			
			if (errstatus!=='')
				$(this.target).removeAttr("title");	// remove title text to make room for error message
			this.target.setCustomValidity(errstatus);
		};
	};
	
	$(this.item).find('.sent .w').each(function (w) {
		if (theann.isPrep($(this).text())) {
			var a = theann._makeTarget(this,w);
			decorateActor(a);
			$(this).addClass("prepTarget");
		}
		$(this).dblclick(function (evt) {
			var w = $(this).data("w");
			if ($(this).hasClass("prepTarget")) {
				for (var j=0; j<theann.actors.length; j++) {
					if (theann.actors[j].tokenOffset==w) {
						theann.actors[j].remove()
						break;
					}
				}
				$(this).removeClass("prepTarget");
			}
			else {

				var a = theann._makeTarget(this,w);
				decorateActor(a);
				$(this).addClass("prepTarget");
				a.initValue();
				a.listenForInteraction();

				if (a.ann.constructor.started)
					a.start();
				if (a.ann.constructor.stopped)
					a.stop();
			}
		});
	});
	
	theann.submittable = true;
}
PrepTokenAnnotator.prototype.updateTargets = function(updateInfo) {
	var theann = this;
	$.each(this.actors, function (j, a) {
		// update defaults to reflect the current MWE analysis
		if (AA[a.ann.I][MWEAnnotator.annotatorTypeIndex].isGrouped(a.tokenOffset, 'strong'))
			$(a.target).attr("placeholder", "X").removeAttr("required");
		else
			$(a.target).removeAttr("placeholder").attr("required","required");
	});
}

PrepTokenAnnotator.prototype.start = function() {
	for (var j=0; j<this.actors.length; j++) {
		this.actors[j].start();
	}
	this.started = true;
	this.updateTargets();	// ensure required attribute is set even if no MWE annotations 
}

PrepTokenAnnotator.prototype.validate = function() {
	var preps = {};
	$.each(this.actors, function (j, a) {
		a.validate();
		var tag = a.getValue();
		preps[a.tokenOffset] = $(a.word).text() + '|' + (tag || $(a.target).attr("placeholder") || '');
	});
	$(this.target).val($.toJSON(preps));
}

ChunkLabelAnnotator.prototype = new TokenLabelAnnotator();
ChunkLabelAnnotator.prototype.constructor = ChunkLabelAnnotator;
function ChunkLabelAnnotator(I, itemId) {
	TokenLabelAnnotator.call(this, I, itemId);
	this._name = 'ChunkLabelAnnotator';
	this.actors = [];
	this.pos = $.parseJSON($(this.item).find('input.pos').val());
}
ChunkLabelAnnotator.prototype.computeVisibleWidth = function(a, $control, $word) {
	if (AA[a.ann.I][MWEAnnotator.annotatorTypeIndex].isChunkSegmentEnder(a.tokenOffset, 'strong')) {
		return Math.max($control.outerWidth(),$word.outerWidth());
	}
	return "inherit";	// the chunk has multiple words, so don't make extra room for the control
}
ChunkLabelAnnotator.prototype.start = function() {
	for (var j=0; j<this.actors.length; j++) {
		this.actors[j].pos = this.pos[this.actors[j].tokenOffset];
	}
	this.updateTargets();
	for (var j=0; j<this.actors.length; j++) {
		this.actors[j].start();
	}
	this.started = true;
}
ChunkLabelAnnotator.prototype.identifyTargets = function() {
	var theann = this;
	
	// create a hidden field for aggregating all the values of individual actors
	var item = this.item;
	var $sentence = $(item).find('p.sent');
	var $out = $(item).find('input.chklbls').attr({"id": "chklbls_"+this.itemId});
	$out.insertAfter($sentence);
	this.target = $out.get(0);
	
	// create individual actors
	decorateActor = function (a) {
		a.deserialize = function (isInitial) {
			var val = '';
			if (this.ann.target.value) {
				var v = $.parseJSON(this.ann.target.value)[this.tokenOffset];
				if (v) {
					v = v.split('|');	// if of the form word|LABEL, strip the first part
					val = v[v.length-1];
				}
			}
			
			if (isInitial)
				this.initval = val;	// will be initialized in firstrender()
			else if (this.target.value!==val)	// condition avoids triggering form validation error on an empty field
				$(this.target).val(val);
		}
		a.initValue = function () {	// look for previously saved value
			this.deserialize(true);
			return Actor.prototype.initValue.call(this);
		}
	
		a.validate = function () {
			var v = this.getValue();
			var theactor = this;
			var errstatus = function() {
				if (v.trim()==="") {
					return '';	// don't worry about empty values. The "required" attribute is used to block submission of illegally empty fields.
				}
				else if (theactor.labels.indexOf(v)==-1 && v.charAt(0)!='?') {
					if (v.charAt(v.length-1)!='?')
						return 'Tag must be drawn from the predefined label list, or begin or end with "?"';
					else {	// LABEL? or LABEL1,LABEL2? etc.
						var parts = v.substring(0,v.length-1).split(',');
						for (var p=0; p<parts.length; p++) {
							var part = parts[p];
							if (theactor.labels.indexOf(part)==-1)
								return 'Invalid label in tag: "'+part+'" (tags ending in "?" must contain 0 or more comma-separated labels from the predefined list)';
						}
					}
				}
				// OK
				return '';
			}();
			
			if (errstatus!=='')
				$(this.target).removeAttr("title");	// remove title text to make room for error message
			this.target.setCustomValidity(errstatus);
		};
	};
	
	$(this.item).find('.sent .w').each(function (w) {
		var a = theann._makeTarget(this,w);
		decorateActor(a);
		$(this).addClass("chklblTarget");
	});
	
	theann.submittable = true;
}
ChunkLabelAnnotator.prototype.updateTargets = function(updateInfo) {
	var theann = this;
	$.each(this.actors, function (j, a) {
		this.deserialize(false);	// look up this actor's value from overall annotator value (may have changed in the versions browser)
		
		// update defaults to reflect the current MWE analysis
		if (AA[a.ann.I][MWEAnnotator.annotatorTypeIndex].isChunkBeginner(a.tokenOffset, 'strong', theann.pos, theann.filterFxn, (theann.filterFxn==ChunkLabelAnnotator.prototype.P_FILTER) ? PREP_SPECIAL_MW_BEGINNERS : false)) {
			$(a.target).prop("disabled","").prop("required","required");
			a.submittable = true;
			if (theann.filterFxn==ChunkLabelAnnotator.prototype.V_FILTER) {
				if (AA[a.ann.I][MWEAnnotator.annotatorTypeIndex].isChunkBeginner(a.tokenOffset, 'strong', theann.pos, ChunkLabelAnnotator.prototype.N_FILTER))
					// this chunk has BOTH a noun and a verb, so allow either kind of label
					a.populateLabels(ALL_LABEL_SHORTCUTS);
				else // this chunk has only verbs
					a.populateLabels($.extend({}, V_LABEL_SHORTCUTS, GENERAL_LABEL_SHORTCUTS));
				a.validate();	// if the noun label was saved, remove the invalid flag
			}
			else if (theann.filterFxn==ChunkLabelAnnotator.prototype.NV_FILTER) {
				// we are labeling nouns and verbs
				if (!AA[a.ann.I][MWEAnnotator.annotatorTypeIndex].isChunkBeginner(a.tokenOffset, 'strong', theann.pos, ChunkLabelAnnotator.prototype.N_FILTER))
					// but this chunk contains no nouns: limit to verbs
					a.populateLabels($.extend({}, V_LABEL_SHORTCUTS, GENERAL_LABEL_SHORTCUTS));
				else if (!AA[a.ann.I][MWEAnnotator.annotatorTypeIndex].isChunkBeginner(a.tokenOffset, 'strong', theann.pos, ChunkLabelAnnotator.prototype.V_FILTER))
					// but this chunk contains no verbs: limit to nouns
					a.populateLabels($.extend({}, N_LABEL_SHORTCUTS, GENERAL_LABEL_SHORTCUTS));
				else // this chunk contains nouns and verbs
					a.populateLabels(ALL_LABEL_SHORTCUTS);
				a.validate();
			}
			if (theann.constructor.started) a.aparecium();
			//$(a.target).attr("placeholder", "X").removeAttr("required");
		}
		else if (AA[a.ann.I][MWEAnnotator.annotatorTypeIndex].isChunkBeginner(a.tokenOffset, 'strong', theann.pos, function (word, pos) { return true; })) {
			// would begin a chunk but fails POS filter
			a.submittable = (a.target.value!=="");	// so previous values will be saved when annotating other POSes
			if (a.initval.indexOf("#")>-1) {	// enable due to a reconciliation conflict
				$(a.target).prop("disabled","").prop("required","required");
				a.populateLabels(ALL_LABEL_SHORTCUTS);	// TODO: this is sort of a hack--ideally we'd still filter possible labels by POS
				a.validate();
			}
			else {
				$(a.target).prop("disabled","disabled").prop("required","");
			}
			if (theann.constructor.started && a.submittable) a.aparecium();
		}
		else {
			// irrelevant given MWE analysis
			a.submittable = false;
			$(a.target).prop("disabled","disabled").prop("required","");
			if (theann.constructor.started) a.evanesco();
		}
	});
}
ChunkLabelAnnotator.prototype.validate = function() {
	var lbls = {};
	$.each(this.actors, function (j, a) {
		a.validate();
		var tag = a.getValue();
		if (tag!=='' && a.submittable)	// don't include empty tags
			lbls[a.tokenOffset] = $(a.word).text() + '|' + (tag || $(a.target).attr("placeholder") || '');
	});
	$(this.target).val($.toJSON(lbls));
}

// global variables for the annotation protocol
annotators = [ItemNoteAnnotator, MWEAnnotator <?= (($prep) ? ', PrepTokenAnnotator' : '') ?> <?= (($nsst || $vsst || $psst) ? ', ChunkLabelAnnotator' : '') ?>];	//, ChunkLabelAnnotator];
II = []; // item offset on page => DOM node
AA = []; // annotators for each item

// stages allow different kinds of annotation to become available to the user at different times
// 'submit' progresses to the next stage until all are exhausted, then submits the form
<? $num_stages = 1+intval($prep)+intval($nsst || $vsst || $psst); ?>
INIT_STAGE = <?= ($embedded) ? $num_stages : $init_stage ?>;
CUR_STAGE = 0;
NUM_STAGES = <?= $num_stages ?>;
ItemNoteAnnotator.prototype.startStage = 0;
MWEAnnotator.prototype.startStage = 0;
PrepTokenAnnotator.prototype.startStage = 1;
ChunkLabelAnnotator.prototype.startStage = 1;
ItemNoteAnnotator.prototype.stopStage = -1;
MWEAnnotator.prototype.stopStage = -1;
PrepTokenAnnotator.prototype.stopStage = -1;
ChunkLabelAnnotator.prototype.stopStage = -1;
ChunkLabelAnnotator.prototype.N_FILTER = function (word, pos) { return pos.match(/^(N|ADD)/); };
ChunkLabelAnnotator.prototype.V_FILTER = function (word, pos) { return pos.match(/^V/); };
ChunkLabelAnnotator.prototype.NV_FILTER = function (word, pos) { return pos.match(/^(N|ADD|V)/); };
ChunkLabelAnnotator.prototype.PREPS = ['of','to','for','by','with','from','at','over','out','about','in','on','off','as','down','under','above','across','after','against','ago','among','during','before','behind','below','beneath','beside','besides','between','beyond','away','back','into','near','since','until','together','toward','towards','apart','within','without'];
ChunkLabelAnnotator.prototype.P_FILTER = function (word, pos) {	// should the given token be tagged as a preposition?
	return pos.match(/^(RP|TO)/) || (pos.match(/^(IN|RB)/) && PREPS_MASTER.indexOf(word.toLowerCase())>-1);
}	// TODO: use multiword entries in PREPS_MASTER?
<? if ($nsst || $vsst || $psst) { ?>
ChunkLabelAnnotator.prototype.filterFxn = ChunkLabelAnnotator.prototype.<?= ($psst) ? 'P' : '' ?><?= ($nsst) ? 'N' : '' ?><?= ($vsst) ? 'V' : '' ?>_FILTER;
<? } else { ?>
ChunkLabelAnnotator.prototype.filterFxn = function (word, pos) { return true; };
<? } ?>

function ann_init() {
	for (var k=0; k<annotators.length; k++) {
		annotators[k].annotatorTypeIndex = k;
	}
	$('.item').each(function (j) {
		var itm = this;
		var itmId = $(itm).attr("id").substring(1);	// strip initial underscore
		var I = II.push(itm)-1;
		anns = [];
		for (var k=0; k<annotators.length; k++) {
			anns.push(new annotators[k](I, itmId));
			anns[anns.length-1].annotatorTypeIndex = anns.length-1;
		}
		AA.push(anns);
	});
}
function ann_setup() {
	for (var I=0; I<II.length; I++) {
		$.each(AA[I], function (j, A) {
			A.identifyTargets();
			$.each(A.actors, function (k, a) {
				a.initValue();
				a.listenForInteraction();
			});
		});
	}
	while (CUR_STAGE<INIT_STAGE)
		ann_submit();
	if (<?= ($readonly) ? 'true' : 'false' ?>) {
		$('form input,form textarea').prop("readonly", "true").each(function (j) {
			if (this.value=="") $(this).hide();
		});
	}
}
function ann_update(a, newval) {
	var I = a.ann.I;
	var updateInfo = a.setValue(newval);
	for (var j=a.ann.annotatorTypeIndex; j<AA[I].length; j++) {
		var A = AA[I][j];
		if (updateInfo.isStructural)
			A.updateTargets(updateInfo);
		A.validate();
	}
}
function ann_submit() {
	var startReady = [];
	var stopReady = [];
	for (var I=0; I<AA.length; I++) {
		for (var j=0; j<AA[I].length; j++) {
			var A = AA[I][j];
			
			var thisStage = (A.startStage===undefined || A.startStage<=CUR_STAGE);
			thisStage = thisStage && (A.stopStage===undefined || A.stopStage<0 || A.stopStage>CUR_STAGE);

			if (thisStage) {
				A.validate(true);
				if (A.stopStage && A.stopStage==(CUR_STAGE+1)) { // stop
					A.stop();
				}
			}
			else if (A.startStage==(CUR_STAGE+1)) {	// start
				A.start();
			}
		}
	}
	CUR_STAGE++;
	if (PrepTokenAnnotator.annotatorTypeIndex!==undefined && PrepTokenAnnotator.prototype.startStage==(CUR_STAGE+1))
		$('input[type=submit]').val("Continue to prepositions »");
	else if (ChunkLabelAnnotator.annotatorTypeIndex!==undefined && ChunkLabelAnnotator.prototype.startStage==(CUR_STAGE+1))
		$('input[type=submit]').val("Continue to supersenses »");
	else
		$('input[type=submit]').val("Save & continue »");
	return (CUR_STAGE>=NUM_STAGES);	// ready to submit form
}


/*********************************************/




KEY_LEFT_ARROW = 37;
KEY_UP_ARROW = 38;
KEY_RIGHT_ARROW = 39;
KEY_DOWN_ARROW = 40;

var inited = false;

function init() {
	if (inited) return;	// only do this once
	inited = true;
	
	$('.item > p.sent').each(function (j) {
		ww = $(this).text().split(/\s+/g);
		$(this).html('');
		if (ww.length>1 || ww[0]!=="") {
			for (var i=0; i<ww.length; i++) {
				$('<span>').attr({"id": "i"+j+"w"+i, "class": "w", "data-w": i}).text(ww[i]).appendTo($(this));
				if (i<ww.length-1) $(this).append(' ');
			}
		}
	});
		
	ann_init();
	ann_setup();


	if ((CUR_STAGE+1)>=NUM_STAGES) {
		//$('input[type=submit]').val("Save & continue »");
	}
	else if (PrepTokenAnnotator.annotatorTypeIndex!==undefined && PrepTokenAnnotator.prototype.startStage==(CUR_STAGE+1))
		$('input[type=submit]').val("Continue to prepositions »");
	else if (ChunkLabelAnnotator.annotatorTypeIndex!==undefined && ChunkLabelAnnotator.prototype.startStage==(CUR_STAGE+1))
		$('input[type=submit]').val("Continue to supersenses »");
	
//	$('form').sisyphus();	// use localstorage to cache form data so it is preserved across refresh
	for (var I=0; I<II.length; I++) {	// now that input fields may have been populated, re-render the MWE annotations
		AA[I][MWEAnnotator.annotatorTypeIndex].validate();
	}
	
<? if ($embedded) { ?>
	$('select').each(function (j) { this.size=Math.min(5,this.options.length); $(this).change(); });
<? } ?>
}

$(function () {
	if (<?= ($embedded) ? 'false' : 'true' ?>) {	// if embedded, init() will be called when it is displayed so that size computations will work
		init();
	}
});

function arraysEq(x, y) {
	if (x.length!=y.length) return false;
	for (var i=0; i<x.length; i++) {
		if (x[i]!==y[i]) return false;
	}
	return true;
}

function parseMWEMarkup(s) {
	SPECIALS = ['_', '~', '|', '$'];
	//ww = (original word tokens)
	/*assert not any(w for w in ww if w not in SPECIALS and any(c for c in SPECIALS if c in w)), 'Original sentence contains special characters within other tokens'*/
	// Assumption: these will not be changed except for marking MWEs
	s = s.replace(/\s+/g, ' ').trim();
	reJoiners = /[_~]|\|\S+|(\|\S+)?\$\S+/g;
	var tt = [];
	var parts = s.split(' ');
	for (var i=0; i<parts.length; i++) {
		/*if (SPECIALS.indexOf(parts[i])>-1)
			parts[i] = [parts[i]];
		else*/ 
		if (SPECIALS.indexOf(parts[i][0])>-1 && (parts[i].length==1 || parts[i][1]==='|' || parts[i][1]==='$')) {
			if (parts[i].length==1)
				parts[i] = [parts[i]];
			else if (parts[i][1]==='|' || parts[i][1]==='$')
				parts[i] = [parts[i][0]];
		}
		else
			parts[i] = parts[i].split(reJoiners);
		
		// remove empty strings
		while (parts[i].indexOf('')>-1)
			parts[i].splice(parts[i].indexOf(''),1);
		while (parts[i].indexOf(undefined)>-1)
			parts[i].splice(parts[i].indexOf(undefined),1);
			
		tt = tt.concat(parts[i]);
	}
	
	if (!arraysEq(tt,ww)) {
//console.log(tt);
//console.log(ww);
		return 'Ensure that none of the tokens from the original sentence have been split, modified, or deleted';
	}
	// allow single-character tokens ~ and _, optionally with user-specified indices. (However, a~ ~ ~b and a~ ~$1 ~b should be invalid.)
	// check validity of ~'s
//	var valid = (s+' ').match(/^(\S |[^~\s]\S*[^~\s] |~(\|\S+)?(\$\S+)? |[^~\s]\S*(~ ([^~\s] |[^~\s]\S*[^~\s] )+~)\S*[^~\s] )+$/)!==null;
	var valid = (s+' ').match(/^(\S |[^~\s]\S*[^~\s] |~(\|\S+)?(\$\S+)? |[^~\s]\S*(~ ([^~\s] |[^~\s]\S*[^~\s] )+~\S*[^~\s])+ )+$/)!==null;
	if (!valid) {
	   return 'Invalid use of ~ joiners';
	}
	// remove ~ joiners
	var s2 = s.replace(/(?=\S)~|~(?=\S)/g, ' ')
	s2 = s2.replace(/\s+/g, ' ', s).trim()
	// check validity of _'s
//	valid = (s2+' ').match(/^(\S |[^_\s]\S*[^_\s] |_(\|\S+)?(\$\S+)? |[^_\s]\S*(_ ([^_\s] |[^_\s]\S*[^_\s] )+_)\S*[^_\s] )+$/)!==null;
	valid = (s2+' ').match(/^(\S |[^_\s]\S*[^_\s] |_(\|\S+)?(\$\S+)? |[^_\s]\S*(_ ([^_\s] |[^_\s]\S*[^_\s] )+_\S*[^_\s])+ )+$/)!==null;
	if (!valid) {
	   return 'Invalid use of _ joiners';
	}
	
	if (s.search(/__/)>-1 || s.search(/~~/)>-1 || s2.search(/__/)>-1) {
		return 'Invalid sequence: __ or ~~';
	}
	 
	// now parse the markup
	 
	var i = 0;	// character position in string
	var j = 0;	// offset of next token in the source sentence to be consumed
	var sgrouping = [];	// strong grouping (x_y or x|1 y|1)
	var wgrouping = [];	// weak grouping (x~y or x$1 y$1)
	var sindexCounter = 0;
	var windexCounter = 0;
	var underscoreStartGap = -1;
	var tildeStartGap = -1;
	var rLexer = /( |^)[_~](\|\S+)?(\$\S+)?(?=( |$))|( ?([ _~] ?)+|[^_~\s]+)/g;
	var underscoreResume = false;
	var tildeResume = false;
	while ((m = rLexer.exec(s))!==null) {
		//if (m.index!=i) { console.log(s); console.log(i, m); }
		var t = m[0];
		i = m.index + t.length;
		
		if (t.indexOf('~')>-1 && t.indexOf('_')>-1) {
			return 'Illegal sequence: '+t;
		}
		else if (t==' ') {
			sindexCounter++;
			windexCounter++;
			underscoreResume = false;
			tildeResume = false;
		}
		else if (t.indexOf('_')>-1 && t!=' _ ' && j>0 && i<s.length && s[i]!==' ') {
		// should NOT be _ occurring as a raw token
			if (t[0]==' ') {	// ' _'
				underscoreResume = true;
			}
			else if (t=='_ ') {
				if (!underscoreResume)	// necessary for structures like a_ b _c_ d _e
					underscoreStartGap = sindexCounter;
				sindexCounter++;
				underscoreResume = false;
			}
			tildeResume = false;
			windexCounter++;
		}
		else if (t.indexOf('~')>-1 && t!=' ~ ' && j>0 && i<s.length && s[i]!==' ') {
//console.log(j,'"'+t+'"');	// should NOT be ~ occurring as a raw token
			if (t[0]==' ') {	// ' ~'
				tildeResume = true;
			}
			else if (t=='~ ') {
				if (!tildeResume)	// necessary for structures like a~ b ~c~ d ~e
					tildeStartGap = windexCounter;
				windexCounter++;
				tildeResume = false;
			}
			underscoreResume = false;
			sindexCounter++;
		}
		else {
			if (t[0]==' ') {	// ' _ ', ' ~ ', ...
				sindexCounter++;
				windexCounter++;
				underscoreResume = false;
				tildeResume = false;
			}
		
			if (t.indexOf('|',1)>-1) {	// user-specified strong index (allow first character to be raw |)
				var sidx = (t.indexOf('|',1)+1<t.length) ? t.substr(t.indexOf('|',1)+1) : '';
				var tail = '';
				if (sidx.indexOf('$')>-1) {
					tail = sidx.substring(sidx.indexOf('$'));
					sidx = sidx.substring(0,sidx.indexOf('$'));	// also a weak index
				}
				else if (t.indexOf('$',1)>-1) {
					return 'Cannot have $ index before | index: '+t;
				}
				
				if (sidx==='' || isNaN(Number(sidx))) {
					return 'Invalid index (must be an integer): "'+sidx+'"';
				}
				else if (underscoreResume) {
					return 'Cannot index the second part of an underscore-gapped expression: "'+sidx+'"';
				}
				var sgindex = sidx;
//console.log('token',j,'sgindex',sgindex);
				// since the index may be applied to a (non-gappy) multiword unit, 
				// ensure all parts of the unit share the user-specified index
				for (var k=sgrouping.length-1; k>=0 && sgrouping[k]===sindexCounter; k--) {
					sgrouping[k] = sgindex;
				}
				
				t = t.substring(0,t.indexOf('|',1)) + tail;
			}
			else
				var sgindex = (underscoreResume) ? underscoreStartGap : sindexCounter;
			
			sgrouping.push(sgindex)	// will be a string if specified in the input, and an int otherwise
			
			
			
			if (t.indexOf('$',1)>-1) {	// user-specified weak index (allow first character to be raw $)
				var widx = (t.indexOf('$',1)+1<t.length) ? t.substr(t.indexOf('$',1)+1) : '';
				if (widx==='' || isNaN(Number(widx))) {
					return 'Invalid index (must be an integer): "'+widx+'"';
				}
				else if (tildeResume) {
					return 'Cannot index the second part of an tilde-gapped expression: "'+widx+'"';
				}
				var wgindex = widx;
//console.log('token',j,'wgindex',wgindex);
				// since the index may be applied to a (non-gappy) multiword unit, 
				// ensure all parts of the unit share the user-specified index
				for (var k=wgrouping.length-1; k>=0 && wgrouping[k]===windexCounter; k--) {
					wgrouping[k] = wgindex;
				}
				
				t = t.substring(0,t.indexOf('$',1));
			}
			else
				var wgindex = (tildeResume) ? tildeStartGap : windexCounter;
			
			wgrouping.push(wgindex)	// will be a string if specified in the input, and an int otherwise
			
			if (t.trim()!=ww[j])
				return 'Ensure that none of the tokens from the original sentence have been split, modified, or deleted: expected token '+j+' to be "'+ww[j]+'", got "'+t+'"';
			
			
			if (((j==0 && (t[0]=='_' || t[0]=='~')) || (t.substring(0,2)==' _' || t.substring(0,2)==' ~')) 
			    && (i==s.length || s[i]==' ')) {	// _ or ~ used as a raw symbol
				sindexCounter++;
				windexCounter++;
				underscoreResume = false;
				tildeResume = false;
			}
			
			j++;
		}
	}
	
	//if (grouping.length!=ww.length) console.log(grouping.length, ww.length);
	
	
	// convert user-specified group indices to integers
	for (var i=0; i<sgrouping.length; i++) {
		if (typeof sgrouping[i] !== "number") {
			if (wgrouping.indexOf(sgrouping[i])>-1)
				return 'Cannot use the same index for a weak group and a strong group: '+sgrouping[i];
			if (sgrouping.lastIndexOf(sgrouping[i])==i)
				return 'Index specified for only one part of a group: '+sgrouping[i];
		
			sindexCounter++;
			for (var j=i+1; j<sgrouping.length; j++) {
				if (sgrouping[j]==sgrouping[i])
					sgrouping[j] = sindexCounter;
			}
			sgrouping[i] = sindexCounter;
		}
	}
	for (var i=0; i<wgrouping.length; i++) {
		if (typeof wgrouping[i] !== "number") {
			if (wgrouping.lastIndexOf(wgrouping[i])==i)
				return 'Index specified for only one part of a group: '+wgrouping[i];
			
			windexCounter++;
			for (var j=i+1; j<wgrouping.length; j++) {
				if (wgrouping[j]==wgrouping[i])
					wgrouping[j] = windexCounter;
			}
			wgrouping[i] = windexCounter;
		}
	}
	
	return [sgrouping, wgrouping];
}


function loadVersion(I, versionS) {
	parts = versionS.split('\t');

	var mweactor = AA[I][MWEAnnotator.annotatorTypeIndex].actors[0];
	$(mweactor.target).val(parts[parts.length-2]);
	ann_update(mweactor, mweactor.getValue());
	
	var noteactor = AA[I][ItemNoteAnnotator.annotatorTypeIndex].actors[0];
	$(noteactor.target).val(parts[parts.length-1]);
	ann_update(noteactor, noteactor.getValue());
	
	var meta = $.parseJSON(parts[parts.length-3].replace(/\\'/g,"'"));
	var chklblann = AA[I][ChunkLabelAnnotator.annotatorTypeIndex];
	$(chklblann.target).val($.toJSON(meta.chklbls));
	chklblann.updateTargets();
	
	// TODO: preps
}


function doSubmit() {

<? if ($iFrom<0) { // demo mode
      if ($iFrom==-1) { ?>
	var orig = "It even got a little worse during a business trip to the city , so on the advice of a friend I set up an appointment with True Massage .";
	var markup = "It even got~ a_little ~worse during a business~trip to the city , so on|1 the advice|1 of a friend I set_up an appointment~with True_Massage .";
<?    } else if ($iFrom==-2) { ?>
	var orig = "~ TORTURE TEST $ & &c. \" $ | a w x y1 y2 z q b ~ c ~ _ _";
	var markup = "~|1 TORTURE~TEST $|2 &|2 &c. \" $$3 ||2 a$3 w_x_ y1_y2 _z~q~b ~ c$4 ~$4 _|1 _";
	// TODO: figure out how to deal with &amp; in the input
<?    } ?>
	$('.item .input').val(($('.item .input').val()==orig) ? markup : orig);
<? } ?>

	return ann_submit();
}
</script>
<title>Multiword Expression Annotation: #<?= $iFrom ?> in <?= (isset($query)) ? $query : $split ?> (<?= $_SERVER['REMOTE_USER'] ?> as <?= $u ?>)</title>
</head>
<body<?= ($embedded) ? ' class="embedded"' : '' ?>>

<? if (!$embedded) {
		$ixqs = $_GET;
		$ixqs['from'] = 0;
		$ixurl = 'nanni_items.php?' . http_build_query($ixqs) . "#n$iFrom";
?>
	<div class="indexlinks"><a href="<?= $ixurl ?>">item index</a></div>
<? } ?>

<form id="mainform" action="" method="post">

<? if (count($SENTENCES)==0) { ?><h1 style="text-align: center;">DONE.</h1><? } ?>
<? $I=0;
   foreach ($SENTENCES as $s) { 	// TODO: submit button will have to move to actually support multiple sentences per page?
		$sid = $s['sentenceId'];
?>

<div id="_<?= $sid ?>" class="item">
<input type="hidden" name="sentid[]" value="<?= $sid ?>" />
<input type="hidden" name="split[]" value="<?= $s['split'] ?>" />
<input type="hidden" name="reconciled[<?= $I ?>][0]" value="<?= $s['reconciled'][0] ?>" disabled="<?= ($reconcile) ? 'false' : 'disabled' ?>" />
<input type="hidden" name="reconciledtime[<?= $I ?>][0]" value="<?= $s['reconciledtime'][0] ?>" />
<? if (count($s['reconciled'])>1) { ?>
<input type="hidden" name="reconciled[<?= $I ?>][1]" value="<?= $s['reconciled'][count($s['reconciled'])-1] ?>" />
<input type="hidden" name="reconciledtime[<?= $I ?>][1]" value="<?= $s['reconciledtime'][count($s['reconciledtime'])-1] ?>" />
<? } ?>
<input type="hidden" name="initval[]" class="initval" value="<?= $s['initval'] ?>" />
<input type="hidden" name="initnote[]" class="initnote" value="<?= $s['note'] ?>" />
<input type="hidden" name="beforeVExpand[]" class="beforeVExpand" value="" />
<input type="hidden" name="pos[]" class="pos" value="<?= $s['pos'] ?>" />
<input type="hidden" name="chklbls[]" class="chklbls" value="<?= $s['chklbls'] ?>" />
<p id="sent_<?= $sid ?>" class="sent"><? if (!($versions && count($s['versions'])==0)) {
	echo $s['sentence'];
} ?></p>
<!--<p><textarea id="input_<?= $sid ?>" name="annotation" rows="3" cols="80" class="input"><?= $s['sentence'] ?></textarea>
<input type="hidden" id="sgroups_<?= $sid ?>" name="sgroups" class="sgroups" value="" />
<input type="hidden" id="wgroups_<?= $sid ?>" name="wgroups" class="wgroups" value="" /></p>-->
<p style="text-align: center;" class="buttons">
<? if (!$nonav || !$nosubmit) { 
      $reqarr = $_GET;
      $reqarr['from'] = $iFrom-1;
      $prevurl = '?' . http_build_query($reqarr);
      $reqarr['from'] = $iFrom+1;
      $nexturl = '?' . http_build_query($reqarr);
?>
  <? if (!$nonav) { ?><input type="button" class="btnprev" value="&laquo; Previous" onclick="document.location='<?= htmlspecialchars($prevurl) ?>'"<?= (($iFrom==0) ? 'disabled="disabled"' : '') ?> /><? } ?>
	<input type="submit" name="submit" onclick="return doSubmit();" style="white-space: normal;" <? 
      if ($iFrom<0) { ?>value="This is a live demo of the annotation interface. Click here to toggle one possible analysis of this sentence. You can change the analysis by editing the text in the box above."<? } 
   else if ($nonav) { ?>value="Save"<? }
               else { ?>value="Save &amp; continue &raquo;"<? } ?> />
  <? if (!$nonav) { ?><input type="button" class="btnnext" value="Next &raquo;" onclick="document.location='<?= htmlspecialchars($nexturl) ?>'"<?= (($iFrom+1==$iTo) ? 'disabled="disabled"' : '') ?> /><? } ?>
<? } ?>
</p>
<!--<p><textarea id="note_<?= $sid ?>" name="note" rows="1" cols="80" class="comment" placeholder="Note for sentence <?= $sid ?> (optional)"></textarea></p>-->

<?
function versioncmp($a, $b) {
	// sort by submission time (second field in string)
	$partsA = explode("\t", $a);
	$partsB = explode("\t", $b);
	$subtimeA = intval($partsA[1]);
	$subtimeB = intval($partsB[1]);
	if ($subtimeA==$subtimeB) { return 0; }
	return ($subtimeA < $subtimeB) ? 1 : -1;
}
?>
<p style="text-align: center; font-size: small;"><? if ($versions && count($s['versions'])>0) { ?><select onchange="loadVersion(<?= $I ?>, this.value)" style="font-family: monospace; white-space: pre;"><?
	usort($s['versions'], 'versioncmp');
	$iver = 0;
	$nver = count($s['versions']);
	$users = array();
	foreach ($s['versions'] as $ver) {
		$parts = explode("\t", $ver);
		$usr = $parts[2];
		$mweS = htmlspecialchars($parts[count($parts)-2]);
		$noteS = htmlspecialchars($parts[count($parts)-1]);
		$titleS = $mweS . (($noteS) ? "\n\n$noteS" : '');
		
		// filter all but the most recent version from each user
		if ($nooutdated && isset($users[$usr])) { continue; }

		
		echo '<option value="' . htmlspecialchars($ver) . '" title="' . $titleS . '"';
		if (isset($users[$usr])) {
			echo ' class="outdated"';
		}
		else {
			$users[$usr] = true;
		}
		echo '>' . str_replace(" ", "&nbsp;", str_pad($nver-$iver, strlen(strval($nver)), " ", STR_PAD_LEFT)) . ' &nbsp; ' . date('r', intval($parts[1])) . ' &nbsp; ' . htmlspecialchars($usr) . '</option>';
		$iver++;
	}
?></select>
<?
// see special code in init() for initializing the select box
?>


<? } else if ($versions) { echo 'No other versions of this sentence.'; } ?>
</p>

	<? if ($vv!==null && !$versions) { 
		  foreach (((is_array($vv)) ? $vv : array($vv)) as $thisv) {
		  	$thisvHidden = !(strlen($thisv)>=1 && substr($thisv,0,1)==' ');
	?>
	<div class="versions">
		<p style="text-align: center; font-size: small;"><a href="#" onclick="$('a').eq(1).parent().parent().siblings('.beforeVExpand').val($('a').eq(1).parent().parent().siblings().find('.input').val()); $(this).parent().next('iframe').toggle(); <? if ($thisvHidden) { ?>$(this).parent().next('iframe').get(0).contentWindow.init();<? } ?>"><?= count($s['versions']) ?> versions in history</a></p>
		<iframe src="<?= htmlspecialchars($_SERVER["REQUEST_URI"]) ?>&amp;versions=<?= urlencode(preg_replace('/^ /', '', $thisv)) ?>&amp;nonav&amp;nosubmit&amp;noinstr&amp;readonly&amp;embedded" 
				<? if (!$thisvHidden) { ?>onload="this.contentWindow.init()"<? } ?>
				style="<?= ($thisvHidden) ? 'display: none; ' : '' ?>width: 106%; height: 15em; position: relative; left: -2%; border: none; background-color: #eee;"></iframe>
	</div>
	<? 
	      }
	  } ?>

</div>

<?   $I++;
   } ?>

<!-- TOOD: reset button?
<p style="text-align: center;"><input type="reset" name="reset" style="background-color: #000; border: solid 1px #000; color: #fff;" /></p>
-->

<p style="text-align: center">
<? if (!$noinstr) { ?>
	<small><a href="<?= $instructions ?>" style="color: #aaa;" target="_blank">instructions</a></small>
<? } ?>
</p>


<div class="controls">
<p>
<? if (isset($query)) { ?>
	<input type="hidden" name="q" value="<?= $query ?>" />
<? } ?>
	<input type="hidden" name="from" value="<?= ($iFrom+$perpage) ?>" />
	<input type="hidden" name="to" value="<?= $iTo ?>" />
	<input type="hidden" name="loadtime" value="<?= mktime(); ?>" />
	<input type="hidden" name="resultsLog" id="results" value="" />
</p>

</div>
</form>
</body>
</html>