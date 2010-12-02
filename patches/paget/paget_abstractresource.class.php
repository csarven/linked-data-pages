<?php
class PAGET_AbstractResource {
  var $_uri;
  
  function __construct($uri) {
    $this->_uri = $uri;
  } 
  
  function get_uri() {
    return $this->_uri; 
  }
  
  function get(&$urispace,&$request) {
    $extension = 'rdf';
    $accepts = $request->accept;
    foreach ($accepts as $accept) {
      if ($accept == 'application/rdf+xml') {
        break;          
      }
      else if ($accept == 'application/json') {
        $extension = 'json';
        break;          
      }
      else if ($accept == 'text/plain') {
        $extension = 'turtle';
        break;          
      }
      else if ($accept == 'text/html') {
        $extension = 'html';
        break;          
      }
    }

    $parts = parse_url($this->_uri);

    $base_uri = $parts['scheme'] . '://' . $parts['host'] . $parts['path'];
    $suffix = '';
    if (!empty($parts['query'])) $suffix = '?' . $parts['query']; 
  
    $desc_uri = $base_uri . '.' . $extension . $suffix ;
    
    return new PAGET_Response(303, 'See ' . $desc_uri, array('location' => $desc_uri));
  }    
    
}
