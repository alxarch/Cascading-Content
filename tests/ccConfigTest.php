<?php

require_once 'ccTestCase.php';

require_once 'lib/ccConfig.php';

class ccConfigTest extends ccTestCase
{
    public function testConstructor()
    {
        $conf = new ccConfig();

        $e = 'ccConfig';

        $this->assertEquals($e, get_class($conf));
    }

    public function testGet()
    {
        $c = new ccConfig();

        $this->assertNull($c->get('some'));

        $e = 'success';

        $r = $c->get('some', $e);

        $this->assertEquals($e, $r);

    }

    public function testConstructorMore()
    {
        $c = new ccConfig(array('test' => 'more'));

        $e = 'more';
        $r = $c->get('test');
        $this->assertEquals($e, $r);

        $r = $c->test;
        $this->assertEquals($e, $r);
    }

    public function testSet()
    {
        $c = new ccConfig();

        $c->set('some', 'more');

        $e = 'more';

        $r = $c->get('some');

        $this->assertEquals($e, $r);

        $e = 'less';
        $c->some = $e;
        $r = $c->get('some');
        $this->assertEquals($e, $r);

    }

    public function testDefaults()
    {
        $e = array('test' => 'some');
        $c = new ccConfig(array(), $e);
        $r = $c->getDefaults();
        $this->assertEquals($e, $r);

        $e = 'some';
        $r = $c->getDefault('test');
        $this->assertEquals($e, $r);

        $e = 'sex';

        $c->setDefault('mony', $e);
        $r = $c->getDefault('mony');
        $this->assertEquals($e, $r);


    }


    public function testReconfigure()
    {
        $data = array('one' => 'dream');
        $rec = array('one' => 'nightmare');
        $c = new ccConfig($data);
        $c->reconfigure($rec);
        $this->assertEquals($rec['one'], $c->get('one'));
    } 


}