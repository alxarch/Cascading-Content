<?php

class ccRenderer
{
  protected $_content, $_commands = array(), $_aliases = array(), $_context = null;
  
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
  
  protected function setContext($context)
  {
    $this->_context = $context;
  }
  
  protected function getContext()
  {
    return $this->_context;
  }
  
  protected function doRender($context)
  {
    
    $output = $this->getContent()->getContents();
    
    $output = $this->uncommentTokens($output);
    
    $output = $this->executeCommands($output, $context);    
    
    $context = $this->tokenizeContext($context);
    
    $output = strtr($output, $context);
    
    return $output;
  }
  
  protected function executeCommands($output, $context)
  {
    $class = $this->getContentClass();
    
    $pat = '/ %s \s* ([\w\$\+~!#@&][\w\-_]*) \s* : \s* ( [\w\-\/\s]+ ) \s* %s /x';
    
    $pat = sprintf($pat, preg_quote($class::TOKEN_OPEN),
                         preg_quote($class::TOKEN_CLOSE));
    
    $this->setContext($context);
    
    $output = preg_replace_callback($pat, array($this, 'execute'), $output);
    
    return $output;
  }
  
  public function getCommand($name)
  {
    if(isset($this->_aliases[$name]))
    {
      $name = $this->_aliases[$name];
    }
    
    if(isset($this->_commands[$name]))
    {
      return $this->_commands[$name];
    }
    
    return null;
  }
  
  protected function execute($matches)
  {
    $com = $this->getCommand($matches[1]);
    
    if(null !== $com)
    {
      $args = $matches[2];
      
      $result = $com->run($args, $this->getContext());
      
      return $result;
    }
    
    return '';
  }
  
  public function addCommand($name, $command, $aliases=array())
  {
    if(!$command instanceof ccRendererCommandInterface)
    {
      $msg = 'Command does not implement ccRendererCommandInterface';
      throw new InvalidArgumentException($msg);
    }
    
    $this->_commands[$name] = $command;
    $aliases = ccArray::make($aliases);
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
    $this->addCommand('wiki', new ccWikiCommand(), 'w');
    $this->addCommand('partial', new ccPartialCommand(), '+');
    $this->addCommand('image', new ccImageCommand(), '!');
    $this->addCommand('style', new ccStyleCommand(), '~,css');
    $this->addCommand('script', new ccScriptCommand(), '$,js');
    $this->addCommand('attachment', new ccAttachmentCommand(), '@,att');
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
  public function run($args, $context)
  {
    return $args;
  }
}

class ccFinderCommand extends ccRendererCommand
{
  protected $_finder = null;
  
  public function __construct()
  {
    $this->configure();
    
    if(null === $this->getFinder())
    {
      $finder = ccCascadingContent::getInstance()->getFinder('content');
      
      $this->setFinder($finder);
    }
  }
  
  public function run($args, $context)
  {
    $content = $this->getFinder()->find(ccPath::web($context['path'], $args));
    
    return $content->getPath($this->getFinder()->getRoot());
    
  }
  
  protected function find($name, $context)
  {
    if((is_array($context) || method_exists($context, '__get')) && isset($object['path']))
    {
      $path = ccPath::web($context['path'], $name);
    }
    else
    {
      $path = ccPath::web($name);
    }
    
    return $this->getFinder()->find($path);
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

class ccPartialCommand extends ccFinderCommand
{
  protected function configure()
  {
    $this->setFinder(ccCascadingContent::getInstance()->getFinder('partial'));  
  }
  
  public function run($args, $context)
  {
    $partial = $this->find($args, $context);
    
    if($partial)
    {
      // Render the partial passing it a new context that has path reset.
      $path = $partial->getPath($this->getFinder()->getRoot());
      
      $context['path'] = $path;
      
      $renderer = new ccRenderer($partial);
      
      $output = $renderer->render($context);
      
      return $output;
    
    }
    
    return '';
  }
}

class ccScriptCommand extends ccFinderCommand
{
  protected function configure()
  {
    $f = ccCascadingContent::getInstance()->getFinder('script');
    $f->setIndexName(null);
    $this->setFinder($f);  
  }
}


class ccStyleCommand extends ccFinderCommand
{
  protected function configure()
  {
    $f = ccCascadingContent::getInstance()->getFinder('style');
    $f->setIndexName(null);
    $f->setMultiple(false);
    $this->setFinder($f);
  }
}

class ccAttachmentCommand extends ccFinderCommand
{
  protected function configure()
  {
    $f = ccCascadingContent::getInstance()->getFinder('attachment');
    $this->setFinder($f);
  }
}

class ccImageCommand extends ccFinderCommand
{
  protected function configure()
  {
    $f = ccCascadingContent::getInstance()->getFinder('image');
    $this->setFinder($f);
  }
}

class ccWikiCommand extends ccFinderCommand
{
  protected function configure()
  {
    $f = ccCascadingContent::getInstance()->getFinder('content');
    $f->setIndexName(null);
    $this->setFinder($f);  
  }
  
  public function run($name, $context)
  {
    $wiki = $this->find($name, $context);
    
    $base = $this->getFinder()->getRoot();
    
    if(null === $wiki)
    {
      $path = isset($context['path']) ? $context['path'] : '';
      
      $file = ccPath::os($base, $path);
      
      if(is_dir($file))
      {
        $file = ccPath::os($file, $name);
        $result = ccPath::web($path, $name);
      }
      else
      {
        $file = ccPath::os(ccPath::to($file), $name);
        
        $result = ccPath::web(ccPath::to($path), $name);
      }
      
      $file .= '.md';
      
      $title = ccPath::title($name);
      
      $contents = "##$title\n\nNo contents.\n";
       
      file_put_contents($file, $contents);
      
      return $result;
    }
    else
    {
      return $page->getPath($base);      
    }
  }
}