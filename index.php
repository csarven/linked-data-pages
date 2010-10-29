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
    var $remoteServer = 'dbpedia.org';       /* used for mapping between $siteServer and $remoteServer */

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
         */
    }
}


$config = new SITE_Config();    /* Grabs configuration values from this site */
$config->getCurrentRequest();   /* Sets configuration for current request */

$space = new LATC_UriSpace($config); /* Starts to bulid the request */
$space->dispatch();                  /* Dispatches the requested URI to appropriate URI */
?>
