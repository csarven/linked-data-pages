<?php

$config['site']['name']   = 'My LDP site'; /* Name of your site. Appears in page title, address etc. */
$config['site']['server'] = 'site';     /* 'site' in http://site */
$config['site']['path']   = '';         /* 'foo' in http://site/foo */
$config['site']['base']   = 'http://'.$config['site']['server'].$config['site']['path'];
$config['site']['theme']  = 'default';      /* 'default' in /var/www/site/theme/cso */
$config['site']['logo']   = 'logo_latc.png';  /* logo.png in /var/www/site/theme/default/images/logo.jpg */

$config['server']['site'] = 'site';

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
 * '<URI>' value is auto-assigned from current request URI
 */
$config['sparql_query']['empty'] = "";
$config['sparql_query']['default'] = "
    DESCRIBE <URI>
";

/**
 * Entity sets can be configured here:
 */
$config['sparql_query']['site_home'] = "";

/* URI path for this entity */
$config['entity']['site_home']['path']     = "/";
/* SPARQL query to use for this entity e.g., $config['sparql_query']['site_home'] */
$config['entity']['site_home']['query']    = 'site_home';
/* HTML template to use for this entity */
$config['entity']['site_home']['template'] = 'page.home.html';

$config['entity']['site_about']['path']     = "/about";
$config['entity']['site_about']['query']    = 'empty';
$config['entity']['site_about']['template'] = 'page.about.html';

?>
