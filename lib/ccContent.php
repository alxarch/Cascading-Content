<?php

require_once 'ccUtils.php';

class ccContentFactory
{
  static public function createContent($file, $type=null)
  {
    $class = self::getClassName($type);

    if(self::validate($class))
    {
      $content = new $class($file);
      return $content;
    }
  }

  static public function validate($class)
  {
    $class = self::getClassName($class);
    if(class_exists($class))
    {
      $interfaces = class_implements($class);
      
      if(array_key_exists('ccContentInterface', $interfaces))
      {
        return true;
      }
    }

    throw new InvalidArgumentException("Invalid content class.");
  }
  
  static protected function getClassName($class)
  {
    return $class && class_exists($class) ? $class : 'ccContent'.ucfirst($class);
  }
}

interface ccContentInterface
{
  public function __construct($file);
  public function getPath($base);
  public function getFile();
  public function setFile($file);
  public function isMaster();
  public function setMaster(boolean $master);
  public function isCascading();
  public function setCascading(boolean $cascading);
  public function render($context);
  public function getRawContents();
  public function getContents();
}

class ccContent implements ccContentInterface
{
  protected $_opentag = '{{';
  protected $_closetag = '}}';
  
  protected $_type;
  protected $_file;
  protected $_master = false;
  
  public function getPath($base)
  {
    return ccPath::relative($this->getFile(), (string)$base);
  }
  
  public function __construct($file)
  {
    $this->setFile($file);
  }

  public function isMaster()
  {
    return false;
  }
  
  public function isCascading()
  {
    return false;
  }
  
  public function setCascading(boolean $cascading)
  {
    return;
  }
  
  public function setMaster(boolean $master)
  {
    return;
  }
  
  public function render($context)
  {
    $content = $this->getContents();
    
    $content = $this->doRender($content, $context);
    
    if(method_exists($this, 'postFilter'))
    {
      $content = $this->postFilter($content);
    }
    
    return $content;
  }
  
  public function getRawContents()
  {
    $contents = file_get_contents($this->getFile());
  }
  
  public function getContents()
  {
    $contents = $this->getRawContents();
    if(method_exists($this, 'preFilter'))
    {
      $contents = $this->preFilter($contents);
    }
    
    return $contents;
  }
  
  public function setFile($file)
  {
    $file = (string) $file;
    if(!is_file($file))
    {
      throw new InvalidArgumentException('Invalid file provided.');
    }
    
    $this->_file = $file;
  }
  
  public function getFile()
  {
    return $this->_file;
  }
    
  protected function doRender($content, $context)
  {
    
    $finalContext = array();
    
    foreach($context as $key => $value)
    {
      $finalContext[$open.$key.$close] = $value;
    }
    
    return strtr($content, $context);
  }

  public function __get($key)
  {
    switch($key)
    {
      case 'file':
        return $this->getFile();
        break;
      case 'master':
        return $this->isMaster();
        break;
      case 'path':
        return $this->getPath();
        break;
      case 'type':
        return $this->getType();
        break;
      default:
        throw new Exception('Undefined property %s.', $key);
        break;
    }
  }

  public function __set($k, $v)
  {
    switch($k)
    {
      case 'file':
        return $this->setFile($v);
        break;
      case 'master':
        return $this->isMaster($v);
        break;
      case 'path':
        return $this->setPath($v);
        break;
      case 'type':
        throw new Exception('Content type of an instance cannot be changed.');
        break;
      default:
        throw new Exception('Undefined property %s.', $key);
        break;
    }
  }
  
  protected function matchFirstLine($pattern)
  {
    $line = ccFile::firstLine($this->getFile());
    return preg_match($pattern, $line);
  }
}

class ccContentHtml extends ccContent
{
  protected function preFilter($content)
  {
    // strip html comments around escaping chars.
    $p = '/<!\-\-\s*(%s[\w\-_]+%s)\s*\-\->/';
    
    $p = sprintf($p, preg_quote($this->_opentag), preg_quote($this->_closetag));
    
    $content = preg_replace($p, '$1', $content);
    
    return $content;
  }
  
  public function isMaster()
  {
    return $this->matchFirstLine('/ <!doctype /xi') ||
           $this->matchFirstLine('/ <!\-\- \s* master: \s* true \s* \-\-> /x');
  }
  
  public function isCascading()
  {
    return !$this->matchFirstLine('/<!\-\-\s* cascades: \s* false \s* \-\->/x');
  }
}

class ccContentYaml extends ccContent
{
  protected function postFilter($content)
  {
    require_once 'vendors/spyc.php';
    $content = spyc_load($content);
    return $content;
  }
  
  public function isMaster()
  {
    return $this->matchFirstLine('/ @master: \s* true /x');
  }
  
  public function isCascading()
  {
    return !$this->matchFirstLine('/ @cascade: \s* false /x');
  }
}

class ccContentPhp extends ccContent
{
  public function render($context)
  {
    ob_start();
    
    extract($context);
    
    include($this->getFile());
    
    $content = ob_get_clean();
    
    return $content;
  }
}

class ccContentCss extends ccContent
{
  protected function configure()
  {
    $this->_closetag = '%%';
    $this->_opentag = '%%';
  }
  
  public function isMaster()
  {
    return $this->matchFirstLine('~ /\* \s* @master: \s* true ~x');
  }
  
  public function isCascading()
  {
    return !$this->matchFirstLine('~ /\* \s* @cascade: \s* false ~x');
  }
}

class ccContentMarkdown extends ccContentHtml
{
  protected function configure()
  {
    $this->_closetag = '%';
    $this->_opentag = '%';
  }
  
  public function preFilter($content)
  {
    require_once 'vendors/markdown.php';
    $content = Markdown($content);
    $content = parent::preFilter($content);
    return $content;
  }
}

class ccContentJs extends ccContent
{
  /**
   * Minifies javascripts.
   * @uses JSMin.php from @link https://github.com/mrclay/jsmin_minify
   */
  protected function postFilter($content)
  {
    require_once 'vendors/JSMin.php';
    return JSMin::minify($content);
  }
  
  public function isMaster()
  {
    return $this->matchFirstLine('~ /[\*/] \s* @master: \s* true ~x');
  }
  
  public function isCascading()
  {
    return !$this->matchFirstLine('~ /[\*/] \s* @cascade: \s* false ~x');
  }
}

class ccContentLess extends ccContentCss
{
  /**
   * Compiles less files into css.
   *
   * @uses lessc.inc.php from @link https://github.com/leafo/lessphp
   *
   * @param string $less
   * @return string $css
   * 
   */
  protected function preFilter($content)
  {
    require_once 'vendors/lessc.inc.php';
    $lc = new lessc();
    $content = $lc->parse($content);
    
    return parent::preFilter($content);
  }
}