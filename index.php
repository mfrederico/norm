<link rel="stylesheet" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/black-tie/jquery-ui.css" type="text/css" media="all" />
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js" type="text/javascript"></script>
<script src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/jquery-ui.min.js" type="text/javascript"></script>

<style>
	body	{ background:#EFEFEF}
	.content { width:800px;border:1px solid #CDCDCD;margin:7px; margin:auto;background:#FFFFFF;padding:7px;}
	h1 { background:#CDCDCD;color:black;padding:7px;margin:0px}
	body{ font-family: arial}
	blockquote { font-weight:normal }
	p { text-indent: 37px;padding:7px;}
	a { text-decoration:none;padding:7px }
	ul { list-style-type:none}
	h2 { background: #abcdef;padding:7px;font-size:15px }
</style>

<body>
	<div class="content">
	<h1>Norm</h1>
	<blockquote><b>N</b>ot an <b>ORM</b></blockquote>
	<p>Norm more closely resembles an object mapper in that you can store and retrieve objects to and from a database without having to write any SQL code.   It dynamically maps the database tables, data columns and data types to maintain a structured hiearchy of object relationship.</p>
	<p>I originally created norm because there were so many great ORMS/Object Mappers out there for PHP but to be honest, they were overly complicated for my feeble mind to wrap around.  I just wanted something I could throw a &quot;regular&quot; object at, tie in a couple other objects that relate to it, store it in a database and return it as a regular ASSOC array I could consume as JSON for jQuery or as an array for a  template engine .. </p>
	<p>So that's what I endeavored to do.</p>
	<p style="text-align:right;padding-right:33px"><a class="ui-state-highlight" href="doc/Norm/Norm.html">Norm::PHPDoc</a></p>
	<p style="text-align:right;padding-right:33px"><a class="ui-state-highlight" href="http://www.github.com/mfrederico/norm/">Download Norm</a></p>

	<div style="padding:12px">
	<small style="text-align:right;display:block">Tested: Win/Linux / PHP 5.2.9 + PDO::MySQL (so far)</small>
	<h2>A working blog example for norm</h2>
	<p> There was zero SQL used in the creation of NormBlog.</p>
	<ul>
		<li><a class="ui-state-highlight" href="examples/norm_blog.php">Click here to experience: NormBlog</a></li>
	</ul>
	<br />
	<h2>See how easy Norm is with these examples:</h2>

	<div id="accordion" style="font-size:12px">
		<h3><a href="#">Example 1 - Lets store an object</a></h3>
		<div class="ui-helper-hidden" id="example1">
			<?php highlight_string(file_get_contents("examples/example1.php")); ?>
		</div>

		<h3><a href="#">Example 2 - Lets get an object</a></h3>
		<div class="ui-helper-hidden" id="example2">
			<?php highlight_string(file_get_contents("examples/example2.php")); ?>
		</div>

		<h3><a href="#">Example 3 - Changing field values and storing</a></h3>
		<div class="ui-helper-hidden" id="example3">
			<?php highlight_string(file_get_contents("examples/example3.php")); ?>
		</div>

		<h3><a href="#">Example 4 - Tie or Relate two objects together</a></h3>
		<div class="ui-helper-hidden" id="example4">
			<?php highlight_string(file_get_contents("examples/example4.php")); ?>
		</div>

		<h3><a href="#">Example 5 - Dynamic column creation</a></h3>
		<div class="ui-helper-hidden" id="example5">
			<?php highlight_string(file_get_contents("examples/example5.php")); ?>
		</div>

		<h3><a href="#">Example 6 - Delete the object structure</a></h3>
		<div class="ui-helper-hidden" id="example6">
			<?php highlight_string(file_get_contents("examples/example6.php")); ?>
		</div>
	</div>
	</div>
	</div>

	<script>
		$(document).ready(function(){
			$('#accordion').accordion({ autoHeight: false,collapsible: true,active:false});
		});
	</script>
</body>
