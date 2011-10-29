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
require_once 'ccContent.php';

// Finder ______________________________________________________________________

class ccFinderFactory
{
  static public function createFinder($type, $root, $filetypes, $index=null, $dir=null)
  {
    $class = 'cc'.ucfirst($type).'Finder';
    if(class_exists($class))
    {
      $finder = new $class($root);
      
      foreach($filetypes as $type => $extensions)
      {
        $finder->addFiletype($type, $extensions);
      }

      $finder->setIndexName($index);
      $finder->setDirName($dir);
    }

    return $finder;
  }
}

class ccFinder
{
  protected $_filetypes = array();
  protected $_index, $_dir, $_root;

// constructor _________________________________________________________________
  
  public function __construct($root)
  {
    $this->setRoot($root);
  }
  
// public functions ____________________________________________________________
  
  public function find($path)
  {
    return $this->doFind($path);
  }
  
  public function findPath($path, $base)
  {
    $result = $this->find($path);
    
    return null === $result ? null : $result->getPath($base);
  }

  public function setDirName($name)
  {
    $this->_dir = $name;
  }
  
  public function getDirName()
  {
    return isset($this->_dir) ? $this->_dir : false;
  }

  public function setIndexName($name)
  {
    $this->_index = (string) $name;
  }
  
  public function getIndexName()
  {
    return isset($this->_index) ? $this->_index : false;
  }

  public function setRoot($dir)
  {
    $this->_root = $dir;
    if(!is_dir($dir))
    {
      throw new InvalidArgumentException("Invalid root directory.");
    }
  }
  
  public function getRoot()
  {
    return $this->_root;
  }
  
  public function getFiletypes()
  {
    return $this->_filetypes;
  }
  
  public function addFiletype($type, $extensions)
  {
    $extensions = ccArray::make($extensions);
    $this->_filetypes[$type] = $extensions;

  }
  
// protected functions _________________________________________________________

  protected function doFind($path)
  {

    $filename = ccPath::os($this->getRoot(), $path);
    
    $locations = $this->getPossibleLocations($filename);

    foreach ($this->getFiletypes() as $type => $extensions)
    {
      foreach($locations as $loc)
      {
        foreach($extensions as $ext)
        {
          if(file_exists($loc . '.' . $ext))
          {
            //echo "<pre>$loc.$ext</pre>";
            return ccContentFactory::createContent($loc.'.'.$ext, $type);
          }
        }
      }
    }
    
    return null;
    
  }
  
  protected function getPossibleLocations($filename)
  {
    $filename = ccPath::clean($filename);

    $dir = $this->getDirName();
    $idx = $this->getIndexName();
    
    $locations = array();
    
    if($dir && $idx && is_dir($filename))
    {
      $locations[] = ccPath::os($filename, $dir, $idx);
    }
    
    if($idx && is_dir($filename))
    {
      $locations[] = ccPath::os($filename , $idx);
    }
    
    if($idx && !is_dir($filename))
    {
      $locations[] = ccPath::os(dirname($filename), $idx);
    }
    
    if($dir && $idx && !is_dir($filename))
    {
      $locations[] = ccPath::os(dirname($filename), $dir, $idx);
    }
    
    $locations[] = $filename;
    
    return $locations;
  }

}

class ccCascadingFinder extends ccFinder
{
  protected $_multiple = true;

  public function find($path)
  {
    $r = $this->cascade($path);
    return $r;
  }

  public function findPath($path, $base)
  {
    if(!$this->_multiple)
    {
      return parent::findPath($path, $base);
    }
    
    $paths = array();
    
    $results = $this->find($path);
    
    foreach($results as $result)
    {
      $paths[] = $result->getPath($base);
    }
    
    return $paths;
  }
  
// protected ___________________________________________________________________
  
  protected function cascade($path)
  {
    $paths = ccPath::cascade($path);
    
    $results = array();
    
    foreach($paths as $p)
    {
      $result = $this->doFind($p);
      
      if($result)
      {
        $results[] = $result;
        
        if(!$this->_multiple)
        {
          return $result;
        }
        
        if($result->isMaster())
        {
          return array_reverse($results);
        }

        if(!$result->isCascading())
        {
          break;
        }
      }
    }
    
    $master = $this->doFind('/');
    $results[] = $master;
    $results = array_filter($results);
    $results = array_reverse($results);
    return $this->_multiple ? $results : $master;
  }
}

class ccContentFinder extends ccFinder
{
  protected $_filetypes = array('html' => 'html');
}

class ccMetaFinder extends ccCascadingFinder
{
  protected $_filetypes = array('yaml' => 'yml, yaml');

  public function find($path)
  {
    $r = parent::find($path);
    return $r;
    
  }
}

class ccStyleFinder extends ccCascadingFinder
{
  protected $_filetypes = array('css' => 'css');
}

class ccScriptFinder extends ccCascadingFinder
{
  protected $_filetypes = array('js' => 'js');
}

class ccPartialFinder extends ccCascadingFinder
{
  protected $_multiple = false;
  protected $_filetypes = array('html' => 'html');
  
  /**
   * Partials are prefixed with '_' and have no index or dir.
   */
  protected function getPossibleLocations($filename)
  {
    $loc = ccPath::to($filename);
    $loc = ccPath::os($loc, '_'.ccFile::name($filename));
    return array($loc);
  }
}

class ccLayoutFinder extends ccCascadingFinder
{
  protected $_multiple = false;
  protected $_filetypes = array('php' => 'php', 'html' => 'html');
}