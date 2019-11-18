<?php

/**
 * This class is used to create the attribute input form
 *
 * @param Zend_Translator the translator that should be used to translate the Form fields and the excpetion messages
 * @param configFile the configuration Item where the config information are stored
 *
 */
class Form_Mail_Update extends Form_AbstractAppForm
{

    public function __construct($translator, $templates = array(), $options = null)
    {
        parent::__construct($translator, $options);
        $this->setName('CreateForm');
        $this->setAttrib('enctype', 'multipart/form-data');

        $name = new Zend_Form_Element_Text('name');
        $name->setLabel('name');
        $name->setAttrib('size', 30);
        $name->setRequired(true);
        $name->setAutoInsertNotEmptyValidator(true);
        $name->setAttrib('title', $translator->translate('mailNameTitle'));

        $description = new Zend_Form_Element_Text('description');
        $description->setLabel('description');
        $description->setAttrib('size', 30);
        $description->setRequired(true);
        $description->setAutoInsertNotEmptyValidator(true);
        $description->setAttrib('title',
            $translator->translate('mailDescriptionTitle'));

        $note = new Zend_Form_Element_Textarea('note');
        $note->setLabel('note');
        $note->setAttrib('cols', 30);
        $note->setAttrib('rows', 5);
        $note->setAttrib('title', $translator->translate('mailNoteTitle'));

        // recipients
        $userrecipients = new Zend_Form_Element_Multiselect("userRecipients");
        $userrecipients->setAttrib('class', 'multiselect');
        $userrecipients->setLabel('userRecipients');
        $userrecipients->setRegisterInArrayValidator(false);

        $recipients = new Zend_Form_Element_Textarea('customRecipients');
        $recipients->setLabel('customRecipients');
        $recipients->setAttrib('cols', 30);
        $recipients->setAttrib('rows', 5);
        $recipients->setAttrib('title',
            $translator->translate("recipientsHelp"));
        $recipients->setAttrib('alt', $translator->translate("recipientsHelp"));

        $subject = new Zend_Form_Element_Text('subject');
        $subject->setLabel('subject');
        $subject->setAttrib('size', 30);
        $subject->setRequired(true);
        $subject->setAutoInsertNotEmptyValidator(true);
        $subject->setAttrib('title', $translator->translate('mailSubjectTitle'));

        $mimeType = new Zend_Form_Element_Select('mime_type');
        $mimeType->setLabel('mimeType');
        $mimeType->addMultiOptions(
            array(
                Zend_Mime::TYPE_HTML => Service_Mail_Get::getMimeTranslation(Zend_Mime::TYPE_HTML),
                Zend_Mime::TYPE_TEXT => Service_Mail_Get::getMimeTranslation(Zend_Mime::TYPE_TEXT),
            )
        );
        $mimeType->setRequired(true);
        $mimeType->setAutoInsertNotEmptyValidator(true);
        $mimeType->setAttrib('title', $translator->translate('mailMimeTypeTitle'));
        $mimeType->setAttrib('onclick', 'changeEditor()');

        $enableEditor = new Zend_Form_Element_Checkbox('editor_enbaled');
        $enableEditor->setLabel('mailEditor');
        $enableEditor->setAttrib('title', $translator->translate('mailEditorTitle'));
        $enableEditor->setAttrib('onclick', 'changeEditor()');

        $editor = new Zend_Form_Element_Hidden('editor');

        $body = new Zend_Form_Element_Textarea('body');
        $body->setAttrib('id', 'mail_body');
        $body->setLabel('body');
        $body->setAttrib('style', 'width: 800px; height: 500px; margin-top: 2px');
        $body->setAttrib('title', $translator->translate('mailBodyTitle'));

        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('save');
        $submit->setAttrib('class', 'standard_button');
        $submit->addDecorators(
            array(
                array(
                    array(
                        'data' => 'HtmlTag',
                    ),
                    array(
                        'tag'   => 'td',
                        'class' => 'element',
                        'style' => 'width:20%;',
                    ),
                ),
            ));

        $this->addElements(
            array(
                $name,
                $description,
                $note,
                $userrecipients,
                $recipients,
                $subject,
                $mimeType,
                $enableEditor,
                $template,
                $body,
            ));

        $this->setElementDecorators(
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

        $this->addDecorators(
            array(
                'FormElements',
                array(
                    'HtmlTag',
                    array(
                        'tag' => 'table',
                    ),
                ),
                'Form',
            ));

        $this->addElement($editor);
        $this->addElement($submit);
    }
}