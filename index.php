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

define('STORE_URI', 'http://dbpedia.org/sparql');

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
            $this->config['property']['city']        = 'http://dbpedia.org/property/city';
         */
    }
}


/**
 * Methods that handle the data in the query result. Usually called from templates.
 */
class SITE_Template extends LATC_Template {
    var $siteConfig;

    function __construct($template_filename, $desc, $urispace, $request, $sC)
    {
        //XXX: Beginning of DO NOT MODIFY
        $this->siteConfig = $sC;
        parent::__construct($template_filename, $desc, $urispace, $request, $sC);
        //XXX: End of DO NOT MODIFY
    }

    /**
     * Add site specific methods here e.g.,
        function renderMaritalStatusAgePopulation()
        {
            $c = $this->siteConfig->getConfig();

            $p['city'] = 'http://'.$c['server']['dbpedia.org'].'/property/city';
            $p['foo']  = 'http://'.$c['server']['dbpedia.org'].'/property/foo';

            $resource_uri = $this->desc->get_primary_resource_uri();
            $subjects = $this->desc->get_subjects_where_resource($p['city'], $resource_uri);
            $triples = $this->getTriples($subjects, array($p['foo']));
        }
     */
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
                                  ?s <{$c['property']['city']}> <$uri> .
                              }
                         }";
                break;
             */
        }

        return $this->graph($query, $output);
    }

}


$config = new SITE_Config();    /* Grabs configuration values from this site */
$config->getCurrentRequest();   /* Sets configuration for current request */

$space = new LATC_UriSpace($config); /* Starts to bulid the request */
$space->dispatch();                  /* Dispatches the requested URI to appropriate URI */
?>
