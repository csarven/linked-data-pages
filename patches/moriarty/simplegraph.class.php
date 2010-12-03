<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'moriarty.inc.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'labeller.class.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'httprequestfactory.class.php';
require_once MORIARTY_ARC_DIR . "ARC2.php";

/**
 * Represents an RDF graph and provides some simple functions for traversing and manipulating it.
 */
class SimpleGraph {
  var $_index = array();
  var $_image_properties =  array( 'http://xmlns.com/foaf/0.1/depiction', 'http://xmlns.com/foaf/0.1/img');
  var $_property_order =  array('http://www.w3.org/2004/02/skos/core#prefLabel', RDFS_LABEL, 'http://purl.org/dc/terms/title', DC_TITLE, FOAF_NAME, 'http://www.w3.org/2004/02/skos/core#definition', RDFS_COMMENT, 'http://purl.org/dc/terms/description', DC_DESCRIPTION, 'http://purl.org/vocab/bio/0.1/olb', RDF_TYPE);
  var $request_factory = false;
  protected $_ns = array (
                    'rdf' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
                    'rdfs' => 'http://www.w3.org/2000/01/rdf-schema#',
                    'owl' => 'http://www.w3.org/2002/07/owl#',
                    'cs' => 'http://purl.org/vocab/changeset/schema#',
                    'bf' => 'http://schemas.talis.com/2006/bigfoot/configuration#',
                    'frm' => 'http://schemas.talis.com/2006/frame/schema#',

                    'dc' => 'http://purl.org/dc/elements/1.1/',
                    'dct' => 'http://purl.org/dc/terms/',
                    'dctype' => 'http://purl.org/dc/dcmitype/',
                    'geo' => 'http://www.w3.org/2003/01/geo/wgs84_pos#',
                    'rel' => 'http://purl.org/vocab/relationship/',
                    'wn' => 'http://xmlns.com/wordnet/1.6/',
                    'air' => 'http://www.daml.org/2001/10/html/airport-ont#',
                    'contact' => 'http://www.w3.org/2000/10/swap/pim/contact#',
                    'frbr' => 'http://purl.org/vocab/frbr/core#',

                    'ad' => 'http://schemas.talis.com/2005/address/schema#',
                    'lib' => 'http://schemas.talis.com/2005/library/schema#',
                    'dir' => 'http://schemas.talis.com/2005/dir/schema#',
                    'user' => 'http://schemas.talis.com/2005/user/schema#',
                    'sv' => 'http://schemas.talis.com/2005/service/schema#',
                    'mo' => 'http://purl.org/ontology/mo/',
                    'status' => 'http://www.w3.org/2003/06/sw-vocab-status/ns#',
                    'label' => 'http://purl.org/net/vocab/2004/03/label#',
                    'skos' => 'http://www.w3.org/2004/02/skos/core#',
                    'bibo' => 'http://purl.org/ontology/bibo/',
                    'ov' => 'http://open.vocab.org/terms/',
                    'foaf' => 'http://xmlns.com/foaf/0.1/',
                    'void' => 'http://rdfs.org/ns/void#',
                    'xsd' => 'http://www.w3.org/2001/XMLSchema#',
                  );

  var $_labeller;

  function __construct($graph=false){
    $this->_labeller = new Labeller();
    if($graph){
      if(is_string($graph)){
        $this->add_rdf($graph);
      } else {
        $this->_index = $graph;
      }
    }

  }

  function __destruct(){
    unset($this->_index);
    unset($this);
  }

  function set_request_factory($request_factory) {
    $this->request_factory = $request_factory;
  }


  /**
   * Map a portion of a URI to a short prefix for use when serialising the graph
   * @param string prefix the namespace prefix to associate with the URI
   * @param string uri the URI to associate with the prefix
   */
  function set_namespace_mapping($prefix, $uri) {
    $this->_labeller->set_namespace_mapping($prefix, $uri);
  }

  /**
   * Convert a QName to a URI using registered namespace prefixes
   * @param string qname the QName to convert
   * @return string the URI corresponding to the QName if a suitable prefix exists, null otherwise
   */
  function qname_to_uri($qname) {
    return $this->_labeller->qname_to_uri($qname);
  }

  /**
   * Convert a URI to a QName using registered namespace prefixes
   * @param string uri the URI to convert
   * @return string the QName corresponding to the URI if a suitable prefix exists, null otherwise
   */
  function uri_to_qname($uri) {
    return $this->_labeller->uri_to_qname($uri);
  }

  function get_prefix($ns) {
    return $this->_labeller->get_prefix($ns);
  }

  function add_labelling_property($p)  {
    $this->_labeller->add_labelling_property($p);
  }


  function update_prefix_mappings() {
    foreach ($this->_index as $s => $p_list) {
      foreach ($p_list as $p => $v_list) {
        $prefix = $this->_labeller->uri_to_qname($p);
      }
    }
  }




  /**
   * Constructs an array containing the type of the resource and its value
   * @param string resource a URI or blank node identifier (prefixed with _: e.g. _:name)
   * @return array an associative array with two keys: 'type' and 'value'. Type is either bnode or uri
   */
  function make_resource_array($resource) {
    $resource_type = strpos($resource, '_:' ) === 0 ? 'bnode' : 'uri';
    return array('type' => $resource_type, 'value' => $resource);
  }

  /**
   * Adds a triple with a resource object to the graph
   * @param string s the subject of the triple, either a URI or a blank node in the format _:name
   * @param string p the predicate URI of the triple
   * @param string o the object of the triple, either a URI or a blank node in the format _:name
   * @return boolean true if the triple was new, false if it already existed in the graph
   */
  function add_resource_triple($s, $p, $o) {
    return $this->_add_triple($s, $p, array('type' => strpos($o, '_:' ) === 0 ? 'bnode' : 'uri', 'value' => $o));
  }

  /**
   * Adds a triple with a literal object to the graph
   * @param string s the subject of the triple, either a URI or a blank node in the format _:name
   * @param string p the predicate of the triple as a URI
   * @param string o the object of the triple as a string
   * @param string lang the language code of the triple's object (optional)
   * @param string dt the datatype URI of the triple's object (optional)
   * @return boolean true if the triple was new, false if it already existed in the graph
   */
  function add_literal_triple($s, $p, $o, $lang = null, $dt = null) {
    $o_info = array('type' => 'literal', 'value' => $o);
    if ( $lang != null ) {
      $o_info['lang'] = $lang;
    }
    if ( $dt != null ) {
      $o_info['datatype'] = $dt;
    }
    return $this->_add_triple($s, $p, $o_info);
  }

  private function _add_triple($s, $p, $o_info) {
    if (!isset($this->_index[$s])) {
      $this->_index[$s] = array();
      $this->_index[$s][$p] = array( $o_info );
      return true;
    }
    elseif (!isset($this->_index[$s][$p])) {
      $this->_index[$s][$p] = array( $o_info);
      return true;
    }
    else {
      if ( ! in_array( $o_info, $this->_index[$s][$p] ) ) {
        $this->_index[$s][$p][] = $o_info;
        return true;
      }
    }
    return false;
  }

  /**
   * @deprecated this is deprecated
   */
  function get_triples() {
    return ARC2::getTriplesFromIndex($this->_to_arc_index($this->_index));
  }

  /**
   * Get a copy of the graph's triple index
   * @see http://n2.talis.com/wiki/RDF_PHP_Specification
   */
  function get_index() {
    return $this->_index;
  }


  /**
   * Serialise the graph to RDF/XML
   * @return string the RDF/XML version of the graph
   */
  function to_rdfxml() {
    $this->update_prefix_mappings();
    $serializer = ARC2::getRDFXMLSerializer(
        array(
          'ns' => $this->_labeller->get_ns(),
        )
      );
    return $serializer->getSerializedIndex($this->_to_arc_index($this->_index));
 }

  /**
   * Serialise the graph to Turtle
   * @see http://www.dajobe.org/2004/01/turtle/
   * @return string the Turtle version of the graph
   */
  function to_turtle() {
    $this->update_prefix_mappings();
    $serializer = ARC2::getTurtleSerializer(
        array(
          'ns' => $this->_labeller->get_ns(),
        )
      );
    return $serializer->getSerializedIndex($this->_to_arc_index($this->_index));
  }

  /**
   * Serialise the graph to N-Triples
   * @see http://www.w3.org/TR/rdf-testcases/#ntriples
   * @return string the N-Triples version of the graph
   */
  function to_ntriples() {
    $serializer = ARC2::getComponent('NTriplesSerializer', array());
    return $serializer->getSerializedIndex($this->_to_arc_index($this->_index));
  }


  /**
   * Serialise the graph to JSON
   * @see http://n2.talis.com/wiki/RDF_JSON_Specification
   * @return string the JSON version of the graph
   */
  function to_json() {
    return json_encode($this->_index);
  }


  /**
   * Serialise the graph to HTML
   * @return string a HTML version of the graph
   */
  function to_html($s = null, $guess_labels = true) {
    
    $this->update_prefix_mappings();
    $h = '';

    if ($s) {
      if (is_array($s)) {
        $subjects = array_intersect($s, $this->get_subjects());
        if (count($subjects) == 0) return '';
      }
      else {
        if (array_key_exists($s, $this->_index)) {
          $subjects = array($s);
        }
        else {
          return '';
        }
      }
    }
    else {
      $subjects = $this->get_subjects();
    }


    if (count($subjects) > 0) {
      foreach ($subjects as $subject) {
        if (count($subjects) > 1) {
          $h .= '<h1><a href="' . htmlspecialchars($subject) . '">' . htmlspecialchars($this->get_label($subject)) . '</a></h1>' . "\n";
        }
        $h .= '<table>' . "\n";

        $properties = $this->get_subject_properties($subject, TRUE);
        $priority_properties = array_intersect($properties, $this->_property_order);
        $properties = array_merge($priority_properties, array_diff($properties, $priority_properties));

        foreach ($properties as $p) {
          $h .= '<tr><th valign="top"><a href="' . htmlspecialchars($p). '">' . htmlspecialchars($this->get_label($p, true)). '</a></th>';
          $h .= '<td valign="top">';
          for ($i = 0; $i < count($this->_index[$subject][$p]); $i++) {
            if ($i > 0) $h .= '<br />';
            if ($this->_index[$subject][$p][$i]['type'] === 'literal') {
              $h .= htmlspecialchars($this->_index[$subject][$p][$i]['value'] );
            }
            else {
              $h .= '<a href="' . htmlspecialchars($this->_index[$subject][$p][$i]['value']). '">';
              if ($guess_labels) {
                $h .= htmlspecialchars($this->get_label($this->_index[$subject][$p][$i]['value']) );
              }
              else {
                $h .= htmlspecialchars($this->_index[$subject][$p][$i]['value'] );
              }
            
              $h .= '</a>';
            }
          }
          $h .= '</td>';
          $h .= '</tr>' . "\n";
        }
        
        $backlinks = array();
        foreach ($this->_index as $rev_subj => $rev_subj_info) {
          foreach ($rev_subj_info as $rev_subj_p => $rev_subj_p_list) {
            foreach ($rev_subj_p_list as $rev_value) {
              if ( ( $rev_value['type'] == 'uri' || $rev_value['type'] == 'bnode') && $rev_value['value'] === $subject) {
                if (!isset($backlinks[$rev_subj_p])) {
                  $backlinks[$rev_subj_p] = array();
                }
                $backlinks[$rev_subj_p][] = $rev_subj;
              }
            }
          }
        }
        
        foreach ($backlinks as $backlink_p => $backlink_values) {
          $h .= '<tr><th valign="top"><a href="' . htmlspecialchars($backlink_p). '">' . htmlspecialchars($this->get_inverse_label($backlink_p, true)). '</a></th>';
          $h .= '<td valign="top">';
          for ($i = 0; $i < count($backlink_values); $i++) {
            if ($i > 0) $h .= '<br />';

            $h .= '<a href="' . htmlspecialchars($backlink_values[$i]). '">';
            if ($guess_labels) {
              $h .= htmlspecialchars($this->get_label($backlink_values[$i]) );
            }
            else {
              $h .= htmlspecialchars($backlink_values[$i] );
            }
          
            $h .= '</a>';
          }
          $h .= '</td>';
          $h .= '</tr>' . "\n";
        }
        
        $h .= '</table>' . "\n";
      }
    }
    return $h;
  }


  /**
   * Fetch the first literal value for a given subject and predicate. If there are multiple possible values then one is selected at random.
   * @param string s the subject to search for
   * @param string p the predicate to search for, or an array of predicates
   * @param string default a default value to use if no literal values are found
   * @return string the first literal value found or the supplied default if no values were found
   */
  function get_first_literal($s, $p, $default = null, $preferred_language = null) {

    $best_literal = $default;
    if ( array_key_exists($s, $this->_index)) {
      if (is_array($p)) {
        foreach($p as $p_uri) {
          if(array_key_exists($p_uri, $this->_index[$s]) ) {
            foreach ($this->_index[$s][$p_uri] as $value) {
              if ($value['type'] == 'literal') {
                if ($preferred_language == null) {
                  return $value['value'];
                }
                else {
                  if (array_key_exists('lang', $value) && $value['lang'] == $preferred_language) {
                    return $value['value'];
                  }
                  else {
                    $best_literal = $value['value'];
                  }
                }
              }
            }
          }
        }
      }
      else if(array_key_exists($p, $this->_index[$s]) ) {
        foreach ($this->_index[$s][$p] as $value) {
          if ($value['type'] == 'literal') {
            if ($preferred_language == null) {
              return $value['value'];
            }
            else {
              if (array_key_exists('lang', $value) && $value['lang'] == $preferred_language) {
                return $value['value'];
              }
              else {
                $best_literal = $value['value'];
              }
            }
          }
        }
      }
    }

    return $best_literal;
  }

  /**
   * Fetch the first resource value for a given subject and predicate. If there are multiple possible values then one is selected at random.
   * @param string s the subject to search for
   * @param string p the predicate to search for
   * @param string default a default value to use if no literal values are found
   * @return string the first resource value found or the supplied default if no values were found
   */
  function get_first_resource($s, $p, $default = null) {
    if ( array_key_exists($s, $this->_index) && array_key_exists($p, $this->_index[$s]) ) {
      foreach ($this->_index[$s][$p] as $value) {
        if ($value['type'] == 'uri' || $value['type'] == 'bnode' ) {
          return $value['value'];
        }
      }
    }
    else {
      return $default;
    }
  }

  /**
   * Remove a triple with a resource object from the graph
   * @param string s the subject of the triple, either a URI or a blank node in the format _:name
   * @param string p the predicate URI of the triple
   * @param string o the object of the triple, either a URI or a blank node in the format _:name
   */
  function remove_resource_triple( $s, $p, $o) {
    for ($i = count($this->_index[$s][$p]) - 1; $i >= 0; $i--) {
      if (($this->_index[$s][$p][$i]['type'] == 'uri' || $this->_index[$s][$p][$i]['type'] == 'bnode') && $this->_index[$s][$p][$i]['value'] == $o)  {
        array_splice($this->_index[$s][$p], $i, 1);
      }
    }

    if (count($this->_index[$s][$p]) == 0) {
      unset($this->_index[$s][$p]);
    }
    if (count($this->_index[$s]) == 0) {
      unset($this->_index[$s]);
    }

  }

  function remove_literal_triple( $s, $p, $o) {
    for ($i = count($this->_index[$s][$p]) - 1; $i >= 0; $i--) {
      if ($this->_index[$s][$p][$i]['type'] == 'literal' && $this->_index[$s][$p][$i]['value'] == $o)  {
        array_splice($this->_index[$s][$p], $i, 1);
      }
    }

    if (count($this->_index[$s][$p]) == 0) {
      unset($this->_index[$s][$p]);
    }
    if (count($this->_index[$s]) == 0) {
      unset($this->_index[$s]);
    }

  }

  /**
   * Remove all triples having the supplied subject
   * @param string s the subject of the triple, either a URI or a blank node in the format _:name
   */
  function remove_triples_about($s) {
    unset($this->_index[$s]);
  }


  /**
   * Replace the triples in the graph with those parsed from the supplied RDF/XML
   * @param string rdfxml the RDF/XML to parse
   * @param string base the base URI against which relative URIs in the RDF/XML document will be resolved
   */
  function from_rdfxml($rdfxml, $base='') {
    if ($rdfxml) {
      $this->remove_all_triples();
      $this->add_rdfxml($rdfxml, $base);
    }
  }

  /**
   * Replace the triples in the graph with those parsed from the supplied JSON
   * @see http://n2.talis.com/wiki/RDF_JSON_Specification
   * @param string json the JSON to parse
   */
  function from_json($json) {
    if ($json) {
      $this->remove_all_triples();
      $this->_index = json_decode($json, true);
    }
  }


  /**
   * Add the triples parsed from the supplied JSON to the graph
   * @see http://n2.talis.com/wiki/RDF_JSON_Specification
   * @param string json the JSON to parse
   */
  function add_json($json) {
    if ($json) {
      $json_index = json_decode($json, true);
      $this->_index = $this->merge($this->_index, $json_index);
    }
  }

  /**
   * Add the triples parsed from the supplied RDF to the graph - let ARC guess the input
   * @param string rdf the RDF to parse
   * @param string base the base URI against which relative URIs in the RDF document will be resolved
   * @author Keith Alexander
   */
  function add_rdf($rdf=false, $base='') {
    if ($rdf) {
      $parser = ARC2::getRDFParser();
      $parser->parse($base, $rdf);
      $this->_add_arc2_triple_list($parser->getTriples());
      unset($parser);
    }
  }

  /**
   * Add the triples parsed from the supplied RDF/XML to the graph
   * @param string rdfxml the RDF/XML to parse
   * @param string base the base URI against which relative URIs in the RDF/XML document will be resolved
   */
  function add_rdfxml($rdfxml, $base='') {
    if ($rdfxml) {
      $parser = ARC2::getRDFXMLParser();
      $parser->parse($base, $rdfxml );
      $this->_add_arc2_triple_list($parser->getTriples());
      unset($parser);
    }
  }

  /**
   * Replace the triples in the graph with those parsed from the supplied Turtle
   * @see http://www.dajobe.org/2004/01/turtle/
   * @param string turtle the Turtle to parse
   * @param string base the base URI against which relative URIs in the Turtle document will be resolved
   */
  function from_turtle($turtle, $base='') {
    if ($turtle) {
      $this->remove_all_triples();
      $this->add_turtle($turtle, $base);
    }
  }

  /**
   * Add the triples parsed from the supplied Turtle to the graph
   * @see http://www.dajobe.org/2004/01/turtle/
   * @param string turtle the Turtle to parse
   * @param string base the base URI against which relative URIs in the Turtle document will be resolved
   */
  function add_turtle($turtle, $base='') {
    if ($turtle) {
      $parser = ARC2::getTurtleParser();
      $parser->parse($base, $turtle );
      $this->_add_arc2_triple_list($parser->getTriples());
      unset($parser);
    }
  }


  /**
   * Replace the triples in the graph with those parsed from the supplied RDFa
   * @param string html the HTML containing RDFa to parse
   * @param string base the base URI against which relative URIs in the Turtle document will be resolved
   */
  function from_rdfa($html, $base='') {
    if ($html) {
      $this->remove_all_triples();
      $this->add_rdfa($html, $base);
    }
  }
  /**
   * Add the triples parsed from the supplied RDFa to the graph
   * @param string html the HTML containing RDFa to parse
   * @param string base the base URI against which relative URIs in the Turtle document will be resolved
   */
  function add_rdfa($html, $base='') {
    if ($html) {
      $parser = ARC2::getSemHTMLParser();
      $parser->parse($base, $html );
      $parser->extractRDF('rdfa');    
      $this->_add_arc2_triple_list($parser->getTriples());
      unset($parser);
    }
  }

  /**
   * Add the triples in the supplied graph to the current graph
   * @param SimpleGraph g the graph to read
   */
  function add_graph($g) {
    $triples_were_added = false;
    $index = $g->get_index();
    foreach ($index as $s => $p_list) {
      foreach ($p_list as $p => $o_list) {
        foreach ($o_list as $o_info) {
          if ($this->_add_triple($s, $p, $o_info) ) {
            $triples_were_added = true;
          }
        }
      }
    }
    return $triples_were_added;
  }


  private function _add_arc2_triple_list(&$triples) {
    $bnode_index = array();

    // We can safely preserve bnode labels if the graph is empty, otherwise we need to rewrite them
    $rewrite_bnode_labels = $this->is_empty() ? FALSE : TRUE;

    foreach ($triples as $t) {
      $obj = array();

      if ($rewrite_bnode_labels && $t['o_type'] == 'bnode') {
        if (!array_key_exists($t['o'], $bnode_index)) {
          $bnode_index[$t['o']] = uniqid('_:mor');
        }
        $obj['value'] = $bnode_index[$t['o']];
      }
      else {
        $obj['value'] = $t['o'];
      }

      if ($rewrite_bnode_labels && strpos($t['s'], '_:' ) === 0) {
        if (!array_key_exists($t['s'], $bnode_index)) {
          $bnode_index[$t['s']] = uniqid('_:mor');
        }
        $t['s'] = $bnode_index[$t['s']];
      }

      $t['s'] = $this->map_uri($t['s']);
      $t['p'] = $this->map_uri($t['p']);

      if ($t['o_type'] === 'iri' ) {
        $obj['type'] = 'uri';
      }
      elseif ($t['o_type'] === 'literal1' ||
              $t['o_type'] === 'literal2' ||
              $t['o_type'] === 'long_literal1' ||
              $t['o_type'] === 'long_literal2'
      ) {
        $obj['type'] = 'literal';
      }
      else {
        $obj['type'] = $t['o_type'];
      }



      if ($obj['type'] == 'literal') {
        if ( isset( $t['o_dt'] ) && $t['o_dt'] ) {
          $obj['datatype'] = $t['o_dt'];
        }
        else if ( isset( $t['o_datatype'] ) && $t['o_datatype'] ) {
          $obj['datatype'] = $t['o_datatype'];
        }
        if ( isset( $t['o_lang']) && $t['o_lang'])  {
          $obj['lang'] = $t['o_lang'];
        }
      }
      elseif ($obj['type'] == 'uri') {
        $obj['value'] = $this->map_uri($obj['value']);
      }

      if (!isset($this->_index[$t['s']])) {
        $this->_index[$t['s']] = array();
        $this->_index[$t['s']][$t['p']] = array($obj);
      }
      elseif (!isset($this->_index[$t['s']][$t['p']])) {
        $this->_index[$t['s']][$t['p']] = array($obj);
      }
      else {
        if ( ! in_array( $obj, $this->_index[$t['s']][$t['p']] ) ) {
          $this->_index[$t['s']][$t['p']][] = $obj;
        }
      }
    }
  }


  // until ARC2 upgrades to support RDF/PHP we need to rename all types of "uri" to "iri"
  private function _to_arc_index(&$index) {
    $ret = array();

    foreach ($index as $s => $s_info) {
      $ret[$s] = array();
      foreach ($s_info as $p => $p_info) {
        $ret[$s][$p] = array();
        foreach ($p_info as $o) {
          if (isset($o) && is_array($o)) {
            $o_new = array();
            foreach ($o as $key => $value) {
              if ( $key == 'type' && $value == 'uri' ) {
                $o_new['type'] = 'iri';
              }
              else {
                $o_new[$key] = $value;
              }
            }
            $ret[$s][$p][] = $o_new;
          }
        }
      }
    }
    return $ret;
  }

  /**
   * Tests whether the graph contains the given triple
   * @param string s the subject of the triple, either a URI or a blank node in the format _:name
   * @param string p the predicate URI of the triple
   * @param string o the object of the triple, either a URI or a blank node in the format _:name
   * @return boolean true if the triple exists in the graph, false otherwise
   */
  function has_resource_triple($s, $p, $o) {
    if (array_key_exists($s, $this->_index) ) {
      if (array_key_exists($p, $this->_index[$s]) ) {
        foreach ($this->_index[$s][$p] as $value) {
          if ( ( $value['type'] == 'uri' || $value['type'] == 'bnode') && $value['value'] === $o) {
            return true;
          }
        }
      }
    }

    return false;
  }

  /**
   * Tests whether the graph contains the given triple
   * @param string s the subject of the triple, either a URI or a blank node in the format _:name
   * @param string p the predicate URI of the triple
   * @param string o the object of the triple as a literal value
   * @return boolean true if the triple exists in the graph, false otherwise
   */
  function has_literal_triple($s, $p, $o, $lang = null, $dt = null) {
    if (array_key_exists($s, $this->_index) ) {
      if (array_key_exists($p, $this->_index[$s]) ) {
        foreach ($this->_index[$s][$p] as $value) {
          if ( ( $value['type'] == 'literal') && $value['value'] === $o) {

            if ($lang !== null) {
              return (array_key_exists('lang', $value) && $value['lang'] === $lang);
            }

            if ($dt !== null) {
              return (array_key_exists('datatype', $value) && $value['datatype'] === $dt);
            }
            return true;
          }
        }
      }
    }

    return false;
  }

  /**
   * Fetch the resource values for a given subject and predicate.
   * @param string s the subject to search for
   * @param string p the predicate to search for
   * @return array list of URIs and blank nodes that are the objects of triples with the supplied subject and predicate
   */
  function get_resource_triple_values($s, $p) {
    $values = array();
    if (array_key_exists($s, $this->_index) ) {
      if (array_key_exists($p, $this->_index[$s]) ) {
        foreach ($this->_index[$s][$p] as $value) {
          if ( ( $value['type'] == 'uri' || $value['type'] == 'bnode')) {
            $values[] = $value['value'];
          }
        }
      }
    }
    return $values;
  }

  /**
   * Fetch the literal values for a given subject and predicate.
   * @param string s the subject to search for
   * @param string p the predicate to search for
   * @return array list of literals that are the objects of triples with the supplied subject and predicate
   */
  function get_literal_triple_values($s, $p) {
    $values = array();
    if ( array_key_exists($s, $this->_index)) {
      if (is_array($p)) {
        foreach($p as $p_uri) {
          if(array_key_exists($p_uri, $this->_index[$s]) ) {
            foreach ($this->_index[$s][$p_uri] as $value) {
              if ($value['type'] == 'literal') {
                $values[] = $value['value'];
              }
            }
          }
        }
      }
      else if(array_key_exists($p, $this->_index[$s]) ) {
        foreach ($this->_index[$s][$p] as $value) {
          if ($value['type'] == 'literal') {
            $values[] = $value['value'];
          }
        }
      }
    }

    return $values;
  }


  /**
   * Fetch the values for a given subject and predicate.
   * @param string s the subject to search for
   * @param string p the predicate to search for
   * @return array list of values of triples with the supplied subject and predicate
   */
  function get_subject_property_values($s, $p) {
    $values = array();
    if (! is_array($p)) $p = array($p);
    if (array_key_exists($s, $this->_index) ) {
      foreach ($p as $pinst) {
        if (array_key_exists($pinst, $this->_index[$s]) ) {
          foreach ($this->_index[$s][$pinst] as $value) {
            $values[] = $value;
          }
        }
      }
    }
    return $values;
  }

  /**
   * Fetch a subgraph where all triples have given subject
   * @param string s the subject to search for
   * @return SimpleGraph triples with the supplied subject
   */
  function get_subject_subgraph($s) {
    $sub = new SimpleGraph();
    if (array_key_exists($s, $this->_index) ) {
      $sub->_index[$s] = $this->_index[$s];
    }
    return $sub;
  }

  /**
   * Fetch an array of all the subjects
   * @return array
   */
  function get_subjects() {
    return array_keys($this->_index);
  }


  /**
   * Fetch an array of all the subject that have and rdf type that matches that given
   * @param $t the type to match
   * @return array
   */
  function get_subjects_of_type($t) {
    return $this->get_subjects_where_resource('http://www.w3.org/1999/02/22-rdf-syntax-ns#type', $t);
  }

  /**
   * Fetch an array of all the subjects where the predicate and object match a ?s $p $o triple in the graph and the object is a resource
   * @param $p the predicate to match
   * @param $o the resource object to match
   * @return array
   */
  function get_subjects_where_resource($p, $o) {
    return array_merge($this->get_subjects_where($p, $o, 'uri'), $this->get_subjects_where($p, $o, 'bnode'));
  }

  /**
   * Fetch an array of all the subjects where the predicate and object match a ?s $p $o triple in the graph and the object is a literal value
   * @param $p the predicate to match
   * @param $o the resource object to match
   * @return array
   */
  function get_subjects_where_literal($p, $o) {
    return $this->get_subjects_where($p, $o, 'literal');
  }

  private function get_subjects_where($p, $o, $type)
  {
    $subjects = array();
    foreach ($this->_index as $subject => $properties)
    {
      if (array_key_exists($p, $properties))
      {
        foreach ($properties[$p] as $object)
        {
          if ($object['type'] == $type && $object['value'] == $o)
          {
            $subjects[] = $subject;
            break;
          }
        }
      }
    }
    return $subjects;
  }

  /**
   * Fetch the properties of a given subject and predicate.
   * @param string s the subject to search for
   * @param boolean distinct if true then duplicate properties are included only once (optional, default is true)
   * @return array list of property URIs
   */
  function get_subject_properties($s, $distinct = TRUE) {
    $values = array();
    if (array_key_exists($s, $this->_index) ) {
      foreach ($this->_index[$s] as $prop => $prop_values ) {
        if ($distinct) {
          $values[] = $prop;
        }
        else {
          for ($i = 0; $i < count($prop_values); $i++) {
            $values[] = $prop;
          }
        }
      }
    }
    return $values;
  }


  /**
   * Tests whether the graph contains a triple with the given subject and predicate
   * @param string s the subject of the triple, either a URI or a blank node in the format _:name
   * @param string p the predicate URI of the triple
   * @return boolean true if a matching triple exists in the graph, false otherwise
   */
  function subject_has_property($s, $p) {
    if (array_key_exists($s, $this->_index) ) {
      return (array_key_exists($p, $this->_index[$s]) );
    }
    return false;
  }

  /**
   * Tests whether the graph contains a triple with the given subject
   * @param string s the subject of the triple, either a URI or a blank node in the format _:name
   * @return boolean true if the graph contains any triples with the specified subject, false otherwise
   */
  function has_triples_about($s) {
    return array_key_exists($s, $this->_index);
  }


  /**
   * Removes all triples with the given subject and predicate
   * @param string s the subject of the triple, either a URI or a blank node in the format _:name
   * @param string p the predicate URI of the triple
   */
  function remove_property_values($s, $p) {
    unset($this->_index[$s][$p]);
  }

  /**
   * Clears all triples out of the graph
   */
  function remove_all_triples() {
    $this->_index = array();
  }

  /**
   * Tests whether the graph contains any triples
   * @return boolean true if the graph contains no triples, false otherwise
   */
  function is_empty() {
    return ( count($this->_index) == 0);
  }


  function get_label($resource_uri, $capitalize = false, $use_qnames = FALSE) {
    return $this->_labeller->get_label($resource_uri, $this, $capitalize, $use_qnames);
  }

  function get_inverse_label($resource_uri, $capitalize = false, $use_qnames = FALSE) {
    return $this->_labeller->get_inverse_label($resource_uri, $this, $capitalize, $use_qnames);
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
      $text = $this->get_first_literal($resource_uri,'http://purl.org/dc/terms/abstract', '', 'en');
    }
    if ( strlen($text) == 0) {
      $text = $this->get_first_literal($resource_uri,'http://purl.org/vocab/bio/0.1/olb', '', 'en');
    }
    return $text;
  }



  function reify($resources, $nodeID_prefix='Statement')
  {
    $RDF = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#';
    $reified = array();
    $statement_no = 1;
    foreach($resources as $uri => $properties){
      foreach($properties as $property => $objects){
        foreach($objects as $object){
          while(!isset($statement_nodeID) OR isset($resources[$statement_nodeID]) OR isset($reified[$statement_nodeID]))
          {
            $statement_nodeID = '_:'.$nodeID_prefix.($statement_no++);
          }
          $reified[$statement_nodeID]= array(
            $RDF.'type'=>array(
                array('type'=>'uri','value'=>$RDF.'Statement')
                  ),
            $RDF.'subject' => array(array('type'=>  (substr($uri,0,2)=='_:')? 'bnode' : 'uri', 'value'=>$uri)),
            $RDF.'predicate' => array(array('type'=>'uri','value'=>$property)),
            $RDF.'object' => array($object),
                );

        }
      }
    }

    return ($reified);
  }

  /**
   * diff
   * returns a simpleIndex consisting of all the statements in array1 that weren't found in any of the subsequent arrays
   * @param array1, array2, [array3, ...]
   * @return array
   * @author Keith
   **/
    function diff(){
      $indices = func_get_args();
      if(count($indices)==1){
        array_unshift($indices, $this->_index);
      }
      $base = array_shift($indices);
      if (count($base) === 0) return array();
      $diff = array();

      foreach($base as $base_uri => $base_ps) {
        foreach($indices as $index){
          if(!isset($index[$base_uri])) {
            $diff[$base_uri] = $base_ps;
          }
          else {
            foreach($base_ps as $base_p => $base_obs) {
              if(!isset($index[$base_uri][$base_p])) {
                $diff[$base_uri][$base_p] = $base_obs;
              }
              else {
                foreach($base_obs as $base_o){
                  if(!in_array($base_o, $index[$base_uri][$base_p])) {
                    $diff[$base_uri][$base_p][]=$base_o;
                  }
                }
              }
            }
          }
        }
      }

      return $diff;
    }

/**
 * merge
 * merges all  rdf/json-style arrays passed as parameters
 * @param array1, array2, [array3, ...]
 * @return array
 * @author Keith
 **/

  function merge(){

    $old_bnodeids = array();
    $indices = func_get_args();
    if(count($indices)==1){
      array_unshift($indices, $this->_index);
    }

    $current = array_shift($indices);
    foreach($indices as $newGraph)
    {
      if (isset($newGraph) && is_array($newGraph)) {
        foreach($newGraph as $uri => $properties)
        {
          /* Make sure that bnode ids don't overlap:
          _:a in g1 isn't the same as _:a in g2 */

          if(substr($uri,0,2)=='_:')//bnode
          {
            $old_id = $uri;
            $count = 1;

            while(isset($current[$uri]) OR
            ( $old_id!=$uri AND isset($newGraph[$uri]) )
            OR isset($old_bnodeids[$uri])
            )
            {
              $uri.=$count++;
            }

            if($old_id != $uri) $old_bnodeids[$old_id] = $uri;
          }

          if (isset($properties) && is_array($properties)) {
            foreach($properties as $property => $objects)
            {
              if (isset($objects) && is_array($objects)) {
                foreach($objects as $object)
                {
                  /* make sure that the new bnode is being used*/
                  if(isset($object['type']) && $object['type']=='bnode')
                  {
                    $bnode = $object['value'];

                    if(isset($old_bnodeids[$bnode])) $object['value'] = $old_bnodeids[$bnode];
                    else //bnode hasn't been transposed
                    {
                        $old_bnode_id = $bnode;
                        $count=1;
                        while(isset($current[$bnode]) OR
                        ( $object['value']!=$bnode AND isset($newGraph[$bnode]) )
                        OR isset($old_bnodeids[$uri])
                        )
                        {
                          $bnode.=$count++;
                        }

                        if($old_bnode_id!=$bnode) $old_bnodeids[$old_bnode_id] = $bnode;
                        $object['value'] = $bnode;
                    }
                  }

                  if(!isset($current[$uri][$property]) OR !in_array($object, $current[$uri][$property]))
                  {
                    $current[$uri][$property][]=$object;
                  }
                }
              }
            }
          }
        }
      }
    }
    return $current;
  }

  function replace_resource($look_for, $replace_with) {
    $remove_list_resources = array();
    $remove_list_literals = array();
    $add_list_resources = array();
    $add_list_literals = array();
    foreach ($this->_index as $s => $p_list) {
      if ($s == $look_for) {
        foreach ($p_list as $p => $o_list) {
          if ($p == $look_for) {
            foreach ($o_list as $o_info) {
              if ($o_info['type'] == 'literal') {
                $lang = array_key_exists('lang', $o_info) ? $o_info['lang'] : null;
                $dt = array_key_exists('datatype', $o_info) ? $o_info['datatype'] : null;

                $remove_list_literals[] = array($look_for, $look_for, $o_info['value'], $lang, $dt);
                $add_list_literals[] = array($replace_with, $replace_with, $o_info['value'], $lang, $dt);
              }
              else  {
                if ($o_info['value'] == $look_for) {
                  $remove_list_resources[] = array($look_for, $look_for, $look_for);
                  $add_list_resources[] = array($replace_with, $replace_with, $replace_with);
                }
                else {
                  $remove_list_resources[] = array($look_for, $look_for, $o_info['value']);
                  $add_list_resources[] = array($replace_with, $replace_with, $o_info['value']);
                }
              }
            }
          }
          else {
            foreach ($o_list as $o_info) {
              if ($o_info['type'] == 'literal') {
                $lang = array_key_exists('lang', $o_info) ? $o_info['lang'] : null;
                $dt = array_key_exists('datatype', $o_info) ? $o_info['datatype'] : null;

                $remove_list_literals[] = array($look_for, $p, $o_info['value'], $lang, $dt);
                $add_list_literals[] = array($replace_with, $p, $o_info['value'], $lang, $dt);
              }
              else  {
                if ($o_info['value'] == $look_for) {
                  $remove_list_resources[] = array($look_for, $p, $look_for);
                  $add_list_resources[] = array($replace_with, $p, $replace_with);
                }
                else {
                  $remove_list_resources[] = array($look_for, $p, $o_info['value']);
                  $add_list_resources[] = array($replace_with, $p, $o_info['value']);
                }
              }
            }
          }
        }
      }
      else {

        foreach ($p_list as $p => $o_list) {
          if ($p == $look_for) {
            foreach ($o_list as $o_info) {
              if ($o_info['type'] == 'literal') {
                $lang = array_key_exists('lang', $o_info) ? $o_info['lang'] : null;
                $dt = array_key_exists('datatype', $o_info) ? $o_info['datatype'] : null;

                $remove_list_literals[] = array($s, $look_for, $o_info['value'], $lang, $dt);
                $add_list_literals[] = array($s, $replace_with, $o_info['value'], $lang, $dt);
              }
              else  {
                if ($o_info['value'] == $look_for) {
                  $remove_list_resources[] = array($s, $look_for, $look_for);
                  $add_list_resources[] = array($s, $replace_with, $replace_with);
                }
                else {
                  $remove_list_resources[] = array($s, $look_for, $o_info['value']);
                  $add_list_resources[] = array($s, $replace_with, $o_info['value']);
                }
              }
            }
          }
          else {
            foreach ($o_list as $o_info) {
              if ($o_info['type'] != 'literal' && $o_info['value'] == $look_for) {
                $remove_list_resources[] = array($s, $p, $look_for);
                $add_list_resources[] = array($s, $p, $replace_with);
              }
            }
          }
        }
      }
    }

    foreach ($remove_list_resources as $t) {
      $this->remove_resource_triple($t[0], $t[1], $t[2]);
    }
    foreach ($add_list_resources as $t) {
      $this->add_resource_triple($t[0], $t[1], $t[2]);
    }

    foreach ($remove_list_literals as $t) {
      $this->remove_literal_triple($t[0], $t[1], $t[2], $t[3], $t[4]);
    }
    foreach ($add_list_literals as $t) {
      $this->add_literal_triple($t[0], $t[1], $t[2], $t[3], $t[4]);
    }

  }

  /**
   * Read RDF from the supplied URIs and add to the current graph
   * @param Any uri_list a URI, or array of URIs to fetch
   * @param boolean include_response when TRUE include RDF about each retrieval operation
   */
  function read_data($uri_list, $include_response = FALSE) {
    if (empty( $this->request_factory) ) {
      $this->request_factory = new HttpRequestFactory();
    }

    if (! is_array($uri_list)) {
      $uri_list= array($uri_list);
    }

    $requests = array();
    foreach ($uri_list as $uri) {
      $request = $this->request_factory->make( 'GET', $uri );
      $request->set_accept('application/json, text/turtle, text/n3, text/rdf+n3, application/x-turtle, application/rdf+xml;q=0.8,application/xml;q=0.6, */*');
      $request->execute_async();
      $requests[] = $request;
    }

    foreach ($requests as $request) {
      $response = $request->get_async_response();

      if ($include_response) {
        $this->add_turtle($response->to_turtle());
      }
      if ($response->is_success()) {
        if (    strpos($response->headers['content-type'], 'application/rdf+xml') === 0
             || strpos($response->headers['content-type'], 'application/xml') === 0) {
          $this->add_rdfxml($response->body);
        }
        else if (    strpos($response->headers['content-type'], 'text/turtle') === 0
                  || strpos($response->headers['content-type'], 'text/n3') === 0
                  || strpos($response->headers['content-type'], 'text/rdf+n3') === 0
                  || strpos($response->headers['content-type'], 'application/x-turtle') === 0) {
          $this->add_turtle($response->body);
        }
        else if (    strpos($response->headers['content-type'], 'application/json') === 0) {
          $this->add_json($response->body);
        }
      }
    }
  }

  function get_list_values($listUri) {
      $array = array();
      while(!empty($listUri) AND $listUri != RDF_NIL){
          $array[]=$this->get_first_resource($listUri, RDF_FIRST);
          $listUri = $this->get_first_resource($listUri, RDF_REST);
      }
      return $array;
  }

  function get_sequence_values($sequenceUri) {
          $triples = $this->get_index();
          $properties = array();

          if (isset($triples[$sequenceUri]))
          {
              foreach ($triples[$sequenceUri] as $property => $objects)
              {
                  if (strpos($property, RDF_) !== false)
                  {
                      $key = substr($property, strpos($property, '_') + 1  );
                      $value = $this->get_first_resource($sequenceUri, $property);


                      if (empty($value))
                      {
                      $value = $this->get_first_literal($sequenceUri, $property);
                      }

                      $properties[$key] = $value;
                  }
              }

              ksort($properties, SORT_NUMERIC);
          }

          $values = array();

          foreach($properties as $key=>$value)
          {
              $values[] = $value;
          }

          return $values;
      }

}

