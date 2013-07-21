<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'paget_urispace.class.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'paget_abstractresource.class.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'paget_storebackedresourcedescription.class.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'paget_filebackedresourcedescription.class.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'paget_storesearch.class.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'paget_storeoai.class.php';

class PAGET_StoreBackedUriSpace extends PAGET_UriSpace {
  var $_store_uri;
  var $_description_template;
  var $_search_template;
  var $_base_path = '/';
  var $_static_data = array() ;
  var $_ns = array();
  function __construct($store_uri) {
    $this->_store_uri = $store_uri; 
  }

  function get_resource($request) {
    $request_uri = $request->uri;
    
    if ( preg_match('~^(.+)\.(html|rdf|json|ttl)$~', $request->full_path, $m)) {
      $base_path = $m[1];
      $type = $m[2];  
      if ($base_path == $this->_base_path. '~search') {
        $query = isset($request->data["query"]) ? $request->data["query"] : '';
        $offset = isset($request->data["offset"]) ? $request->data["offset"] : '0';
        
        $desc = new PAGET_StoreSearch($request_uri, $type, $this->_store_uri, $query, 30, $offset);
        $desc->set_template($this->_description_template);
        return $desc;
      }
      else if ($base_path == $this->_base_path. '~browse') {
        if (! defined('AUTH_USER') && ! defined('AUTH_PWD')) return null;
        $token = isset($request->data["token"]) ? $request->data["token"] : null;
        
        $desc = new PAGET_StoreOAI($request_uri, $type, $this->_store_uri, $token);
        $desc->set_template($this->_description_template);
        return $desc;
      
      }
      else {
        $resource_uri = preg_replace("~\.local/~", "/", substr($request->uri, 0, strlen($request->uri)-strlen($type) - 1));
        if (isset($this->_static_data[$resource_uri])) {
          $desc = new PAGET_FileBackedResourceDescription($request_uri, $resource_uri, $type, $this->_static_data[$resource_uri], 'rdfxml'); 
        }
        else {
          $desc = new PAGET_StoreBackedResourceDescription($request_uri, $resource_uri, $type, $this->_store_uri); 
        }
        $desc->set_template($this->_description_template);
        foreach ($this->_ns as $short_name => $uri) {
          $desc->set_namespace_mapping($short_name , $uri);
        }
        if ($desc->is_valid()) {
          return $desc;  
        }
      }
    }
    else {
      return new PAGET_AbstractResource($request_uri);
    }

    return null;
  } 
  
  function set_namespace_mapping($short_name, $uri) {
    $this->_ns[$short_name] = $uri;
  }

  function set_static_data($resource_uri, $filename) {
    $this->_static_data[$resource_uri] = $filename;
  }
  function set_description_template($filename) {
    $this->_description_template = $filename;    
  }
  
  function set_search_template($filename) {
    $this->_search_template = $filename;    
  }  

  function get_template($request) {
    return $this->_description_template;
  } 
    
  function set_base_path($path) {
    $this->_base_path = $path;  
  }
}

