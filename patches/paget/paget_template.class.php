<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "paget_termwidget.class.php";
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "paget_datawidget.class.php";
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "paget_tabledatawidget.class.php";
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "paget_historywidget.class.php";
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "paget_ontologywidget.class.php";
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "paget_rsswidget.class.php";
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "paget_seqwidget.class.php";
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "paget_literalwidget.class.php";
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "paget_bagwidget.class.php";


class PAGET_Template {
  var $desc;
  var $template_filename;
  var $urispace;
  var $request;
  var $excludes = array();
  function __construct($template_filename, $desc, $urispace, $request) {
    $this->desc = $desc;  
    $this->template_filename = $template_filename;  
    $this->urispace = $urispace;  
    $this->request = $request;  

    $this->table_widget = new PAGET_TableDataWidget($this->desc, $this, $urispace);
    $this->seq_widget = new PAGET_SeqWidget($this->desc, $this, $urispace);
    $this->bag_widget = new PAGET_BagWidget($this->desc, $this, $urispace);
    $this->rss_widget = new PAGET_RSSWidget($this->desc, $this, $urispace);
    $this->ontology_widget = new PAGET_OntologyWidget($this->desc, $this, $urispace);
    $this->term_widget = new PAGET_TermWidget($this->desc, $this, $urispace);
    $this->literal_widget = new PAGET_LiteralWidget($this->desc, $this, $urispace);

  }

  function execute() {
     ob_start();
    try {
      include($this->template_filename);
      $buffer = ob_get_clean();
      return $buffer;
    } 
    catch (Exception $ex) {
      ob_end_clean();
      throw $ex;
    }   
  }

  function get_title($resource_uri = null) {
    return $this->desc->get_label($resource_uri, $this);
  }  
  
  function get_description($resource_uri = null) {
    return $this->desc->get_description($resource_uri, $this);
  }    



  function render($resource_info, $inline = FALSE, $brief = FALSE) {
    if ($resource_info['type'] == 'bnode' || $resource_info['type'] == 'uri') {
      $resource_uri = $resource_info['value'];
      if ( $this->desc->has_resource_triple($resource_uri, RDF_TYPE, RDF_PROPERTY) || $this->desc->has_resource_triple($resource_uri, RDF_TYPE, RDFS_CLASS) ) {
        $widget = $this->term_widget;
      }
      else if ( $this->desc->has_resource_triple($resource_uri, RDF_TYPE, 'http://www.w3.org/2002/07/owl#Ontology')  ) {
        $widget = $this->ontology_widget;
      }
      else if ( $this->desc->has_resource_triple($resource_uri, RDF_TYPE, 'http://purl.org/rss/1.0/channel')  ) {
        $widget = $this->rss_widget;
      }
      else if ( $this->desc->has_resource_triple($resource_uri, RDF_TYPE, 'http://www.w3.org/1999/02/22-rdf-syntax-ns#Seq')  ) {
        $widget = $this->seq_widget;
      }
      else if ( $this->desc->has_resource_triple($resource_uri, RDF_TYPE, 'http://www.w3.org/1999/02/22-rdf-syntax-ns#Bag')  ) {
        $widget = $this->bag_widget;
      }
      else {
        $widget = $this->table_widget;
      }  
    }
    else {
      $widget = $this->literal_widget;
    }
    return $widget->render($resource_info, $inline, $brief);
  }



  function exclude($resource_uri, $property_uri) {
    $this->excludes[$resource_uri . ' ' . $property_uri] = 1;
  }

  function is_excluded($resource_uri, $property_uri) {
    return array_key_exists($resource_uri . ' ' . $property_uri, $this->excludes);
  }

}
