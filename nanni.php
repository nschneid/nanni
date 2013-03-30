<?php
// session_start();	// has started hanging... :(
 echo '<!DOCTYPE html>'; 

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
<link rel="stylesheet" href="http://code.jquery.com/ui/1.9.2/themes/base/jquery-ui.css" />
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

.indexlinks { position: fixed; bottom: 10px; right: 10px; }
.indexlinks a { color: #aaa; font-variant: small-caps; }
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

if (!array_key_exists('from', $_REQUEST)) {	// demo mode
	$iFrom = -1;
	$iTo = -1;
}
else {
	$iFrom = intval($_REQUEST['from']);
	$iTo = (array_key_exists('to', $_REQUEST)) ? intval($_REQUEST['to']) : -1;
}



if ($iFrom>-1) {
	if (isset($_REQUEST['split']))
		$split = $_REQUEST['split'];
	else
		die("missing: split");
	
	/*
	$perpage = intval($_REQUEST['perpage']);
	if ($perpage<=0)
		$perpage = 10;
	*/
	$perpage = 1;	// TODO: allow multiple sentences per page

	$kv = "$udir/$split.nanni";
	
	if (array_key_exists("submit", $_REQUEST)) {	// save data from a page of annotation
		
		$TAG_FILE = "$udir/$split.nanni.all";
		$tagF = fopen($TAG_FILE, 'a');
		if (!$tagF) die("Unable to save annotations: " . getcwd() . "/$TAG_FILE");
		
		$ann = $_REQUEST['annotation'];
		//$ann = htmlspecialchars_decode(stripslashes($ann), ENT_QUOTES);
		$ann = trim(preg_replace('/\s+/', ' ', $ann));
		//$note = htmlspecialchars_decode(stripslashes($note), ENT_QUOTES);
		
		for ($I=0; $I<count($_REQUEST['sentid']); $I++) {
			$mwe = trim(preg_replace('/\s+/', ' ',  $_REQUEST['mwe'][$I]));
			$note = trim(preg_replace('/\s+/', ' ',  $_REQUEST['note'][$I]));
			$key = $_REQUEST['sentid'][$I];
			$initval = addslashes($_REQUEST['initval'][$I]);
			$val = $_REQUEST['loadtime'] . "\t" . mktime() . "\t@$u\t$ann\t{\"initval\": \"$initval\",   \"sgroups\": " . $_REQUEST['sgroups'][$I] . ",   \"wgroups\": " . $_REQUEST['wgroups'][$I] . (($prep) ? ",   \"preps:\" " . $_REQUEST['preps'][$I] : '') . ",   \"url\": \"" . $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"] . "\",   \"session\": \"" . session_id() . "\",  \"authuser\": \"$user\"}\t$mwe\t$note\n";
			fwrite($tagF, "$key\t$val");
			update_key_value($kv, $key, $val);
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
		header('Location: ?' . http_build_query($qsarray));
	}
	
	($iFrom>=0 && ($iTo>$iFrom || $iTo==-1)) or die("You have finished annotating the current batch. Thanks!");
	
	$IN_FILE = "$ddir/$split";
	$f = fopen($IN_FILE, 'r');
	
	
	// Load the input sentences
	$SENTENCES = array();
	
	if ($f) {
		$l = 0;
		while (($entry = fgets($f)) !== false) {
			if ($l >= $iFrom) {
				if (($iTo>-1 && $l >= $iTo) || $l >= ($iFrom+$perpage)) break;
	
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
	
				$sentdata = array('sentence' => trim($tokenizedS), 'sentenceId' => $sentId);

				if (!$new) {	// load user's current version of the sentence, if available

					
					if ($readonly) {
						$sentdata['versions'] = get_key_values(get_user_dir($_REQUEST['versions'])."/$split.nanni", $sentId);
					}
					else {
						$v = get_key_value($kv, $sentId);
						if ($v!==null) {
							$parts = explode("\t", $v);
							$sentdata['initval'] = htmlspecialchars($parts[count($parts)-2]);
							$sentdata['note'] = htmlspecialchars($parts[count($parts)-1]);
						}
					}
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
										  "rows": "1", "cols": "80",
										  "readonly": <?= ($readonly) ? 'true' : 'false' ?>}).addClass("comment").val(this.initval);
		this.target = $control.get(0);
		$('<p/>').append($control).insertAfter($(item).find('p.buttons'));
		this.ann.submittable = true;
		if ($control.prop("readonly") && $control.val()==="") $control.hide();
	}
	a.rerender = function () {
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
										  "rows": "3", "cols": "80",
										  "readonly": <?= ($readonly) ? 'true' : 'false' ?>}).addClass("input").val(this.initval);
		if ($control.prop("readonly") && $control.val()==="") $control.hide();
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
function TokenLabelAnnotator(I, itemId) {
	this._name = 'TokenLabelAnnotator';
	this.labels = ['LOCATION', 'PERSON', 'TIME', 'GROUP', 'OTHER'];
	if (arguments.length == 0) return; // stop -- necessary for inheritance
	
	Annotator(this, I, itemId);
	this.actors = [];
}
TokenLabelAnnotator.prototype._makeTarget = function (wordelt, wordOffset) {
	var a = new Actor(this);
	a.word = wordelt;
	a.tokenOffset = wordOffset;
	a.labels = this.labels;
	a.labeldescriptions = this.labeldescriptions;
	this.actors.push(a);
	a.getValue = function () {
		return this.target.value;
	}
	a.firstrender = function () {
		var theactor = this;
		var word = this.word;
		var wordId = $(this.word).attr("id");
		// http://api.jqueryui.com/autocomplete/
		// TODO: depending on defaults/preannotations, set placeholder for preps belonging to an MWE
		var $control = $('<input class="tokenlabel" />').attr({"id": "toktag_"+this.ann.itemId+"_"+wordId.replace(/^i\d+/, ''), 
			"name": "tag_"+this.ann.itemId+"_"+wordId.replace(/^i\d+/, '')}).autocomplete({ source: this.labels, 
			autoFocus: true, minLength: 0, 
			focus: function (evt, ui) {	// apply tooltip to focused menu item
				var v = ui.item.value;
				if (theactor.labeldescriptions && theactor.labeldescriptions[v]!==undefined)
					$(evt.currentTarget).attr("title", theactor.labeldescriptions[v]);
				else
					$(evt.currentTarget).attr("title", "");
			},
			// don't handle 'select' event because the value won't be updated yet!
			change: function (evt, ui) {
				// apply tooltip to input box to reflect selection
				var v = theactor.getValue();
				
				if (theactor.labeldescriptions && theactor.labeldescriptions[v]!==undefined)
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
		this.target = $control.get(0);
		var wordWidth = $(word).outerWidth();
		var width = Math.max($control.outerWidth(),wordWidth);
		$(word).append($control);

		this.submittable = true;
		
		// prevent double-click on preposition label text box from hiding it
		$control.dblclick(function (evt) { evt.stopPropagation(); });
		
		this.aparecium = function () {
			$control.show();
			$(word).css("width", width);
		}
		this.evanesco = function () {
			$control.hide();
			$(word).css("width", "inherit");
		}
		
		this.evanesco();
			
		this.start = function () {
			this.aparecium();
			this.ann.constructor.started = true;
		}
		this.stop = function () {
			this.evanesco();
			this.ann.constructor.stopped = true;
		}
	}
	return a;
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
	this.labels = ['Agent', 'Audience', 'Cause/Reason', 'Comitative', 'Communicator', 'Comparison', 'Cost/Compensation/Price', 
 					'Destination/Direction', 'Duration', 'Giver', 'GOAL', 'Instrument', 'Location', 'Manner', 'Means', 'Medium', 
					'Origin', 'OTHER', 'PATH', 'Patient/Affected', 'Purpose', 'Recipient/Beneficiary/Maleficiary', 'Reciprocal/Co-participant',
					'Result', 'SOURCE', 'State', 'Time', 'Topic/Content', 'Trajectory', 'X', '?'];
	this.labeldescriptions = {'X': 'X: No semantic function; purely collocational or syntactic',
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

function ChunkLabelAnnotator(I, itemId) {
	this._name = 'ChunkLabelAnnotator';
	this.actors = [];
}



// global variables for the annotation protocol
annotators = [ItemNoteAnnotator, MWEAnnotator <?= (($prep) ? ', PrepTokenAnnotator' : '') ?>];	//, ChunkLabelAnnotator];
II = []; // item offset on page => DOM node
AA = []; // annotators for each item

// stages allow different kinds of annotation to become available to the user at different times
// 'submit' progresses to the next stage until all are exhausted, then submits the form
INIT_STAGE = <?= $init_stage ?>;
CUR_STAGE = 0;
NUM_STAGES = <?= (($prep) ? '2' : '1') ?>;
ItemNoteAnnotator.prototype.startStage = 0;
MWEAnnotator.prototype.startStage = 0;
PrepTokenAnnotator.prototype.startStage = 1;
ItemNoteAnnotator.prototype.stopStage = -1;
MWEAnnotator.prototype.stopStage = -1;
PrepTokenAnnotator.prototype.stopStage = -1;

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
	$('input[type=submit]').val((CUR_STAGE==0) ? "Continue to prepositions »" : "Next sentence »");
	return (CUR_STAGE>=NUM_STAGES);	// ready to submit form
}


/*********************************************/




KEY_LEFT_ARROW = 37;
KEY_UP_ARROW = 38;
KEY_RIGHT_ARROW = 39;
KEY_DOWN_ARROW = 40;

$(function () {

	$('.item > p.sent').each(function (j) {
		ww = $(this).text().split(/\s+/g);
		$(this).html('');
		for (var i=0; i<ww.length; i++) {
			$('<span>').attr({"id": "i"+j+"w"+i, "class": "w", "data-w": i}).text(ww[i]).appendTo($(this));
			if (i<ww.length-1) $(this).append(' ');
		}
	});
	
	
	ann_init();
	ann_setup();
	
	if ((CUR_STAGE+1)>=NUM_STAGES) {
		//$('input[type=submit]').val("Next sentence »");
	}
	else if (PrepTokenAnnotator.annotatorTypeIndex!==undefined && PrepTokenAnnotator.prototype.startStage==(CUR_STAGE+1))
		$('input[type=submit]').val("Continue to prepositions »");
	
//	$('form').sisyphus();	// use localstorage to cache form data so it is preserved across refresh
	for (var I=0; I<II.length; I++) {	// now that input fields may have been populated, re-render the MWE annotations
		AA[I][MWEAnnotator.annotatorTypeIndex].validate();
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
	
	// TOOD: prep tags
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
<title>Multiword Expression Annotation (<?= $_SERVER['REMOTE_USER'] ?> as <?= $u ?>)</title>
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
<input type="hidden" name="initval[]" class="initval" value="<?= $s['initval'] ?>" />
<input type="hidden" name="initnote[]" class="initnote" value="<?= $s['note'] ?>" />
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
<script type="text/javascript">
$(function () {
	$('select').each(function (j) { this.size=Math.min(5,this.options.length); $(this).change(); })
});
</script>

<? } else if ($versions) { echo 'No other versions of this sentence.'; } ?>
</p>

	<? if ($vv!==null && !$versions) { 
		  foreach (((is_array($vv)) ? $vv : array($vv)) as $thisv) {
	?>
	<div class="versions">
		<p style="text-align: center; font-size: small;"><a href="#" onclick="$(this).parent().next('iframe').toggle();"><?= count($s['versions']) ?> versions in history</a></p>
		<iframe src="<?= htmlspecialchars($_SERVER["REQUEST_URI"]) ?>&amp;versions=<?= preg_replace('/^ /', '', $thisv) ?>&amp;nonav&amp;nosubmit&amp;noinstr&amp;readonly&amp;embedded" 
				style="<?= (strlen($thisv)>=1 && substr($thisv,0,1)==' ') ? '' : 'display: none; ' ?>width: 106%; height: 15em; position: relative; left: -2%; border: none; background-color: #eee;"></iframe>
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
	<input type="hidden" name="split" value="<?= $split ?>" />
	<input type="hidden" name="from" value="<?= ($iFrom+$perpage) ?>" />
	<input type="hidden" name="to" value="<?= $iTo ?>" />
	<input type="hidden" name="loadtime" value="<?= mktime(); ?>" />
	<input type="hidden" name="resultsLog" id="results" value="" />
</p>

</div>
</form>
</body>
</html>