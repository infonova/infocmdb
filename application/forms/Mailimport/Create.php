<?php

/**
 * This class is used to create the attribute input form
 *
 * @param Zend_Translator the translator that should be used to translate the Form fields and the excpetion messages
 * @param configFile the configuration Item where the config information are stored
 *
 */
class Form_Mailimport_Create extends Form_AbstractAppForm
{
    public function __construct($translator, $isUpdate = false, $options = null)
    {
        parent::__construct($translator, $options);
        $this->setName('create');
        $this->setAttrib('enctype', 'multipart/form-data');

        $host = new Zend_Form_Element_Text('host');
        $host->setLabel('host');
        $host->setRequired(true);
        $host->setAutoInsertNotEmptyValidator(true);
        $host->setAttrib('title', $translator->translate('mailimportHostTitle'));

        $user = new Zend_Form_Element_Text('user');
        $user->setLabel('user');
        $user->setRequired(true);
        $user->setAutoInsertNotEmptyValidator(true);
        $user->setAttrib('title', $translator->translate('mailimportUserTitle'));

        $password = new Zend_Form_Element_Text('password');
        $password->setLabel('password');
        $password->setAttrib('autocomplete', 'off');
        $password->setRequired(true);
        $password->setAutoInsertNotEmptyValidator(true);
        $password->setAttrib('title', $translator->translate('mailimportPasswordTitle'));

        $ssl = new Zend_Form_Element_Select('ssl');
        $ssl->setMultiOptions(array('SSL' => 'SSL', 'TLS' => 'TLS'));
        $ssl->setLabel('ssl');
        $ssl->setAttrib('title', $translator->translate('mailimportSslTitle'));


        $protocol = new Zend_Form_Element_Select('protocol');
        $protocol->setMultiOptions(array('POP3' => 'POP3', 'IMAP' => 'IMAP'));
        $protocol->setLabel('protocol');
        $protocol->setAttrib('title', $translator->translate('mailimportProtocolTitle'));

        $port = new Zend_Form_Element_Text('port');
        $port->setLabel('port');
        $port->setAttrib('title', $translator->translate('mailimportPortTitle'));

        $move_folder = new Zend_Form_Element('move_folder');
        $move_folder->setLabel('move_folder');
        $move_folder->setAttrib('title', $translator->translate('mailimportMoveFolderTitel'));


        $ciField = new Zend_Form_Element_Text('ciField');
        $ciField->setLabel('ciPrefix');
        $ciField->setRequired(true);
        $ciField->setAutoInsertNotEmptyValidator(true);
        $ciField->setAttrib('title', $translator->translate('mailimportCiFieldTitle'));

        $attachBody = new Zend_Form_Element_Checkbox('attachBody');
        $attachBody->setLabel('attachBody');
        $attachBody->setAttrib('title', $translator->translate('mailimportAttachBodyTitle'));

        // can be null
        $body_attribute_id = new Zend_Form_Element_Text('bodyAttributeId');
        $body_attribute_id->setLabel('bodyAttributeId');
        $body_attribute_id->setAttrib('class', 'attributeSelect');
        $body_attribute_id->setAttrib('title', $translator->translate('mailimportBodyAttributeIdTitle'));

        $attachmentAttributeId = new Zend_Form_Element_Text('attachmentAttributeId');
        $attachmentAttributeId->setLabel('attachmentAttributeId');
        $attachmentAttributeId->setAttrib('class', 'attributeSelect');
        $attachmentAttributeId->setAttrib('title', $translator->translate('mailimportAttachementAttributeIdTitle'));
        $attachmentAttributeId->setRequired(true);
        $attachmentAttributeId->setAutoInsertNotEmptyValidator(true);

        $enableCiMail = new Zend_Form_Element_Checkbox('enableCiMail');
        $enableCiMail->setLabel('enableCiMail');
        $enableCiMail->setAttrib('title', $translator->translate('mailimportEnableCiMailTitle'));

        $note = new Zend_Form_Element_Text('note');
        $note->setLabel('note');
        $note->setAttrib('title', $translator->translate('mailimportNoteTitle'));

        $active = new Zend_Form_Element_Checkbox('active');
        $active->setLabel('active');
        $active->setAttrib('title', $translator->translate('mailimportActiveTitle'));

        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('save');
        $submit->setAttrib('class', 'standard_button');

        $this->addElements(array(
            $host,
            $protocol,
            $port,
            $move_folder,
            $user,
            $password,
            $ssl,
            $ciField,
            $attachBody,
            $body_attribute_id,
            $attachmentAttributeId,
            $enableCiMail,
            $note
        ));

        if ($isUpdate) {
            $this->addElement($active);
        }

        $this->addElement($submit);
    }

}