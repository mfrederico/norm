<?php
ob_start();
session_start();

// So I can be a bit sloppy in my coding... 
error_reporting(E_ALL & ~E_NOTICE);

include('../norm.php');
/* I like Norm, Norm is easy and wears a tie. :-) */

// Create a norm instance - sqlite
$w = new Norm("sqlite:normblog.sqlite");

$w->setTablePrefix('NORM_');

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

$u = new User(); // Root element

// initialize with some default data
if (isset($_REQUEST['init']))
{
	// Create an inital user
	$u = new User();
	$u->login		= 'test';
	$u->password	= 'test';

	// Create an inital post BY that user
	$p = new Post();
	$p->title	= 'This is an initial post!';
	$p->body	= 'Norm likes to wear a tie.  It makes him feel friendly!';


	// combine them
	$u->post = $p;

	// Store them
	$w->store($u);

	// Create an intial "comment"
	$c = new Comment();
	$c->comment = 'First comment';
	$c->post_id	= 1;
	$w->store($c);

	header("Location: ".$_SERVER['SCRIPT_NAME']);
}


// Authenticate this user!
if (isset($_REQUEST['user']))
{
	// Should validate form, check for login && password
	$user = $w->stuff($_REQUEST['user'],$u)->get($u,'user_login,user_password,user_id',Norm::SINGLE)->results['user'][0];
	if (!empty($user)) $_SESSION['user'] = $user;
	else die('<h1>Invalid login</h1>');
}

// Make sure we're authenticated to perform these actions
if (isset($_SESSION['user']['id']) && isset($_REQUEST['a']))
{
	switch(strtolower($_REQUEST['a']))
	{
		case 'c':
			$c = new Comment();
			$c->post_id = $_REQUEST['post_id'];
			$c->id		= null;
			$c->comment = $_REQUEST['comment'];
			$w->store($c);
			break;
		case 'p':
			$u = new User();
			$u->id = $_SESSION['user']['id'];
			$u->post = new Post();

			// using "stuff" here allows us to expand our post table dynamically
			$w->stuff($_REQUEST['post'],$u->post);
			$w->store($u);
			break;
		case 'd':
			$p = new Post();
			$p->id = intval($_REQUEST['id']);
			$w->del($p);
			break;
		default:
			die('<h2>Please authenticate.</h2>');
	}
}

// Ok, now for the actual markup content

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
<input type="hidden" name="a" value="p">
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
<input type="hidden" name="a" value="c">
<input type="hidden" name="post_id" value="{$post_id}">
Comment: <input name="comment" size="50"><input type="submit" value="&raquo;">
</form>
__EOT__;
	return($commentForm);
}

// check to make sure we're logged in:
print (!isset($_SESSION['user'])) ?	$loginForm  : $postForm;

// Lets grab the full hierarchy of all users
// Because we will be recieving posts, we can order by them as well.
$users = array_shift($w->orderby('post_updated','DESC')->get($u,'*',Norm::FULL)->results);

if (!empty($users))
{
	print "<h1>Things posted to Norm</h1>";
	foreach($users as $idx=>$data)
	{
		foreach($data['post'] as $pidx=>$postData)
		{
			// Get all the comments for this post
			$c = new Comment();
			$c->post_id = $postData['id'];
			$comments = @$w->get($c,'comment_comment,comment_id',Norm::FULL)->results;

			if ($data['id'] == @$_SESSION['user']['id']) $delButton = " | <a class=\"ctrls\" href=\"{$_SERVER['PHP_SELF']}?a=d&id={$postData['id']}\">X</a>";
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

?>
