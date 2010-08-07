<?php
ob_start();
session_start();

include('../norm.php');
include('../../norm_db_config.php');
/* I like Norm, Norm is easy and wears a tie. :-) */
$w = new Norm("mysql:host=localhost;dbname={$dbname}",$login,$pass);


class User
{
	var $login;
	var $password;
}

class Post
{
	var $title;
	var $body;
}

class Comment
{
	var $comment;
}

// initialize with some default data
if (isset($_REQUEST['init']))
{
	// Create an inital user
	$u = new User();
	$u->login		= 'test';
	$u->password	= 'test';
	$w->store($u);

	// Create an inital post BY that user
	$p = new Post();
	$p->title	= 'This is an initial post!';
	$p->body	= 'Norm likes to wear a tie.  It makes him feel friendly!';
	$w->tie($u,$p);

	header("Location: ".$_SERVER['SCRIPT_NAME']);
}
if (isset($_REQUEST['post']))
{
	$p = new Post();
	$u = new User();
	$w->stuff($_REQUEST['post'],$p);
	$w->stuff($_SESSION['user'],$u);
	$w->tie($u,$p);
}

// Ok, now for the actual blog
$u = new User();
$p = new Post();
$c = new Comment();

// Do we have a login request?
if (isset($_REQUEST['user']))
{
	$user = $w->stuff($_REQUEST['user'],$u)->get($u);
	if (isset($user[0])) $_SESSION['user'] = $user[0];
}

$style = <<<__STYLE__
<style>
	body{ font-family:arial;}
	fieldset { background: #EFEFEF;width:600px; }
</style>
__STYLE__;

$loginForm = <<<__EOT__
{$style}
<h1>login</h1>
Login: <i>test</i> Pass: <i>test</i>
<form action="{$_SERVER['SCRIPT_NAME']}" method="post">
login: <input type="text" name="user[login]">	<br />
pass: <input type="password" name="user[password]">	<br/>
<input type="submit">
__EOT__;

$postForm = <<<__EOT__
{$style}
<h1>Post Something Interesting!</h1>
<fieldset>
<form action="{$_SERVER['SCRIPT_NAME']}" method="post">
<input type="hidden" name="post">
Title: <input type="text" name="post[Post_title]">	<br />
Body:<br />
<textarea name="post[Post_body]" rows="5" cols="80"></textarea><br />
<input type="submit">
</fieldset>
__EOT__;

// check to make sure we're logged in:
if (!isset($_SESSION['user']['User_id']))	print $loginForm;
else 										print $postForm;

// So my user has POSTS .. so lets get everybody's posts
$posts = $w->get($u,'User_login,Post_title,Post_body,Post_updated',NORM_FULL);

if (!empty($posts))
{
	print "<h1>Things posted to Norm</h1>";
	foreach($posts as $idx=>$post)
	{
	print <<<__EOT__
	<fieldset>
		<legend>{$post['Post_title']}</legend>
		{$post['Post_body']}<br/ >
		<p style="font-size:11px;">Posted by: {$post['User_login']} on {$post['Post_updated']}</p>
	</fieldset>
__EOT__;
	}
}
else 
{
	print <<<__EOT__
<h1>Nothing posted yet - </h1>
<p><a href="{$_SERVER['SCRIPT_NAME']}?init=true">Click here to initialize tables</a></p>
__EOT__;
}

?>
