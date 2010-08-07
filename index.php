<style>
	.content { width:600px;border:1px solid #CDCDCD;margin:7px; }
	h1 { background:#CDCDCD;color:black;padding:7px;margin:0px}
	body{ font-family: arial}
	blockquote { font-weight:normal }
	p { text-indent: 37px;padding:7px;}
</style>

<div class="content">
<h1>Norm</h1>
<blockquote><b>N</b>ot an <b>ORM</b></blockquote>
<p>I created norm because there were so many "COOL" ORMS out there for PHP but holy smokes, they were overly complicated for my feeble mind to wrap around.  I just wanted something I could throw a regular object at, tie in a couple other objects to relate to it, store it in a database and return it as a regular ASSOC array I could toss into json or a template .. </p>
<p>So that's what I endeavored to do.</p>
<p style="text-align:right;padding-right:33px"><a href="norm-0.1.tgz">Download Norm v.01</a></p>
<small> Tested: Linux / PHP 5.2.9 + PDO::MySQL (so far)</small>
</div>
<h2>A wee blog app for norm</h2>
<ul>
	<li><a href="examples/norm_blog.php">Blog Example</a></li>
</ul>
<br/>
<h2>Some example code for norm:</h2>
<?php highlight_string(file_get_contents("examples/norm_example.php")); ?>
