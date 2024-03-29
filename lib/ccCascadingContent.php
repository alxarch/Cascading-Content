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
require_once 'ccConfig.php';
require_once 'ccContent.php';
require_once 'ccCache.php';
require_once 'ccFinder.php';

class ccCascadingContent
{
  static protected $_instance;
  protected $_context, $_config, $_cache, $_registered_types;
  
  static public function getInstance($userconf=array())
  {
    if(!isset(self::$_instance))
    {
      self::$_instance = new ccCascadingContent($userconf);
    }
    
    return self::$_instance;
  }
  
  public function getDefaults()
  {

    $root = dirname($_SERVER['SCRIPT_FILENAME']);
    "<pre>".print_r($root, 1)."</pre>";
    
    return array(
      'content_path'   => 'content',
      'cache_path'     => 'cache',
      'layout_name'    => 'layout',
      'index_name'     => 'index',
      'style_name'     => 'style',
      'script_name'    => 'script',
      'meta_name'      => 'meta',
      'style_dir'      => '__css__',
      'script_dir'     => '__js__',
      'meta_dir'       => '__meta__',
      'part_dir'       => '__part__',
      'image_dir'      => '__img__',
      'error_dir'      => '__error__',
      'attachment_dir' => '__attachments__',
      'base_path'      => ccPath::to($_SERVER['SCRIPT_NAME']),
      'root_dir'       => $root,
      'content_dir'    => ccPath::os($root, 'content'),
      'cache_dir'      => ccPath::os($root, 'cache'),
    );
  }
  
  protected function __construct($userconf = array())
  {
    if(!is_array($userconf))
    {
      $userconf = (string) $userconf;
      if(file_exists($userconf))
      {
        $y = new ccContentYaml($userconf);
        $userconf = ccContentYaml::filter($y->getContents());
      }
      else
      {
        $userconf = array();
      }
    }

    $this->initialize($userconf);
    $this->registerContentTypes();
    $this->initContext();
  }

  protected function initialize($userconf)
  {
    $c = new ccConfig($userconf, $this->getDefaults());
    
    $c->content_dir = ccPath::os($c->root_dir, $c->content_path);
    
    $c->cache_dir = ccPath::os($c->root_dir, $c->cache_path);
    
    $this->_config = $c;
    
    $this->_cache = new ccCache($c->cache_dir);
    
  }

  public function __get($k)
  {
    switch ($k)
    {
      case 'cache':
        return $this->getCache();
        break;
      case 'config':
        return $this->getConfig();
        break;
      case 'context':
        return $this->getContext();
        break;
      default:
        return $this->getConfig()->get($k);
        break;
    }
  }

  protected function registerContentTypes()
  {
    $this->registerContentType('meta', 'yaml', 'yaml, yml');

    $this->registerContentType('style', 'css', 'css');
    $this->registerContentType('style', 'less', 'less');

    $this->registerContentType('script', 'js', 'js');

    $this->registerContentType('partial', 'markdown', 'markdown,md');
    $this->registerContentType('partial', 'html', 'html,htm');
    $this->registerContentType('partial', 'php', 'php,phtml');

    $this->registerContentType('content', 'markdown', 'markdown,md');
    $this->registerContentType('content', 'html', 'html,htm');
    $this->registerContentType('content', 'php', 'php,phtml');
    
    $this->registerContentType('error', 'html', 'html,htm');
    
    $this->registerContentType('layout', 'html', 'html');
    $this->registerContentType('layout', 'php', 'php');
    
    $this->registerContentType('image', 'image', 'jpg,png');
    
    $this->registerContentType('attachment', 'attachment', null);
    
  }

  public function registerContentType($category, $type, $extensions=null)
  {
    ccContentFactory::validate($type);

    if(!isset($this->_registered_types[$category]))
    {
      $this->_registered_types[$category] = array();
    }
    
    if(null !== $extensions)
    {
      $extensions = ccArray::make($extensions);
    }

    $this->_registered_types[$category][$type] = $extensions;
    
  }


  public function getRegisteredTypes($category)
  {
    if(isset($this->_registered_types[$category]))
    {
      return $this->_registered_types[$category];
    }

    return array();
  }
  
  public function getConfig()
  {
    return $this->_config;
  }
    
  public function getCache()
  {
    return $this->_cache;
  }

  public function serve($path)
  {
    if($this->getConfig()->get('cache',true))
    {
      $output = $this->getCache()->retrieve($path);
    }
    else
    {
      $output = false;
    }
    
    if(!$output)
    {
         
      $output = $this->generate($path);
    
      if(null === $output)
      {
        $this->notFound($path);
      }

      $this->getCache()->store($path.'.html', $output);
    }
    echo $output;
    exit(0);
  }
  
  protected function notFound($path)
  {
    @header( CGI ? "Status: 404 Not Found" : "HTTP/1.1 404 Not Found");
    echo $this->error(404, $path);
  }
  
  protected function generate($path)
  {
    require_once 'ccHelpers.php';

    $this->setContext('path', $path);

    $content = $this->getContent($path);

    if(null == $content)
    {
      return null;
    }

    $scripts = $this->getScripts($path);

    $styles  = $this->getStyles($path);
    $meta    = $this->getMeta($path);
    $layout  = $this->getLayout($path);

    $title = isset($meta['@title']) ? $meta['@title'] : path_to_title($path);
    $this->setContext('title', $title);
    
    if(is_a($layout, 'ccContentPhp'))
    {
      $this->setContext(array(
        'title'   => $title,
        'meta'    => $meta,
        'scripts' => $scripts,
        'styles'  => $styles,
      ));
    }
    else
    {
       $this->setContext(array(
        'title'   => $title,
        'meta'    => meta($meta),
        'scripts' => scripts($scripts, $this->base_path),
        'styles'  => styles($styles, $this->base_path),
      ));
    }
    $content = $content->render($this->getContext());
    
    $this->setContext('content', $content);
    
    return $layout->render($this->getContext());
  }

  
  protected function getContent($path)
  {
    $finder = $this->getFinder('content');
    
    $result = $finder->find($path);
    
    return $result;
  }
  
  protected function getMeta($path)
  {
    $meta = array();

    $finder = $this->getFinder('meta');

    $results = $finder->find($path);

    foreach($results as $r)
    {
      $m = $r->render($this->getContext());
      
      if(isset($m['all']) && is_array($m['all']))
      {
        $meta = $m['all'] + $meta;
      }
      
      $path = $r->getPath($this->getConfig()->get('content_dir'));
      $name = ccFile::name($path, false);
      
      if(isset($m[$name]) && is_array($m[$name]))
      {
        $meta = $m[$meta] + $meta;
      }
    }
    //todo: cache to php files.
    return $meta;
  }
  
  protected  function getStyles($path)
  {
    $finder = $this->getFinder('style');

    $styles = $finder->findPath($path, $this->getConfig()->get('root_dir'));
    
    return $styles;
  }   
  
  protected function getLayout($path)
  {
    $finder = $this->getFinder('layout');

    $layout = $finder->find($path);

    return $layout;
  }
 
  protected function getScripts($path)
  {
    
    $finder = $this->getFinder('script');

    $scripts = $finder->findPath($path, $this->getConfig()->get('root_dir'));
    
    return $scripts;
  }
  
  public function getContext($name = null, $default = null)
  {
    if(null === $name)
    {
      return $this->_context;
    }
    
    return isset($this->_context[$name]) ? $this->_context[$name] : $default;
  }
  
  public function setContext($name, $value = null)
  {
    $values = is_array($name) ? $name : array($name => $value);
    foreach($values as $name => $value)
    {
      $this->_context[$name] = $value;
    }
  }
  
  protected function initContext()
  {
    $this->setContext(array(
      'js'  => $this->getConfig()->get('script_dir'),
      'css' => $this->getConfig()->get('style_dir'),
      'img' => $this->getConfig()->get('img_dir'),
      '/'   => $this->getConfig()->get('base_path'),
      '@'   => $this->getConfig()->get('attachments'),
    ));
  }
  
  /**
   * Creates a ccFinder instance with filetypes defined in registerContentTypes
   * 
   * @param string $type a finder type.
   *
   * @return ccFinder $finder
   */
  public function getFinder($type)
  {
    $idx = sprintf("%s_name", $type === 'content' ? 'index' : $type);
    $dir = $type === 'content' ? null : $type.'_dir';
    
    $finder = ccFinderFactory::createFinder($type, 
      $this->getConfig()->get('content_dir'),
      $this->getRegisteredTypes($type),
      $this->getConfig()->get($idx),
      $this->getConfig()->get($dir));
    
    return $finder;
  }
  
  protected function postProccess($output)
  {
    return $output;
    //TODO: find all scripts and cache them h5bp style.
  }
  
  public function error($code, $path='/')
  {
    $f = $this->getFinder('error');
    
    $f->setIndexName($code);
    
    $error = $f->find($path);
    
    if(null === $error)
    {
      return "Error $code.";
    }
    
    return $error->render($this->getContext());
  }
  
  //protected function cacheResults($path, $items, $glue="\n;")
  //{
  //
  //  $paths = array();
  //  $concat = array();
  //  $bp = $this->getConfig()->get('base_path');
  //  $cd = $this->getConfig()->get('content_dir');
  //  foreach($items as $i)
  //  {
  //    $filepath = ccPath::offset($i, $bp);
  //    $file = ccPath::os($cd, $filepath);
  //
  //    $type = Content::guess($file);
  //    $content = Content::init($file, $type);
  //
  //
  //    if($content->master)
  //    {
  //      $p = ccPath::offset($this->content_dir, $content->getFile());
  //      $content = $content->render($this->getContext());
  //      $p = $this->getCache()->store($content, $p);
  //      $paths[] = ccPath::web($this->base_path, $p);
  //    }
  //    else
  //    {
  //      $concat[] = $content->render();
  //    }
  //  }
  //
  //  $concat = implode($glue, $concat);
  //
  //  $p = $this->getCache()->store($path, $concat);
  //
  //  $paths[] = ccPath::web($this->base_path, $p);
  //
  //  return $paths;
  //}
  
}
