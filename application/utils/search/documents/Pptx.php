<?php


class Util_Search_Document_Pptx extends Zend_Search_Lucene_Document_Pptx
{

    /**
     * Constructor. Creates our indexable document and adds all
     * necessary fields to it using the passed in document
     */
    public function __construct($values, $documentName)
    {
        // If the Filename or the Key values are not set then reject the document.
        if (!isset($values['Filename']) && !isset($values['key'])) {
            return false;
        }

        $res = $this->loadPptxFile($documentName);

        $fieldNames = $res->getFieldNames();

        if (in_array('creator', $fieldNames))
            $values['Author'] = $res->getFieldValue('creator');

        if (in_array('created', $fieldNames))
            $values['CreationDate'] = $res->getFieldValue('created');

        if (in_array('modified', $fieldNames))
            $values['ModDate'] = $res->getFieldValue('modified');

        if (in_array('size', $fieldNames))
            $values['Size'] = $res->getFieldValue('size');

        if (in_array('body', $fieldNames))
            $values['Contents'] = $res->getFieldValue('body');

        // Add the Filename field to the document as a Keyword field.
        $this->addField(Zend_Search_Lucene_Field::Keyword('Filename', $values['Filename']));
        // Add the Key field to the document as a Keyword.
        $this->addField(Zend_Search_Lucene_Field::Keyword('Key', $values['Key']));

        if (!isset($values['Title'])) {
            // Add the Title field to the document as a Text field.
            $values['Title'] = ' ';
        }
        $this->addField(Zend_Search_Lucene_Field::Text('Title', $values['Title']));

        if (!isset($values['Subject'])) {
            // Add the Subject field to the document as a Text field.
            $values['Subject'] = ' ';
        }
        $this->addField(Zend_Search_Lucene_Field::Text('Subject', $values['Subject']));

        if (!isset($values['Author'])) {
            // Add the Author field to the document as a Text field.
            $values['Author'] = ' ';
        }
        $this->addField(Zend_Search_Lucene_Field::Text('Author', $values['Author']));

        if (!isset($values['Keywords'])) {
            // Add the Keywords field to the document as a Keyword field.
            $values['Keywords'] = ' ';
        }
        $this->addField(Zend_Search_Lucene_Field::Keyword('Keywords', $values['Keywords']));

        if (!isset($values['CreationDate'])) {
            // Add the CreationDate field to the document as a Text field.
            $values['CreationDate'] = ' ';
        }
        $this->addField(Zend_Search_Lucene_Field::Text('CreationDate', $values['CreationDate']));

        if (!isset($values['ModDate'])) {
            // Add the ModDate field to the document as a Text field.
            $values['ModDate'] = ' ';
        }
        $this->addField(Zend_Search_Lucene_Field::Text('ModDate', $values['ModDate']));

        if (!isset($values['Size'])) {
            // Add the ModDate field to the document as a Text field.
            $values['Size'] = ' ';
        }
        $this->addField(Zend_Search_Lucene_Field::Text('Size', $values['Size']));

        if (!isset($values['Contents'])) {
            // Add the Contents field to the document as an UnStored field.
            $values['Contents'] = ' ';
        }
        $this->addField(Zend_Search_Lucene_Field::Text('Contents', $values['Contents'], 'iso-8859-1'));
        $this->addField(Zend_Search_Lucene_Field::UnStored('Contents2', $values['Contents'], 'utf-8'));

        if (!isset($values['CIID'])) {
            // Add the Contents field to the document as an UnStored field.
            $values['CIID'] = ' ';
        }
        $this->addField(Zend_Search_Lucene_Field::Text('CIID', $values['CIID']));

    }
}