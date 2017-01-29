<?php

include (dirname(__DIR__)."/src/IObject.php");

class DataTest extends PHPUnit_Framework_TestCase
{
    // ...

	public function testCanBeSetted()
	{
		$object =new IObject();
		$this->assertNotNull($object);

		$object->setDatas(array('a'=>'1'));

		$this->assertFalse($object->hasDatas());

		$data = $object->get('a');

		$this->assertEquals($data,'1');

		$data = $object->getDatas();
		$this->assertArrayHasKey('a',$data);


		$this->assertEquals($data['a'],1);

		$object->setDatas(array('b'=>2));

		$this->assertFalse($object->hasDatas());

 		//checking that we have not erased the a
		$data = $object->get('a');

		$this->assertEquals($data,'1');

		$data = $object->getDatas();
		$this->assertArrayHasKey('a',$data);


		$this->assertEquals('1',$object->getA());
		$this->assertEquals('2',$object->getB());


		$object->addA(2);

		$data = $object->getA();



		$this->assertEquals(2,count($data));


		$this->assertEquals('1',$data[0]);


		$this->assertEquals('2',$data[1]);

		$object->addField('test');

		$object->addField('test2');
		$array = $object->getField();
		$this->assertNotNull($array[0]);
		//var_dump($array);
		$this->assertEquals(2, count($array));

	}


	public function testCanBeDeleted(){
		$object =new IObject();
		$this->assertNotNull($object);
		$object->setDatas(array('a'=>'1'));

		$this->assertFalse($object->hasDatas());
		$object->setDatas(array('b'=>2));

		$this->assertFalse($object->hasDatas());
		$object->removeDatas(array('a'));
		$data = $object->getDatas();
		$this->assertArrayNotHasKey('a',$data);
		$this->assertArrayHasKey('b',$data);
	}

	public function testMagic(){
		$object =new IObject();
		$this->assertNotNull($object);


		$object->setPrimaryKey(true);
		$this->assertTrue($object->isPrimaryKey());
		$this->assertTrue($object->hasPrimaryKey());

		$object->addField('test');
		$this->assertTrue($object->hasField());

		$object->removeDatas(array('field'));
		$this->assertFalse($object->hasField());

	//	vaR_dump($o);
	}

	public function testWith(){
		$object=  new IObject();

		$this->assertNotNull($object);

		$object->setObject(new \IObject());
		$this->assertTrue($object->hasObject());

		$object->withObject(function($o){
			$o->setSuperVar('testingunit');
		});

		$this->assertEquals($object->getObject()->getSuperVar(),'testingunit');


		//multiple objects
		$object->addObject(new \IObject());
		$object->addObject(new \IObject());
		$this->assertTrue(count($object->getObject())==3);
		$object->withObject(function($o){
			$o->setSuperVar('testingunit');
		});
		foreach($object->getObject() as $o){
			$this->assertEquals($o->getSuperVar(),'testingunit');
		}

	}

}
