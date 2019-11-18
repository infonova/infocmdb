<?php

/**
 * This class is used to create the attribute input form
 *
 * @param Zend_Translator the translator that should be used to translate the Form fields and the excpetion messages
 * @param configFile the configuration Item where the config information are stored
 *
 */
class Form_Attribute_IndividualScript extends Zend_Form_SubForm
{

    public function __construct($translator, $options = null)
    {
        $workflowInfo = new Zend_Form_Element_Note('workflow_info');
        $this->addElement($workflowInfo);


        $workflows = array();
        if (isset($options['workflows'])) {
            $workflows = $options['workflows'];
        }

        $workflowOptions = array('' => '');
        foreach ($workflows as $workflow) {
            $workflowOptions[$workflow[Db_Workflow::ID]] = $workflow[Db_Workflow::NAME];
        }

        $workflowId = new Zend_Form_Element_Select('workflow_id');
        $workflowId->setDescription($translator->translate('attributeWorkflowDesc'));
        $workflowId->setLabel('attributeWorkflow');
        $workflowId->setAttrib('title', $translator->translate('attributeWorkflow'));
        $workflowId->setMultiOptions($workflowOptions);
        $workflowId->setAttrib('class', 'combobox');
        $workflowId->setAttrib('style', 'width: 600px');
        $workflowId->setAttrib('onload', "$('.combobox').combobox();");
        $this->addElement($workflowId);

        $isEvent = new Zend_Form_Element_Checkbox('isevent');
        $isEvent->setDescription($translator->translate('iseventDesc'));
        $isEvent->setLabel('isevent');
        $isEvent->setDecorators(
            array(
                'ViewHelper',
                'Errors',
                array(
                    array(
                        'data' => 'HtmlTag',
                    ),
                    array(
                        'tag'   => 'td',
                        'class' => 'element',
                    ),
                ),
                array(
                    'Label',
                    array(
                        'tag' => 'td',
                    ),
                ),
                array(
                    array(
                        'row' => 'HtmlTag',
                    ),
                    array(
                        'tag' => 'tr',
                    ),
                ),
            ));
        $this->addElement($isEvent);


        $this->setElementDecorators(array(
            'ViewHelper',
            'Errors',
            array(new Form_Decorator_MyDescription()),
            array(array('data' => 'HtmlTag'), array('tag' => 'td', 'class' => 'element')),
            array('Label', array('tag' => 'td')),
            array(array('row' => 'HtmlTag'), array('tag' => 'tr')),
        ));


        $this->addDecorators(array(
            'FormElements',
            array('HtmlTag', array('tag' => 'table', 'class' => 'create_form')),
            'Form',
        ));

    }

    private function renderTemplate()
    {
        $view = new Zend_View();
        $view->setEscape('htmlentities');
        $view->addScriptPath(APPLICATION_PATH . '/modules/cmdb/views/scripts/workflow/');

        $config                   = new Util_Config('individualization.ini', APPLICATION_ENV);
        $view->perlLibPath        = $config->getValue('perl.lib.path', '/app/library/perl/libs', Util_Config::STRING);
        $view->infocmdbConfigName = $config->getValue('perl.lib.config_name', 'infocmdb', Util_Config::STRING);

        return $view->render('_template_script_attribute.phtml');
    }
}