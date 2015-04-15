<?php

/* RULES.PHP - Batch Language Rule Processor (BLRP), Spec. Version 2.1.

   The test code makes this a website "rules" manager/handler.

   Basically, "rule" is an array of function names with optional space 
   delimited arguments which will be run for a URL with a rule name.

   License: http://creativecommons.org/licenses/by-nc-sa/4.0/
*/

define('RULES_FILE','rules');
define('RULES_VERSION','2.1');
define('RULES_MAX_LOOP',1000);


/* rules_run - execute the rules of a rule */

function rules_run($rule) {

	if (!($rules = rules($rule)))
		return;

# these data live locally here only; two are passed around
	$rval = $dval = $sval = NULL;

	while (($rulestr = array_shift($rules)) !== NULL) {
		rules('_var','_rval',$rval);

		// if loop rule, store it, set $rulestr to next rule
		if (rules_loop($rval,$dval,$rulestr,$rules) === TRUE)
			continue;

		$args = rule_parse($rulestr);	// turn into arguments
		$name = array_shift($args);	// shift out function name

		debug($name);
		debug($args);

		// `command` check (sets $cmd)
		rules_exec($cmd,$args);

		// interpolate "special" arguments
		rules_args($rval,$dval,$args);

		// exec `command`
		if ($cmd)
			rules_exec($cmd,$args);

		// set r-value
		if ($name == '=') {
			if (isset($args[0]))
				$rval = $args[0];
			//debug($rval);
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

		debug($rval);
	}
}

/* rules_loop - execute a rule loop */

// if loop rule set $func to statement; $rulestr to next rule; store loop type

function rules_loop($rval, &$dval, &$rulestr, &$rules) {
static $loop, $func, $count;

	if ($loop) {
		if (!is_array($func) && function_exists($func))
			$dval = $func();
		else {
			if (is_array($rval))
				$dval = array_shift($func);
			else
				$dval = $func;
		}
		if ($loop == '@while' && !$dval)
			$loop = FALSE;
		if ($loop == '@until' && $dval)
			$loop = FALSE;
		if ($loop == '@for' && !$dval)
			$loop = FALSE;
		if ($loop) {
			if (++$count < RULES_MAX_LOOP) {
				array_unshift($rules,$rulestr);	// do again
				return FALSE;
			}
		}
		$count = 0;
		return TRUE;
	}
	if ($rulestr[0] != '@')
		return FALSE;

# break in two?????

	$t = explode(' ',$rulestr);
	if (!isset($t[1]))
		$func = $rval;
	else {
		$func = $t[1];
		if (!function_exists($func))	// bad rule format
			return TRUE;		//  ignore the loop rule
	}
	$loop = $t[0];
	return TRUE;
}

/* rules_exec - execute command, stores result back into $args */

// first call $cmd = NULL, $args = `command [arguments]`
// second call $cmd = command, $args = [arguments]

function rules_exec(&$cmd, &$args) {

	// if in `` extract command and any arguments
	if ($args && $args[0][0] == '`') {
		$arr = explode(' ',$args[0]);
		$cmd = trim(array_shift($arr),'`');
		$args = array();
		foreach ($arr as $a)
			$args[] = rtrim($a,'`');
		// now $cmd and $args (array) are stored
		return;
	}

	if ($cmd === NULL)
		return;

	// now we actually exec the $cmd with the interpolated $args
	if (isset($args[0]))
		$args[0] = call_user_func_array($cmd,$args);
	else
		$args[0] = $cmd();

	$cmd = NULL;
}

/* rules_args - iterpolate strings as arguments */

function rules_args($rval, $dval, &$args) {
# not much can be done to lessen this complexity
$s = array('$0','$_','$,','$"','$[','$]',);
$r = array(
($rval === NULL) ? '' : $rval,
($dval === NULL) ? '' : $dval,
rules('_var','_trm'),
rules('_var','_sep'),
phpversion(),
RULES_VERSION,
);
if (is_array($rval)) $r[0] = 'Array';
foreach (range(1,9) as $n) {
	$s[] = '$'.$n;
	$r[] = rules('_var','_m'.$n);
}
	if ($args)
	foreach ($args as &$a)
		$a = str_replace($s,$r,$a);
}

/* rules_search - search/replace/regex on r-value */

function rules_search(&$rval, &$dval, $name) {

	if (empty($name) || $name[0] != '/')
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
			rules('_var','_m'.$i++,$r);
	}

	return TRUE;
}

/* rules_operater - check rule operator (returns FALSE if not) */

// maps "operator" to function; function takes argument; argument is required

function rules_operator(&$rval, $name, $args) {
static $op = array(
'-f' => 'is_file',
'-d' => 'is_dir',
'-r' => 'is_readable',
'-w' => 'is_writeable',
);
# these are just aliases as the actual functions can be used instead
# can also uses a special "substitution" array in the INI file to map them 
# via a simple "if $sub[$name] $name = $sub[$name]" like function
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
	if (empty($name) || strpos('+-',$name[0]) === FALSE)
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
		rules('_var',$name,$rval);
		break;
	case '-':
		$rval = rules('_var',$name);
		break;
	}

	return TRUE;
}

/* rules_cond - check rule for conditional execution; also does jumps */

function rules_cond(&$rval, &$name, &$rules) {
static $cond,$aval,$op;

	if (empty($name) || strpos('?:!.><',$name[0]) === FALSE)
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
		rules_print('Rules '.RULES_VERSION);
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


/* rules - initialize the rules data; return rules or rule */

// loads "rules" file which could be in INI file format or a PHP file 
// directly defining the array (JSON, XML to be supported)

function rules($rule = NULL, $var = NULL, $val = NULL) {
static $rules = array();

	if ($rules === array()) {
// to reduce load time, but then this/that file must be a different name
//		if (is_file($f=RULES_FILE.'.php'))
//			include $f;
//		else {
			$rules = rules_load(RULES_FILE.'.ini');
			if ($rules == array())
				return $rules = FALSE;
			if (!isset($rules['_var']['_trm']))
				$rules['_var']['_trm'] = '<br>';
			if (!isset($rules['_var']['_sep']))
				$rules['_var']['_sep'] = ',';
//		}
	}

	if ($rules === FALSE)			// not needed if parse error 
		return FALSE;			//  condition is handled

	if ($rule === NULL)
		return $rules;

	// meta (internal) data
	if ($rule == '_var') {
		$vars =& $rules['_var'];
		if ($var === NULL)
			return $vars;
		if ($val !== NULL)
			return $vars[$var] = $val;
		return (isset($vars[$var])) ? $vars[$var] : '';
	}

	if (!isset($rules[$rule]))
		return (isset($rules['default'])) ? $rules[$rules['default']] : '';
	return $rules[$rule];
}


/* special command function aliases */

function rules_print() {
	$args = func_get_args();
	if (is_array($args[0]))
		print implode(rules('_var','_sep'),$args[0]);
	else
		print implode(' ',$args);
	print rules('_var','_trm');
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

/* rule_parse - parse a string into arguments */

function rule_parse($rulestr) {
static $a = array('/"(.+)"/','/^TRUE$/','/^FALSE$/','/^NULL$/',"/''/",'/""/');
static $b = array('$1',TRUE,FALSE,NULL,'','');

	$RE = 
	'/(\/.+\/)|'.			// a regex
	'([\.\?\-!:<>=\w]+)|'.		// function or command
	'([\w_\$\{\}\[\]\']+)|'.	// super global
	'"([^"]*)"|'.			// quoted string
	'`([^`]+)`|'.			// exec string
	'([-\d]+)|'.			// number
	'(\'\')/';			// empty string

	preg_match_all($RE,$rulestr,$res);
	$args = $res[0];
	$args = preg_replace($a,$b,$args);
	$args = preg_replace_callback('/\$([a-z]+)/','_arg',$args);
	$args = preg_replace_callback('/{\$_[A-Z]+\[\'[A-Za-z_]+\'\]}/','_glob',$args);

	return $args;
}

function _arg($res) {
	return $r = (isset($_GET[$res[1]])) ? $_GET[$res[1]] : '';
}

function _glob($res) {
	error_reporting(($e=error_reporting()) ^ E_NOTICE);
	eval("\$r = \"{$res[0]}\";");
	error_reporting($e);
	return $r;
}


/* miscellaneous, made up functions for testing */

function return10() {
	return 10;
}
