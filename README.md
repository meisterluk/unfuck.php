unfuck.php
==========

:project:       unfuck.php
:author:        meisterluk
:date:          since 2011, 12.09.10
:version:       beta
:license:       Emailware

A set of PHP snippets to avoid bullshitty php code. Grab any code you like.

[I dislike PHP][0]. But I have some legacy code I have to deal with. Therefore I wrote some snippets I can easily copy and paste to my source code and make PHP better. The `unfuck.php` file focuses on the following things:

* Enforce basic Unicode support
* Provide missing stdlib functions and classes
* Dealing with php's buggy type system

Installation
------------

* Install the user.ini according to the [PHP Manual][1].
* Copy&Paste or `include` source code of `unfuck.php`.
* Watch out for `@config` directives in `unfuck.php` where you have to adjust configuration variables to you own needs.

Please stop using PHP. Every time you write php code little kittens die.

greets,
meisterluk

  [0]: http://lukas-prokop.at/proj/documents/php_rant/  "Why I dislike PHP"
  [1]: http://www.php.net/manual/en/configuration.file.per-user.php  "PHP Manual - .user.ini files"
