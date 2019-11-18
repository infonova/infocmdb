<?php

/**
 * This class is used to create the customization input form
 *
 * @param Zend_Translator the translator that should be used to translate the Form fields and the excpetion messages
 * @param configFile the configuration Item where the config information are stored
 *
 */
class Form_Query_Create extends Form_AbstractAppForm
{
    public function __construct($translator, $config = null)
    {
        parent::__construct($translator, $options);
        $this->setName('CreateForm');

        $definitionGroups = array(
            'query' => array(
                'name' => $translator->translate('queryQueryHelp'),
                'list' => array(
                    ':user_id:' => 'session user id',
                    ':argv1:'   => 'api param 1',
                    ':argv2:'   => 'api param 2',
                    ':argvX:'   => '...',
                ),
            ),
        );
        $view             = new Zend_View();
        $view->addScriptPath(APPLICATION_PATH . '/modules/cmdb/views/scripts/templates/');
        $view->groups = $definitionGroups;
        $queryDesc    = $view->render('_definition_groups.phtml');

        $this->setDescription($queryDesc);

        $name = new Zend_Form_Element_Text('name');
        $name->setLabel('queryName');
        $name->setAttrib('size', '60');
        $name->setRequired(true);
        $name->setAutoInsertNotEmptyValidator(true);
        $name->setAttrib('title', $translator->translate('queryNameTitle'));

        $note = new Zend_Form_Element_Textarea('description');
        $note->setLabel('queryDescription');
        $note->setAttrib('cols', '58');
        $note->setAttrib('rows', '5');
        $note->setRequired(true);
        $note->setAutoInsertNotEmptyValidator(true);
        $note->setAttrib('title', $translator->translate('queryDescriptionTitle'));

        $query = new Zend_Form_Element_Textarea('query');
        $query->setLabel('query');
        $query->setAttrib('class', 'query');
        $query->setAttrib('cols', '58');
        $query->setAttrib('rows', '10');
        $query->setRequired(true);
        $query->setAutoInsertNotEmptyValidator(true);
        $query->setAttrib('title', $translator->translate('queryQueryTitle'));
        $query->addValidator(new Form_Validator_SqlQuery());

        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('save');
        $submit->setAttrib('class', 'standard_button');

        $cancle = new Zend_Form_Element_Submit('cancle');
        $cancle->setLabel('cancle');
        $cancle->setAttrib('onClick', 'return cancelForm();');
        $cancle->setAttrib('class', 'standard_button');


        $this->addElements(array($name, $note, $query, $submit, $cancle));

        $this->setElementDecorators(array(
            'ViewHelper',
            'Errors',
            array(
                array('data' => 'HtmlTag'), array('tag' => 'td', 'class' => 'element query')),
            array('Label', array('tag' => 'td'),
            ),
        ));

        $this->getElement('submit')->removeDecorator('Label');
        $this->getElement('submit')->removeDecorator('data');
        $this->getElement('cancle')->removeDecorator('Label');
        $this->getElement('cancle')->removeDecorator('data');
    }
}