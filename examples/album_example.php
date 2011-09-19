<?php 

// Define your object models
class artist
{
	var $name;
	var $birthday;
}

class album
{
	var $title;
	var $time;
}

class track
{
	var $trackname;
	var $length;
	var $format;
	var $genre;
}


/* I like Norm, Norm is easy and wears a tie. :-) */
include('../norm.php');

// Create a norm instance - sqlite
$w = new Norm("sqlite:album.sqlite");


// Lets create an artist!
$artist = new artist();
$artist->name			= 'm4tZi11a';
$artist->birthday		= '12/24/1975';

// Save the initial parent artist 
$w->store($artist);

// ok, now lets create an album!
$album = new album();
$album->title			= 'infinity over unity';
$album->url				= 'http://www.infinityoverunity.com';
$album->time			= '30min';

// Lets set a 1:1 for this artist and his album
$w->tie($artist,$album);

// Now for some tracks for this album
$tracks[0] = new track();
$tracks[0]->trackname		='Breath';
$tracks[0]->length			='4:00';
$tracks[0]->genre			='Hardstyle';

$tracks[1] = new track();
$tracks[1]->trackname		='Naked';
$tracks[1]->genre			='Dance / Techno';
// Leaving out some of the vars for fun

// This album has many tracks (autodetects if array of objects)
$w->tie($album,$tracks); //

print "* Done creating the database with default values .. \n";

// Ok, now lets start over:
unset ($tracks);
unset ($artist);
unset ($album);

// Set up a new artist
$artist = new artist();

// This is the criteria I will be looking for:
$artist->id = 1;

// Get the artist with accompanying album and tracks heirarchy
$fullSet = $w->get($artist,'*')->results;
print "<pre>\n* Complete dataset";
print_pre($fullSet);

// Or if I just want the artist only
$artistOnly = $w->get($artist,'*',Norm::SINGLE)->results;
print "<br />\n* Artist Only *";
print_pre($artistOnly);

// And that's norm!


// Oh - one more thing: dynamic column creation
$artist = new artist(); // nothing up my sleeve ..

$artist->id			= $artistOnly['artist'][0]['id'];
$artist->name		= $artistOnly['artist'][0]['name'];
$artist->birthday	= $artistOnly['artist'][0]['birthday'];
$artist->updated	= date('Y-m-d H:i:s');

/// Lest add an "image" column:
$artist->image = 'http://www.infinityoverunity.com/overunitylogo.png';

// Store it and check your DB!
$w->store($artist);

?>
