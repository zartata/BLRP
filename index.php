<?php

include 'rules.php';
include 'debug.php';

rules();			// initialize
rules_setup();			// for the testing code

error_reporting(-1);

if (isset($_GET['rule'])) {
	debuglog(1);
	rules_run($_GET['rule']);
	print '<br>rules:<br><pre>';
	foreach (rules($_GET['rule']) as $r)
		print "$r<br>";
}
else {
?>
<!DOCTYPE html>
<style>dt { float: left; width: 1in; }</style>
<p>The documentation: <a href="rules.txt">rules.txt</a>. The test rules file: <a href="rules.ini">rules.ini</a>. <a href="https://gist.github.com/AndovaBegarin/2f0e61e6978b5a514f12">Gist</a></p>
<p>The tests:<br>
<dl>
<dt><a href="?print">?print</a><dd>print version
<dt><a href="?phpinfo">?phpinfo</a><dd>phpinfo
<dt><a href="?vars&a=a&b=1">?vars</a><dd>var dumps &a &b &c
<dt><a href="?test&a=10">?test</a><dd>display whether &a is number
<dt><a href="?not&a=a">?not</a><dd>display if &a is not number
<dt><a href="?set&a=10">?set</a><dd>displays value of &a
<dt><a href="?gt&a=10">?gt</a><dd>tests value of &a > 10
<dt><a href="?jmp&a=">?jmp</a><dd>display if &a is true
<dt><a href="?store&a=11">?store</a><dd>store and display &a
<dt><a href="?files&a=rules.php">?files</a><dd>file operators
<dt><a href="?array">?array</a><dd>array commands
<dt><a href="?search&a=foobar">?search</a><dd>search &a
<dt><a href="?exec">?exec</a><dd>run command
<dt><a href="?loop">?loop</a><dd>run loop
</dl>
<pre>
<?php
}

/* rules_setup - check and verify rule argument */

// this is not part of the rules code per se but the test code; it sets 
// $_GET['rule'] to the value of the first argument on the URL

function rules_setup() {

	$rules = rules();

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

