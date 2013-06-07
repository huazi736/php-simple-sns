<?php
class ajax extends DK_Controller
{
	public function index()
	{
	   echo "[{'id':1,'txt':'这里是文字11111'},
		   {'id':2,'txt':'这里是文字22222'},
		   {'id':3,'txt':'这里是文字33333'},
		   {'id':4,'txt':'这里是文字44444444'}]";
	}


	public function pic()
	{
 echo "[{'id':1,'txt':'dddd'},
		   {'id':2,'txt':'这里是文字22222'},
		   {'id':3,'txt':'这里是文字33333'},
		   {'id':4,'txt':'这里是文字44444444'}]";
	}
 
	public function test()
	{
		echo('it is test');
	}
}
?>