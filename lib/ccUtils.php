<?php

define('DS', DIRECTORY_SEPARATOR);

class ccFile
{
  
  static public function firstLine($file)
  {
    $fh = @fopen($file, 'r');
    $line = false;
    
    if($fh)
    {
      for($line=""; empty($line); $line = trim(fgets($fh)));
    }
    
    fclose($fh);
    return $line;
  }
   
  static public function extension($f)
  {
    $dot = strrpos($f, '.');
    if(false === $dot)
    {
      return '';
    }
    
    return substr($f, $dot+1, strlen($f));
  }
  
  static public function name($s, $ext=true)
  {
    $ds = strrpos($s, DS);
    if(false !== $ds)
    {
      $s = substr($s, $ds+1, strlen($s));
    }
    
    if(!$ext)
    {
      $dot = strrpos($s, '.');
      if(false !== $dot)
      {
        $s = substr($s, 0,  $dot);
      }
    }
    
    return $s;
  }
}


class ccPath
{
  static function to($path)
  {
    $path = self::web($path);
    $pos = strrpos($path, '/');
    return $pos ? substr($path, 0, $pos) : false;
  }

  static function relative($path, $base)
  {
    if(strpos($path, $base) == 0)
    {
      $path = substr($path, strlen($base), strlen($path));
      $path = self::web($path);
      return $path;
    }
    
    return false;
  }

  static function trim($path)
  {
    return trim($path, ' /\\');
  }

  static function clean($path)
  {
    return rtrim(trim($path),'/\\');
  }

  static function os($parts)
  {

    $parts = array();
    
    foreach(func_get_args() as $a)
    {
      $a = self::clean($a);
      $a = str_replace('/', DS, $a);
      $a = str_replace('\\', DS, $a);
      $parts[] = $a;
    }
    
    return implode(DS, $parts);
    
  }
  
  static function web($parts)
  {
    $parts = array();
    
    foreach(func_get_args() as $a)
    {
      $a = self::clean($a);
      $a = str_replace('\\', '/', $a);
      $parts[] = $a;
    }
    
    return implode(DS, $parts);
    
  }

  static public function cascade($path)
  {
    $path = self::web($path);
    $paths = array();

    for(;($i=strrpos($path, '/')) !== false; $path = substr($path, 0, $i))
    {
      $paths[] = $path;
    }
    
    return $paths;
  }
}