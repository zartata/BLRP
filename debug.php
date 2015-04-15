<?php

/* comprehensive and complex debugger! */

define('DBG_TRUNC',0);

function debuglog($log) {
	if ($log && !isset($GLOBALS['debugmsgs']))
		register_shutdown_function('debug_msgs');
	$GLOBALS['debugmsgs'] = array();
}

function debug($msg, $s = '') {
	if (!isset($GLOBALS['debugmsgs']))
		return;

	$trun = DBG_TRUNC;

	if ($msg === '')
		$msg = '""';
	else
	if ($msg === NULL)
		$msg = '(null)';
	else
	if ($msg === TRUE)
		$msg = '(true)';
	else
	if ($msg === FALSE)
		$msg = '(false)';
	else
	if (is_array($msg))
		$msg = 'Array {'. implode(',',$msg) .'}';

	if ($trun > 0 && strlen($msg) > $trun)
		$msg = substr($msg,0,$trun).' ...';

	$msg = htmlentities($msg);
	$msg = str_replace("\n",'',$msg);

	list($file,$line,$func) = caller(1);
	$func = caller(2,'function');
	list($f,$l,$na) = caller(2);
	$c = "($f,$l)";
	$msg = "$c($file,$line,$func) $s$msg";
	$GLOBALS['debugmsgs'][] = $msg;
}

function debug_msgs() {

	echo "<pre><br><b>message log:</b><br>";
	foreach($GLOBALS['debugmsgs'] as $msg)
		echo "$msg<br>";
}

function caller($depth = 1, $elem = NULL) {

	$bt = debug_backtrace();

	if ($elem) {
		$s = (isset($bt[$depth][$elem])) ? $bt[$depth][$elem] : '';
		if ($elem == 'file')
			$s = basename($s);
		return $s;
	}

	if (!isset($bt[$depth]))
		return(array('?',$depth,''));

	$bt = $bt[$depth];
	$file = (isset($bt['file'])) ? basename($bt['file']) : '';
	$line = (isset($bt['line'])) ? $bt['line'] : '';
	$func = $bt['function'];

	if ($elem === '')
		return "$file,$line,$func";

	return array($file,$line,$func);
}
