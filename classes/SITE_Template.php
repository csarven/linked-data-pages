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
}
?>
