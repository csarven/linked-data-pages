<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'moriarty.inc.php';
require_once MORIARTY_ARC_DIR . DIRECTORY_SEPARATOR . "ARC2.php";

/**
 * Represents the base class for various sparql services.
 */
class SparqlServiceBase {
  /**
   * @access private
   */
  var $uri;
  /**
   * @access private
   */
  var $request_factory;
  /**
   * @access private
   */
  var $credentials;

  /**
   * Create a new instance of this class
   * @param string uri URI of the sparql service
   * @param Credentials credentials the credentials to use for authenticated requests (optional)
   */
  function __construct($uri, $credentials = null, $request_factory = null) {
    $this->uri = $uri;
    $this->credentials = $credentials;
    $this->request_factory = $request_factory;
  }

  /**
   * Obtain a bounded description of a given resource. Various types of description are supported:
   * <ul>
   * <li><em>cbd</em> - concise bounded description</li>
   * <li><em>scbd</em> - symmetric bounded description</li>
   * <li><em>lcbd</em> - labelled bounded description</li>
   * <li><em>slcbd</em> - symmetric labelled bounded description</li>
   * </ul>
   * See http://n2.talis.com/wiki/Bounded_Descriptions_in_RDF for more information on these types of description
   * Only cbd type is supported for arrays of URIs
   * @param mixed uri the URI of the resource to be described or an array of URIs
   * @param string type the type of bounded description to be obtained (optional)
   * @param string output the format of the RDF to return - one of rdf, turtle, ntriples or json (optional)
   * @return HttpResponse
   */
  function describe( $uri, $type = 'cbd', $output = OUTPUT_TYPE_RDF ) {
    if ( is_array( $uri ) ) {
      $query="DESCRIBE <" . implode('> <' , $uri) . ">";
    }
    else {
      if ($type == 'scbd') {
        $query = "CONSTRUCT {<$uri> ?p ?o . ?s ?p2 <$uri> .} WHERE { {<$uri> ?p ?o .} UNION {?s ?p2 <$uri> .} }";
      }
      else if ($type == 'lcbd') {
//        $query = "PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#> CONSTRUCT {<$uri> ?p ?o . ?o rdfs:label ?label . ?o rdfs:comment ?comment . ?o rdfs:seeAlso ?seealso.} WHERE {<$uri> ?p ?o . OPTIONAL { ?o rdfs:label ?label .} OPTIONAL {?o rdfs:comment ?comment . } OPTIONAL {?o rdfs:seeAlso ?seealso.}}";
        $query = "PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#> CONSTRUCT {<$uri> ?p ?o . ?o rdfs:label ?label . ?o rdfs:comment ?comment . ?o <http://www.w3.org/2004/02/skos/core#prefLabel> ?plabel . ?o rdfs:seeAlso ?seealso.} WHERE {<$uri> ?p ?o . OPTIONAL { ?o rdfs:label ?label .} OPTIONAL {?o <http://www.w3.org/2004/02/skos/core#prefLabel> ?plabel . } OPTIONAL {?o rdfs:comment ?comment . } OPTIONAL {?o rdfs:seeAlso ?seealso.}}";
      }
      else if ($type == 'slcbd') {
        $query = "PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#> CONSTRUCT {<$uri> ?p ?o . ?o rdfs:label ?label . ?o rdfs:comment ?comment . ?o rdfs:seeAlso ?seealso. ?s ?p2 <$uri> . ?s rdfs:label ?label . ?s rdfs:comment ?comment . ?s rdfs:seeAlso ?seealso.} WHERE { { <$uri> ?p ?o . OPTIONAL {?o rdfs:label ?label .} OPTIONAL {?o rdfs:comment ?comment .} OPTIONAL {?o rdfs:seeAlso ?seealso.} } UNION {?s ?p2 <$uri> . OPTIONAL {?s rdfs:label ?label .} OPTIONAL {?s rdfs:comment ?comment .} OPTIONAL {?s rdfs:seeAlso ?seealso.} } }";
      }
      else {
        $query="DESCRIBE <$uri>";
      }


    }
    return $this->graph($query, $output);
  }

  /**
   * @deprecated triple lists are deprecated
   */
  function describe_to_triple_list( $uri ) {
    $triples = array();

    $response = $this->describe( $uri );
    $parser_args=array(
      "bnode_prefix"=>"genid",
      "base"=> $this->uri
    );
    $parser = ARC2::getRDFXMLParser($parser_args);

    if ( $response->body ) {
      $parser->parse($this->uri, $response->body );
      $triples = $parser->getTriples();
    }

    return $triples;
  }

  /**
   * Obtain a bounded description of a given resource as a SimpleGraph. An empty SimpleGraph is returned if any HTTP errors occur.
   * @param mixed uri the URI of the resource to be described or an array of URIs
   * @return SimpleGraph
   */
  function describe_to_simple_graph( $uri, $type='cbd' ) {
    $graph = new SimpleGraph();

    $response = $this->describe( $uri, $type, OUTPUT_TYPE_JSON );

    if ( $response->is_success() ) {
      $graph->from_json( $response->body );
    }

    return $graph;
  }

  /**
   * Execute an arbitrary query on the sparql service. Will use GET for short queries to enhance cacheability.
   * @param string query the query to execute
   * @param string mime the media type of the expected response or the short name as listed at http://n2.talis.com/wiki/Store_Sparql_Service#Output_Formats (optional, defaults to RDF/XML and SPARQL results XML)
   * @return HttpResponse
   */
  function query($query, $mime=''){
    if (empty( $this->request_factory) ) {
      $this->request_factory = new HttpRequestFactory();
    }

    $get_uri = $this->get_query_uri($query, $mime);
    
    if (strlen($get_uri) <= $this->get_max_uri_length()) {
      $request = $this->request_factory->make( 'GET', $get_uri, $this->credentials );
      if(empty($mime)) $mime = MIME_RDFXML.','.MIME_SPARQLRESULTS;
      $request->set_accept($mime);
    }
    else {
      $request = $this->request_factory->make( 'POST', $this->uri, $this->credentials );
      if(empty($mime)) $mime = MIME_RDFXML.','.MIME_SPARQLRESULTS;
      $request->set_accept($mime);
      $request->set_content_type(MIME_FORMENCODED);
      $request->set_body( $this->get_query_params($query, $mime) );
    }

    return $request->execute();

  }

  function get_max_uri_length() {
    return 1024;
  }

  function get_query_params($query, &$mime = '') {
    $params = 'query=' . urlencode($query);
    if ( !empty($mime) && strstr($mime, '/') === FALSE) {
      $params .= '&output=' . $mime;
      $mime = '*/*';
    }
    return $params;
  }

  function get_query_uri($query, &$mime = '') {
    return $this->uri . '?' . $this->get_query_params($query, $mime);
  }


  /**
   * Execute a graph type sparql query, i.e. a describe or a construct
   * @param string query the describe or construct query to execute
   * @return HttpResponse
   */
  function graph( $query, $output = 'rdf' ) {
    return $this->query($query, $output);
  }

  /**
   * @deprecated triple lists are deprecated
   */
  function graph_to_triple_list($query ) {
    $triples = array();
    $response = $this->graph( $query );

    $parser_args=array(
      "bnode_prefix"=>"genid",
      "base"=> $this->uri
    );
    $parser = ARC2::getRDFXMLParser($parser_args);

    if ( $response->body ) {
      $parser->parse($this->uri, $response->body );
      $triples = $parser->getTriples();
    }

    return $triples;
  }

  /**
   * @deprecated triple lists are deprecated
   */
  function construct_to_triple_list($query ) {
    return $this->graph_to_triple_list($query );
  }

  /**
   * Execute a graph type sparql query and obtain the result as a SimpleGraph. An empty SimpleGraph is returned if any HTTP errors occur.
   * @param string query the describe or construct query to execute
   * @return SimpleGraph
   */
  function graph_to_simple_graph( $query ) {
    $graph = new SimpleGraph();

    $response = $this->graph( $query );

    if ( $response->is_success() ) {
      $graph->from_rdfxml( $response->body );
    }

    return $graph;
  }

  /**
   * @deprecated use graph_to_simple_graph
   */
  function construct_to_simple_graph( $query ) {
    return $this->graph_to_simple_graph($query);
  }


  /**
   * Execute a select sparql query
   * @param string query the select query to execute
   * @return HttpResponse
   */
  function select( $query ) {
    return $this->query($query, MIME_SPARQLRESULTS);
  }

  /**
   * Execute a select sparql query and return the results as an array. An empty array is returned if any HTTP errors occur.
   * @param string query the select query to execute
   * @return array parsed results in format returned by parse_select_results method
   */
  function select_to_array( $query ) {
    $results = array();
    $response = $this->select( $query );
    if ( $response->is_success() ) {
      $results = $this->parse_select_results( $response->body );
    }
    return $results;
  }

  /**
   * Parse the SPARQL XML results format into an array. The array consist of one element per result.
   * Each element is an associative array where the keys correspond to the variable name and the values are
   * another associative array with the following keys:
   * <ul>
   * <li><em>type</em> => the type of the result binding, one of 'uri', 'literal' or 'bnode'</li>
   * <li><em>value</em> => the value of the result binding</li>
   * <li><em>lang</em> => the language code (if any) of the result binding</li>
   * <li><em>datatype</em> => the datatype uri (if any) of the result binding</li>
   * </ul>
   * For example: $results[2]['foo']['value'] will obtain the value of the foo variable for the third result
   * @param string xml the results XML to parse
   * @return array
   */
  function parse_select_results( $xml ) {
    $results = array();
    $reader = new XMLReader();
    $reader->XML($xml);

    $result = array();
    $bindingName = null;
    $binding = array();
    while ($reader->read()) {
      if ( $reader->name == 'result') {

        if ( $reader->nodeType == XMLReader::ELEMENT) {
          $result = array();
        }
        elseif ( $reader->nodeType == XMLReader::END_ELEMENT) {
          array_push( $results, $result);
          $result = array();
        }
      }
      elseif ( $reader->name == 'binding') {
        if ( $reader->nodeType == XMLReader::ELEMENT) {
          $bindingName = $reader->getAttribute("name");
          $binding = array();
        }
        elseif ( $reader->nodeType == XMLReader::END_ELEMENT) {
          $result[ $bindingName ] = $binding;
          $bindingName = null;
          $binding = array();
        }
      }
      elseif ( $reader->name == 'uri' && $reader->nodeType == XMLReader::ELEMENT) {
        $binding['type'] = 'uri';
        $value = '';
        while ($reader->read()) {
          if ($reader->nodeType == XMLReader::TEXT
            || $reader->nodeType == XMLReader::CDATA
            || $reader->nodeType == XMLReader::WHITESPACE
            || $reader->nodeType == XMLReader::SIGNIFICANT_WHITESPACE) {
             $value .= $reader->value;
          }
          else if ($reader->nodeType == XMLReader::END_ELEMENT) {
            break;
          }
        }
        $binding['value'] = $value;
      }
      elseif ( $reader->name == 'literal' && $reader->nodeType == XMLReader::ELEMENT) {
        $binding['type'] = 'literal';
        $datatype = $reader->getAttribute("datatype");
        if ( $datatype ) {
          $binding['datatype'] = $datatype;
        }
        $lang = $reader->getAttribute("xml:lang");
        if ( $lang ) {
          $binding['lang'] = $lang;
        }
        $value = '';
        while ($reader->read()) {
          if ($reader->nodeType == XMLReader::TEXT
            || $reader->nodeType == XMLReader::CDATA
            || $reader->nodeType == XMLReader::WHITESPACE
            || $reader->nodeType == XMLReader::SIGNIFICANT_WHITESPACE) {
             $value .= $reader->value;
          }
          else if ($reader->nodeType == XMLReader::END_ELEMENT) {
            break;
          }
        }
        $binding['value'] = $value;
      }
      elseif ( $reader->name == 'bnode' && $reader->nodeType == XMLReader::ELEMENT) {
        $binding['type'] = 'bnode';
        $value = '';
        while ($reader->read()) {
          if ($reader->nodeType == XMLReader::TEXT
            || $reader->nodeType == XMLReader::CDATA
            || $reader->nodeType == XMLReader::WHITESPACE
            || $reader->nodeType == XMLReader::SIGNIFICANT_WHITESPACE) {
             $value .= $reader->value;
          }
          else if ($reader->nodeType == XMLReader::END_ELEMENT) {
            break;
          }
        }
        $binding['value'] = $value;
      }
    }
    $reader->close();
    return $results;
  }

  /**
   * Execute an ask sparql query
   * @param string query the ask query to execute
   * @return HttpResponse
   */
  function ask( $query ) {
    return $this->query($query, MIME_SPARQLRESULTS);
  }

  /**
   * Parse the SPARQL XML results format from an ask query.
   * @param string xml the results XML to parse
   * @return array true if the query result was true, false otherwise
   */
  function parse_ask_results( $xml ) {
    $reader = new XMLReader();
    $reader->XML($xml);

    $result = false;
    $bindingName = null;
    $binding = array();
    while ($reader->read()) {
      if ( $reader->name == 'boolean') {
        $value = '';
        while ($reader->read()) {
          if ($reader->nodeType == XMLReader::TEXT
            || $reader->nodeType == XMLReader::CDATA
            || $reader->nodeType == XMLReader::WHITESPACE
            || $reader->nodeType == XMLReader::SIGNIFICANT_WHITESPACE) {
             $value .= $reader->value;
          }
          else if ($reader->nodeType == XMLReader::END_ELEMENT) {
            break;
          }
        }
        $reader->close();
        return ( strtolower(trim($value)) == 'true' );
      }
    }

    return false;
  }


}
?>
