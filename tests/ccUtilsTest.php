<?php

require_once 'ccTestCase.php';

require_once 'lib/ccUtils.php';

class ccPathTest extends ccTestCase
{
  public function testWeb()
  {
    $expected = '/one/two/three/four/five';
    
    $path = '\one\two\three\four\five';
    $result = ccPath::web($path);
    $this->assertEquals($expected, $result);

    
    $path = '\one\two\three\four\five\\';
    $result = ccPath::web($path);
    $this->assertEquals($expected, $result);
    
    $path1 = '\one\two';
    $path2 = 'three\four\five';
    $result = ccPath::web($path1, $path2);
    $this->assertEquals($expected, $result);
  }
  
  public function testOs()
  {
    $expected = DS.'one'.DS.'two'.DS.'three';
    
    $path = '/one/two/three/ ';
    $result = ccPath::os($path);
    $this->assertEquals($expected, $result);
    
    $path = '/one/two/three';
    $result = ccPath::os($path);
    $this->assertEquals($expected, $result);
    
    $path1 = '\one\two';
    $path2 = 'three';
    $result = ccPath::os($path1, $path2);
    $this->assertEquals($expected, $result);
  }
  
  
  public function testTrim()
  {
    $expected = 'one/two/three';
    
    $path = '/one/two/three/ ';
    
    $result = ccPath::trim($path);
    
    $this->assertEquals($expected, $result);
  }
  
  public function testClean()
  {
    $expected = '/one/two/three';
    
    $path = '/one/two/three/ ';
    
    $result = ccPath::clean($path);
    
    $this->assertEquals($expected, $result);
  }
  
  public function testRelative()
  {
    $expected = '/jesus/christ/almighty';
    
    $base = '/path/to';
    $path = '/path/to/jesus/christ/almighty';
    
    $result = ccPath::relative($path, $base);
    
    $this->assertEquals($expected, $result);
    
  }

  public function testCascade()
  {
    // simple case
    $path = '/one/two/three';
    
    $expected = array(
      '/one/two/three',
      '/one/two',
      '/one'
    );
    
    $result = ccPath::cascade($path);
    
    $this->assertEquals($result, $expected);

  }
}

class ccFileTest extends ccTestCase
{
  public function testFirstLine()
  {
    $path = $this->getFilePath('testFirstLine');
    $expected = "first line";
    
    $f = $path.'simple.txt';
    $result = ccFile::firstLine($f);
    $this->assertEquals($expected, $result);
    
    $f = $path.'empty_lines.txt';
    $result = ccFile::firstLine($f);
    $this->assertEquals($expected, $result, "Empty lines were not ignored.");
  }

  public function testExtension()
  {
    $file = '/som/path/to/filename.ext';
    $expected = 'ext';
    $result = ccFile::extension($file);
    $this->assertEquals($result, $expected);
  }

  public function testName()
  {
        // simple case
    $file = '/some/path/filename.ext';
    
    $expected = 'filename.ext';
    $result = ccFile::name($file);
    $this->assertEquals($result, $expected);
    
    // without extension
    $expected = 'filename';
    $result = ccFile::name($file, false);
    $this->assertEquals($result, $expected);
    
    // without path
    $expected = 'filename.ext';
    $result = ccFile::name($expected);
    $this->assertEquals($result, $expected);
  }

  public function testTo()
  {
    $expected = '/some/path';

    $path = '/some/path/file.test';

    $result = ccPath::to($path);

    $this->assertEquals($expected, $result);


    $path = '\\some\\path\\file.test';

    $result = ccPath::to($path);

    $this->assertEquals($expected, $result);
  }
}