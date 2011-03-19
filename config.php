<?php

$config['site']['name']   = 'My Linked Data site'; /* Name of your site. Appears in page title, address etc. */
$config['site']['server'] = 'site';                /* 'site' in http://site */
$config['site']['path']   = '';                    /* '/foo' in http://site/foo */
$config['site']['theme']  = 'default';             /* 'default' in /var/www/site/theme/default */
$config['site']['logo']   = 'logo_latc.png';       /* 'logo_latc.png' in /var/www/site/theme/default/images/logo_latc.png */

/*
 * Common prefixes for this dataset
 */
$config['prefixes'] = array(
    'rdf'               => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
    'rdfs'              => 'http://www.w3.org/2000/01/rdf-schema#',
    'xsd'               => 'http://www.w3.org/2001/XMLSchema#',
    'skos'              => 'http://www.w3.org/2004/02/skos/core#',
    'foaf'              => 'http://xmlns.com/foaf/0.1/',
    'dcterms'           => 'http://purl.org/dc/terms/'
);

/**
 * SPARQL Queries
 */
/* Empty query (temporary) */
$config['sparql_query']['empty'] = '';

/**
 * Default query is DESCRIBE
 * '<URI>' value is auto-assigned from current request URI
 */
$config['sparql_query']['default'] = "
    DESCRIBE <URI>
";
/**
 * Entity Set
 */
/* URI path for this entity */
$config['entity']['default']['path']     = '';
/* SPARQL query to use for this entity e.g., $config['sparql_query']['default'] */
$config['entity']['default']['query']    = 'default';
/* HTML template to use for this entity */
$config['entity']['default']['template'] = 'page.default.html';


$config['sparql_query']['site_home'] = "";
$config['entity']['site_home']['path']     = "/";
$config['entity']['site_home']['query']    = 'site_home';
$config['entity']['site_home']['template'] = 'page.home.html';

$config['entity']['site_about']['path']     = "/about";
$config['entity']['site_about']['query']    = 'empty';
$config['entity']['site_about']['template'] = 'page.about.html';

?>
