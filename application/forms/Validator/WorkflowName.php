<?php

class Form_Validator_WorkflowName extends Zend_Validate_Abstract
{
    const INVALID_WORKFLOW_NAME = 'invalidWorkflowName';
    const WORKFLOW_NAME_CANNOT_END_WITH_TEST = 'workflowNameCannotEndWithTest';

    protected $_messageTemplates = array(
        self::INVALID_WORKFLOW_NAME              => 'Name is invalid',
        self::WORKFLOW_NAME_CANNOT_END_WITH_TEST => 'Name cannot end with _test',
    );

    public function __construct($translator)
    {
        $this->translator = $translator;
    }

    public function isValid($value, $context = null)
    {
        $value = (string)$value;
        $this->_setValue($value);

        if (!is_array($context) || !isset($context['script_lang'])) {
            $this->_error(self::INVALID_WORKFLOW_NAME);
            return false;
        }

        $lang = $context['script_lang'];

        if ($lang == "golang" && preg_match('/_test$/', $value)) {
            $this->_error(self::WORKFLOW_NAME_CANNOT_END_WITH_TEST);
            return false;
        }

        return true;
    }
}
