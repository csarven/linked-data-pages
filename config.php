<?php
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

$this->config['site']['server'] = 'site';
$this->config['site']['theme']  = 'cso';      // 'default' in /var/www/site/theme/cso
$this->config['site']['logo']   = 'logo_data-gov.ie.png';  // logo.png in /var/www/site/theme/default/images/logo.jpg

$this->config['server']['govdata.ie']       = 'site';
$this->config['server']['geo.govdata.ie']   = 'geo.site';
$this->config['server']['stats.govdata.ie'] = 'stats.site';

/*
 * Common prefixes for this dataset
 */
$this->config['prefixes'] = array(
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
$this->config['sparql_query']['empty'] = "";
$this->config['sparql_query']['default'] = "
    DESCRIBE <URI>
";


$this->config['entity']['cso_data']['path']     = '/data';
$this->config['entity']['cso_data']['query']    = 'default';
$this->config['entity']['cso_data']['template'] = 'page.default.html';

$this->config['entity']['cso_codelist']['path']     = '/codelist';
$this->config['entity']['cso_codelist']['query']    = 'default';
$this->config['entity']['cso_codelist']['template'] = 'page.default.html';


$this->config['sparql_query']['cso_home'] = "
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
$this->config['entity']['cso_home']['path']     = "/";
$this->config['entity']['cso_home']['query']    = 'cso_home';
$this->config['entity']['cso_home']['template'] = 'page.home.html';



$this->config['entity']['cso_about']['path']     = "/about";
$this->config['entity']['cso_about']['query']    = 'empty';
$this->config['entity']['cso_about']['template'] = 'page.about.html';



$this->config['sparql_query']['cso_city'] = "
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
$this->config['entity']['cso_city']['path']     = '/city';
$this->config['entity']['cso_city']['query']    = 'cso_city';
$this->config['entity']['cso_city']['template'] = 'page.geo.html';



$this->config['sparql_query']['cso_class'] = "
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
$this->config['entity']['cso_class_administrative-county']['path']     = '/AdministrativeCounty';
$this->config['entity']['cso_class_administrative-county']['query']    = 'cso_class';
$this->config['entity']['cso_class_administrative-county']['template'] = 'page.class.html';

$this->config['entity']['cso_class_city']['path']     = '/City';
$this->config['entity']['cso_class_city']['query']    = 'cso_class';
$this->config['entity']['cso_class_city']['template'] = 'page.class.html';

$this->config['entity']['cso_class_electoral-division']['path']     = '/ElectoralDivision';
$this->config['entity']['cso_class_electoral-division']['query']    = 'cso_class';
$this->config['entity']['cso_class_electoral-division']['template'] = 'page.class.html';

$this->config['entity']['cso_class_enumeration-area']['path']     = '/EnumerationArea';
$this->config['entity']['cso_class_enumeration-area']['query']    = 'cso_class';
$this->config['entity']['cso_class_enumeration-area']['template'] = 'page.class.html';

$this->config['entity']['cso_class_province']['path']     = '/Province';
$this->config['entity']['cso_class_province']['query']    = 'cso_class';
$this->config['entity']['cso_class_province']['template'] = 'page.class.html';

$this->config['entity']['cso_class_state']['path']     = '/State';
$this->config['entity']['cso_class_state']['query']    = 'cso_class';
$this->config['entity']['cso_class_state']['template'] = 'page.class.html';

$this->config['entity']['cso_class_traditional-county']['path']     = '/TraditionalCounty';
$this->config['entity']['cso_class_traditional-county']['query']    = 'cso_class';
$this->config['entity']['cso_class_traditional-county']['template'] = 'page.class.html';
?>
