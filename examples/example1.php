<?php
//------------------------------------
// Example 1
//------------------------------------
// Storing an object in a database 

// Include Norm
include('../norm.php');

// Create a norm instance - sqlite
$norm = new Norm("sqlite:costume.sqlite");

// My container object [NOTE: no vars specified - that's OK!]
class Costume { } 

// Instantiate my container object
$costume = new Costume();

// Set up some vars for this costume
$costume->title = 'Scary Mask';
$costume->sku   = '324-2444-234';
$costume->price = '19.95'; 

// Store them in the database
$norm->store($costume);

// Once costume is "created" it's instance will be "stuffed" with a new id
// check it:
echo "Costume Id: {$costume->id}<br />";

// Also check your database - you should see a new "costume" table!
// If you run this several times, you should see the costume id increment.

//------------------------------------
// NEXT UP: example2.php 
// - Lets 'GET' those costumes out of the database!
//------------------------------------
?>
