<?php

class ccConfig
{
  protected $_conf = array();
  protected $_defaults = array();
  
  public function __construct($conf = array(), $defaults = array())
  {
    $this->_defaults = is_array($defaults) ? $defaults : array();
    $this->_conf = $conf + $this->_defaults;    
  }
  
  public function getDefaults()
  {
    return $this->_defaults;    
  }
  
  public function setDefault($name ,$value)
  {
    $this->_defaults[$name] = $value;
  }
  
  public function getDefault($name)
  {
    return isset($this->_defaults[$name]) ? $this->_defaults[$name] : null;
  }
  
  public function reconfigure($conf)
  {
    $this->_conf = $conf + $this->_defaults;
  }
  
  public function get($name, $default=null)
  {
    return isset($this->_conf[$name]) ? $this->_conf[$name] : $default;
  }
  
  public function set($name, $value)
  {
    $this->_conf[$name] = $value;
  }

  public function __set($k, $v)
  {
    return $this->set($k, $v);
  }

  public function __get($k)
  {
    return $this->get($k);
  }
}