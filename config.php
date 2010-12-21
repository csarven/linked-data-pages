<?php

$config['site']['name']   = 'My LATC site'; /*Name of your site. Appears in page title, address etc. */
$config['site']['server'] = 'site';     /* 'site' in http://site */
$config['site']['path']   = '';         /* 'foo' in http://site/foo */
$config['site']['base']   = 'http://'.$config['site']['server'].$config['site']['path'];
$config['site']['theme']  = 'cso';      /* 'default' in /var/www/site/theme/cso */
$config['site']['logo']   = 'logo_data-gov.ie.png';  /* logo.png in /var/www/site/theme/default/images/logo.jpg */

$config['server']['govdata.ie']       = 'site';
$config['server']['geo.govdata.ie']   = 'geo.site';
$config['server']['stats.govdata.ie'] = 'stats.site';

/*
 * Common prefixes for this dataset
 */
$config['prefixes'] = array(
    'rdf'               => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
    'rdfs'              => 'http://www.w3.org/2000/01/rdf-schema#',
    'xsd'               => 'http://www.w3.org/2001/XMLSchema#',
    'skos'              => 'http://www.w3.org/2004/02/skos/core#',

    'sdmx-concept'      => 'http://purl.org/linked-data/sdmx/2009/concept#',
    'sdmx-dimension'    => 'http://purl.org/linked-data/sdmx/2009/dimension#',
    'qb'                => 'http://purl.org/linked-data/cube#',

    'statsDataGov' => 'http://stats.govdata.ie/',
    'concept'      => 'http://stats.govdata.ie/concept/',
    'codelist'     => 'http://stats.govdata.ie/codelist/',
    'property'     => 'http://stats.govdata.ie/property/',
    'geoDataGov'   => 'http://geo.govdata.ie/'
);

/** 
 * SPARQL Queries
 * '<URI>' value is auto-assigned from current request URI
 * 
 */
$config['sparql_query']['empty'] = "";
$config['sparql_query']['default'] = "
    DESCRIBE <URI>
";

/**
 * Entity sets can be configured here:
 */
$config['sparql_query']['cso_home'] = "
CONSTRUCT {
    ?city a geoDataGov:City .
    ?city a skos:Concept .
    ?city skos:prefLabel ?cityLabel .

    ?province a geoDataGov:Province .
    ?province a skos:Concept .
    ?province skos:prefLabel ?provinceLabel .
}
WHERE {
    ?city a geoDataGov:City .
    ?city a skos:Concept .
    ?city skos:prefLabel ?cityLabel .

    ?province a geoDataGov:Province .
    ?province a skos:Concept .
    ?province skos:prefLabel ?provinceLabel .
}
";

/* URI path for this entity */
$config['entity']['cso_home']['path']     = "/";
/* SPARQL query to use for this entity e.g., $config['sparql_query']['cso_home'] */
$config['entity']['cso_home']['query']    = 'cso_home';
/* HTML template to use for this entity */
$config['entity']['cso_home']['template'] = 'page.home.html';

$config['entity']['cso_about']['path']     = "/about";
$config['entity']['cso_about']['query']    = 'empty';
$config['entity']['cso_about']['template'] = 'page.about.html';

$config['entity']['cso_data']['path']     = '/data';
$config['entity']['cso_data']['query']    = 'default';
$config['entity']['cso_data']['template'] = 'page.default.html';

$config['entity']['cso_codelist']['path']     = '/codelist';
$config['entity']['cso_codelist']['query']    = 'default';
$config['entity']['cso_codelist']['template'] = 'page.default.html';

$config['sparql_query']['cso_city'] = "
CONSTRUCT {
    ?s ?geoArea <URI> .
    ?s ?p ?o .

    ?o a skos:Concept .
    ?o skos:prefLabel ?o_prefLabel .
    <URI> ?p0 ?o0 .
}
WHERE {
    {
        ?s ?geoArea <URI> .
        ?s ?p ?o .
        OPTIONAL {
            ?o a skos:Concept .
            ?o skos:prefLabel ?o_prefLabel .
        }
    }
    UNION
    {
        <URI> ?p0 ?o0 .
    }
}
";
$config['entity']['cso_city']['path']     = '/city';
$config['entity']['cso_city']['query']    = 'cso_city';
$config['entity']['cso_city']['template'] = 'page.geo.html';


$config['entity']['cso_province']['path']     = '/province';
$config['entity']['cso_province']['query']    = 'cso_city';
$config['entity']['cso_province']['template'] = 'page.geo.html';


$config['sparql_query']['cso_class'] = "
CONSTRUCT {
    <URI> ?p1 ?o1 .

    ?s2 a <URI> .
    ?s2 skos:prefLabel ?o3 .
}
WHERE {
    {
     <URI> ?p1 ?o1 .
    }
    UNION
    {
        ?s2 a <URI> .
        OPTIONAL {
           ?s2 skos:prefLabel ?o3 .
        }
    }
}
";
$config['entity']['cso_class_administrative-county']['path']     = '/AdministrativeCounty';
$config['entity']['cso_class_administrative-county']['query']    = 'cso_class';
$config['entity']['cso_class_administrative-county']['template'] = 'page.class.html';

$config['entity']['cso_class_city']['path']     = '/City';
$config['entity']['cso_class_city']['query']    = 'cso_class';
$config['entity']['cso_class_city']['template'] = 'page.class.html';

$config['entity']['cso_class_electoral-division']['path']     = '/ElectoralDivision';
$config['entity']['cso_class_electoral-division']['query']    = 'cso_class';
$config['entity']['cso_class_electoral-division']['template'] = 'page.class.html';

$config['entity']['cso_class_enumeration-area']['path']     = '/EnumerationArea';
$config['entity']['cso_class_enumeration-area']['query']    = 'cso_class';
$config['entity']['cso_class_enumeration-area']['template'] = 'page.class.html';

$config['entity']['cso_class_province']['path']     = '/Province';
$config['entity']['cso_class_province']['query']    = 'cso_class';
$config['entity']['cso_class_province']['template'] = 'page.class.html';

$config['entity']['cso_class_state']['path']     = '/State';
$config['entity']['cso_class_state']['query']    = 'cso_class';
$config['entity']['cso_class_state']['template'] = 'page.class.html';

$config['entity']['cso_class_traditional-county']['path']     = '/TraditionalCounty';
$config['entity']['cso_class_traditional-county']['query']    = 'cso_class';
$config['entity']['cso_class_traditional-county']['template'] = 'page.class.html';

?>
