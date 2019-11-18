<?php

/**
 * This class is used to create the citype filter
 *
 * @param Zend_Translator the translator that should be used to translate the Form fields and the excpetion messages
 * @param configFile the configuration Item where the config information are stored
 *
 */
class Form_Favourites_Filter extends Zend_Form
{
    public function __construct($translator, $groups, $options = null)
    {
        parent::__construct($translator, $options);
        $this->setName('filterForm');
        $this->setAttrib('enctype', 'multipart/form-data');

        if (!$groups) {
            $groups = array();
        }

        $newGroup            = array();
        $newGroup[null]      = '';
        $newGroup['default'] = 'default';


        foreach ($groups as $group) {
            $newGroup[$group[Db_CiFavourites::GROUP]] = $group[Db_CiFavourites::GROUP];
        }
        //		$colorList = array();
        //		$colorList[null] = '';
        //		$colorList['r'] = 'red';
        //		$colorList['g'] = 'green';
        //		$colorList['b'] = 'blue';
        //		$colorList['v'] = 'purple';

        // parent Ci type ->option drop down
        $search = new Zend_Form_Element_Select('color');
        $search->setLabel('colorFilter');
        $search->addMultiOptions($newGroup);
        $search->setAttrib('onChange', 'this.form.submit();');
        $search->setAttrib('style', 'width:200px');

        $this->addElement($search);
    }
}