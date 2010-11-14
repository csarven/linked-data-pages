<?php
/**
 * Base class for all URI dispatching, requesting and responding
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 3
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.

 * @category  Action
 * @package   LATC
 * @author    Sarven Capadisli <sarven.capadisli@deri.org>
 * @copyright 2010 Digital Enterprise Research Institute
 * @license   http://www.fsf.org/licensing/licenses/gpl-3.0.html GNU General Public License version 3.0
 * @link      http://deri.org/
 */

require_once PAGET_DIR . 'paget_urispace.class.php';
require_once PAGET_DIR . 'paget_simplepropertylabeller.class.php';
require_once PAGET_DIR . 'paget_storedescribegenerator.class.php';
require_once PAGET_DIR . 'paget_storebackedurispace.class.php';

/**
 * TODO:
 * Move these classes into their own files.
 */


/**
 * Primary class that figures out the request and prepares a response
 */
class LATC_UriSpace extends PAGET_StoreBackedUriSpace
{
    var $siteConfig;

    function __construct($sC)
    {
        $this->siteConfig = $sC;
        parent::__construct(STORE_URI);
    }


    function get_resource($request)
    {
        $request_uri = $request->uri;

        if (preg_match('~^(.+)\.(html|rdf|json|turtle)$~', $request->full_path, $m)) {
            $base_path = $m[1];
            $type      = $m[2];

            if ($base_path == $this->_base_path.'~search') {
                $query  = isset($request->data["query"]) ? $request->data["query"] : '';
                $offset = isset($request->data["offset"]) ? $request->data["offset"] : '0';

                $desc = new PAGET_StoreSearch($request_uri, $type, $this->_store_uri, $query, 30, $offset);
                $desc->set_template($this->_description_template);

                return $desc;
            } else if ($base_path == $this->_base_path. '~browse') {
                if (! defined('AUTH_USER') && ! defined('AUTH_PWD')) {
                    return null;
                }

                $token = isset($request->data["token"]) ? $request->data["token"] : null;

                $desc = new PAGET_StoreOAI($request_uri, $type, $this->_store_uri, $token);
                $desc->set_template($this->_description_template);

                return $desc;
            } else {
                $resource_uri = $this->siteConfig->getRemoteURIFromCurrentRequest();

                if (isset($this->_static_data[$resource_uri])) {
                    $desc = new PAGET_FileBackedResourceDescription($request_uri, $resource_uri, $type, $this->_static_data[$resource_uri], 'rdfxml'); 
                } else {
                    $desc = new LATC_ResourceDescription($request_uri, $resource_uri, $type, $this->_store_uri, $this->siteConfig);
                }

                $desc->set_template($this->_description_template);

                foreach ($this->_ns as $short_name => $uri) {
                    $desc->set_namespace_mapping($short_name, $uri);
                }

                if ($desc->is_valid()) {
                    return $desc;
                }
            }
        } else {
            return new PAGET_AbstractResource($request_uri);
        }

        return null;
    }


    function get_description($uri, $resource)
    {
        return new LATC_ResourceDescription($uri, $resource, 'rdf');
    }
}



/**
 * Figures out how to respond; prepares a SPARQL query to be used, 
 * reads the triples from the response, maps the URIs, 
 * assigns a template to be used for the HTML response
 */
class LATC_ResourceDescription extends PAGET_ResourceDescription
{
    var $siteConfig;

    function __construct($rt, $re, $te, $su, $sC)
    {
        $this->siteConfig = $sC;
        parent::__construct($rt, $re, $te, $su);
    }


    /*
     * Seeds the triples in the description using provided Store URI and 
     * bounded description type.
     *
     * @return array
     */
    function get_generators()
    {
        /**
         * FIXME:
         * 'foo' is a bit hacky. Double check with Store::describe().
         * While making sure it is a DESCRIBE. do we want to use scbd?
         * We might control this value from site config.
         *
         * I'm not sure if the above FIXME still applies. I'll check later. :)
         */

        $eQ = $this->siteConfig->getEntityQuery();

        return array(new LATC_StoreDescribeGenerator(STORE_URI, $eQ, $this->siteConfig));
    }


    /**
     * Prepares the template to be used based on entity configuration
     */
    function get_html(&$urispace, &$request)
    {
        $tmpl = $this->_template;
        if ( null == $tmpl ) {
            $tmpl = $urispace->get_template($request);
        }
        if ( null == $tmpl ) {
            $sC = $this->siteConfig->getConfig();

            $entityId = $this->siteConfig->getEntityId();

            $tmpl = SITE_DIR . 'templates/' . $sC['entity'][$entityId]['template'];
        }

        $template = new SITE_Template($tmpl, $this, $urispace, $request, $this->siteConfig);

        return $template->execute();
    }


    /**
     * Takes the request URI and maps it to a local equivalent (in RDF store)
     * 
     * @return string
     */
    function map_uri($uri)
    {
        if (isset($_SERVER["HTTP_HOST"])) {
            if (preg_match('#http://([^/]+)/#i', $uri, $m)) {
                $c = $this->siteConfig->getConfig();

                if ( $_SERVER["HTTP_HOST"] != $m[1] && isset($c['server'][$m[1]])) {
                    $r = str_replace($m[1], $c['server'][$m[1]], $uri);
                    return $r;
                } else {
                    return $uri;
                }
            }
        }
    }


    /**
     * Initiates functions to get the requested resource results
     * into a local index.
     * 
     * @return nothing
     */
    function read_triples() {
        $resources_mapped = $resources = $this->get_resources();

        foreach ($resources_mapped as $key => $value) {
            $resources_mapped[$key] = $this->map_uri($value);
        }

        if (count($resources) > 0) {
            $this->_primary_resource = $resources_mapped[0];
        }

        $this->add_representation_triples();

        $this->_is_valid = false;

        foreach ($resources as $key => $value) {
            $this->add_resource_triple($this->_uri, FOAF_TOPIC, $resources_mapped[$key]);

            $generators = $this->get_generators();
            foreach ($generators as $generator) {
                $generator->add_triples($resources[$key], $this);
            }

            //XXX: Is this hackish?
            if (array_key_exists($resources_mapped[$key], $this->get_index())
                || array_key_exists($resources_mapped[$key], $this->get_inverse_index())) {
                $this->_is_valid = true;
            }
        }

        $augmentors = $this->get_augmentors();
        foreach ($augmentors as $augmentor) {
            $augmentor->process($this);
        }
    }
}



/**
 * Figures out how to add the RDF triples to the index from the SPARQL query result.
 *
 */
class LATC_StoreDescribeGenerator extends PAGET_StoreDescribeGenerator
{
    var $siteConfig;

    function __construct($su, $eQ, $sC)
    {
        $this->siteConfig = $sC;
        parent::__construct($su, $eQ);
    }

    /**
    * XXX: Should we always ask for rdf(/xml) response?
    */
    function add_triples($resource_uri, &$desc)
    {
        $store = new LATC_Store($this->_store_uri, $this->siteConfig);

        $response = $store->describe($resource_uri, $this->_type, 'rdf');

        if ($response->is_success()) {
            //XXX: This is sort of another place to map URIs. Revisit after paget issues

            $desc->add_rdf($response->body);
        }
    }
}


/**
 * Used for LATC specific templates.
 * TODO: render()
 *
 */
class LATC_Template extends PAGET_Template
{
    var $siteConfig;

    function __construct($template_filename, $desc, $urispace, $request, $sC)
    {
        $this->siteConfig = $sC;
        parent::__construct($template_filename, $desc, $urispace, $request);

        $this->table_widget = new LATC_TableDataWidget($this->desc, $this, $urispace);
    }


    /**
     * Grab triples from the index
     */
    function getTriples($subjects = null, $properties = null, $objects = null)
    {
        $triples = array();

        $index = $this->desc->get_index();

        if ($subjects != null && !is_array($subjects)) {
            $subjects = array($subjects);
        }
        if ($properties != null && !is_array($properties)) {
            $properties = array($properties);
        }
        if ($objects != null && !is_array($objects)) {
            $objects = array($objects);
        }

        foreach($index as $s => $po) {
            if (!is_null($subjects) && !in_array($s, $subjects)) {
                continue;
            }

            $po_candidates = array();
            $p_count = 0;

            foreach ($po as $p => $o) {
                if (!is_null($properties) && !in_array($p, $properties)) {
                    continue;
                }

                $p_count += 1;

                if (count($o > 0)) {
                    foreach ($o as $o_k) {
                        if (!is_null($objects) && !in_array($o_k['value'], $objects)) {
                            continue;
                        }

                        $po_candidates[$p] = $o;
                    }
                }
            }

            if ($p_count == count($properties)) {
                $triples[$s] = $po_candidates;
            }
        }

        asort($triples);
        return $triples;
    }


    /**
     * A generic method to render properties.
     * XXX: Status: testing
     * TODO: Output <http://site/property/foo> <rdf:type> <rdf:Property>
     */
    function renderProperty()
    {
        $subjects   = null;
        $properties = $this->desc->get_primary_resource_uri();
        $objects    = null;

        $triples = $this->getTriples($subjects, $properties, $objects);

        $r = '';
        $r .= "\n".'<dl id="subjects-with-this-property">';
        $r .= "\n".'<dt>Some subjects with this property</dt>';
        $r .= "\n".'<dd>';
        $r .= "\n".'<ul>';
        foreach($triples as $triple => $po) {
            //TODO: Use rdfs:label if available.
            $r .= "\n".'<li><a href="'.$triple.'">'.$triple.'</a></li>';
        }
        $r .= "\n".'</ul>';
        $r .= "\n".'</dd>';
        $r .= "\n".'</dl>';
        return $r;
    }


    function indexToRDFXML()
    {
        return $this->desc->to_rdfxml();
    }
}



/**
 * LATC's lean table for basic key value pair output
 *
 */
class LATC_TableDataWidget extends PAGET_TableDataWidget
{
    function format_table(&$data)
    {
        $resource_uri = $this->desc->get_primary_resource_uri();

        $ret = '';
        if (count($data) > 0) {
            $ret .= "\n".'<table class="propertiesobjects">';
            $ret .= "\n".'<thead><tr><th>Subject</th><td>'.'<a href="'.$resource_uri.'">'.$resource_uri.'</a></td></tr></thead>';
            $ret .= "\n".'<tbody>';
            $ret .= "\n".'<tr><th>Property</th><th>Object</th></tr>';
            foreach ($data as $item) {
                $ret .= "\n".'<tr><td>' . $item['label'] . '</td><td>' . $item['value'] . '</td></tr>';
            }
            $ret .= "\n".'</tbody>';
            $ret .= "\n".'</table>';
        }

        return $ret;
    }
}


/**
 * Overrides Moriarty's default store URI
 *
 */
class LATC_Store extends Store
{
    var $siteConfig;

    function __construct($su, $sC)
    {
        $this->siteConfig = $sC;
        parent::__construct($su);
    }


   /**
    * Obtain a reference to this store's sparql service
    *
    * @return SparqlService
    */
    function get_sparql_service()
    {
        return new SITE_SparqlServiceBase($this->uri, $this->credentials, $this->request_factory, $this->siteConfig);
    }
}



/**
 * Controls which SPARQL query to use
 * TODO: More query types
 */
class LATC_SparqlServiceBase extends SparqlServiceBase
{
    var $siteConfig;

    function __construct($tu, $tc, $trq, $sC)
    {
        $this->siteConfig = $sC;
        parent::__construct($tu, $tc, $trq);
    }


    function describe($uri, $type = 'cbd', $output = OUTPUT_TYPE_RDF)
    {
        if (is_array($uri)) {
            $query = "DESCRIBE <" . implode('> <', $uri) . ">";
        } else {
            switch($type) {
                case 'scbd':
                    $query = "CONSTRUCT {<$uri> ?p ?o . ?s ?p2 <$uri> .} WHERE { {<$uri> ?p ?o .} UNION {?s ?p2 <$uri> .} }";
                    break;

                case 'lcbd':
                    $query = "PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#> CONSTRUCT {<$uri> ?p ?o . ?o rdfs:label ?label . ?o rdfs:comment ?comment . ?o <http://www.w3.org/2004/02/skos/core#prefLabel> ?plabel . ?o rdfs:seeAlso ?seealso.} WHERE {<$uri> ?p ?o . OPTIONAL { ?o rdfs:label ?label .} OPTIONAL {?o <http://www.w3.org/2004/02/skos/core#prefLabel> ?plabel . } OPTIONAL {?o rdfs:comment ?comment . } OPTIONAL {?o rdfs:seeAlso ?seealso.}}";
                    break;

                case 'slcbd':
                    $query = "PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#> CONSTRUCT {<$uri> ?p ?o . ?o rdfs:label ?label . ?o rdfs:comment ?comment . ?o rdfs:seeAlso ?seealso. ?s ?p2 <$uri> . ?s rdfs:label ?label . ?s rdfs:comment ?comment . ?s rdfs:seeAlso ?seealso.} WHERE { { <$uri> ?p ?o . OPTIONAL {?o rdfs:label ?label .} OPTIONAL {?o rdfs:comment ?comment .} OPTIONAL {?o rdfs:seeAlso ?seealso.} } UNION {?s ?p2 <$uri> . OPTIONAL {?s rdfs:label ?label .} OPTIONAL {?s rdfs:comment ?comment .} OPTIONAL {?s rdfs:seeAlso ?seealso.} } }";
                    break;

                case 'cbd': default:
                    $query = "DESCRIBE <$uri>";
                    break;
            }
        }

        return $this->graph($query, $output);
    }

}
