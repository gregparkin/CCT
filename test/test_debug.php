<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<body>
<center><h1>Testing New Debug Class</h1></center>
<?php

if (!function_exists('__autoload'))
{
	require_once('autoloader.php');
}

class A extends debug
{
	public function __construct()
	{
		$this->debug_on('class_a.html',1,2,3,4,5);
	}
	
	function doit($msg)
	{
		$b = new B();
		
		$this->debug1('Hi from class A->doit()');
		$b->doit('go daddy');
	}
}

class B extends debug
{
	public function __construct()
	{
		$this->debug_on('class_b.html',1,2,3,4,5);
	}
	
	function doit($msg)
	{
		$db1 = new dbms();
		
		$db1->sql("select * from cct6_mnet where mnet_cuid = 'gparkin'");
		$this->debug_oracle($db1);
	}
}

$c = new dbms();
$d = new debug();

//$d->debug_on('test_debug.html',1,2,3,4,5);

//$d->debug1('This is test1');

function doit()
{
	global $d, $c;
	
	$c->sql("select * from cct6_mnet where mnet_cuid = 'gparkin'");
	$d->debug_oracle($c);
	$d->debug1('TEST3');
}

//doit();

$a = new A();
$a->doit('rumble');

?>
</body>
</html>
