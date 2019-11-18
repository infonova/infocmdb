<?php

class Form_Attribute_AddIcon extends Form_AbstractAppForm
{
    public function __construct($translator, $options = null)
    {
        parent::__construct($translator, $options);

        $this->setName('CreateForm');
        $this->setAttrib('enctype', 'multipart/form-data');
    }

    public function addIconUpload($fileUploadConfig)
    {
        $icon = new Zend_Form_Element_File('ciicon');
        $icon->setLabel('icon');

        $path        = $fileUploadConfig->file->upload->path->folder;
        $path        = APPLICATION_PUBLIC .'/'. $path;
        $destination = $fileUploadConfig->file->upload->icon->folder;

        $minwidth  = $fileUploadConfig->file->upload->icon->minwidth;
        $minheight = $fileUploadConfig->file->upload->icon->minheight;
        $maxwidth  = $fileUploadConfig->file->upload->icon->maxwidth;
        $maxheight = $fileUploadConfig->file->upload->icon->maxheight;

        if (!$minwidth) {
            $minwidth = 10;
        }
        if (!$minheight) {
            $minheight = 10;
        }
        if (!$maxwidth) {
            $maxwidth = 30;
        }
        if (!$maxheight) {
            $maxheight = 30;
        }

        $icon->setMaxFileSize($fileUploadConfig->file->upload->icon->maxfilesize);
        $icon->setDestination($path . $destination);
        $icon->addValidator('Extension', false, 'jpg,jpeg,png,gif');
        $icon->addValidator('ImageSize', false, array($minwidth, $minheight, $maxwidth, $maxheight));
        $icon->setRequired(false);

        $icon->addDecorators(array(
            array('HtmlTag', array('tag' => 'td')),
            array('Label', array('tag' => 'td')),
        ));
        $this->addElement($icon);

        $submit = new Zend_Form_Element_Submit('upload');
        $submit->setLabel('upload');
        $this->addElement($submit);
    }

}