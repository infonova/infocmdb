<?php

/**
 *
 *
 *
 */
class Service_Config_Update extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 503, $themeId);
    }

    // TODO: replace with zend_config_writer_ini ???
    public function savePropertiesToFile($filename, $parameterList)
    {
//        $config = new Zend_Config_Ini($filename, null, array('skipExtends'=> true, 'allowModifications'=>true));
//        $config->production->translation->lifetime = 12;
//        foreach($parameterList as $key => $value) {
//        	$key = '$config->production->' . str_replace('_', '->', $key);
//        	if (1==2)
//	        	$key = $value;
//        }
//        $writer = new Zend_Config_Writer_Ini();
//        $writer->write($filename, $config);
//        return true;

        if (!is_resource($filename)) {
            if (!$file = fopen($filename, 'w+')) return false;
        } else {
            $file = $filename;
        }

        // first write staging
        $newfile = "[production]\r\n";

        unset($parameterList['submit']);
        foreach ($parameterList as $key => $value) {
            $key     = str_replace('_', '.', $key);
            $newfile .= $key . ' = ' . $value;
            $newfile .= "\r\n";
        }

        // finally write staging
        $newfile    .= "[staging : production]";
        $sFileWrite = fwrite($file, trim($newfile));

        if ($sFileWrite === false) {
            // Unable to write data to file
            // try to restore old file

            // exception??
            return false;
        }

        fclose($file);
        return true;
    }

    public function createEditForm($location)
    {
        $config = parse_ini_file($location, 'staging');

        $form = new Zend_Form('createForm');
        $form->setName('createForm');
        $form->setAttrib('enctype', 'multipart/form-data');

        $productionArray = ($config['production']) ? $config['production'] : $config;
        foreach ($productionArray as $key => $value) {
            $key = str_replace('.', '_', $key);
            if (strstr($key, 'element_file_upload')) {
                $configFileupload = new Zend_Config_Ini(APPLICATION_PATH . '/configs/fileupload.ini', APPLICATION_ENV);

                $useDefaultPath = $configFileupload->file->upload->path->default;
                $defaultFolder  = $configFileupload->file->upload->path->folder;
                $path           = ($useDefaultPath) ? APPLICATION_PUBLIC . $defaultFolder : $configFileupload->file->upload->path->custom;
                $folder         = $configFileupload->file->upload->individualization->folder;

                $curr = new Zend_Form_Element_File($key);
                $curr->setDestination($path . $folder . '/');
                $curr->addValidator('Extension', false, 'jpg,jpeg,png,gif');
                $curr->setMaxFileSize($configFileupload->file->upload->individualization->maxfilesize);
                $curr->setDecorators(array(
                    'File',
                    'Errors',
                    array(array('data' => 'HtmlTag'), array('tag' => 'td', 'class' => 'element', 'style' => 'width: 60%')),
                    array('Label', array('tag' => 'td')),
                    array(array('row' => 'HtmlTag'), array('tag' => 'tr')),
                ));

            } else {
                $curr = new Zend_Form_Element_Text($key);
                $curr->setDecorators(array(
                    'ViewHelper',
                    'Errors',
                    array(array('data' => 'HtmlTag'), array('tag' => 'td', 'class' => 'element', 'style' => 'width: 60%')),
                    array('Label', array('tag' => 'td')),
                    array(array('row' => 'HtmlTag'), array('tag' => 'tr')),
                ));
            }
            $curr->setLabel($key);
            $curr->setValue($value);
            $form->addElement($curr);
        }

        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('save');
        $submit->setAttrib('class', 'standard_button');
        $submit->setDecorators(array(
            'ViewHelper',
            'Errors',
            array(array('data' => 'HtmlTag'), array('tag' => 'td', 'class' => 'element', 'style' => 'width: 60%')),
            array('Label', array('tag' => 'td', 'class' => 'invisible')),
            array(array('row' => 'HtmlTag'), array('tag' => 'tr')),
        ));
        $form->addElement($submit);

        $form->addDecorators(array(
            'FormElements',
            array('HtmlTag', array('tag' => 'table', 'class' => 'config')),
            'Form',
        ));

        return $form;
    }

}