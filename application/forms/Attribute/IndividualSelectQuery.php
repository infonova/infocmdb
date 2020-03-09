<?php

class Form_Attribute_IndividualSelectQuery extends Zend_Form_SubForm
{

    public function __construct($translator, $options = null)
    {
        parent::__construct($translator, $options);

        $this->setName('query');

        // set form description
        $definitionGroups = array(
            'query' => array(
                'name'        => $translator->translate('editQuery'),
                'description' => $translator->translate("attributeHintIndividualSelectQueryEditHelp"),
                'list'        => array(
                    ':id:'    => $translator->translate('attributeHintIndividualSelectQueryEditCurrentCiId'),
                    ':value:' => $translator->translate('attributeHintIndividualSelectQueryEditCurrentValue'),
                ),
            ),
            'listQuery' => array(
                'name'        => $translator->translate('listQuery'),
                'description' => $translator->translate("attributeHintIndividualSelectQueryListHelp"),
            ),
        );
        $view = new Zend_View();
        $view->addScriptPath(APPLICATION_PATH . '/modules/cmdb/views/scripts/templates/');
        $view->groups = $definitionGroups;
        $queryDesc    = $view->render('_definition_groups.phtml');
        $this->setDescription($queryDesc);

        $query = new Zend_Form_Element_Textarea('query');
        $query->setLabel('editQuery');
        $query->addValidator(new Form_Validator_SqlQuery());
        $this->addElement($query);

        $listQuery = new Zend_Form_Element_Textarea('listQuery');
        $listQuery->setLabel('listQuery');
        $listQuery->addValidator(new Form_Validator_SqlQuery());
        $this->addElement($listQuery);

        $multiselectForm = new Form_Attribute_IndividualMultiselect($translator);
        $this->addElements($multiselectForm->getElements());

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
