<?php

/**
 *
 *
 *
 */
class Service_Attribute_Delete extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 104, $themeId);
    }


    /**
     * deletes or deactivates an attribute by the given attributeId
     *
     * @param int $attributeId
     */
    public function deleteAttribute($attributeId)
    {
        // check if attribute is used somewhere
        $attributeServiceGet = new Service_Attribute_Get($this->translator, $this->logger, $this->getThemeId());
        $attributeDaoImpl    = new Dao_Attribute();
        $count               = $attributeDaoImpl->countAttributeUsed($attributeId);

        $statusCode = 0;
        try {
            if ($count['cnt'] != 0) {
                // deactivate
                $attributeDaoImpl->deactivateAttribute($attributeId);
                $statusCode = 2;
            } else {
                $attributeStored = $attributeServiceGet->getAttibute($attributeId);
                Util_Workflow::archiveScript($attributeStored[Db_Attribute::SCRIPT_NAME], null, 'executable');

                $attributeDaoImpl->deleteAttribute($attributeId);
                $statusCode = 1;
            }
        } catch (Exception $e) {
            $this->logger->log($e);
            try {
                $attributeDaoImpl->deactivateAttribute($attributeId);
                $statusCode = 2;
            } catch (Exception $e) {
                $this->logger->log($e);
                $statusCode = 0;
            }
        }

        return $statusCode;
    }

}