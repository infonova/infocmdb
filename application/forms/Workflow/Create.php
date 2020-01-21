<?php

/**
 * This class is used to create the workflow input form
 *
 * @param Zend_Translator the translator that should be used to translate the Form fields and the excpetion messages
 * @param configFile the configuration Item where the config information are stored
 *
 */
class Form_Workflow_Create extends Form_AbstractAppForm
{
    public function __construct($translator, $userlist, $options = null)
    {
        parent::__construct($translator, $options);
        $this->setName('create');
        $this->setAttrib('enctype', 'multipart/form-data');
        $this->setAction(APPLICATION_URL . 'workflow/create');

        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/forms/workflow.ini', APPLICATION_ENV);

        // Name
        $name = new Zend_Form_Element_Text('name');
        $name->setLabel('name');
        $name->setAttrib('title', $translator->translate('workflowNameTitle'));
        $name->addValidator(new Form_Validator_UniqueConstraintWorkflows($isupdate = true));
        $name->setAttrib('size', '30');
        $name->setDescription($translator->translate('name_desc'));
        if ($config->name->validators->notempty->enabled) {
            $name->setRequired();
        }
        $name->addValidator(new Form_Validator_WorkflowName($translator));

        // description
        $description = new Zend_Form_Element_Text('description');
        $description->setLabel('description');
        $description->setAttrib('title', $translator->translate('workflowDescriptionTitle'));
        $description->setAttrib('size', '30');
        $description->setDescription($translator->translate('description_desc'));
        if ($config->description->validators->notempty->enabled) {
            $description->setRequired();
        }

        // note
        $note = new Zend_Form_Element_Text('note');
        $note->setLabel('note');
        $note->setAttrib('title', $translator->translate('workflowNoteTitle'));
        $note->setAttrib('size', '30');
        $note->setDescription($translator->translate('note_desc'));
        if ($config->note->validators->notempty->enabled) {
            $note->setRequired();
        }

        $user = new Zend_Form_Element_Select('user');
        $user->setLabel('user');
        $user->addMultiOptions($userlist);
        $user->setAttrib('title', $translator->translate('workflowUserTitle'));
        $user->setAttrib('style', 'width:230px');
        $user->setDescription($translator->translate('user_desc'));

        if ($config->user->validators->notempty->enabled) {
            $user->setRequired();
        }

        $trigger = new Zend_Form_Element_Select('trigger');
        $trigger->setLabel('trigger');
        $trigger->addMultiOptions(array('pleaseChose', 'manual' => 'manual', 'time' => 'time', 'activity' => 'activity'));
        $trigger->setAttrib('title', $translator->translate('workflowTriggerTitle'));
        $trigger->setAttrib('style', 'width:230px');
        $trigger->setDescription($translator->translate('trigger_desc'));

        $responseFormat = new Zend_Form_Element_Select('responseFormat');
        $responseFormat->setLabel('responseFormat');
        $responseFormat->addMultiOptions(array('json' => 'json', 'plain' => 'plain'));
        $responseFormat->setAttrib('title', $translator->translate('workflowResponseFormatTitle'));
        $responseFormat->setAttrib('style', 'width:230px');
        $responseFormat->setDescription($translator->translate('responseFormat_desc'));

        $asynch = new Zend_Form_Element_Checkbox('asynch');
        $asynch->setLabel('isAsynch');
        $asynch->setAttrib('title', $translator->translate('workflowAsynchTitle'));
        $asynch->setDescription($translator->translate('asynch_desc'));

        $active = new Zend_Form_Element_Checkbox('active');
        $active->setLabel('active');
        $active->setValue(1);
        $active->setAttrib('title', $translator->translate('workflowActiveTitle'));
        $active->setDescription($translator->translate('active_desc'));

        $lang = new Zend_Form_Element_Select('script_lang');
        $lang->setLabel('scriptLang');
        $lang->setAttrib('title', $translator->translate('workflowScriptLangTitle'));
        $lang->setAttrib('id', 'lang_selector');
        $lang->setAttrib('onchange', 'changeCodeEditorLanguage()');
        $lang->addMultiOptions(array('perl' => 'Perl', 'golang' => 'Golang',));


        $script = new Zend_Form_Element_Textarea('script');
        $script->setLabel('script');
        $script->setAttrib('title', $translator->translate('workflowScriptTitle'));
        if ($config->script->validators->notempty->enabled)
            $script->setRequired();
        $script->setValue($this->renderTemplate());

        $scriptTest = new Zend_Form_Element_Textarea('script_test');
        $scriptTest->setLabel('scriptTest');
        $scriptTest->setAttrib('title', $translator->translate('workflowScriptTestTitle'));

        $this->addElements(array($name, $description, $note, $user, $trigger, $responseFormat, $asynch, $active, $lang, $script, $scriptTest));

        $this->setElementDecorators(array(
            'ViewHelper',
            'Errors',
            array(new Form_Decorator_MyDescription()),
            array(array('data' => 'HtmlTag'), array('tag' => 'td', 'class' => 'element')),
            array('Label', array('tag' => 'td')),
        ));


    }

    private function renderTemplate()
    {
        $workflowType = Util_Workflow_TypeFactory::create('perl');
        return $workflowType->getTemplate();
    }

}
