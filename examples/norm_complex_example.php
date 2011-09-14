<?php 
include('../norm.php');
include('../../norm_db_config.php');

// A more complex NORM example

class titles
{
	var $name;
}

class movieList
{
	var $url;
	var $start		= 0;
	var $duration	= 0;
}

class cuePoints
{
	var $time		= 0;
	var $type		= '';
	var $url		= '';
	var $caption	= '';
	var $text		= '';
	var $disabled	= 0;
}


$N = new Norm("mysql:host=localhost;dbname={$dbname}",$login,$pass);


// Set up inheritence
$t							= new titles();
if (isset($_REQUEST['init']) || $argv[1] == 'init')
{
	$t->name = 'movie test 1';

	$t->movieList				= new movieList();
	$t->movieList->url		= 'mp4:002324.facebook_overview.mp4';
	$t->movieList->start	= 250;
	$t->movieList->duration	= 30;

	$cuePoints[]    = array('time'=>1000, 'type'=>'chapter','disabled'=>true);
	$cuePoints[]    = array('time'=>1000, 'type'=>'chapter','caption'=>'Chapter 1','id'=>0);
	$cuePoints[]    = array('time'=>10000,'type'=>'chapter','caption'=>'Chapter 2','id'=>1);
	$cuePoints[]    = array('time'=>20000,'type'=>'chapter','caption'=>'Chapter 3','id'=>2);
	$cuePoints[]    = array('time'=>30000,'type'=>'chapter','caption'=>'Chapter 4','id'=>3);
	$cuePoints[]    = array('time'=>2000,'type'=>'support','caption'=>'Sidebar Survey','url'=>'svy.html');
	$cuePoints[]    = array('time'=>5000,'type'=>'support','caption'=>'More Comments','text'=>'Here are some more comments regarding blah blah .. ');
	$cuePoints[]    = array('time'=>9000,'type'=>'support','caption'=>'Another survey','url'=>'svy.html');
	$cuePoints[]    = array('time'=>3100,'type'=>'overlay','caption'=>'Modal / Overlay Survey','text'=>'this is just a pop-up box');
	$cuePoints[]    = array('time'=>3300,'type'=>'links','caption'=>'Variable Link Test','text'=>"<a href=\"google.com\">Google.com</a><br /><a href=\"hotmail.com\">hotmail.com</a>");
	$cuePoints[]    = array('time'=>7300,'type'=>'links','disabled'=>'true');
	$cuePoints[]    = array('time'=>7300,'type'=>'presenter','text'=>'Bobby Polkaroo','url'=>'http://localhost/fp/example/Polkaroo.jpg');

	foreach ($cuePoints as $cp)
	{
		// Create a cuepoint object
		$cuePoints = new cuePoints();
		// Stuff the object with the kvp of cuePoints array
		$N->stuff($cp,$cuePoints);
		// add it to the movieList of objects
		$t->movieList->cuePoints[] = $cuePoints;
	}
	$N->store($t);
}
else
{
    // Get all data linked where title id = 1
	$t->id=1;
	print "<pre>";
	print_pre($N->get($t)->results);
}


?>
