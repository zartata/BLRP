Batch Language Rule Processor

This is version one of Batch Language Rule Processor.[1]

This implements what I call a "Rule Language". Simply put, a "Rule" is a list of one or more "rules". And a "rule" is a PHP function.[2] A basic Rule is, in INI file format, like:

	[name]
	function

(A proper case "Rule" is a list of lowercase "rules". Sometimes, due to English language rules, I will use the term "rule function" for "rule".)

The code includes a simple, web-based test platform only, but other interfaces using the basic algorithm are in process. (The test code is derived from code that is actually used to run a website I developed. So I am calling the test code a "website rules manager/handler".)

The web interface executes a Rule by URL:

	http://localhost/rules.php?name

With this Rule:

	[phpinfo]
	phpinfo

this URL:

	http://localhost/rules.php?phpinfo

Will display the output of phpinfo().

Each rule can have optional "op characters" preceding it to control it's execution -- this will be explained below.

In this implementation, internally the Rule is known as $_GET['rule'], and rules can take arguments.

RULE ARGUMENTS

Currently, arguments are stringized; i.e the Rule:

	[vars]
	var_dump a b c

when executed will result in:

	string(1) "a" string(1) "b" string(1) "c"

Rule function arguments with PHP like variables are compared against GET variables and set to them if they exist, else are set to empty strings. For example:

	[vars]
	var_dump $a $b $c

with '?vars&a=1&c=foo', will result in:

	string(1) "1" string(0) "" string(3) "foo"

Arguments can reference super globals (within {}). This result is the same as above:

	[vars]
	var_dump {$_GET['a']} {$_GET['b']} {$_GET['c']}

An argument of '' (double single quotes) is replaced with the PHP value of empty string. (A possible addition may be to convert NULL, TRUE and FALSE or any defined value.)

RULE CONDITIONALS

Rule functions can be conditional. If a function returns a value it is stored (I call this the "r-value"). A following function can have an indicator to base it's execution on. The conditionals are '!', '?' and ':'. The '!' is for functions that return FALSE (loose), the '?' is for functions that return TRUE and ':' is for an "if else" construct:

	[test]
	is_numeric $a
	?display $a is a number
	:display $a is not a number

Since echo() and print() are not functions they cannot be used as rules; display() is a defined function that prints all of it's arguments and so does the equivalent.

BASIC ARITHMETIC

A boolean value is stored if the return value for the function is:

	>	greater than
	<	less than

This is an example of checking if a number is greater than ten:

	[gt]
	abs $a
	>return10
	?display > 0
	:display <= 10

The odd thing about the arithmetic operators is that they work on the previous value, that is, in the above example, the return value of 'abs \$a' is stored and '>return10' sets a boolean: "N (\$a) > M (10)".

The other odd thing is that "op chars" only work on the return values of functions, and the test code defines return10() to simply return 10.

(If a rule function returns NULL, FALSE or empty string it is converted to an integer for '>' and '<'.)

JUMP TO LOCATION

Locations can be jumped to based on results and a label:

	?.	TRUE
	!.	FALSE
	:.	else
	..	unconditional
	.	define label

This is an example of checking if a number is true (loose):

	[jmp]
	abs $a
	?.t
	display FALSE
	..
	.t
	display TRUE

A unconditional jump without a label, '..', is equivalent to exit().

A FEW OTHER THINGS

This Rule introduces the final four constructs: '=' set the "r-value" to it's argument; '+var' stores the "r-value" into a variable 'var'; '-var' sets the "r-value" to the value of variable 'var'; and an argument of '\$0' is replaced by the "r-value".

	[store]
	= $a
	+r
	display rval stored ''
	-r
	display $0

SUMMARY

The "rules" algorithm is very simple (basically, two functions of 125 lines total), and the syntax is entirely of my own making (I've never seen anything like it[3]). As the test code shows, it is well suited for a website, and it might be useful for other things.

The code uses some static data and no globals, and if you've seen it, it ain't a class -- but the internal static array for data makes it work not unlike like a class (class tendencies?)

The odd "op character" form is because that makes the code small (and there are a few characters left for possible additional features, but I'd hate to see this go beyond that and have no plans to).

NOTES
1. BLRP is Be El Are Pee, not "blurp".
2. A rule function will be a function (procedure) of whatever language the 3. rules algorithm is implemented in.
3. Like a Push-Me-Pull-You.
4. License: http://creativecommons.org/licenses/by-nc-sa/4.0/
