<?php

class Form_Reporting_InputExtended extends Form_AbstractAppForm
{

    public function __construct($translator, $options = null)
    {
        parent::__construct($translator, $options);
        $this->setName('CreateForm');

        $sql = new Zend_Form_Element_Textarea('query');
        $sql->setLabel('sql');
        $sql->setRequired(true);
        $sql->setAttrib('rows', 14);
        $sql->setAutoInsertNotEmptyValidator(true);
        $sql->setAttrib('title', $translator->translate('reportingSqlTitle'));


        $script = new Zend_Form_Element_Textarea('script');
        $script->setLabel('script');
        $script->setRequired(true);
        $script->setAttrib('rows', 14);
        $script->setAutoInsertNotEmptyValidator(true);
        $script->setValue('#!/usr/local/bin/perl -w

use strict;

my $file = $ARGV[0];

open( INFILE, $file )
  or die("Can not open input file: $!");

my @content = <INFILE>;
close(INFILE);


	print "hallo";
	print ";";
	print "du";
	print ";|;";
foreach my $line (@content) {
	print $line;
	print ";|;";
}
	 
');

        $script->setAttrib('title', $translator->translate('reportingScriptTitle'));
        $this->addElements(array($sql, $script));

        $this->setElementDecorators(array(
            'ViewHelper',
            'Errors',
            array(array('data' => 'HtmlTag'), array('tag' => 'td', 'class' => 'element', 'style' => 'width:80%;')),
            array('Label', array('tag' => 'td')),
        ));

        $this->addDecorators(array(
            'FormElements',
            array('HtmlTag', array('tag' => 'table', 'class' => 'create_form')),
            'Form',
        ));
    }

}