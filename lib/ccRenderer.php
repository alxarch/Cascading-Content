<?php

class ccRenderer
{
  protected $_content, $_commands = array(), $_aliases = array();
  
  /**
   * ccRenderer::__construct
   */
  public function __construct(ccContent $content, $commands = array())
  {
    $this->setContent($content);
    
    foreach ($commands as $name => $command)
    {
      $this->addCommand($name, $command);
    }
    
    $this->configure();
  }
  
  /**
   * Wrapper for doRender.
   *
   * Override this if you want to alter all rendering behaviour.
   *
   * @param array $context
   */
  public function render($context)
  {
    return $this->doRender($context);
  }
  
  /**
   * Content getter.
   */
  public function getContent()
  {
    return $this->_content;
  }
  
  public function setContent(ccContent $content)
  {
    $this->_content = $content;
  }
  
  protected function doRender($context)
  {
    $context = $this->tokenizeContext($context);
    
    $output = $this->getContent()->getContents();
    
    $output = $this->uncommentTokens($output);
    
    $output = strtr($output, $context);
    
    $output = $this->filter($output);
    
    return $output;
  }
  
  public function filter($output)
  {
    $class = $this->getContentClass();
    
    $output = $class::filter($output);
    
    return $output;
  }
  
  public function addCommand($name, $command, $aliases=array())
  {
    if(!$command instanceof ccRendererCommandInterface)
    {
      throw new InvalidArgumentException('Command does not implement ccRendererCommandInterface');
    }
    
    $this->_commands[$name] = $command;
    
    foreach($aliases as $alias)
    {
      $this->addAlias($name, $alias);
    }
  }
  
  public function addAlias($name, $alias)
  {
    $this->_aliases[$alias] = $name;
  }
  
  protected function configure()
  {
    
  }
  
  public function uncommentTokens($output)
  {
    $class = $this->getContentClass();
    $search = '~ %s \s* ( %s [\w\-/_:\s]+ %s ) \s* %s ~x';
    $search = sprintf($search,
                      preg_quote($class::BLOCK_COMMENT_OPEN),
                      preg_quote($class::TOKEN_OPEN),
                      preg_quote($class::TOKEN_CLOSE),
                      preg_quote($class::BLOCK_COMMENT_CLOSE));
    
    $output = preg_replace($search, '$1', $output);
    
    if($class::LINE_COMMENT)
    {
      $search = '/ %s \s* ( %s [\w\-\/\s_:]+ %s ) /x';
      $search = sprintf($search,
                        preg_quote($class::LINE_COMMENT),
                        preg_quote($class::TOKEN_OPEN),
                        preg_quote($class::TOKEN_CLOSE));
      
      $output = preg_replace($search, '$1', $output);
    }
    
    return $output;
  }
  
  public function tokenizeContext($context)
  {
    $class = $this->getContentClass();
    $result = array();
    
    foreach($context as $key => $value)
    {
      $key = $class::TOKEN_OPEN . $key . $class::TOKEN_CLOSE;
      $result[$key] = $value;
    }
    
    return $result;
    
  }
  
  /**
   * Docs
   */
  public function getContentClass()
  {
    return get_class($this->_content);
  }
  
}

interface ccRendererCommandInterface
{
  public function run($args, $context);
}

class ccRendererCommand implements ccRendererCommandInterface
{
  
}

class ccLinkCommand extends ccRendererCommand
{
  protected $_finder = null;
  
  public function __construct()
  {
    $this->configure();
    
    if(null === $this->getFinder())
    {
      $class = get_class($this);
      if(preg_match('/cc([A-Z][a-z]+)Command/', $class, $mathes))
      {
        $type = strtolower($mathes[1]);
      }
      else
      {
        $type = 'content';
      }
      
      $finder = ccCascadingContent::getInstance()->getFinder($type);
      
      $this->setFinder($finder);
    }
  }
  
  public function setFinder(ccFinder $finder)
  {
    $this->_finder = $finder;
  }
  
  public function getFinder()
  {
    return $this->_finder;
  }
}

class ccPartialCommand extends ccRendererCommand
{
  public function run($args, $context)
  {
    return '';
  }
}

class ccScriptCommand extends ccRendererCommand
{
  /**
   * Cascades through directories to find script content.
   *
   * @param string $scriptname
   * @param context $context
   */
  public function run($scriptname, $context)
  {
    return '';
  }
}


class ccStyleCommand extends ccRendererCommand
{
  
}

class ccAttachmentCommand extends ccRendererCommand
{
  
}

class ccImageCommand extends ccRendererCommand
{
  
}