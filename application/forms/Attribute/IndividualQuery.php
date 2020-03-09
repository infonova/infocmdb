<?php

class Form_Attribute_IndividualQuery extends Zend_Form_SubForm
{

    public function __construct($translator, $options = null)
    {
        parent::__construct($translator, $options);
        $this->setName('query');

        // set form description
        $view = new Zend_View();
        $view->addScriptPath(APPLICATION_PATH . '/modules/cmdb/views/scripts/templates/');
        $view->groups = array(
            'query' => array(
                'name'        => $translator->translate('query'),
                'description' => $translator->translate('attributeHintIndividualQueryHelp'),
                'list'        => array(
                    ':id:' => $translator->translate('attributeHintIndividualQueryCurrentCiId'),
                ),
            ),
        );
        $queryDesc    = $view->render('_definition_groups.phtml');
        $this->setDescription($queryDesc);

        // Query
        $query = new Zend_Form_Element_Textarea('query');
        $query->setLabel('cqlQuery');
        $query->addValidator(new Form_Validator_SqlQuery());
        $this->addElement($query);

        $is_light = new Zend_Form_Element_Checkbox('isLight');
        $is_light->setLabel('isLight');
        $is_light->setAttrib('title', $translator->translate('isLight_query'));
        $this->addElement($is_light);

        $this->setElementDecorators(array(
            'ViewHelper',
            'Errors',
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
}
