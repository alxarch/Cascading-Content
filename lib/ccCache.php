<?php
/**
 *  @package CascadingContent
 *  
 *  @author Alexandros Sigalas <alxarch@gmail.com>
 *  @copyright Copyright (c) 2011, Alexandros Sigalas
 *  
 *  @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, v2.0
 * 
 */

require_once 'ccUtils.php';

class ccCache
{
  protected $_root;
  
  public function __construct($root)
  {
    $this->setRoot($root);
  }
  
  public function getRoot()
  {
    return $this->_root;
  }
  public function setRoot($root)
  {
    if(!is_dir($root))
    {
      throw new InvalidArgumentException("Invalid root path given.");
    }
    $this->_root = $root;
  }
  
  public function store($path, $content)
  {
    $file = ccPath::os($this->getRoot(), $path);
    
    if(!file_exists(dirname($file)))
    {
      mkdir(dirname($file), 0777, true);
    }
    
    file_put_contents($file, $content);
    
    return $file;
  }
  
  public function retrieve($path)
  {
    $file = ccPath::os($this->getRoot(), $path);
    if(!file_exists($file))
    {
      return null;
    }
    
    return file_get_contents($file);
  }
  
  public function concatenate($path, $contents, $glue="\n")
  {
    $contents = implode($glue, $contents);
    return $this->store($path, $contents);
  }

}

