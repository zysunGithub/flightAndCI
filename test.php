<?php
class Foo
{
    public static $my_static = array('test' => 'test');

    public function staticValue() {
        return self::$my_static;
    }
}

class Bar extends Foo
{
    public function fooStatic() {
        return parent::$my_static;
    }
}


print Foo::$my_static . "\n";

$foo = new Foo();
Foo::$my_static['test'] = 'test2';
var_dump($foo->staticValue());
//print $foo->my_static . "\n";       // Undefined "Property" my_static
//
//print $foo::$my_static . "\n";
//$classname = 'Foo';
//print $classname::$my_static . "\n"; // As of PHP 5.3.0
//
//print Bar::$my_static . "\n";
//$bar = new Bar();
//print $bar->fooStatic() . "\n";