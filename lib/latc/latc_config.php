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
    /**
     * XXX: These variables could be moved to construct
     */
    var $siteName   = 'My LATC site';        /* Name of your site. Appears in page title, address etc. */
    var $siteServer = 'site';                /* "site" in http://site */
    var $sitePath   = '';                    /* "/foo" in http://site/foo. Leave bllank if there isn't one. */
    var $siteTheme  = 'default';             /* "default" in /var/www/site/theme/default */
    var $siteLogo   = 'logo.png';            /* "logo.png" in /var/www/site/theme/default/images/logo.jpg */

    var $entityId       = 'resource';        /* Name of the entity set */
    var $entityPath     = '/resource';       /* URI path e.g., resource */
    var $entityQuery    = '';                /* query to use for this resource. Default should be DESCRIBE <uri> */
    var $entityTemplate = 'default.resource.template.html';    /* HTML template */

    var $remoteServer = 'dbpedia.org';       /* Default. Used for mapping between $siteServer and $remoteServer */

    var $requestURI = '';

    var $currentRequest = array();

    function __construct()
    {
        $this->config['site']['name']      = $this->siteName;
        $this->config['site']['server']    = $this->siteServer = $_SERVER['SERVER_NAME'];
        $this->config['site']['path']      = $this->sitePath;
        $this->config['site']['theme']     = $this->siteTheme;
        $this->config['site']['logo']      = $this->siteLogo;

        $this->config['entity'][$this->entityId]['path']     = $this->entityPath;
        $this->config['entity'][$this->entityId]['query']    = $this->entityQuery;
        $this->config['entity'][$this->entityId]['template'] = $this->entityTemplate;

        $this->config['entity']['class']['path']     = '/class';
        $this->config['entity']['class']['query']    = '';
        $this->config['entity']['class']['template'] = 'default.resource.template.html';

        $this->config['entity']['ontology']['path']     = '/ontology';
        $this->config['entity']['ontology']['query']    = '';
        $this->config['entity']['ontology']['template'] = 'default.resource.template.html';

        $this->config['entity']['property']['path']     = '/property';
        $this->config['entity']['property']['query']    = '';
        $this->config['entity']['property']['template'] = 'default.resource.template.html';

        /**
         * TODO:        if ($_SERVER["QUERY_STRING"]) { '?' . $_SERVER["QUERY_STRING"]) : ''
         */
        $this->requestURI = "http://".$this->siteServer.$_SERVER['REQUEST_URI'];
    }


    /**
     * Figure out what to give to the client
     */
    function getCurrentRequest()
    {
        $ePs = implode("|", array_reverse($this->getEntityPaths()));

        $search = '#^(http://)('.$this->config['site']['server'].')('.$this->config['site']['path'].')('.$ePs.')?(.+)?\.(html|rdf|json|turtle)$#i';

        if (preg_match($search, $this->requestURI, $matches)) {
            $this->currentRequest = $matches;
        }
        else {
            /* TODO */
        }

        return $this->currentRequest;
    }


    /**
     * Returns the entity id from the current request.
     */
    function getEntityId()
    {
        return $this->getKeyFromValue($this->currentRequest[4], $this->config['entity']);
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
     * Returns the server name that was set for URI mapping.
     */
    function getRemoteServer()
    {
        return $this->remoteServer;
    }


    /**
     * Transforms the current request URI to the URI found in the RDF store.
     */
    function getRemoteURIFromCurrentRequest()
    {
        $cR = $this->currentRequest;

        return $cR[1].$this->remoteServer.$cR[3].$cR[4].$cR[5];

    }


    /**
     * Returns all of the configuration values that was set in site and LATC
     */
    function getConfig()
    {
        return $this->config;
    }


    function getKeyFromValue($needle, $a) {
        foreach ($a as $key => $subArray) {
            foreach ($subArray as $value) {
                $b[$value] = $key;
            }
        }

        return $b[$needle];
    }
}

?>
