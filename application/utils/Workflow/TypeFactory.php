<?php

/**
 * Class Util_WorkflowTypeFactory
 */
class Util_Workflow_TypeFactory
{

    /**
     * @param string $name
     * @param array $workflow
     * @param array $options
     * @return Util_Workflow_Type_Abstract A "Util_WorkflowTypeAbstract" subclass object
     */
    public static function create($name, $workflow=null, $options = array())
    {
        $className = "Util_Workflow_Type_" . ucfirst($name);
        return new $className($workflow, $options);
    }
}