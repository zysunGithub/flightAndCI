<?php
require 'flight/Flight.php';

Flight::route('/', function(){
    echo 'hello world!';
});

Flight::route('/phpinfo', function(){
    phpinfo();
});

Flight::start();
?>
