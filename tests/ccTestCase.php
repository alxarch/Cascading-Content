<?php


abstract class ccTestCase extends PHPUnit_Framework_TestCase
{
  static protected function getProtectedMethod($instance, $name)
  {
      $ref = new ReflectionClass($instance);
      $method = $ref->getMethod($name);
      $method->setAccessible(true);
      return $method;
  }
  
  static protected function getProtectedProperty($instance, $name)
  {
      $ref = new ReflectionClass($instance);
      $prop = $ref->getProperty($name);
      $prop->setAccessible(true);
      return $prop->getValue($instance);
  }
  
  public function getFilePath($test)
  {
    $test = get_class($this) . '_' . $test . DIRECTORY_SEPARATOR;
    return implode(DIRECTORY_SEPARATOR, array(dirname(__FILE__),'files', $test));
  }
  
  static protected function rmrf($dir)
  {
      foreach(scandir($dir) as $f)
      {
          if(preg_match('/^\.\.?$/', $f)) continue;
          $f = $dir . DIRECTORY_SEPARATOR . $f;
          if(is_dir($f))
          {
              self::rmrf($f);
          }
          else
          {
              unlink($f);
          }

      }
      rmdir($dir);

  }

}