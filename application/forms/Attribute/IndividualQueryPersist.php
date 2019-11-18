<?php

class Form_Attribute_IndividualQueryPersist extends Zend_Form_SubForm
{
    public function __construct($translator, $options = null)
    {
        parent::__construct($translator, $options);
        $this->setName('queryPersist');

        // set form description
        if(isset($options['placeholders']) && !empty($options['placeholders'])) {
            $definitionGroups = array(
                'query' => array(
                    'name'        => $translator->translate('query'),
                    'description' => $translator->translate('attributeHintIndividualQueryHelp'),
                    'list'        => $options['placeholders'],
                ),
            );

            $view = new Zend_View();
            $view->addScriptPath(APPLICATION_PATH . '/modules/cmdb/views/scripts/templates/');
            $view->groups = $definitionGroups;
            $queryDesc    = $view->render('_definition_groups.phtml');

            $this->setDescription($queryDesc);
        }


        // Query
        $query = new Zend_Form_Element_Textarea('query');
        $query->setLabel('cqlQuery');
        $query->addValidator(new Form_Validator_SqlQuery());
        $this->addElement($query);


        $useScript = new Zend_Form_Element_Checkbox('usescript');
        $useScript->setLabel('useScript');
        $this->addElement($useScript);


        // add script 
        $description = new Zend_Form_Element_Text('scriptdescription');
        $description->setLabel('scriptname');
        $description->setAttrib('readonly', true);
        $this->addElement($description);


        $fileName = new Zend_Form_Element_Text('scriptfilename');
        $fileName->setLabel('filename');
        $fileName->setAttrib('readonly', true);
        $this->addElement($fileName);


        $link = new Zend_Form_Element_Image('script');
        $link->setImage(APPLICATION_URL . 'images/icon/upload.png');
        $link->setLabel('queryScript');
        $link->setAttrib('title', $translator->translate("upload"));
        $link->setAttrib('class', "tu_iframe_500x120");
        $link->setAttrib('tabindex', '-1');
        $link->setAttrib('href', APPLICATION_URL . 'fileupload/index/filetype/query/attributeId/script/genId/0');
        $link->setAttrib('toptions', "effect = clip, layout = flatlook");
        $this->addElement($link);


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