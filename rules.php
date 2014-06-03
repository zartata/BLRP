<?php

/* RULES.PHP - Batch Language Rule Processor (BLRP), Spec. Version 1.0.

   The test code makes this a website "rules" manager/handler.

   Basically, "rule" is an array of function names with optional space 
   delimited arguments which will be run for a URL with a rule name.

   License: http://creativecommons.org/licenses/by-nc-sa/4.0/
   rules@gajennings.net
*/

define('RULES_FILE','rules');
define('RULES_VERS','Rules 1.0');
define('RULES_DBG',0);


_rules();			/* self initialize */

/* testing */

if ($_GET['rule'])
	rules_run($_GET['rule']);
else {
?>
<!DOCTYPE html>
<style>dt { float: left; width: 1in; }</style>
<p>The documentation: <a href="rules.txt">rules.txt</a>. The test rules file: <a href="rules.ini">rules.ini</a>. <a href="https://gist.github.com/AndovaBegarin/2f0e61e6978b5a514f12">Gist</a></p>
<p>The tests:<br>
<dl>
<dt><a href="rules.php?print">?print</a><dd>print version
<dt><a href="rules.php?phpinfo">?phpinfo</a><dd>phpinfo
<dt><a href="rules.php?vars&a=a&b=1">?vars</a><dd>var dumps &a &b &c
<dt><a href="rules.php?test&a=10">?test</a><dd>display whether &a is number
<dt><a href="rules.php?not&a=a">?not</a><dd>display if &a is not number
<dt><a href="rules.php?set&a=10">?set</a><dd>displays value of &a
<dt><a href="rules.php?gt&a=10">?gt</a><dd>tests value of &a > 10
<dt><a href="rules.php?jmp&a=">?jmp</a><dd>display if &a is true
<dt><a href="rules.php?store&a=11">?store</a><dd>store and display &a
</dl>
<pre>
<?php
}


/* rules_run - execute the rules of a rule */

function rules_run($rule) {

	debug("rule: '$rule'");

	if (!($rules = _rules($rule)))
		return;

	$rval = $sval = NULL;

	while (($rulestr = array_shift($rules)) !== NULL) {

		// convert '$var' from $_GET['var'] (if set else '')
		$rulestr = rules_getargs($rulestr);

		// convert any super globals
		if (preg_match('/{\$[_A-Z]*\[/',$rulestr)) {
			$rulestr = "\$rulestr = \"$rulestr\";";
			error_reporting(($e=error_reporting()) ^ E_NOTICE);
			eval($rulestr);
			error_reporting($e);
			// simply fails if syntax error
		}

		// get function name and any argument(s)
		$args = explode(' ',$rulestr);
		$name = array_shift($args);

		// look for "special" arguments (can add NULL, TRUE, etc.)
		foreach ($args as &$a) {
			$a = str_replace("''",'',$a);
			if ($a == '$0')
				$a = $rval;
		}
		unset($a);

		debug("'$name'");
		debug($args);

		// set r-value
		if ($name == '=') {
			$rval = $args[0];
			continue;
		}

		// conditionals test
		if (rules_cond($rval,$name,$rules) === FALSE)
			continue;

		// call the rule function, silently ignore not founds
		if (function_exists($name))
			$rval = call_user_func_array($name,$args);
		else
			debug("in rule '$rule', function '$name' not found");
	}
}

/* rules_cond - check rule for conditional execution (returns FALSE if not) */

function rules_cond(&$rval, &$name, &$rules) {
static $cond,$aval,$op,$vars = array();

	if (strpos('?:!.><-+',$name[0]) === FALSE)
		return;

	switch ($ch = $name[0]) {
	// if arithmetic, store $rval and operator for next rule
	case '>':
	case '<':
		$aval = $rval;
		$op = $name[0];
		break;

// just a simple state machine; the odd thing is that the tests are backward, 
// as in for '?' (execute if TRUE) the test here is for FALSE -- this is 
// because this function returns FALSE to tell the caller ^to not execute the 
// function^ (via a continue statement)

	case '?':			// if $rval false skip this rule
					//  but must check if $aval was set
		if ($aval !== NULL) {
			$rval = (int)$rval;
			$rval = eval("return $aval $op $rval;");
			$aval = NULL;
		}
		$cond = $rval;
		if (!$cond)
			return FALSE;
		break;
	case ':':			// if $cond (previous) true skip rule
		if ($cond)
			return FALSE;
		break;
	case '!':			// if $rval true skip rule
		if ($rval)
			return FALSE;
		break;
	case '.':			// skip label; or fall thru to jump
		if ($name[1] != '.')
			return FALSE;
		break;
	}

	$name = substr($name,1);

	// store or restore r-value
	switch ($ch) {
	case '-':
		$rval = $vars[$name];
		return FALSE;
	case '+':
		$vars[$name] = $rval;
		return FALSE;
	}

	// jump to label (.label) or exit (.)
	if ($name[0] == '.') {
		if ($name == '.')
			$rules = array();
		else {
			$jmp = array_search($name,$rules);
			if ($jmp !== FALSE)
				array_shiftn($rules,$jmp);
		}
		return FALSE;
	}

	return TRUE;
}

/* array_shiftn - shift array N times */

function array_shiftn(&$array, $n) {

	while ($n--)
		array_shift($array);

}

/* rules_setup - check and verify rule argument */

// this is not part of the rules code per se but the test code; it sets 
// $_GET['rule'] to the value of the first argument on the URL (which would 
// be the "rule"; if the rule is not found in the rules array 'rule' is set 
// to the value of the '_error' rule if defined (if not defined it will be '')

function rules_setup() {

	$rules = _rules();

	if (empty($_GET)) {
		$_GET['rule'] = $rules['_default'];
		return;
	}

# this is a little over kill but it gets the job done 
	foreach ($_GET as $get => $na) {
		unset($_GET[$get]);
		if (!isset($rules[$get])) {
			$_GET['rule'] = $rules['_error'];
			break;
		}
		$_GET['rule'] = $get;
		break;
	}
}

/* rules_getargs - replace '$var' in a string with "$_GET['var']" or "''" */

function rules_getargs($_) {

	$re = '/\$([a-z]+)/';
	if (preg_match_all($re,$_,$res)) {
		foreach ($res[0] as $k => $v) {
			$r = (isset($_GET[$res[1][$k]])) ? $_GET[$res[1][$k]] : "''";
			$_ = str_replace($v,$r,$_);
		}
	}
	return $_;
}

/* _rules - initialize the rules data; return rules or rule */

// loads "rules" file which could be n INI file format or a PHP file 
// directly defining the array (like a class without it being a class)

function _rules($rule = NULL) {
static $rules = array();

	if ($rules == array()) {
// this would reduce load time, but then this file must be a different name
//		if (is_file($f=RULES_FILE.'.php'))
//			include $f;
//		else
			$rules = rules_load(RULES_FILE.'.ini');
		if ($rules == array())
			return $rules = FALSE;
		$rules['_version'] = RULES_VERS;
		foreach (explode(',','_default,_error,') as $d)
			if (!isset($rules[$d]))
				$rules[$d] = '';

		rules_setup();	// for the testing code
	}

	if ($rule === NULL)
		return $rules;

	if (!isset($rules[$rule]))
		$rule = $rules['_default'];

	return $rules[$rule];
}

/* rules_load - the rules array is an associative array of arrays */

// simplified parse_ini_file()

function rules_load($file) {

	if (($file = file($file)) == FALSE)
		return FALSE;

	$section = '';
	$data = array();

	while (list (, $l) = each($file)) {
		$l = chop($l);
		if ($l == '' || $l[0] == ';')
			continue;

		if (!$section && preg_match('/(.*)\s*=\s*(.*)/',$l,$res)) {
			$data[trim($res[1])] = $res[2];
			continue;
		}

		if (preg_match('/^\[(.*)\]/',$l,$res)) {
			$section = $res[1];
			continue;
		}

		$data[$section][] = $l;
	}

	return $data;
}

/* miscellaneous, made up functions for testing */

function rules_version() {
	print _rules('_version');
}

function display() {
	$args = func_get_args();
	print implode(' ',$args);
}

function return10() {
	return 10;
}


function debug($a) {
if (!RULES_DBG) return;
	if (is_array($a))
		$a = implode(',',$a);
	print "$a<br>";
}
