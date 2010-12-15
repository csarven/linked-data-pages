<?php
/**
 * Site configuration class
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

 * @category  Config
 * @package   LATC
 * @author    Sarven Capadisli <sarven.capadisli@deri.org>
 * @copyright 2010 Digital Enterprise Research Institute
 * @license   http://www.fsf.org/licensing/licenses/gpl-3.0.html GNU General Public License version 3.0
 * @link      http://deri.org/
 */


class LATC_Config
{
    var $config = array();
    var $requestURI = '';
    var $currentRequest = array();

    function __construct()
    {
        /* Name of your site. Appears in page title, address etc. */
        $this->config['site']['name']      = 'My LATC site';

        /* "site" in http://site */
        $this->config['site']['server']    = $_SERVER['SERVER_NAME'];

        /* "/foo" in http://site/foo. Leave bllank if there isn't one. */
        $this->config['site']['path']      = '';

        /* "default" in /var/www/site/theme/default */
        $this->config['site']['theme']     = 'default';

        /* "logo.png" in /var/www/site/theme/default/images/logo.png */
        $this->config['site']['logo']      = 'logo.png';

        /* URI maps e.g., $this->config['server']['dbpedia.org'] = 'site';
         * http://dbpedia.org/resource/Montreal to http://site/resource/Montreal
         */

        /* URI path e.g., resource */
        $this->config['entity']['resource']['path']     = '/resource';

        /* query to use for this resource. Default should be DESCRIBE <uri> */
        $this->config['entity']['resource']['query']    = '';

        /* HTML template */
        $this->config['entity']['resource']['template'] = 'default.resource.template.html';

        /**
         * TODO:        if ($_SERVER["QUERY_STRING"]) { '?' . $_SERVER["QUERY_STRING"]) : ''
         */
        $this->requestURI = "http://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];

        require_once SITE_DIR . 'config.php';
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
    function getEntityId()
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
        $entityId = $this->getEntityId();

        return $this->config['entity'][$entityId]['query'];
    }


    /**
     * Returns all of the entity paths that was set in site and LATC configuration
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
     * Returns all of the configuration values that was set in site and LATC
     */
    function getConfig()
    {
        return $this->config;
    }


    /**
     * Returns a namespace of prefix or the whole LATC and SITE prefix set
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

?>
