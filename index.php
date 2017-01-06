<?php
require 'flight/Flight.php';
require 'controllers/ObjContainer.php';

Flight::route('/', function(){
    echo 'hello world!';
});

Flight::route('/phpinfo', function(){
    phpinfo();
});

Flight::start();
?>
