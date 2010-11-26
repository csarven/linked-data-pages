<?php
/**
 * Base file where everything is configured and dispatched to
 * 
 * PHP version 5
 *
 * @category  Base
 * @package   LATC
 * @author    Sarven Capadisli <sarven.capadisli@deri.org>
 * @copyright 2010 Digital Enterprise Research Institute
 * @license   http://www.fsf.org/licensing/licenses/gpl-3.0.html GNU General Public License version 3.0
 * @link      http://deri.org/
 */

ini_set('display_errors', '1');

define('SITE_DIR', '/var/www/site/');
define('LIB_DIR', '/var/www/lib/');
define('PAGET_DIR', '/var/www/lib/paget/');
define('LATC_DIR', '/var/www/site/lib/latc/');
define('MORIARTY_DIR', '/var/www/lib/moriarty/');
define('MORIARTY_ARC_DIR', '/var/www/lib/arc2/');

if (!defined('MORIARTY_HTTP_CACHE_DIR')  && file_exists(dirname(__FILE__).DIRECTORY_SEPARATOR.'cache')) {
    define('MORIARTY_HTTP_CACHE_DIR', dirname(__FILE__).DIRECTORY_SEPARATOR.'cache');
}
define('MORIARTY_HTTP_CACHE_READ_ONLY', true);
define('MORIARTY_HTTP_CACHE_USE_STALE_ON_FAILURE', true); /* use a cached response if network fails */

define('STORE_URI', 'http://localhost:3030/cso/query');

require_once LATC_DIR . 'latc_config.php';
require_once LATC_DIR . 'latc.php';


/**
 * Site specific configuration.
 *
 * @category  Config
 * @package   LATC
 * @author    Sarven Capadisli <sarven.capadisli@deri.org>
 * @copyright 2010 Digital Enterprise Research Institute
 * @license   http://www.fsf.org/licensing/licenses/gpl-3.0.html GNU General Public License version 3.0
 * @link      http://deri.org/
 */
class SITE_Config extends LATC_Config
{
    /**
     * Base constructor extends default configuration
     */
    function __construct()
    {
        parent::__construct();

        /**
         * Site specific configuration values can be set here e.g.,
            $this->config['site']['name']      = 'My 1337 site'
            $this->config['site']['server']    = 'site';      // 'site' in http://site
            $this->config['site']['path']      = '';          // 'foo' in http://site/foo
            $this->config['site']['theme']     = 'site';      // 'default' in /var/www/site/theme/default
            $this->config['site']['logo']      = 'logo.png';  // logo.png in /var/www/site/theme/default/images/logo.jpg

         * Entity sets can be configured here e.g.,
            $this->config['entity']['dbpr']['path']     = '/resource';
            $this->config['entity']['dbpr']['query']    = '';
            $this->config['entity']['dbpr']['template'] = 'default.resource.template.html';

         * Properties found in the dataset
            $this->config['ns']['birthplace']     = 'http://dbpedia.org/property/birthplace';
         */

        $this->config['server']['govdata.ie']   = 'site';
        $this->config['server']['geo.govdata.ie']     = 'geo.site';
        $this->config['server']['stats.govdata.ie']   = 'stats.site';

        $this->config['site']['theme']     = 'cso';      // 'default' in /var/www/site/theme/cso
        $this->config['site']['logo']      = 'logo.png';  // logo.png in /var/www/site/theme/default/images/logo.jpg

        $this->config['entity']['cso_home']['path']     = "/";
        $this->config['entity']['cso_home']['query']    = 'cso_home';
        $this->config['entity']['cso_home']['template'] = 'home.resource.template.html';

        $this->config['entity']['cso_data']['path']     = '/data';
        $this->config['entity']['cso_data']['query']    = '';
        $this->config['entity']['cso_data']['template'] = 'default.resource.template.html';

        $this->config['entity']['cso_codelist']['path']     = '/codelist';
        $this->config['entity']['cso_codelist']['query']    = 'cso_codelist';
        $this->config['entity']['cso_codelist']['template'] = 'default.resource.template.html';

        $this->config['entity']['cso_codelist-geo']['path']     = '/codelist/geo';
        $this->config['entity']['cso_codelist-geo']['query']    = 'cso_codelist-geo';
        $this->config['entity']['cso_codelist-geo']['template'] = 'default.resource.template.html';


        $this->config['entity']['cso_class_administrative-county']['path']     = '/AdministrativeCounty';
        $this->config['entity']['cso_class_administrative-county']['query']    = 'cso_class';
        $this->config['entity']['cso_class_administrative-county']['template'] = 'class.resource.template.html';

        $this->config['entity']['cso_class_city']['path']     = '/City';
        $this->config['entity']['cso_class_city']['query']    = 'cso_class';
        $this->config['entity']['cso_class_city']['template'] = 'class.resource.template.html';

        $this->config['entity']['cso_class_electoral-division']['path']     = '/ElectoralDivision';
        $this->config['entity']['cso_class_electoral-division']['query']    = 'cso_class';
        $this->config['entity']['cso_class_electoral-division']['template'] = 'class.resource.template.html';

        $this->config['entity']['cso_class_enumeration-area']['path']     = '/EnumerationArea';
        $this->config['entity']['cso_class_enumeration-area']['query']    = 'cso_class';
        $this->config['entity']['cso_class_enumeration-area']['template'] = 'class.resource.template.html';

        $this->config['entity']['cso_class_province']['path']     = '/Province';
        $this->config['entity']['cso_class_province']['query']    = 'cso_class';
        $this->config['entity']['cso_class_province']['template'] = 'class.resource.template.html';

        $this->config['entity']['cso_class_state']['path']     = '/State';
        $this->config['entity']['cso_class_state']['query']    = 'cso_class';
        $this->config['entity']['cso_class_state']['template'] = 'class.resource.template.html';

        $this->config['entity']['cso_class_traditional-county']['path']     = '/TraditionalCounty';
        $this->config['entity']['cso_class_traditional-county']['query']    = 'cso_class';
        $this->config['entity']['cso_class_traditional-county']['template'] = 'class.resource.template.html';


        $this->config['entity']['cso_city']['path']     = '/city';
        $this->config['entity']['cso_city']['query']    = 'cso_city';
        $this->config['entity']['cso_city']['template'] = 'geo.resource.template.html';

        $this->config['entity']['cso_county']['path']     = '/county';
        $this->config['entity']['cso_county']['query']    = 'cso_county';
        $this->config['entity']['cso_county']['template'] = 'geo.resource.template.html';

        $this->config['entity']['cso_property']['path']     = '/property';
        $this->config['entity']['cso_property']['query']    = 'cso_property';
        $this->config['entity']['cso_property']['template'] = 'property.resource.template.html';

        $this->config['entity']['cso_traditional-county']['path']     = '/traditional-county';
        $this->config['entity']['cso_traditional-county']['query']    = 'cso_traditional-county';
        $this->config['entity']['cso_traditional-county']['template'] = 'geo.resource.template.html';


        /*
         * Some of the namespaces used in this dataset
         */
        $this->config['ns']['concept'][0]             = 'http://stats.govdata.ie/concept/';

        $this->config['ns']['codelist'][0]            = 'http://stats.govdata.ie/codelist/';

        $this->config['ns']['prop']['geoArea']        = 'http://stats.govdata.ie/property/geoArea';
        $this->config['ns']['prop']['maritalStatus']  = 'http://stats.govdata.ie/property/maritalStatus';
        $this->config['ns']['prop']['age1']           = 'http://stats.govdata.ie/property/age1';
        $this->config['ns']['prop']['age2']           = 'http://stats.govdata.ie/property/age2';
        $this->config['ns']['prop']['population']     = 'http://stats.govdata.ie/property/population';
        $this->config['ns']['prop']['usualResidence'] = 'http://stats.govdata.ie/property/usualResidence';
        $this->config['ns']['prop']['religion']       = 'http://stats.govdata.ie/property/religion';

        $this->config['ns']['class'][0]                 = 'http://geo.govdata.ie/';
        $this->config['ns']['class']['City']            = 'http://geo.govdata.ie/City';
        $this->config['ns']['class']['Province']        = 'http://geo.govdata.ie/Province';

        $this->config['ns']['concept'][0]                 = 'http://stats.govdata.ie/concept/';
        $this->config['ns']['concept']['birthplace']      = 'http://stats.govdata.ie/concept/birthplace';
        $this->config['ns']['concept']['ethnic-group']    = 'http://stats.govdata.ie/concept/ethnic-group';
        $this->config['ns']['concept']['geo-area']        = 'http://stats.govdata.ie/concept/geo-area';
        $this->config['ns']['concept']['nationality']     = 'http://stats.govdata.ie/concept/nationality';
        $this->config['ns']['concept']['marital-status']  = 'http://stats.govdata.ie/concept/marital-status';
        $this->config['ns']['concept']['religion']        = 'http://stats.govdata.ie/concept/religion';
        $this->config['ns']['concept']['usual-residence'] = 'http://stats.govdata.ie/concept/usual-residence';
    }
}


/**
 * Methods that handle the data in the query result. Usually called from templates.
 */
class SITE_Template extends LATC_Template
{
    var $siteConfig;

    function __construct($template_filename, $desc, $urispace, $request, $sC)
    {
        //XXX: Beginning of DO NOT MODIFY
        $this->siteConfig = $sC;
        parent::__construct($template_filename, $desc, $urispace, $request, $sC);
        //XXX: End of DO NOT MODIFY
    }


    /*
     * TODO: Change bunch of render*() to renderTabularDimensions($object, $dimensions) or renderDimensions()
     * Perhaps $object is a uri, 
     * $dimensions is like array($ns['prop']['maritalStatus'], $ns['prop']['age2'], $ns['prop']['population'])
     */
    function renderMaritalStatusAgePopulation()
    {
        $c = $this->siteConfig->getConfig();
        $ns = array();

        //XXX: Would it be better to use the values from index or the config's ns?
        $ns_prop                      = 'http://'.$c['server']['stats.govdata.ie'].'/property/';
        $ns['prop']['geoArea']        = $ns_prop.'geoArea';
        $ns['prop']['maritalStatus']  = $ns_prop.'maritalStatus';
        $ns['prop']['age2']           = $ns_prop.'age2';
        $ns['prop']['population']     = $ns_prop.'population';

        $ns_codeList = 'http://'.$c['server']['stats.govdata.ie'].'/codelist/';
        $c['ns']['codelist']['marital-status'] = $ns_codeList.'marital-status';
        $c['ns']['codelist']['age2'] = $ns_codeList.'age2';

        $resource_uri = $this->desc->get_primary_resource_uri();

        /**
         * This will get only the triples that have maritalStatus age2 population geoArea as property
         */
        $subjects = $this->desc->get_subjects_where_resource($ns['prop']['geoArea'], $resource_uri);
        $properties = array($ns['prop']['maritalStatus'], $ns['prop']['age2'], $ns['prop']['population']);
        $objects    = null;
        $triples = $this->getTriples($subjects, $properties, $objects);

        /**
         * This will get the prefLabels of marital-status age2
         */
        $subjects   = $this->desc->get_subjects_where_resource($c['ns']['skos']['topConceptOf'], $c['ns']['codelist']['marital-status']);
        $properties = array($c['ns']['skos']['prefLabel']);
        $objects    = null;
        $triples_propertyLabels = $this->getTriples($subjects, $properties, $objects);

        $triples = array_merge_recursive($triples, $triples_propertyLabels);

        $subjects   = $this->desc->get_subjects_where_resource($c['ns']['skos']['topConceptOf'], $c['ns']['codelist']['age2']);
        $properties = array($c['ns']['skos']['prefLabel']);
        $objects    = null;
        $triples_propertyLabels = $this->getTriples($subjects, $properties, $objects);

        $triples = array_merge_recursive($triples, $triples_propertyLabels);

        $maritalStatusAgePopulation = array();

        foreach($triples as $subject => $po) {
            if (isset($po[$ns['prop']['maritalStatus']])
                && isset($triples[$po[$ns['prop']['maritalStatus']][0]['value']][$c['ns']['skos']['prefLabel']][0]['value'])

                && isset($po[$ns['prop']['age2']])
                && isset($triples[$po[$ns['prop']['age2']][0]['value']][$c['ns']['skos']['prefLabel']][0]['value'])

                && isset($po[$ns['prop']['population']][0]['value'])) {

                $maritalStatusLabel = $triples[$po[$ns['prop']['maritalStatus']][0]['value']][$c['ns']['skos']['prefLabel']][0]['value'];
                $ageLabel = $triples[$po[$ns['prop']['age2']][0]['value']][$c['ns']['skos']['prefLabel']][0]['value'];
                $population = $po[$ns['prop']['population']][0]['value'];

                if (array_key_exists($ageLabel, $maritalStatusAgePopulation)
                    && array_key_exists($maritalStatusLabel, $maritalStatusAgePopulation[$ageLabel])) {
                    $maritalStatusAgePopulation[$ageLabel][$maritalStatusLabel] += $population;
                }
                else {
                    $maritalStatusAgePopulation[$ageLabel][$maritalStatusLabel] = $population;
                }
            }
        }

        $r = '';
        $r .= "\n".'<table>';
        $r .= "\n".'<caption>Marital status and age breakdown</caption>';
        $r .= "\n".'<thead><tr><td>Age</td><td>Marital status</td></tr></thead>';
        $r .= "\n".'<tbody>';
        $r .= "\n".'<tr><th></th>';
        //FIXME: Looping over just for this is dirty. Revisit.
        foreach($maritalStatusAgePopulation as $age => $maritalStatusPopulation) {
            foreach($maritalStatusPopulation as $maritalStatus => $population) {
                $r .= "\n".'<th>'.$maritalStatus.'</th>';
            }
            break;
        }
        $r .= "\n".'</tr>';

        foreach($maritalStatusAgePopulation as $age => $maritalStatusPopulation) {
            $r .= "\n".'<tr>';
            $r .= "\n".'<th>'.$age.'</th>';
            foreach($maritalStatusPopulation as $maritalStatus => $population) {
                $r .= "\n".'<td>'.$population.'</td>';
            }
            $r .= "\n".'</tr>';
        }
        $r .= "\n".'</tbody>';
        $r .= "\n".'</table>';

        return $r;
    }


    function renderBirthplace()
    {
        $c = $this->siteConfig->getConfig();

        //XXX: Would it be better to use the values from index?
        $ns_prop                      = 'http://'.$c['server']['stats.govdata.ie'].'/property/';
        $ns['prop']['geoArea']        = $ns_prop.'geoArea';
        $ns['prop']['birthplace']     = $ns_prop.'birthplace';

        $ns_codeList = 'http://'.$c['server']['stats.govdata.ie'].'/codelist/';
        $c['ns']['codelist']['birthplace'] = $ns_codeList.'birthplace';

        $resource_uri = $this->desc->get_primary_resource_uri();

        $subjects = $this->desc->get_subjects_where_resource($ns['prop']['geoArea'], $resource_uri);
        $properties = array($ns['prop']['birthplace']);
        $objects    = null;
        $triples = $this->getTriples($subjects, $properties, $objects);

        $subjects   = $this->desc->get_subjects_where_resource($c['ns']['skos']['topConceptOf'], $c['ns']['codelist']['birthplace']);
        $properties = array($c['ns']['skos']['prefLabel']);
        $objects    = null;
        $triples_propertyLabels = $this->getTriples($subjects, $properties, $objects);
        $triples = array_merge_recursive($triples, $triples_propertyLabels);

        $r = '';
        $r .= '<dl>';
        $r .= "\n".'<dt>People\'s birthplace</dt>';
        $r .= "\n".'<dd>';
        $r .= "\n".'<ul>';
        foreach($triples as $triple => $po) {
            if (isset($po[$ns['prop']['birthplace']])
                && isset($triples[$po[$ns['prop']['birthplace']][0]['value']][$c['ns']['skos']['prefLabel']][0]['value'])) {
                $birthPlaceLabel = $triples[$po[$ns['prop']['birthplace']][0]['value']][$c['ns']['skos']['prefLabel']][0]['value'];

                $r .= "\n".'<li><a href="'.$po[$ns['prop']['birthplace']][0]['value'].'">'.$birthPlaceLabel.'</a></li>';
            }
        }
        $r .= "\n".'</ul>';
        $r .= "\n".'</dd>';
        $r .= "\n".'</dl>';

        return $r;
    }


    function renderReligionPopulation()
    {
        $c = $this->siteConfig->getConfig();

        //XXX: Would it be better to use the values from index?
        $ns_prop                      = 'http://'.$c['server']['stats.govdata.ie'].'/property/';
        $ns['prop']['geoArea']        = $ns_prop.'geoArea';
        $ns['prop']['religion']       = $ns_prop.'religion';
        $ns['prop']['population']     = $ns_prop.'population';

        $ns_codeList = 'http://'.$c['server']['stats.govdata.ie'].'/codelist/';
        $c['ns']['codelist']['religion']   = $ns_codeList.'religion';
        $c['ns']['codelist']['population'] = $ns_codeList.'population';

        $resource_uri = $this->desc->get_primary_resource_uri();

        $subjects = $this->desc->get_subjects_where_resource($ns['prop']['geoArea'], $resource_uri);
        $properties = array($ns['prop']['religion'], $ns['prop']['population']);
        $objects    = null;
        $triples = $this->getTriples($subjects, $properties, $objects);

        $subjects   = $this->desc->get_subjects_where_resource($c['ns']['skos']['topConceptOf'], $c['ns']['codelist']['religion']);
        $properties = array($c['ns']['skos']['prefLabel']);
        $objects    = null;
        $triples_propertyLabels = $this->getTriples($subjects, $properties, $objects);
        $triples = array_merge_recursive($triples, $triples_propertyLabels);

        $r = '';
        $r .= "\n".'<table>';
        $r .= "\n".'<caption>What are people\'s religion?</caption>';
        $r .= "\n".'<tbody>';
        $r .= "\n".'<tr><th>Religion</th><th># of people</th></tr>';

        foreach($triples as $triple => $po) {
            if (isset($po[$ns['prop']['religion']])
                && isset($triples[$po[$ns['prop']['religion']][0]['value']][$c['ns']['skos']['prefLabel']][0]['value'])
                && isset($po[$ns['prop']['population']][0]['value'])) {

                $religionLabel = $triples[$po[$ns['prop']['religion']][0]['value']][$c['ns']['skos']['prefLabel']][0]['value'];
                $religion      = $po[$ns['prop']['religion']][0]['value'];
                $population    = $po[$ns['prop']['population']][0]['value'];

                $r .= "\n".'<tr><td><a href="'.$religion.'">'.$religionLabel.'</a></td><td>'.$population.'</td></tr>';
            }
        }

        $r .= "\n".'</tbody>';
        $r .= "\n".'</table>';

        return $r;
    }


    function renderUsualResidencePopulation()
    {
        $c = $this->siteConfig->getConfig();

        //XXX: Would it be better to use the values from index?
        $ns_prop                      = 'http://'.$c['server']['stats.govdata.ie'].'/property/';
        $ns['prop']['geoArea']        = $ns_prop.'geoArea';
        $ns['prop']['usualResidence'] = $ns_prop.'usualResidence';
        $ns['prop']['population']     = $ns_prop.'population';

        $ns_codeList = 'http://'.$c['server']['stats.govdata.ie'].'/codelist/';
        $c['ns']['codelist']['usual-residence']   = $ns_codeList.'usual-residence';
        $c['ns']['codelist']['population'] = $ns_codeList.'population';

        $resource_uri = $this->desc->get_primary_resource_uri();

        $subjects = $this->desc->get_subjects_where_resource($ns['prop']['geoArea'], $resource_uri);
        $properties = array($ns['prop']['usualResidence'], $ns['prop']['population']);
        $objects    = null;
        $triples = $this->getTriples($subjects, $properties, $objects);

        $subjects   = $this->desc->get_subjects_where_resource($c['ns']['skos']['topConceptOf'], $c['ns']['codelist']['usual-residence']);
        $properties = array($c['ns']['skos']['prefLabel']);
        $objects    = null;
        $triples_propertyLabels = $this->getTriples($subjects, $properties, $objects);
        $triples = array_merge_recursive($triples, $triples_propertyLabels);

        $r = '';
        $r .= "\n".'<table>';
        $r .= "\n".'<caption>Where do people usually reside?</caption>';
        $r .= "\n".'<tbody>';
        $r .= "\n".'<tr><th>Location</th><th># of people</th></tr>';

        foreach($triples as $triple => $po) {
            if (isset($po[$ns['prop']['usualResidence']])
                && isset($triples[$po[$ns['prop']['usualResidence']][0]['value']][$c['ns']['skos']['prefLabel']][0]['value'])
                && isset($po[$ns['prop']['population']][0]['value'])) {

                $usualResidenceLabel = $triples[$po[$ns['prop']['usualResidence']][0]['value']][$c['ns']['skos']['prefLabel']][0]['value'];
                $usualResidence      = $po[$ns['prop']['usualResidence']][0]['value'];
                $population    = $po[$ns['prop']['population']][0]['value'];

                $r .= "\n".'<tr><td><a href="'.$usualResidence.'">'.$usualResidenceLabel.'</a></td><td>'.$population.'</td></tr>';
            }
        }

        $r .= "\n".'</tbody>';
        $r .= "\n".'</table>';

        return $r;
    }


    function getTriplesOfType ($object = null)
    {
        $c = $this->siteConfig->getConfig();

        //TODO:
        if (!is_null($object)) {
        }

        $subjects = null;
        $properties = $c['ns']['rdf']['type'];
        $objects    = array($object);
        $triples = $this->getTriples($subjects, $properties, $objects);

        $subjects = array_keys($triples);
        $properties = $c['ns']['skos']['prefLabel'];
        $objects    = null;
        $triples_propertyLabels = $this->getTriples($subjects, $properties, $objects);

        $triples = array_merge_recursive($triples, $triples_propertyLabels);

        return $triples;
    }


    function renderListCities()
    {
        $c = $this->siteConfig->getConfig();

        $ns[0]      = 'http://'.$c['server']['geo.govdata.ie'].'/';
        $ns['City'] = $ns[0].'City';

        $triples = $this->getTriplesOfType($ns['City']);

        $r = '';
        $r .= '<dl>';
        $r .= "\n".'<dt>Cities</dt>';
        $r .= "\n".'<dd>';
        $r .= "\n".'<ul>';
        foreach($triples as $triple => $po) {
                $label = $po[$c['ns']['skos']['prefLabel']][0]['value'];
                $r .= "\n".'<li><a href="'.$triple.'">'.$label.'</a></li>';
        }
        $r .= "\n".'</ul>';
        $r .= "\n".'</dd>';
        $r .= "\n".'</dl>';

        return $r;
    }

    function renderListProvinces()
    {
        $c = $this->siteConfig->getConfig();

        $ns[0]          = 'http://'.$c['server']['geo.govdata.ie'].'/';
        $ns['Province'] = $ns[0].'Province';

        $triples = $this->getTriplesOfType($ns['Province']);

        $r = '';
        $r .= '<dl>';
        $r .= "\n".'<dt>Provinces</dt>';
        $r .= "\n".'<dd>';
        $r .= "\n".'<ul>';
        foreach($triples as $triple => $po) {
                $label = $po[$c['ns']['skos']['prefLabel']][0]['value'];
                $r .= "\n".'<li><a href="'.$triple.'">'.$label.'</a></li>';
        }
        $r .= "\n".'</ul>';
        $r .= "\n".'</dd>';
        $r .= "\n".'</dl>';

        return $r;
    }

}


/**
 * Allows new labels to be added
 */
class SITE_SimplePropertyLabeller extends LATC_SimplePropertyLabeller
{
    function __construct()
    {
        parent::__construct();
    }
}


/**
 * Controls which SPARQL query to use
 */
class SITE_SparqlServiceBase extends LATC_SparqlServiceBase
{
    var $siteConfig;

    function __construct($tu, $tc, $trq, $sC)
    {
        //XXX: Beginning of DO NOT MODIFY
        $this->siteConfig = $sC;
        parent::__construct($tu, $tc, $trq, $sC);
        //XXX: End of DO NOT MODIFY
    }


    /**
     * Allows site specific queries. If non provided, it will use LATC's default
        case 'city':
     */
    function describe($uri, $type = 'cbd', $output = OUTPUT_TYPE_RDF)
    {
        $c = $this->siteConfig->getConfig();

        switch($type) {
            //XXX: Beginning of DO NOT MODIFY
            default:
                return parent::describe($uri, $type, $output);
                break;
            //XXX: End of DO NOT MODIFY

            /**
             * Add more cases here e.g.,
                case 'city':

                $c = $this->siteConfig->getConfig();

                $query = "DESCRIBE ?s
                          WHERE {
                              GRAPH ?g {
                                  ?s <{$c['ns']['city']}> <$uri> .
                              }
                          }";
                break;
             */

            case 'cso_class':
                $query = "CONSTRUCT {
                              <$uri> ?p1 ?o1 .

                              ?s2 a <$uri> .
                              ?s2 <{$c['ns']['skos']['prefLabel']}> ?o3 .
                          }
                          WHERE {
                              {
                                  <$uri> ?p1 ?o1 .
                              }
                              UNION
                              {
                                  ?s2 a <$uri> .
                                  OPTIONAL {
                                      ?s2 <{$c['ns']['skos']['prefLabel']}> ?o3 .
                                  }
                              }
                          }
                         ";
                break;

            case 'cso_city': case 'cso_county': case 'cso_traditional-county':
/*
    XXX: We should probably use DESCRIBE
    but Fuseki doesn't play along for a query along these lines

                $query = "DESCRIBE ?s ?codeList
                          WHERE {
                              GRAPH ?g {
                                  ?s ?p <$uri> .
                                  OPTIONAL {
                                      ?s ?p2 ?codeList .
                                      ?codeList ?p3 ?o3 .
                                  }
                              }
                          }";
*/
                $query = "CONSTRUCT {
                              ?s ?geoArea <$uri> .
                              ?s ?p ?o .

                              ?o a <{$c['ns']['skos']['Concept']}> .
                              ?o <{$c['ns']['skos']['prefLabel']}> ?o_prefLabel .
                              <$uri> ?p0 ?o0 .
                          }
                          WHERE {
                              {
                                  ?s ?geoArea <$uri> .
                                  ?s ?p ?o .
                                  OPTIONAL {
                                      ?o a <{$c['ns']['skos']['Concept']}> .
                                      ?o <{$c['ns']['skos']['prefLabel']}> ?o_prefLabel .
                                  }
                              }
                              UNION
                              {
                                  <$uri> ?p0 ?o0 .
                              }
                          }";
                break;

            case 'cso_property':
                $query = "CONSTRUCT { ?s <$uri> ?o }
                          WHERE {
                              GRAPH ?g {
                                  ?s <$uri> ?o .
                              }
                          }
                          LIMIT 10";
                break;

            case 'cso_home':
                $query = "CONSTRUCT {
                              ?city a <{$c['ns']['class']['City']}> .
                              ?city a <{$c['ns']['skos']['Concept']}> .
                              ?city <{$c['ns']['skos']['prefLabel']}> ?cityLabel .

                              ?province a <{$c['ns']['class']['Province']}> .
                              ?province a <{$c['ns']['skos']['Concept']}> .
                              ?province <{$c['ns']['skos']['prefLabel']}> ?provinceLabel .
                          }
                          WHERE {
                              GRAPH ?g {
                                  ?city a <{$c['ns']['class']['City']}> .
                                  ?city a <{$c['ns']['skos']['Concept']}> .
                                  ?city <{$c['ns']['skos']['prefLabel']}> ?cityLabel .

                                  ?province a <{$c['ns']['class']['Province']}> .
                                  ?province a <{$c['ns']['skos']['Concept']}> .
                                  ?province <{$c['ns']['skos']['prefLabel']}> ?provinceLabel .
                              }
                          }";
                break;
        }

        return $this->graph($query, $output);
    }

}


$config = new SITE_Config();    /* Grabs configuration values from this site */
$config->getCurrentRequest();   /* Sets configuration for current request */
//print_r($config);
$space = new LATC_UriSpace($config); /* Starts to bulid the request */
$space->dispatch();                  /* Dispatches the requested URI to appropriate URI */
?>
