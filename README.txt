Batch Language Rule Processor[1]
April 14, 2015, © Greg Jennings

This is the Rule Language Specification Version 2.1.

This is what I call a "Rule Language". Simply put, a "rule" is a function[2] 
with optional arguments. An uppercase "Rule" is an array of "rules". And 
"Rules" are an array with each member a "Rule". Rules are stored in an INI 
format file (but with a different parse function almost any format can be 
used).

Rule Language Specification 2.1

Basic Rule Format

The format of a single rule is:

        [modifier][command|function|label] [arguments]

Here is a basic Rule in INI format:

	[hello]
	print "Hello World"

To run it, the PHP test code "calls" the rule by it's URL interface:

	index.php?hello

Which simply outputs the string. ('print' here is not the PHP print construct 
but is a "command" (an alias to a rules.php defined function).

A "function" can be any PHP function A "label" is for a special jump statement 
defined below.

The optional "arguments" are space separated strings, strings can be within 
quotes. The first example has tww argument, the second has one:

	function foo bar
	function "foo bar"

Arguments can have a PHP superglobals as an argument:

	print "this rule is {$_GET['rule']}"

A $_GET variable can be by shorthand, with this the same"

	print "this rule is $rule"

Other special variables are defined below.

A "modifier" is a single character classified as conditional, arithmetic, 
operator or a label.

Conditionals test the return value of the preceding function. They are:

        ?                    execute if preceding function returned true
        :                    execute if previous condition failed (else)
        !                    execute if preceding function returned false

Arithmetic tests are comparisons of the preceding function's return value to 
the return of the function it is used on:

        >                    true if function return is greater than preceding
        <                    true if function return is less than preceding

These tests would normally be followed by a conditional modifier.

Labels mark a position in the rules:

        .label               define label

They can be combined with conditional to just to a the label:

        ?.label              jump to label if true
        :.label              jump to label if previous condition failed (else)
        !.label              jump to label if false
        ..[label]            unconditional jump to label; exit if no label

When a function is executed it's return valuse is stored. This is called the 
r-value. The operators set, store and restore this value:

        = argument           set r-value to argument
        +var [argument]      store r-value or argument in "var"
        -var                 restore r-value from "var"
        -v var               set r-value true if "var" has been set

There are shorthand "file operator aliases":

        -f file              same as is_file file
        -d file              same as is_dir file
        -r file              same as is_readable file
        -w file              same as is_writeable file

Regular expressions cab be applied to the r-value 

Search and Replace

        /pattern/            search for pattern; sets r-value true or 
                             false; sets variables (see below)
        /pattern/text/       search for pattern and replace with text; 
                             sets r-value to number of replacements; sets 
                             variables

These are actually full regular exprestions and can be used in other ways.
Modifiers are supported. Currently, forward slash (/) in pattern or text is 
not supported.

For string arguments there are several special variables available:

        $0                   r-value
        $1                   the first subexpression that matched; $2 the 2nd; 
                             etc.
        $_                   holds result of string replace and special 
                             commands (see below)
        $]                   Rules version string
        $[                   PHP version number
        $,                   print terminator ("<br>")
        $"                   print array separator (",")

Commands are builtin functions. The first two do not effect the r-value:

        print arguments      print arguments separated by spaces
        include file         includes PHP file (in function scope)
        version              print Rules version

The others act on r-value as an array:

	range 1 4            set r-value to array (this is the PHP function)
        count                sets number of elements of r-value to $_
        pop                  pops last value out of r-value into $_
        push argument        pushes argument to the end of r-value
        reverse              reverses r-value
        shift                shifts first value out of r-value into $_
        sort                 sorts r-value (natural sort)
        unshift argument     prepends argument to r-value

There are limited conditional loops special commands. They all work on the 
r-value:

        @while               while r-value is true
        function [arguments]

        @until               while r-value is fale
        function [arguments]

        @for                 array (simple); sets each value in $_ (string)
        function [arguments]

A function argument can be execute if within backticks:

	print `exec whoami`

SUMMARY

The "rules" algorithm is very simple and the rules language syntax is entirely 
of my own making (I've never seen anything like it).[3] As the test code shows, 
it is well suited for a website, and it might be useful for other things.

The code uses some static data and no globals, and if you've seen it, it ain't 
a class—but the internal static array for data makes it work not unlike like a 
class (class tendencies?)

The odd "modifier character" form is because that makes the code small (and 
there are a few characters left for possible additional features, but I'd hate 
to see this go beyond that and have no plans to).

Notes

1. BLRP is Be El Are Pee, not "blurp".
2. A rule function will be a function (procedure) of whatever language the 
rules algorithm is implemented in.
3. Like a Push-Me-Pull-You.
4. License: http://creativecommons.org/licenses/by-nc-sa/4.0/ 

