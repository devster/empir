source
------

source contains:

* empir.php   : the php script
* package.xml : file to make pear package
* empir       : shell launcher for the pear package
* empir.bat   : Batch launcher for the pear package

The PHP tool to play with phar
==============================

Empir is a simple and robust php script that let's you build
a phar from an entire PHP applicationbut also extract and 
convert phar files. The phar extension provides a way to put 
entire PHP applications into a single file called a "phar" (PHP Archive)
for easy distribution and installation. 
See PHP Phar documentation http://php.net/manual/en/book.phar.php

Empir consists of just one file, a command line tool, written in PHP,
working on Unix and Windows. There are no external dependencies, 
no need for a database, no need to setup credentials, and nothing
needed to be installed or configured.
The only thing that Empir requires is PHP >= 5.3.0 

Install
=======

Direct install
--------------

Installing Empir is as simple as downloading the latest stable empir file
and saving it where you see fit. 
Extract it and check that Empir works correctly by calling it without any argument: 

	$ php empir.php
	
You should see the Empir help. (Instead of empir.php, you'll see "empir" further in the doc).

PEAR install
------------

	$ pear channel-discover pear.devster.org
	$ pear install devster/empir

Check that Empir works correctly by calling it without any argument: 

	$ empir
	
Features
========

* Create a universal and portable phar from an entire php application.
* Extract all files of a phar like a simple archive.
* Convert and/or compress phar file

Usage
=====

Make a phar
-----------

**Basic usage**

	$ php empir make <phar_file> <stub_file> <root_app> [options]
	
To make a phar with empir the setting phar.readonly must be set to 0 
in your php.ini, otherwise you should use Empir like the following: 

	$ php -dphar.readonly=0 empir make <phar_file> <stub_file> <root_app> [options]
	
_If you use Empir from PEAR installation don't care about this php option. It used directly in the executable file._

Let's explain the differents parameters: 

* `phar_file`: this is the name of your future phar file, you can pass absolute or relative path, but don't forget the extension. Ex: ./my.phar
* `stub_file`: this is the entry file of you application, you must specify it with a path relative to your application. Ex: index.php, if index.php is at the root of your application (myapp/index.php)
* `root_app`: this is the root directory of your application you want to turn into phar, you can pass absolute or relative path. Ex (/home/myapp)

**Exclude files from your app**

Use the option --exclude=PATTERN or --fexclude=FILE 

	$ php empir make <phar_file> <stub_file> <root_app> --exclude=".hg/*|*.txt" --fexclude="./excluding-list.txt"
	
These options are used to exclude files from the phar. "exclude" works with
string, one or several patterns separate with a pipe. "fexclude" do the same 
thing but takes a file where are listed the patterns. One per line.
The example above excludes all files from the .hg/ directory, all text 
files and all the files matche patterns of excluding-list.txt of your entire application. 

**Format and compression**

A phar can be one of these 3 formats: phar, zip and tar. Keep in mind that 
zip or tar phar can't be used whitout php extension phar activated. 
So for a best portability don't use format option. The same for compression,
users of your phar will have the correct php extension (zlib or bzip2) to run it.

Use --format=FORMAT and --compress=TYPE 

	$ php empir make <phar_file> <stub_file> <root_app> --format=tar --compress=gz
	
These 2 options are independent from each other. 
The example above creates a compressed tar phar, my.phar.tar.gz

* `FORMAT`: tar or zip
* `TYPE`: gz or bz2 

_! Unfortunately zip compression is not supported !_

Extract a phar
--------------

Extract a phar file (compressed or not) as a vulgar archive:

	$ php empir extract <phar_file> [extract_path]
	
*Extract all type of archive and compression supported by the `make` command.*

See the parameters:

* `phar_file`: path to phar you want to extract, absolute or relative path accepted. Ex: ./my.phar.zip
* `extract_path`: where you want to extract your phar, absolute or relative path accepted of course. Let empty to extract in current folder.

Convert a phar
--------------

This command allows to convert, compress or decrompress a phar file.

	$ php empir convert <phar_file> <format> [compression]

Let's explain the differents parameters:

* `phar_file`: phar file to convert, from absolute or relative path.
* `format`: the format you wan to convert, can be zip, tar or phar.
* `compression`: optional, compression type of your converting, can be gz or bz2.

_! Unfortunately zip compression is not supported !_

Credits
=======
Empir is brought to you by Jeremy Perret <jeremy@devster.org>.
Documentation design inspired by pirum. http://www.pirum-project.org/
Empir is released under The MIT License. http://www.opensource.org/licenses/mit-license.php

