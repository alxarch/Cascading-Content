<?php

require_once 'ccTestCase.php';

require_once 'lib/ccCache.php';

class ccCacheTest extends ccTestCase
{
    
    protected static $_root; 

    static public function setUpBeforeClass()
    {
        self::$_root = dirname(__FILE__).DIRECTORY_SEPARATOR.'cache';
        mkdir(self::$_root);
    }

    static public function tearDownAfterClass()
    {
        self::rmrf(self::$_root);
    }




    public function testConstructor()
    {

        $c = new ccCache(self::$_root);

        $e = 'ccCache';

        $this->assertEquals($e, get_class($c));
        
    }

    /**
     * @expectedException InvalidArgumentException
     *
     * @depends testConstructor
     */
    public function testRootException($value='')
    {
        $fake = $this->getFilePath('common') . 'fake';
        $c = new ccCache($fake);
    }

    public function testStore()
    {
        $c = new ccCache(self::$_root);

        $e = 'test content';
        
        $p = 'testfile';
        
        $r = $c->store($p, $e);
        
        $f = self::$_root.DIRECTORY_SEPARATOR.$p;

        $this->assertTrue(file_exists($f));
        $this->assertEquals($f, $r);

        $contents = file_get_contents($f);

        $this->assertEquals($e, $contents);

        $p = 'nested/one/two/three/testfile';
        $f = self::$_root.DIRECTORY_SEPARATOR. str_replace('/', DIRECTORY_SEPARATOR, $p);
        
        $r = $c->store($p, $e);

        $this->assertTrue(file_exists($f));
        $this->assertEquals($f, $r);

        $contents = file_get_contents($f);

        $this->assertEquals($e, $contents);

        return $p;
    }

    public function testRetrieve()
    {
        $p = 'testretrieve';
        $e = 'test content';
        $c = new ccCache(self::$_root);
        $c->store($p, $e);
        $r = $c->retrieve($p);
        $this->assertEquals($e, $r);

    }
}
