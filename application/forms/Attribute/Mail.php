<?php

class Form_Attribute_Mail extends Form_AbstractAppForm
{
    public function __construct($translator, $options = null)
    {
        parent::__construct($translator, $options);

        // vorhandene mail auswählen
        $mailDaoImpl = new Dao_Mail();
        $mails       = $mailDaoImpl->getMails();

        $mailList    = array();
        $mailList[0] = " ";
        foreach ($mails as $mail) {
            $mailList[$mail[Db_Mail::ID]] = $mail[Db_Mail::DESCRIPTION];
        }

        $select = new Zend_Form_Element_Select('mail');
        $select->setLabel('mailLabel');
        $select->setMultiOptions($mailList);
        $this->addElement($select);

        $note = new Zend_Form_Element_Text('note');
        $note->setLabel('note');
        $this->addElement($note);

        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('save');
        $submit->setAttrib('class', 'standard_button');
        $this->addElement($submit);
    }
}