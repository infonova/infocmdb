<?php

class Form_Reporting_Create extends Form_AbstractAppForm
{

    public function __construct($translator, $options = null)
    {
        parent::__construct($translator, $options);
        $this->setName('createReporting');
        $this->setAttrib('enctype', 'multipart/form-data');


        $name = new Zend_Form_Element_Text('name');
        $name->setLabel('name');
        $name->setRequired(true);
        $name->setAutoInsertNotEmptyValidator(true);
        $name->setAttrib('title', $translator->translate('reportingNameTitle'));
        $name->addValidator(new Form_Validator_UniqueConstraintReports());
        $name->setAttrib('size', '40');
        $name->setDescription($translator->translate('name_desc'));
        $this->addElement($name);


        $description = new Zend_Form_Element_Textarea('description');
        $description->setLabel('description');
        $description->setRequired(true);
        $description->setAttrib('rows', 14);
        $description->setAttrib('cols', 38);
        $description->setDescription($translator->translate('description_desc'));
        $description->setAutoInsertNotEmptyValidator(true);
        $description->setAttrib('title', $translator->translate('reportingDescriptionTitle'));
        $this->addElement($description);


        $note = new Zend_Form_Element_Text('note');
        $note->setLabel('note');
        $note->setAttrib('size', '40');
        $note->setDescription($translator->translate('note_desc'));
        $note->setAttrib('title', $translator->translate('reportingNoteTitle'));
        $this->addElement($note);

        // input
        $inputValues              = array();
        $inputValues[null]        = $translator->translate('pleaseChose');
        $inputValues['sql']       = $translator->translate('inputSql');
        $inputValues['cql']       = $translator->translate('inputCql');
        $inputValues['scriptold'] = $translator->translate('inputScriptold');
        $inputValues['script']    = $translator->translate('inputScript');
        $inputValues['extended']  = $translator->translate('inputExtended');
        $inputValues['gui']       = $translator->translate('inputGui');

        $input = new Zend_Form_Element_Select('input');
        $input->setLabel('input');
        $input->setDescription($translator->translate('input_desc'));
        $input->setRequired(true);
        $input->setAttrib('style', 'width:200px');
        $input->addMultiOptions($inputValues);
        $input->setAttrib('title', $translator->translate('reportingInputTitle'));
        $this->addElement($input);


        // output
        $outputValues             = array();
        $outputValues[null]       = $translator->translate('pleaseChose');
        $outputValues['none']     = $translator->translate('outputNone');
        $outputValues['xls']      = $translator->translate('outputXls');
        $outputValues['csv']      = $translator->translate('outputCsv');
        $outputValues['mailbody'] = $translator->translate('outputMailbody');

        $output = new Zend_Form_Element_Select('output');
        $output->setAttrib('style', 'width:200px');
        $output->setDescription($translator->translate('output_desc'));
        $output->setLabel('output');
        $output->setRequired(true);
        $output->addMultiOptions($outputValues);
        $output->setAttrib('title', $translator->translate('reportingOutputTitle'));
        $this->addElement($output);


        // versand
        $transportValues         = array();
        $transportValues[null]   = $translator->translate('pleaseChose');
        $transportValues['none'] = $translator->translate('transportNone');
        $transportValues['mail'] = $translator->translate('transportMail');
        $transportValues['ftp']  = $translator->translate('transportFtp');

        $transport = new Zend_Form_Element_Select('transport');
        $transport->setDescription($translator->translate('transport_desc'));
        $transport->setAttrib('style', 'width:200px');
        $transport->setLabel('transport');
        $transport->setRequired(true);
        $transport->addMultiOptions($transportValues);
        $transport->setAttrib('title', $translator->translate('reportingTransportTitle'));
        $this->addElement($transport);


        // trigger
        $triggerValues           = array();
        $triggerValues[null]     = $translator->translate('pleaseChose');
        $triggerValues['time']   = $translator->translate('triggerTime');
        $triggerValues['manual'] = $translator->translate('triggerManual');

        $trigger = new Zend_Form_Element_Select('trigger');
        $trigger->setDescription($translator->translate('trigger_desc'));
        $trigger->setAttrib('style', 'width:200px');
        $trigger->setLabel('trigger');
        $trigger->setRequired(true);
        $trigger->addMultiOptions($triggerValues);
        $trigger->setAttrib('title', $translator->translate('reportingTriggerTitle'));
        $this->addElement($trigger);


        $mail = new Zend_Form_Element_Textarea('mail');
        $mail->setLabel('mailaddresses');
        $mail->setAttrib('rows', 14);
        $mail->setAutoInsertNotEmptyValidator(true);
        $mail->setAttrib('title', $translator->translate('reportingMailTitle'));
        $this->addElement($mail);

        // mail_content
        $mail_content = new Zend_Form_Element_Textarea('mail_content');
        $mail_content->setLabel('mail_content');
        $mail_content->setAttrib('title', $translator->translate('reportingMailContentTitle'));
        $mail_content->setAttrib('class', 'tinymce');
        //$mail_content->setDescription($translator->translate('mail_content_desc'));
        $this->addElement($mail_content);

        $timeTrigger = new Zend_Form_Element_Textarea('time');
        $timeTrigger->setLabel('timeTriggered');
        $timeTrigger->setRequired(true);
        $timeTrigger->setAttrib('rows', 14);
        $timeTrigger->setAutoInsertNotEmptyValidator(true);
        $timeTrigger->setAttrib('title', $translator->translate('reportingTimeTriggerTitle'));

        $this->setElementDecorators(array(
            'ViewHelper',
            'Errors',
            array(new Form_Decorator_MyDescription()),
            array(array('data' => 'HtmlTag'), array('tag' => 'td', 'class' => 'element', 'style' => 'width:50%')),
            array('Label', array('tag' => 'td')),
        ));

        $this->addDecorators(array(
            'FormElements',
            array('HtmlTag', array('tag' => 'table', 'class' => 'create_form')),
            'Form',
        ));
    }

}