<?php
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

        /**
         * PREFIXes
         */
        $geoDataGov = $c['prefixes']['geoDataGov'];
        $skos       = $c['prefixes']['skos'];


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
                              ?s <{$c['prefixes']['city']}> <$uri> .
                          }";
                break;
             */

            case 'cso_class':
                $query = "PREFIX skos: <$skos>

                          CONSTRUCT {
                              <$uri> ?p1 ?o1 .

                              ?s2 a <$uri> .
                              ?s2 skos:prefLabel ?o3 .
                          }
                          WHERE {
                              {
                                  <$uri> ?p1 ?o1 .
                              }
                              UNION
                              {
                                  ?s2 a <$uri> .
                                  OPTIONAL {
                                      ?s2 skos:prefLabel ?o3 .
                                  }
                              }
                          }
                         ";
                break;

            case 'cso_city':
                $query = "PREFIX skos: <$skos>

                          CONSTRUCT {
                              ?s ?geoArea <$uri> .
                              ?s ?p ?o .

                              ?o a skos:Concept .
                              ?o skos:prefLabel ?o_prefLabel .
                              <$uri> ?p0 ?o0 .
                          }
                          WHERE {
                              {
                                  ?s ?geoArea <$uri> .
                                  ?s ?p ?o .
                                  OPTIONAL {
                                      ?o a skos:Concept .
                                      ?o skos:prefLabel ?o_prefLabel .
                                  }
                              }
                              UNION
                              {
                                  <$uri> ?p0 ?o0 .
                              }
                          }";
                break;
/*
            case 'cso_property':
                $query = "CONSTRUCT { ?s <$uri> ?o }
                          WHERE {
                              ?s <$uri> ?o .
                          }
                          LIMIT 10";
                break;
*/
            case 'cso_home':
                $query = "PREFIX geoDataGov: <$geoDataGov>
                          PREFIX skos: <$skos>

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
                          }";
                break;
        }

        return $this->graph($query, $output);
    }
}
?>
