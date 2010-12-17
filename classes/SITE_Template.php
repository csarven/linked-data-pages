<?php
/**
 * Methods that handle the data in the query result. Usually called from templates.
 */
class SITE_Template extends LATC_Template
{
    var $sC;

    function __construct($template_filename, $desc, $urispace, $request, $sC)
    {
        //XXX: Beginning of DO NOT MODIFY
        $this->sC = $sC;
        parent::__construct($template_filename, $desc, $urispace, $request, $sC);
        //XXX: End of DO NOT MODIFY
    }


    /*
     * TODO: Change bunch of render*() to renderTabularDimensions($object, $dimensions) or renderDimensions()
     * Perhaps $object is a uri, 
     * $dimensions is like array($ns['property']['maritalStatus'], $ns['property']['age2'], $ns['property']['population'])
     */
    function renderMaritalStatusAgePopulation()
    {
        $sC = $this->sC;
        $c  = $sC->getConfig();

        $ns = array();

        //XXX: Would it be better to use the values from index or the config's ns?
        $ns_property                      = 'http://'.$c['server']['stats.govdata.ie'].'/property/';
        $ns['property']['geoArea']        = $ns_property.'geoArea';
        $ns['property']['maritalStatus']  = $ns_property.'maritalStatus';
        $ns['property']['age2']           = $ns_property.'age2';
        $ns['property']['population']     = $ns_property.'population';

        $ns_codeList = 'http://'.$c['server']['stats.govdata.ie'].'/codelist/';
        $ns['prefixes']['codelist']['marital-status'] = $ns_codeList.'marital-status';
        $ns['prefixes']['codelist']['age2'] = $ns_codeList.'age2';

        $resource_uri = $this->desc->get_primary_resource_uri();

        /**
         * This will get only the triples that have maritalStatus age2 population geoArea as property
         */
        $subjects = $this->desc->get_subjects_where_resource($ns['property']['geoArea'], $resource_uri);
        $properties = array($ns['property']['maritalStatus'], $ns['property']['age2'], $ns['property']['population']);
        $objects    = null;
        $triples = $this->getTriples($subjects, $properties, $objects);

        /**
         * This will get the prefLabels of marital-status age2
         */
        $subjects   = $this->desc->get_subjects_where_resource($sC->getURI('skos:topConceptOf'), $ns['prefixes']['codelist']['marital-status']);
        $properties = array($sC->getURI('skos:prefLabel'));
        $objects    = null;
        $triples_propertyLabels = $this->getTriples($subjects, $properties, $objects);

        $triples = array_merge_recursive($triples, $triples_propertyLabels);

        $subjects   = $this->desc->get_subjects_where_resource($sC->getURI('skos:topConceptOf'), $ns['prefixes']['codelist']['age2']);
        $properties = array($sC->getURI('skos:prefLabel'));
        $objects    = null;
        $triples_propertyLabels = $this->getTriples($subjects, $properties, $objects);

        $triples = array_merge_recursive($triples, $triples_propertyLabels);

        $maritalStatusAgePopulation = array();

        foreach($triples as $s => $po) {
            if (isset($po[$ns['property']['maritalStatus']])
                && isset($triples[$po[$ns['property']['maritalStatus']][0]['value']][$sC->getURI('skos:prefLabel')][0]['value'])

                && isset($po[$ns['property']['age2']])
                && isset($triples[$po[$ns['property']['age2']][0]['value']][$sC->getURI('skos:prefLabel')][0]['value'])

                && isset($po[$ns['property']['population']][0]['value'])) {

                $maritalStatusLabel = $triples[$po[$ns['property']['maritalStatus']][0]['value']][$sC->getURI('skos:prefLabel')][0]['value'];
                $ageLabel = $triples[$po[$ns['property']['age2']][0]['value']][$sC->getURI('skos:prefLabel')][0]['value'];
                $population = $po[$ns['property']['population']][0]['value'];

                if (array_key_exists($ageLabel, $maritalStatusAgePopulation)
                    && array_key_exists($maritalStatusLabel, $maritalStatusAgePopulation[$ageLabel])) {
                    $maritalStatusAgePopulation[$ageLabel][$maritalStatusLabel] += $population;
                }
                else {
                    $maritalStatusAgePopulation[$ageLabel][$maritalStatusLabel] = $population;
                }
            }
        }

        $r = '';
        $r .= "\n".'<table>';
        $r .= "\n".'<caption>Marital status and age breakdown</caption>';
        $r .= "\n".'<thead><tr><td>Age</td><td>Marital status</td></tr></thead>';
        $r .= "\n".'<tbody>';
        $r .= "\n".'<tr><th></th>';
        //FIXME: Looping over just for this is dirty. Revisit.
        foreach($maritalStatusAgePopulation as $age => $maritalStatusPopulation) {
            foreach($maritalStatusPopulation as $maritalStatus => $population) {
                $r .= "\n".'<th>'.$maritalStatus.'</th>';
            }
            break;
        }
        $r .= "\n".'</tr>';

        foreach($maritalStatusAgePopulation as $age => $maritalStatusPopulation) {
            $r .= "\n".'<tr>';
            $r .= "\n".'<th>'.$age.'</th>';
            foreach($maritalStatusPopulation as $maritalStatus => $population) {
                $r .= "\n".'<td>'.$population.'</td>';
            }
            $r .= "\n".'</tr>';
        }
        $r .= "\n".'</tbody>';
        $r .= "\n".'</table>';

        return $r;
    }


    function renderBirthplace()
    {
        $sC = $this->sC;
        $c  = $sC->getConfig();

        //XXX: Would it be better to use the values from index?
        $ns_property                      = 'http://'.$c['server']['stats.govdata.ie'].'/property/';
        $ns['property']['geoArea']        = $ns_property.'geoArea';
        $ns['property']['birthplace']     = $ns_property.'birthplace';

        $ns_codeList = 'http://'.$c['server']['stats.govdata.ie'].'/codelist/';
        $ns['prefixes']['codelist']['birthplace'] = $ns_codeList.'birthplace';

        $resource_uri = $this->desc->get_primary_resource_uri();

        $subjects = $this->desc->get_subjects_where_resource($ns['property']['geoArea'], $resource_uri);
        $properties = array($ns['property']['birthplace']);
        $objects    = null;
        $triples = $this->getTriples($subjects, $properties, $objects);

        $subjects   = $this->desc->get_subjects_where_resource($sC->getURI('skos:topConceptOf'), $ns['prefixes']['codelist']['birthplace']);
        $properties = array($sC->getURI('skos:prefLabel'));
        $objects    = null;
        $triples_propertyLabels = $this->getTriples($subjects, $properties, $objects);
        $triples = array_merge_recursive($triples, $triples_propertyLabels);

        $r = '';
        $r .= '<dl>';
        $r .= "\n".'<dt>People\'s birthplace</dt>';
        $r .= "\n".'<dd>';
        $r .= "\n".'<ul>';
        foreach($triples as $s => $po) {
            if (isset($po[$ns['property']['birthplace']])
                && isset($triples[$po[$ns['property']['birthplace']][0]['value']][$sC->getURI('skos:prefLabel')][0]['value'])) {
                $birthPlaceLabel = $triples[$po[$ns['property']['birthplace']][0]['value']][$sC->getURI('skos:prefLabel')][0]['value'];

                $r .= "\n".'<li><a href="'.$po[$ns['property']['birthplace']][0]['value'].'">'.$birthPlaceLabel.'</a></li>';
            }
        }
        $r .= "\n".'</ul>';
        $r .= "\n".'</dd>';
        $r .= "\n".'</dl>';

        return $r;
    }


    function renderReligionPopulation()
    {
        $sC = $this->sC;
        $c  = $sC->getConfig();

        //XXX: Would it be better to use the values from index?
        $ns_property                      = 'http://'.$c['server']['stats.govdata.ie'].'/property/';
        $ns['property']['geoArea']        = $ns_property.'geoArea';
        $ns['property']['religion']       = $ns_property.'religion';
        $ns['property']['population']     = $ns_property.'population';

        $ns_codeList = 'http://'.$c['server']['stats.govdata.ie'].'/codelist/';
        $ns['prefixes']['codelist']['religion']   = $ns_codeList.'religion';
        $ns['prefixes']['codelist']['population'] = $ns_codeList.'population';

        $resource_uri = $this->desc->get_primary_resource_uri();

        $subjects = $this->desc->get_subjects_where_resource($ns['property']['geoArea'], $resource_uri);
        $properties = array($ns['property']['religion'], $ns['property']['population']);
        $objects    = null;
        $triples = $this->getTriples($subjects, $properties, $objects);

        $subjects   = $this->desc->get_subjects_where_resource($sC->getURI('skos:topConceptOf'), $ns['prefixes']['codelist']['religion']);
        $properties = array($sC->getURI('skos:prefLabel'));
        $objects    = null;
        $triples_propertyLabels = $this->getTriples($subjects, $properties, $objects);
        $triples = array_merge_recursive($triples, $triples_propertyLabels);

        $r = '';
        $r .= "\n".'<table>';
        $r .= "\n".'<caption>What are people\'s religion?</caption>';
        $r .= "\n".'<tbody>';
        $r .= "\n".'<tr><th>Religion</th><th># of people</th></tr>';

        foreach($triples as $s => $po) {
            if (isset($po[$ns['property']['religion']])
                && isset($triples[$po[$ns['property']['religion']][0]['value']][$sC->getURI('skos:prefLabel')][0]['value'])
                && isset($po[$ns['property']['population']][0]['value'])) {

                $religionLabel = $triples[$po[$ns['property']['religion']][0]['value']][$sC->getURI('skos:prefLabel')][0]['value'];
                $religion      = $po[$ns['property']['religion']][0]['value'];
                $population    = $po[$ns['property']['population']][0]['value'];

                $r .= "\n".'<tr><td><a href="'.$religion.'">'.$religionLabel.'</a></td><td>'.$population.'</td></tr>';
            }
        }

        $r .= "\n".'</tbody>';
        $r .= "\n".'</table>';

        return $r;
    }


    function renderUsualResidencePopulation()
    {
        $sC = $this->sC;
        $c  = $sC->getConfig();

        //XXX: Would it be better to use the values from index?
        $ns_property                      = 'http://'.$c['server']['stats.govdata.ie'].'/property/';
        $ns['property']['geoArea']        = $ns_property.'geoArea';
        $ns['property']['usualResidence'] = $ns_property.'usualResidence';
        $ns['property']['population']     = $ns_property.'population';

        $ns_codeList = 'http://'.$c['server']['stats.govdata.ie'].'/codelist/';
        $ns['prefixes']['codelist']['usual-residence']   = $ns_codeList.'usual-residence';
        $ns['prefixes']['codelist']['population'] = $ns_codeList.'population';

        $resource_uri = $this->desc->get_primary_resource_uri();

        $subjects = $this->desc->get_subjects_where_resource($ns['property']['geoArea'], $resource_uri);
        $properties = array($ns['property']['usualResidence'], $ns['property']['population']);
        $objects    = null;
        $triples = $this->getTriples($subjects, $properties, $objects);

        $subjects   = $this->desc->get_subjects_where_resource($sC->getURI('skos:topConceptOf'), $ns['prefixes']['codelist']['usual-residence']);
        $properties = array($sC->getURI('skos:prefLabel'));
        $objects    = null;
        $triples_propertyLabels = $this->getTriples($subjects, $properties, $objects);
        $triples = array_merge_recursive($triples, $triples_propertyLabels);

        $r = '';
        $r .= "\n".'<table>';
        $r .= "\n".'<caption>Where do people usually reside?</caption>';
        $r .= "\n".'<tbody>';
        $r .= "\n".'<tr><th>Location</th><th># of people</th></tr>';

        foreach($triples as $s => $po) {
            if (isset($po[$ns['property']['usualResidence']])
                && isset($triples[$po[$ns['property']['usualResidence']][0]['value']][$sC->getURI('skos:prefLabel')][0]['value'])
                && isset($po[$ns['property']['population']][0]['value'])) {

                $usualResidenceLabel = $triples[$po[$ns['property']['usualResidence']][0]['value']][$sC->getURI('skos:prefLabel')][0]['value'];
                $usualResidence      = $po[$ns['property']['usualResidence']][0]['value'];
                $population    = $po[$ns['property']['population']][0]['value'];

                $r .= "\n".'<tr><td><a href="'.$usualResidence.'">'.$usualResidenceLabel.'</a></td><td>'.$population.'</td></tr>';
            }
        }

        $r .= "\n".'</tbody>';
        $r .= "\n".'</table>';

        return $r;
    }
}
?>
