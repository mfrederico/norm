#!/bin/bash
echo "Running norm tests"
rm costume.sqlite
php example1.php
php example1.php
php example2.php
php example3.php
php example4.php
php example5.php
echo "Done!";
