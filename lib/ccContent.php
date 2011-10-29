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
require_once 'ccRenderer.php';

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
  public function isCascading();
  public function render($context);
  public function getContents();
  static public function filter($content);
}

class ccContent implements ccContentInterface
{
  const TOKEN_OPEN = '{{';
  const TOKEN_CLOSE = '}}';
  const LINE_COMMENT = null;
  const BLOCK_COMMENT_OPEN = '/*';
  const BLOCK_COMMENT_CLOSE = '*/';
  
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
  
  public function setMaster(boolean $master)
  {
    return;
  }
  
  public function render($context, $renderClass = null)
  {
    if(null === $renderClass)
    {
      $renderClass = 'ccRenderer'; 
    }
    
    $renderer = new $renderClass($this);
    
    if(!($renderer instanceof ccRenderer))
    {
      throw new InvalidArgumentException('Invalid renderer class provided.');
    }
    
    $output = $renderer->render($context);
    
    return $output;
  }
  
  public function getContents()
  {
    $contents = file_get_contents($this->getFile());
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

  protected function matchFirstLine($pattern)
  {
    $line = ccFile::firstLine($this->getFile());
    return preg_match($pattern, $line);
  }
  
  static public function filter($content)
  {
    return $content;
  }
}

class ccContentHtml extends ccContent
{
  const BLOCK_COMMENT_OPEN = '<!--';
  const BLOCK_COMMENT_CLOSE = '-->';
  
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
  static public function filter($content)
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
  const TOKEN_OPEN = '%';
  const TOKEN_CLOSE = '%';
  
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
  const TOKEN_OPEN = '%';
  const TOKEN_CLOSE = '%';
  
  static public function filter($content)
  {
    require_once 'vendors/markdown.php';
    $content = Markdown($content);
    $content = parent::filter($content);
    return $content;
  }
}

class ccContentJs extends ccContent
{
  /**
   * Minifies javascripts.
   * @uses JSMin.php from @link https://github.com/mrclay/jsmin_minify
   */
  static public function filter($content)
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
  static public function filter($content)
  {
    require_once 'vendors/lessc.inc.php';
    $lc = new lessc();
    $content = $lc->parse($content);
    
    return parent::filter($content);
  }
}