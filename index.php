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
define('CLASSES_DIR', '/var/www/site/classes/');
define('LIB_DIR', '/var/www/lib/');
define('PAGET_DIR', '/var/www/lib/paget/');
define('MORIARTY_DIR', '/var/www/lib/moriarty/');
define('MORIARTY_ARC_DIR', '/var/www/lib/arc2/');

if (!defined('MORIARTY_HTTP_CACHE_DIR')  && file_exists(dirname(__FILE__).DIRECTORY_SEPARATOR.'cache')) {
    define('MORIARTY_HTTP_CACHE_DIR', dirname(__FILE__).DIRECTORY_SEPARATOR.'cache');
}
define('MORIARTY_HTTP_CACHE_READ_ONLY', true);
define('MORIARTY_HTTP_CACHE_USE_STALE_ON_FAILURE', true); /* use a cached response if network fails */

define('STORE_URI', 'http://localhost:3030/cso/query');

require_once CLASSES_DIR . 'LATC_Config.php';
require_once CLASSES_DIR . 'LATC.php';
require_once CLASSES_DIR . 'SITE_Config.php';
require_once CLASSES_DIR . 'SITE_Template.php';


$config = new SITE_Config();    /* Grabs configuration values from this site */
$config->getCurrentRequest();   /* Sets configuration for current request */
//print_r($config);
$space = new LATC_UriSpace($config); /* Starts to bulid the request */
$space->dispatch();                  /* Dispatches the requested URI to appropriate URI */
?>
