<?php
/**
 * Methods that handle the data in the query result. Usually called from templates.
 */
class SITE_Template extends LDP_Template
{
    //XXX: Beginning of DO NOT MODIFY
    var $sC;

    function __construct($template_filename, $desc, $urispace, $request, $sC)
    {

        $this->sC = $sC;
        parent::__construct($template_filename, $desc, $urispace, $request, $sC);
    }
    //XXX: End of DO NOT MODIFY
}
?>
