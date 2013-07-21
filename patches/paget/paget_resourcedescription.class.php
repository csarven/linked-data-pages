<?php
require_once MORIARTY_DIR . 'simplegraph.class.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'paget_response.class.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'paget_template.class.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'paget_simplepropertylabeller.class.php';

class PAGET_ResourceDescription extends SimpleGraph {   
  var $_uri;
  var $_primary_resource;
  var $_is_valid;
  var $_type;
  var $_template = null;
  var $_inverse_index = null;
  var $_media_types = array(
                          'rdf' => array('type' => 'application/rdf+xml', 'label' => 'RDF/XML'), 
                          'html' => array('type' => 'text/html',  'label' => 'HTML'),
                          'json' => array('type' => 'application/json',  'label' => 'JSON'),
                          'ttl' => array('type' => 'text/turtle', 'label' => 'Turtle'),
                      );  
  function __construct($desc_uri, $resource_uri, $type) {
    $this->_uri = $desc_uri;
    $this->_primary_resource = $resource_uri;
    $this->_type = $type;
    $this->read_triples();
    parent::__construct();

  } 
  
  function get_prefix_mappings() {
    return array_flip($this->_ns);  
  }

  function is_valid() {
    return $this->_is_valid;
  }

  function read_triples() {
    $resources = $this->get_resources();
    if (count($resources) > 0) {
      $this->_primary_resource = $resources[0];
    }
    $this->add_representation_triples();  
  

    
    
    $this->_is_valid = false;
    foreach ($resources as $resource_uri) {
      $this->add_resource_triple( $this->_uri, FOAF_TOPIC, $resource_uri );
      $generators = $this->get_generators();
      foreach ($generators as $generator) {
        $generator->add_triples($resource_uri, $this);  
      }
      
      if ( array_key_exists($resource_uri, $this->get_index())) {
        $this->_is_valid = true;  
      }
    }

    $augmentors = $this->get_augmentors();
    foreach ($augmentors as $augmentor) {
      $augmentor->process($this);  
    }    
  }

  function add_representation_triples() {
    $this->add_resource_triple( $this->_uri, RDF_TYPE, FOAF_DOCUMENT );
    $this->add_resource_triple( $this->_uri, RDF_TYPE, 'http://purl.org/dc/dcmitype/Text' );
    $this->add_resource_triple( $this->_uri, FOAF_PRIMARYTOPIC, $this->_primary_resource );

    $parts = parse_url($this->_uri);

    if ( preg_match('~^(.+)\.(html|rdf|json|ttl)$~', $parts['path'], $m)) {
      $base_uri = $parts['scheme'] . '://' . $parts['host'] . $m[1];
      $suffix = '';
      if (array_key_exists('query', $parts) && strlen($parts['query']) > 0) $suffix = '?' . $parts['query']; 
      
      foreach ($this->_media_types as $extension => $type_info) {
        if ( $extension != $this->_type) {
          $this->add_resource_triple( $this->_uri, 'http://purl.org/dc/terms/hasFormat', $base_uri. '.' . $extension . $suffix );
          $this->add_resource_triple( $base_uri. '.' . $extension . $suffix , RDF_TYPE, 'http://purl.org/dc/dcmitype/Text' );
          $this->add_resource_triple( $base_uri. '.' . $extension . $suffix , RDF_TYPE, FOAF_DOCUMENT );
          $this->add_literal_triple( $base_uri. '.' . $extension . $suffix  , 'http://purl.org/dc/elements/1.1/format', $type_info['type'] );
          $this->add_literal_triple( $base_uri. '.' . $extension . $suffix  , RDFS_LABEL, $type_info['label'] );
        }
      }
    }    
  }


  function get_resources() {
    $resources = array($this->_primary_resource);
    return $resources;
  }

  
  function get_generators() {
    return  array( );
  }
  
  function get_augmentors() {
    return  array( new PAGET_SimplePropertyLabeller() );
  }
  
  function get_primary_resource_uri() {
    return $this->_primary_resource;
  }
  
  function get_uri() {
    return $this->_uri; 
  }

  function get_label($resource_uri = null) {
    if ($resource_uri == null) {
      $resource_uri = $this->_primary_resource; 
    }
    $label = $this->get_first_literal($resource_uri,'http://www.w3.org/2004/02/skos/core#prefLabel', '', 'en');
    if ( strlen($label) == 0) {
      $label = $this->get_first_literal($resource_uri,RDFS_LABEL, '', 'en');
    }
    if ( strlen($label) == 0) {
      $label = $this->get_first_literal($resource_uri,'http://purl.org/dc/terms/title', '', 'en');
    }
    if ( strlen($label) == 0) {
      $label = $this->get_first_literal($resource_uri,DC_TITLE, '', 'en');
    }
    if ( strlen($label) == 0) {
      $label = $this->get_first_literal($resource_uri,'http://purl.org/rss/1.0/title', '', 'en');
    }
    if ( strlen($label) == 0) {
      $label = $this->get_first_literal($resource_uri,FOAF_NAME, '', 'en');
    }
    if ( strlen($label) == 0) {
      $label = $this->get_first_literal($resource_uri,RDF_VALUE, '', 'en');
    }
    if ( strlen($label) == 0) {
      $label = $resource_uri;
    }  
    
  
    return $label;
  }
  
  
  function get_description($resource_uri = null) {
    if ($resource_uri == null) {
      $resource_uri = $this->_primary_resource; 
    }
    $text = $this->get_first_literal($resource_uri,'http://purl.org/dc/terms/description', '', 'en');
    if ( strlen($text) == 0) {
      $text = $this->get_first_literal($resource_uri,DC_DESCRIPTION, '', 'en');
    }
    if ( strlen($text) == 0) {
      $text = $this->get_first_literal($resource_uri,RDFS_COMMENT, '', 'en');
    }
    if ( strlen($text) == 0) {
      $text = $this->get_first_literal($resource_uri,'http://purl.org/rss/1.0/description', '', 'en');
    }
    if ( strlen($text) == 0) {
      $text = $this->get_first_literal($resource_uri,'http://vocab.org/bio/0.1/olb', '', 'en');
    }   
    return $text;
  }  
  function get(&$urispace,&$request) {
    

    $accepts = $request->accept;

    if ($this->_type == 'rdf') {
      $response = new PAGET_Response(200, $this->to_rdfxml(), array('content-type'=>'application/rdf+xml') );
    }
    else if ($this->_type == 'json') {
      $response = new PAGET_Response(200, $this->to_json(), array('content-type'=>'application/json') );
    }
    else if ($this->_type == 'ttl') {
      $response = new PAGET_Response(200, $this->to_turtle(), array('content-type'=>'text/turtle') );
    }
    else if ($this->_type == 'html') {
      $response = new PAGET_Response(200, $this->get_html($urispace, $request), array('content-type'=>'text/html') );
    }
    else {
      $response = new PAGET_Response(200, $this->get_html($urispace, $request), array('content-type'=>'text/html') );
//      $response = new PAGET_Response(200, $this->to_rdfxml(), array('content-type'=>'application/rdf+xml') );
    }
    return $response;
  }

  function set_template($tmpl) {
    $this->_template = $tmpl;  
  }

  function get_html(&$urispace, &$request) {
    $tmpl = $this->_template;
    if ( null == $tmpl ) {
      $tmpl = $urispace->get_template($request);
    }
    if ( null == $tmpl ) {
      $tmpl = PAGET_DIR . 'templates' .  DIRECTORY_SEPARATOR . 'plain.tmpl.html';
    }
    
    $template = new PAGET_Template($tmpl, $this, $urispace, $request);
    return $template->execute();

  }


  function get_inverse_index() {
    if ($this->_inverse_index == null) {
      $g = new SimpleGraph();
      
      foreach ($this->_index as $s => $p_list) {
        foreach ($p_list as $p => $v_list) {
          foreach ($v_list as $v_info) {
            if ( isset($v_info['type']) && $v_info['type'] == 'uri' ) {
              $g->add_resource_triple($v_info['value'], $p, $s);
            } 
          }
        }
      }
      
      $this->_inverse_index = $g->get_index();
    }
    return $this->_inverse_index;
  }
  
  function consume_first_literal($s, $p, $def = '') {
    return $this->get_first_literal($s, $p, $def);
  }
  
  
  // This function maps a URI to a local equivalent
  // Override this when you want the link in your HTML output to point to somewhere other than the URI itself, e.g. a proxy
  // The default implementation rewrites URIs to the domain name suffixed with .local for assisting with testing
  // For example http://example.com/foo might map to http://example.com.local/foo if the application is being accessed from example.com.local
  function map_uri($uri) {
    if (isset($_SERVER["HTTP_HOST"])) {
      if (preg_match('~http://([^/]+)/~i', $uri, $m)) {
        if ( $_SERVER["HTTP_HOST"] == $m[1] . '.local' ) {
          return str_replace($m[1], $_SERVER["HTTP_HOST"], $uri);
        }
        else {
          return $uri;
        }
      }
    }
  }

}
