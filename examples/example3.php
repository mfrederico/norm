<?php
//------------------------------------
// Example 2
//------------------------------------
// This assumes you've already run example 1,2 and have created
// at least one costume object in the database.
// 
// Lets change the prices and names of 2 objects .. 

// Include Norm stuff
include('../norm.php');
include('../../norm_db_config.php');
$norm = new Norm("mysql:host=localhost;dbname={$dbname}",$login,$pass);
// You know the rest .. 
class Costume { } 
$costume = new Costume();

// ** Lets get to it! **

// Lets change the price of costume id 1
$costume->id	= 1;
$costume->price = '100.00';
$norm->store($costume);
// There - now it's updated in the database reflecting a price change!

// NOTE: please run example1.php at least 2 or 3 times .. 
// Lets change the price and NAME of costume id 2
$costume->id	= 2;
$costume->price = '29.00';
$costume->sku	= '4433-22-343';
$costume->title	= 'Bunny outfit';
$norm->store($costume);
// Now the 2nd one is updated in the database 

// Lets check our changes, shall we?
unset($costume->id);

print "<h3>My costumes:</h3>";
print "<pre>".print_r($norm->get($costume),true)."</pre>";

// Woah - wait a second .. this only returned ONE costume .. but WHY?
// Because there is data in the costume instance,  norm is using that to find your record!

// So  .. lets start again ..
$costume = new Costume();
print "<h3>My costumes:</h3>";
print "<pre>".print_r($norm->get($costume),true)."</pre>";

// That's better!

//------------------------------------
// NEXT UP: example4.php 
// - Costume 'ties' !!
//------------------------------------
?>
