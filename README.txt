Batch Language Rule Processor[1]
May 31, 2014, © Greg Jennings

This is the Rule Language Specification Version 2.0.

This is what I call a "Rule Language". Simply put, a "rule" is a function[2] 
with qualifiers.

Rule Language Specification 2.0

Basic Rule Format

The format of a single rule is:

        [modifier][function|command|label] [arguments]

[function] is any PHP function with the return value stored—the r-value.

[command] is a special command with (usually) the r-value un-affected.

[label] is for jump statement; defined below.

[arguments] are strings with the following interpolations:

        superglobal          within {}
        $name                $_GET[name] alias
        $0                   r-value

Other special variables are defined below.

[modifier] is one of the following:

Comments

        ;                    comment

Conditionals

        ?                    execute function if r-value true
        !                    execute function if r-value false
        :                    execute function if previous condition failed

Arithmetic Operators

        >                    true if function return value is greater than r-value
        <                    true if function return value is less than r-value

Tests function return value against r-value and then sets r-value (boolean).

Jump Operators

        .label               define label
        ?.label              jump to label if r-value true
        !.label              jump to label if r-value false
        :.label              jump to label if previous condition failed
        ..[label]            unconditional jump to label; exit if no label

R-value Operators

        = argument           set r-value to argument
        +var [argument]      store r-value or argument in var
        -var                 restore r-value from var (see note)
        -v var               true if var has been set

Single letter vars will not be used as vars if conflicting with file operators 
(see below).

File Operators

        -f file              true if file is a file
        -d file              true if file is a directory
        -r file              true if file is readable
        -w file              true if file is writeable

Search and Replace

        /pattern/            search r-value for pattern; sets r-value true or 
                             false; sets variables (see below)
        /pattern/text/       search r-value for pattern and replace with text; 
                             sets r-value to number of replacements; sets 
                             variables

Modifiers are supported. Currently, forward slash (/) in pattern or text is 
not supported.

Variables

        $0                   r-value
        $1                   the first subexpression that matched; $2 the 2nd; 
                             etc.
        $_                   holds result of string replace and special 
                             commands (see below)
        $]                   version string
        $[                   PHP version number
        $,                   print terminator ("<br>")
        $"                   print array separator (",")

Special commands

        print arguments      print arguments separated by spaces
        include file         includes PHP file (in function scope)

The r-value not affected.

If r-value is array:

        count                sets number of elements of r-value to $_
        pop                  pops last value out of r-value and sets it to $_
        push argument        pushes argument to the end of r-value
        reverse              reverses r-value
        shift                shifts first value out of r-value and sets $_
        sort                 sorts r-value (natural sort)
        unshift argument     prepends argument to r-value

Next Version Considerations

Conditional Loops

        @while function      while function returns true
        function [arguments]

        @until function      while function returns false
        function [arguments]

        @for function        array (simple); sets value in $_ (string)
        function [arguments]

The Test Code

The code includes a simple, web-based test platform with the rules defined in 
an INI file. An uppercase "Rule" is a list of lowercase "rules". You can view 
the test RULES.INI file.

The web interface executes a Rule by URL:

        http://localhost/rules.php?name

With this Rule:

        [phpinfo]
        phpinfo

this URL:

        http://localhost/rules.php?phpinfo

Will execute phpinfo().

Basic arguments are stringized; i.e the Rule:

        [vars]
        var_dump a b c

will result in:

        string(1) "a" string(1) "b" string(1) "c"

Rule function arguments with PHP like variables are compared against GET 
variables and set to them if they exist, else are set to empty strings. For 
example:

        [vars]
        var_dump $a $b $c

with ?vars&a=1&c=foo, will result in:

        string(1) "1" string(0) "" string(3) "foo"

Arguments can reference super globals (within {}). This result is the same as 
above:

        [vars]
        var_dump {$_GET['a']} {$_GET['b']} {$_GET['c']}

An argument of (double single quotes) is replaced with the PHP value of empty 
string. Other substitutions are for NULL, TRUE and FALSE.

More Examples

Checking if a value is a integer:

        [test]
        is_numeric $a
        ?print $a is a number
        :print $a is not a number

Checking if a number is greater than ten:

        [gt]
        = $a
        >return10
        ?print > 0
        :print <= 10

The odd thing about the arithmetic operators is that they work on the previous 
value, that is, in the above example, the $a is stored and >return10 sets a 
boolean: r-value ($a) > 10.

The other odd thing is that the arithmetic operators work on the return values 
of functions (the r-value is not stored). The test code defines return10() to 
simply return 10.

(If a rule function returns NULL, FALSE or empty string it is converted to an 
integer for > and <.)

Checking if a value is true (loose):

        [jmp]
        = $a
        ?.t
        print false
        ..
        .t
        print true

The r-value constructs:

        [store]
        = $a
        +var
        print rval stored
        = 11
        -var
        print $0

Summary

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

