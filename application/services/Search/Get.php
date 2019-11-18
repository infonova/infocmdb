<?php

/**
 *
 *
 *
 */
class Service_Search_Get extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 2301, $themeId);
    }


    public function getSearchForm($isFileSearch = false)
    {
        return new Form_Search_Search($this->translator, $isFileSearch);
    }


    public function handleSearchAction($userDto, $projectId, $form, $formData, $history = false)
    {
        $searchClass                  = new Util_Search_Db($userDto, $projectId);
        $formData['searchstringAjax'] = $formData['searchstring'];


        if ($form->isValid($formData)) {
            $form->populate($formData);
            $values         = $form->getValues();
            $values['page'] = null;
            $newValueList   = $searchClass->search($values, $history);
            return $newValueList;
        } else {
            $form->populate($formData);
            $newValueList = $searchClass->search($formData, $history);

            return $newValueList;
        }
    }
}