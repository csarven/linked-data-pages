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
 * @package   LDP
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
class LDP_UriSpace extends PAGET_StoreBackedUriSpace
{
    var $sC;

    function __construct($sC)
    {
        $this->sC = $sC;

        parent::__construct(STORE_URI);
    }


    function get_resource($request)
    {
        $request_uri = $request->uri;

        if (preg_match('~^(.+)\.(html|rdf|json|turtle|csv)$~', $request->full_path, $m)) {
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
                $resource_uri = $this->sC->getRemoteURIFromCurrentRequest();

                if (isset($this->_static_data[$resource_uri])) {
                    $desc = new PAGET_FileBackedResourceDescription($request_uri, $resource_uri, $type, $this->_static_data[$resource_uri], 'rdfxml');
                } else {
                    $desc = new LDP_ResourceDescription($request_uri, $resource_uri, $type, $this->_store_uri, $this->sC);
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

}



/**
 * Figures out how to respond; prepares a SPARQL query to be used, 
 * reads the triples from the response, maps the URIs, 
 * assigns a template to be used for the HTML response
 */
class LDP_ResourceDescription extends PAGET_ResourceDescription
{
    var $sC;

    function __construct($rt, $re, $te, $su, $sC)
    {
        $this->sC = $sC;
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
        $eQ = $this->sC->getEntityQuery();

        return array(new LDP_StoreDescribeGenerator(STORE_URI, $eQ, $this->sC));
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
            $c = $this->sC->getConfig();

            $entitySetId = $this->sC->getEntitySetId();

            $tmpl = SITE_DIR . 'templates/' . $c['entity'][$entitySetId]['template'];
        }

        $template = new SITE_Template($tmpl, $this, $urispace, $request, $this->sC);

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
                $c = $this->sC->getConfig();

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
class LDP_StoreDescribeGenerator extends PAGET_StoreDescribeGenerator
{
    var $sC;

    function __construct($su, $eQ, $sC)
    {
        $this->sC = $sC;
        parent::__construct($su, $eQ);
    }

    function add_triples($resource_uri, &$desc)
    {
        $store = new LDP_Store($this->_store_uri, $this->sC);

        $response = $store->describe($resource_uri, $this->_type, $desc->_type);

        if ($response->is_success()) {
            //XXX: This is sort of another place to map URIs. Revisit after paget issues

            $desc->add_rdf($response->body);
        }
    }
}



class LDP_SimplePropertyLabeller extends PAGET_SimplePropertyLabeller
{
    function __construct()
    {
        parent::__construct();
    }
}


/**
 * Used for LDP specific templates.
 * TODO: render()
 *
 */
class LDP_Template extends PAGET_Template
{
    var $sC;

    function __construct($template_filename, $desc, $urispace, $request, $sC)
    {
        $this->sC = $sC;

        parent::__construct($template_filename, $desc, $urispace, $request);

        $this->table_widget = new LDP_TableDataWidget($this->desc, $this, $urispace);
    }


    /**
     * Finds triples in an index. If an index (a multi-dimensional array) is not provided,
     * it will use the internal query result. Null acts as a wildcard.
     * e.g., $subjects = array('a', 'b'); $properties = null; $objects = ('c', 'd');
     * would try to find these triples:
     *   a, *, c
     *   a, *, d
     *   b, *, c
     *   b, *, d
     *
     * @return array
     */
    function getTriples($subjects = null, $properties = null, $objects = null, $index = null)
    {
        $triples = $t = array();

        if (is_null($index)) {
            $index = $this->desc->get_index();
        }

        if (!is_null($subjects) && !is_array($subjects)) {
            $subjects = array($subjects);
        }
        if (!is_null($properties) && !is_array($properties)) {
            $properties = array($properties);
        }
        if (!is_null($objects) && !is_array($objects)) {
            $objects = array($objects);
        }

        foreach($index as $s => $po) {
            $s_candidate = null;

            /**
             * These ifs act as a wildcard or a match. Otherwise we skip
             */
            if (empty($subjects) || in_array($s, $subjects)) {
                $s_candidate = $s;

                foreach($po as $p => $o) {
                    $p_candidate = null;

                    if (empty($properties) || in_array($p, $properties)) {
                        $p_candidate = $p;

                        foreach ($o as $o_key) {
                            $o_candidate = null;

                            if (empty($objects) || in_array($o_key['value'], $objects)) {
                                $o_candidate = $o;

                                $triples[$s_candidate][$p_candidate] = $o_candidate;
                            }
                        }
                    }
                }
            }
        }

        //XXX: Is there simpler way to do this natural sort for mutli-dimensional array?
        $triples_keys = array_keys($triples);
        natsort($triples_keys);
        foreach($triples_keys as $key => $value) {
            $t[$value] = $triples[$value];
        }

        return $t;
    }


    function getTriplesOfType ($object = null)
    {
        $c = $this->sC->getConfig();

        //TODO:
        if (!is_null($object)) {
        }

        $subjects = null;
        $properties = $c['prefixes']['rdf'].'type';
        $objects    = array($object);
        $triples = $this->getTriples($subjects, $properties, $objects);

        $subjects = array_keys($triples);
        $properties = $c['prefixes']['skos'].'prefLabel';
        $objects    = null;
        $triples_propertyLabels = $this->getTriples($subjects, $properties, $objects);

        $triples = array_merge_recursive($triples, $triples_propertyLabels);

        return $triples;
    }


    /**
     * Returns object value given subject and property.
     * This method returns the first matching object from triple.
     *
     * @subject paramater value is a full URI
     * @property paramater is a qname
     *
     * @return string
     */
    function getValue($subject, $property)
    {
        $c = $this->sC->getConfig();
        $triples = array();

        if (preg_match("#(.*):(.*)#", $property, $m)) {
            $prefixName = $c['prefixes'][$m[1]];
            if (isset($prefixName)) {
                $triples = $this->getTriples($subject, $prefixName.$m[2]);

                if (count($triples) > 0) {
                    return $triples[$subject][$prefixName.$m[2]][0]['value'];
                }
            }
        }

        return;
    }


    /**
     * A generic method to render properties.
     * XXX: Status: testing.
     * TODO: Output <http://site/property/foo> <rdf:type> <rdf:Property> at least
     * Ideally we want to render what the store knows about this property, and
     * make it more human friendly so the user can understand its purpose and
     * see example relationships (where it is used).
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
        foreach($triples as $s => $po) {
            //TODO: Use rdfs:label if available.
            $r .= "\n".'<li><a href="'.$s.'">'.$s.'</a></li>';
        }
        $r .= "\n".'</ul>';
        $r .= "\n".'</dd>';
        $r .= "\n".'</dl>';
        return $r;
    }


    /**
     * A generic output for resources that is an instance of a Class
     */
    function renderClass()
    {
        $sC = $this->sC;
        $c  = $this->sC->getConfig();

        $resource_uri = $this->desc->get_primary_resource_uri();
        $r = '';

        //XXX: We first output what we know about the Class itself
        $subjects   = $resource_uri;
        $properties = null;
        $objects    = null;

        $triples = $this->getTriples($subjects, $properties, $objects);


        //TODO: Make this a bit more generic. Perhaps use LDP_TableDataWidget::format_table ?
        $r .= "\n".'<dl id="about-this-class">';
        foreach($triples as $s => $po) {
            if ($sC->hasProperty('rdfs:label', $po)) {
                $r .= "\n".'<dt>About</dt>';
                $r .= "\n".'<dd>'.$po[$c['prefixes']['rdfs'].'label'][0]['value'].'</dd>';
            }

            if ($sC->hasProperty('rdfs:comment', $po)) {
                $r .= "\n".'<dt>Comment</dt>';
                $r .= "\n".'<dd>'.$po[$c['prefixes']['rdfs'].'comment'][0]['value'].'</dd>';
            }

            if ($sC->hasProperty('rdfs:subClassOf', $po)) {
                $r .= "\n".'<dt>Semantics</dt>';
                $r .= "\n".'<dd>Being a member of this class implies also being a member of '.$this->term_widget->link_uri($sC->object('rdfs:subClassOf', $po)).'</dd>';
            }
        }
        $r .= "\n".'</dl>';

        //XXX: We now output a list of resources that is a type of this Class
        $subjects   = $this->desc->get_subjects_where_resource($c['prefixes']['rdf'].'type', $resource_uri);
        $properties = null;
        $objects    = null;

        $triples = $this->getTriples($subjects, $properties, $objects);

        $r .= "\n".'<dl id="subjects-with-this-type">';
        $r .= "\n".'<dt>Things that are of this type</dt>';
        $r .= "\n".'<dd>';
        $r .= "\n".'<ul>';
        foreach($triples as $s => $po) {
            //XXX: If there is no label, we should output the URI
            $prefLabel = '';
            if ($sC->hasProperty('skos:prefLabel', $po)) {
                $prefLabel = $sC->object('skos:prefLabel', $po);
            }
            $r .= "\n".'<li><a href="'.$s.'">'.$prefLabel.'</a></li>';
        }
        $r .= "\n".'</ul>';
        $r .= "\n".'</dd>';
        $r .= "\n".'</dl>';

        return $r;
    }


    /**
     * This method can be used if templates wants to use XSLT for RDF/XML.
     */
    function indexToRDFXML()
    {
        return $this->desc->to_rdfxml();
    }
}



/**
 * LDP's lean table for basic key value pair output
 *
 */
class LDP_TableDataWidget extends PAGET_TableDataWidget
{
    function format_table(&$data)
    {
        $resource_uri = $this->desc->get_primary_resource_uri();

        $ret = '';
        if (count($data) > 0) {
            $ret .= "\n".'<table class="resource_about">';
            $ret .= "\n".'<caption>About '.'<a href="'.$resource_uri.'">'.$this->desc->get_label($resource_uri).'</a></caption>';
            $ret .= "\n".'<thead><tr><th>Property</th><th>Object</th></tr></thead>';
            $ret .= "\n".'<tbody>';
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
class LDP_Store extends Store
{
    var $sC;

    function __construct($su, $sC)
    {
        $this->sC = $sC;
        parent::__construct($su);
    }


   /**
    * Obtain a reference to this store's sparql service
    *
    * @return SparqlService
    */
    function get_sparql_service()
    {
        return new LDP_SparqlServiceBase($this->uri, $this->credentials, $this->request_factory, $this->sC);
    }
}



/**
 * Controls which SPARQL query to use
 * TODO: More query types
 */
class LDP_SparqlServiceBase extends SparqlServiceBase
{
    var $sC;

    function __construct($tu, $tc, $trq, $sC)
    {
        $this->sC = $sC;
        parent::__construct($tu, $tc, $trq);
    }


    function describe($uri, $type = 'cbd', $output = OUTPUT_TYPE_RDF)
    {
        $c = $this->sC->getConfig();
        $prefixes = $this->sC->getPrefix();
        $SPARQL_prefixes = '';

//        if (!empty($c['sparql_query'][$type])) {
            foreach($prefixes as $prefixName => $namespace) {
                $SPARQL_prefixes .= "PREFIX $prefixName: <$namespace>\n";
            }

            $query = preg_replace("#<URI>#", "<$uri>", $SPARQL_prefixes.$c['sparql_query'][$type]);

            return $this->graph($query, $output);
//        }
//        else {
//            TODO
//        }
    }

}



/**
 * Main utility class for LDP
 */
class LDP extends LDP_UriSpace
{
    var $sC;
    var $config = array();
    var $requestURI = '';
    var $currentRequest = array();

    function __construct($sC)
    {
        $this->sC = $sC;
        $this->config = $sC->config;

        /**
         * TODO:  if ($_SERVER["QUERY_STRING"]) { '?' . $_SERVER["QUERY_STRING"]) : ''
         */
        $this->requestURI = "http://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];

        $this->getCurrentRequest();   /* Sets configuration for current request */

        parent::__construct($this);
    }


   /**
     * Figure out what to give to the client
     */
    function getCurrentRequest()
    {
        $ePs = implode("|", array_reverse($this->getEntityPaths()));

        $search = '#^(http://)('.$_SERVER['SERVER_NAME'].')('.$this->config['site']['path'].')('.$ePs.')?(.+)?\.(html|rdf|json|turtle)$#i';

        if (preg_match($search, $this->requestURI, $matches)) {
            $this->currentRequest = $matches;
        }
        else {
            /* XXX: This might not be necessary */
        }

        return $this->currentRequest;
    }


    /**
     * Returns the entity id from the current request.
     */
    function getEntitySetId()
    {
        /**
         * We check whether it is recognized or unrecognized.
         * If recognized, we can most likely serve it.
         * If unrecognized, we might be able to serve it depending on what the
         * store responds with.
         */

        if ($this->currentRequest[4] == '/' && !empty($this->currentRequest[5])) {
            return $this->getKeyFromValue($this->config['entity']['resource']['path'], $this->config['entity']);
        } else {
            return $this->getKeyFromValue($this->currentRequest[4], $this->config['entity']);
        }
    }


    /**
     * Returns the query type based on the current request.
     */
    function getEntityQuery()
    {
        $entitySetId = $this->getEntitySetId();

        return $this->config['entity'][$entitySetId]['query'];
    }


    /**
     * Returns all of the entity paths that was set in site and LDP configuration
     */
    function getEntityPaths()
    {
        $c = $this->getConfig();
        $entityPaths = array();

        foreach ($c['entity'] as $key => $value) {
            $entityPaths[] = $c['entity'][$key]['path'];
        }

        asort($entityPaths);

        return $entityPaths;
    }


    /**
     * Transforms the current request URI to the URI found in the RDF store.
     */
    function getRemoteURIFromCurrentRequest()
    {
        $cR = $this->currentRequest;

        return $cR[1].array_search($cR[2], $this->config['server']).$cR[3].$cR[4].$cR[5];
    }


    /**
     * Returns all of the configuration values that was set in site and LDP
     */
    function getConfig()
    {
        return $this->config;
    }


    /**
     * Returns a namespace of prefix or the whole LDP and SITE prefix set
     */
    function getPrefix($prefix = null)
    {
        if (is_null($prefix)) {
            return $this->config['prefixes'];
        }
        else {
            return $this->config['prefixes'][$prefix];
        }
    }


    /**
     * Given QName, returns an URI from configured prefix list
     *
     * @return string
     */
    function getURI($qname)
    {
        $c = $this->getConfig();

        if(preg_match("#(.*):(.*)#", $qname, $m)) {
            $prefixName = $c['prefixes'][$m[1]];
            if(isset($prefixName)) {
                return $prefixName.$m[2];
            }
        }

        return;
    }


    /**
     * Returns the object value for a property object pair
     * based on property QName input
     *
     * @return string
     */
    function object($qname, $po)
    {
        $c = $this->sC->getConfig();

        if(preg_match("#(.*):(.*)#", $qname, $m)) {
            /**
             * $m[1] is prefixName, $m[2] is name
             */
            return $po[$c['prefixes'][$m[1]].$m[2]][0]['value'];
        }

        return;
    }


    /**
     * Checks if a property object pair contains a property based on QName input
     *
     * @return boolean
     */
    function hasProperty($qname, $po)
    {
        $c = $this->sC->getConfig();

        if(preg_match("#(.*):(.*)#", $qname, $m)) {
            if(isset($po[$c['prefixes'][$m[1]].$m[2]])) {
                return true;
            }
        }

        return false;
    }


    /**
     * Given URI, returns QName as an HTML anchor
     *
     * @return string
     */
    function getQName($uri)
    {
        //TODO: Need to access Paget's objects from here
        //        return $this->term_widget->link_uri($uri);
    }


    /**
     * This would return the first match
     *
     * @return string
     */
    function getKeyFromValue($needle, $a)
    {
        foreach ($a as $key => $subArray) {
            foreach ($subArray as $subKey => $value) {
                if ($value == $needle) {
                    return $key;
                }
            }
        }

        return 'resource';
    }
}
