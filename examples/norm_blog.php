<?php
ob_start();
session_start();

// So I can be a bit sloppy in my coding... 
error_reporting(E_ALL & ~E_NOTICE);

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
	var $post_id;
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
if (isset($_REQUEST['cmt']))
{
	$c = new Comment();
	$c->post_id = $_REQUEST['post_id'];
	$c->id		= null;
	$c->comment = $_REQUEST['comment'];
	$w->store($c);
}

if (isset($_REQUEST['post']) && $_SESSION['user']['id'])
{
	$u = new User();
	$u->id = $_SESSION['user']['id'];
	$u->post = new Post();

	$w->stuff($_REQUEST['post'],$u->post);
	$w->store($u);
}

// Ok, now for the actual blog
$u = new User(); // Root element

// Do we have a login request?
if (isset($_REQUEST['user']))
{
	// Should validate form, check for login && password
	$user = $w->stuff($_REQUEST['user'],$u)->get($u,'user_login,user_password,user_id');
	if (!empty($user)) 
	{
		$user = array_shift($user['user']);
		$_SESSION['user'] = $user;
	}
}

if (isset($_REQUEST['del']))
{
	$p = new Post();
	$p->id = intval($_REQUEST['del']);
	$w->del($p);
}

$style = <<<__STYLE__
<style>
	.ctrls		{ font-size:11px;  }
	body		{ font-family:arial;}
	fieldset	{ background: #EFEFEF;width:600px;margin-bottom:25px;}
	legend		{ font-weight:bold;}
</style>
__STYLE__;

$loginForm = <<<__EOT__
{$style}
<h1>login</h1>
Login: <i>test</i> Pass: <i>test</i>
<form action="{$_SERVER['SCRIPT_NAME']}" method="post">
login: <input type="text" name="user[login]">	<br />
pass: <input type="password" name="user[password]">	<br/>
<input type="submit" value="login">
</form>
__EOT__;

$postForm = <<<__EOT__
{$style}
<h1>Post Something Interesting!</h1>
<fieldset>
<form action="{$_SERVER['SCRIPT_NAME']}" method="post">
<input type="hidden" name="post">
Title: <input type="text" name="post[title]" size="50" style="width:90%">	<br />
Body:<br />
<textarea name="post[body]" rows="5" style="width:100%"></textarea><br />
<input type="submit" value="Post">
</form>
</fieldset>
__EOT__;

function commentForm($post_id)
{
	$commentForm = <<<__EOT__
<form action="{$_SERVER['SCRIPT_NAME']}" method="post">
<input type="hidden" name="cmt">
<input type="hidden" name="post_id" value="{$post_id}">
Comment: <input name="comment" size="50"><input type="submit" value="&raquo;">
</form>
__EOT__;
	return($commentForm);
}


// check to make sure we're logged in:
if (!isset($_SESSION['user']))	print $loginForm;
else 							print $postForm;

// So my user has POSTS .. so lets get everybody's posts
//$users = $w->get($u,'post_id,user_login,user_id,post_title,post_body,post_updated','',NORM_FULL);
$users = $w->get($u,'*','',NORM_FULL);

if (!empty($users))
{
	print "<h1>Things posted to Norm</h1>";
	foreach($users['user'] as $user_id=>$data)
	{
		foreach($data['post'] as $pidx=>$postData)
		{
			// Get all the comments for this post
			$c = new Comment();
			$c->post_id = $postData['id'];
			$comments = @$w->get($c,'comment_comment,comment_id','',NORM_FULL);

			if ($data['id'] == @$_SESSION['user']['id']) $delButton = " | <a class=\"ctrls\" href=\"{$_SERVER['PHP_SELF']}?del={$postData['id']}\">X</a>";
			print <<<__EOT__
<fieldset>
	<legend>{$postData['title']} {$delButton}</legend>
	<p style="font-size:11px;">Posted by: {$data['login']} on {$postData['updated']}</p>
	<blockquote>{$postData['body']}</blockquote>
__EOT__;
			if (!empty($_SESSION['user']['id'])) print commentForm($postData['id']);
			print "<b>Comments:</b><ul>";
			if (!empty($comments)) foreach(array_shift($comments) as $cid=>$comment) print "<li>{$comment['comment']}</li><Br />";
			print "</ul>";

		print "</fieldset>";
		}
	}
}
else 
{
	print <<<__EOT__
<h1>Nothing posted yet - </h1>
<p><a href="{$_SERVER['SCRIPT_NAME']}?init=true">Click here to initialize tables</a></p>
__EOT__;
}

function print_pre($dat)
{
	print "<pre>".print_r($dat,true)."</pre>";
}

?>
