<?php

/* RULES.PHP - Batch Language Rule Processor (BLRP), Spec. Version 1.0.

   The test code makes this a website "rules" manager/handler.

   Basically, "rule" is an array of function names with optional space 
   delimited arguments which will be run for a URL with a rule name.

   License: http://creativecommons.org/licenses/by-nc-sa/4.0/
   rules@gajennings.net

   NOTE: This code is only 99% tested!
*/

define('RULES_FILE','rules');
define('RULES_VERS','2.0');
define('RULES_DBG',0);

_rules();			// self initialize
rules_setup();			// for the testing code

error_reporting(-1);

if (isset($_GET['rule'])) {
	rules_run($_GET['rule']);
	print '<br>rules:<br><pre>';
	foreach (_rules($_GET['rule']) as $r)
		print "$r<br>";
}
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
<dt><a href="rules.php?files&a=rules.php">?files</a><dd>file operators
<dt><a href="rules.php?array">?array</a><dd>array commands
<dt><a href="rules.php?search&a=foobar">?search</a><dd>search &a
</dl>
<pre>
<?php
}

/* rules_setup - check and verify rule argument */

// this is not part of the rules code per se but the test code; it sets 
// $_GET['rule'] to the value of the first argument on the URL

function rules_setup() {

	$rules = _rules();

	// if no argument set to default rule if defined else no 'rule' 
	if (empty($_GET)) {
		if (isset($rules['default']))
			$_GET['rule'] = $rules['default'];
		return;
	}

# this is a little over kill but it gets the job done; room for more...
	// set rule to first URL argument; if not defined set to error rule 
	// if error defined else 'rule' is not defined
	foreach ($_GET as $get => $na) {
		unset($_GET[$get]);
		if (!isset($rules[$get])) {
			if (isset($rules['error']))
				$_GET['rule'] = $rules['error'];
			break;
		}
		$_GET['rule'] = $get;
		break;
	}
}


/* rules_run - execute the rules of a rule */

function rules_run($rule) {

	if (!($rules = _rules($rule)))
		return;

# these data live locally here only; two are passed around
	$rval = $dval = $sval = NULL;

	while (($rulestr = array_shift($rules)) !== NULL) {
		_rules('_vars','_rval',$rval);

		// convert '$var' from $_GET['var'] (if set, else '')
		$rulestr = rules_getargs($rulestr);

		// convert any super globals
		if (preg_match('/{\$[_A-Z]*\[/',$rulestr)) {
			$rulestr = "\$rulestr = \"$rulestr\";"; //"
			eval($rulestr);
			// simply fails if syntax error
		}

		// get function name and any argument(s)
		$args = explode(' ',$rulestr);
		$name = array_shift($args);

		// interpolate "special" arguments
		rules_args($rval,$dval,$args);

		// set r-value
		if ($name == '=') {
			if (isset($args[0]))
				$rval = $args[0];
			continue;
		}

# $name has any modifier prefix here

		if (rules_search($rval,$dval,$name) === TRUE)
			continue;

		// operators
		if (rules_operator($rval,$name,$args) === TRUE)
			continue;

		// r-value commands
		if (rules_rval($rval,$dval,$name,$args) === TRUE)
			continue;

		// conditionals test
		if (rules_cond($rval,$name,$rules) === TRUE)
			continue;
# $name does not have any modifier prefix here

		// special commands
		if (rules_command($rval,$dval,$name,$args) === TRUE)
			continue;

		// call the rule function, silently ignore not founds
		if (function_exists($name))
			$rval = call_user_func_array($name,$args);

		if (RULES_DBG) {
			var_dump($name,$rval);
			print '<br>';
		}
	}
}

/* rules_args - iterpolate strings as arguments */

// 1) variable lookup
// 2) variable by function
// 3) string substitution

function rules_args($rval, $dval, &$args) {
static $var = array(
'$,' => '_trm',
'$"' => '_sep',
);
static $fun = array(
'$[' => 'phpversion',
);
static $sub = array(
'$]' => RULES_VERS,
"''" => '',
'TRUE' => TRUE,
'FALSE' => FALSE,
'NULL' => NULL,
);
	foreach ($args as &$a) {
		if (isset($sub[$a]))
			$a = $sub[$a];
		else
		if (isset($fun[$a]) && ($f=$fun[$a]))
			$a = $f();
		else
		if (isset($var[$a]))
			$a = _rules('_vars',$var[$a]);
		// // subexpressions
		else
		if (preg_match('/\$([1-9])/',$a,$r))
			$a = _rules('_vars','_m'.$r[1]);
		else
		// the final two
		if ($a == '$0')
			$a = $rval;
		else
		if ($a == '$_')
			$a = $dval;
	}
}

/* rules_search - search/replace on r-value */

function rules_search(&$rval, &$dval, $name) {

	if ($name[0] != '/')
		return FALSE;

	if (preg_match('/^(\/[^\/]+\/)([^\/]+)\/(.*)/',$name,$r)) {
		if ($r[3])
			$r[1] .= $r[3];
		$dval = preg_replace($r[1],$r[2],$rval,-1,$c);
		$rval = $c;
	}
	else
	if (preg_match('/^(\/[^\/]+\/)(.*)/',$name,$r)) {
		if ($r[2])
			$r[1] .= $r[2];
		$rval = preg_match($r[1],$rval,$rr);
		array_shift($rr); $i = 1;
		foreach ($rr as $r)
			_rules('_vars','_m'.$i++,$r);
	}

	return TRUE;
}

/* rules_operater - check rule operator (returns FALSE if not) */

// maps operator to function; function takes argument; argument is required

function rules_operator(&$rval, $name, $args) {
static $op = array(
'-f' => 'is_file',
'-d' => 'is_dir',
'-r' => 'is_readable',
'-w' => 'is_writeable',
);
# these are just aliases as the actual functions can be used instead
	if (!isset($op[$name]))
		return FALSE;

	if (!isset($args[0])) {
		$rval = NULL;
		return TRUE;
	}

	$f = $op[$name];
	$rval = $f($args[0]);
	return TRUE;
}

/* rules_rval - r-value functions */

function rules_rval(&$rval, &$dval, &$name, $args) {
static $vars = array();
# order dependent; expects other '-' preceded commands (operators) to be 
# parsed beforehand
	if (strpos('+-',$name[0]) === FALSE)
		return FALSE;

	// is var set
	if ($name == '-v') {
		if (!isset($args[0]))
			$dval = TRUE;
		else
			$dval = isset($vars[$args[0]]);
		return TRUE;
	}

	// store or restore r-value
	$ch = $name[0];
	$name = substr($name,1);
	switch ($ch) {
	case '+':
		_rules('_vars',$name,$rval);
		break;
	case '-':
		$rval = _rules('_vars',$name);
		break;
	}

	return TRUE;
}

/* rules_cond - check rule for conditional execution; also does jumps */

function rules_cond(&$rval, &$name, &$rules) {
static $cond,$aval,$op;

	if (strpos('?:!.><',$name[0]) === FALSE)
		return FALSE;

	switch ($name[0]) {
	// if arithmetic, store $rval and operator for next rule
	case '>':
	case '<':
		$aval = $rval;
		$op = $name[0];
		break;

// just a simple state machine; the odd thing is that the tests are backward, 
// as in for '?' (execute if TRUE) the test here is for the negative 
// condition -- this function returns TRUE to tell the caller ^to not execute 
// the function^ (via a continue statement)

	case '?':			// if $rval false skip rule
					//  but must check if $aval was set
		if ($aval !== NULL) {
			$rval = (int)$rval;
			$rval = eval("return $aval $op $rval;");
			$aval = NULL;
		}
		$cond = $rval;
		if (!$cond)
			return TRUE;
		break;
	case '!':			// if $rval true skip rule
		if ($rval)
			return TRUE;
		break;
	case ':':			// if $cond (previous) true skip rule
		if ($cond)
			return TRUE;
		break;
	case '.':			// skip label; or fall thru to jump
		if ($name[1] != '.')
			return TRUE;
		break;
	}

	$name = substr($name,1);

	// jump to label (.label) or exit (.)
	if ($name[0] == '.') {
		if ($name == '.')
			$rules = array();
		else {
			$jmp = array_search($name,$rules);
			if ($jmp !== FALSE)
				array_shiftn($rules,$jmp);
		}
		return TRUE;
	}

	return FALSE;
}

/* array_shiftn - shift array N times */

function array_shiftn(&$array, $n) {

	while ($n--)
		array_shift($array);

}

/* rules_command - check rule for special command (returns TRUE if it was) */

// 1) special commands handled inline
// 2) special command mapped to function; takes argument; argument optional

# currently let missing argument fail

function rules_command(&$rval, &$dval, &$name, &$args) {
static $cmd = array(
'count' => 'count',
'pop' => 'array_pop',
'push' => array('array_push'),
'shift' => 'array_shift',
'unshift' => array('array_unshift'),
'reverse' => 'array_reverse',
'sort' => 'sort',
);
	// constructs: no need to functionize, there will not be many
	switch ($name) {
	case 'print':
		if (!isset($args[0]))
			rules_print($rval);
		else
			call_user_func_array('rules_print',$args);
		return TRUE;		
	case 'include':
		include $args[0];
		return TRUE;
	case 'version':
		rules_print('Rules '.RULES_VERS);
		return TRUE;		
	}

	// special command
	if (isset($cmd[$name])) {
		// for array r-value only
		if (!is_array($rval)) {
			$dval = NULL;
			return TRUE;
		}
		// function does not or does require argument
		if (!is_array($cmd[$name])) {
			$f = $cmd[$name];
			$dval = $f($rval);
		}
		else {
			$f = $cmd[$name][0];
			$dval = (isset($args[0])) ? $f($rval,$args[0]) : NULL;
		}
		return TRUE;
	}

	return FALSE;
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

// loads "rules" file which could be in INI file format or a PHP file 
// directly defining the array (XML to be supported)

function _rules($rule = NULL, $var = NULL, $val = NULL) {
static $rules = array();

	if ($rules === array()) {
// to reduce load time, but then this/that file must be a different name
//		if (is_file($f=RULES_FILE.'.php'))
//			include $f;
//		else
			$rules = rules_load(RULES_FILE.'.ini');
		if ($rules == array())
			return $rules = FALSE;
		$rules['_vars']['_trm'] = '<br>';
		$rules['_vars']['_sep'] = ',';
	}

	if ($rule === NULL)
		return $rules;

	// meta (internal) data
	if ($rule == '_vars') {
		$vars =& $rules['_vars'];
		if ($var === NULL)
			return $vars;
		if ($val !== NULL)
			return $vars[$var] = $val;
		return (isset($vars[$var])) ? $vars[$var] : NULL;
	}

	if (!isset($rules[$rule]))
		return (isset($rules['default'])) ? $rules[$rules['default']] : NULL;
	return $rules[$rule];
}


/* special command function aliases */

function rules_print() {
	$args = func_get_args();
	if (is_array($args[0]))
		print implode(_rules('_vars','_sep'),$args[0]);
	else
		print implode(' ',$args);
	print _rules('_vars','_trm');
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

function return10() {
	return 10;
}


/* comprehensive and complex debugger! */

function debug($a) {
if (!RULES_DBG) return;
	var_dump($a);
	print '<br>';
}
