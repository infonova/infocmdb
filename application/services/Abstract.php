<?php
require_once "Menu/MenuResources.php";

abstract class Service_Abstract
{

    private   $resourceId = null;
    private   $themeId    = null;
    protected $logger     = null;
    protected $translator = null;

    protected function __construct($translator, $logger, $resourceId, $themeId)
    {
        $this->translator = $translator;
        $this->logger     = $logger;
        $this->resourceId = $resourceId;
        $this->themeId    = $themeId;

        if (!$this->checkPermission()) {
            throw new Exception_AccessDenied();
        }
    }

    protected function setResourceId($id)
    {
        $this->resourceId = $id;
    }

    public function getResourceId()
    {
        return $this->resourceId;
    }

    protected function getThemeId()
    {
        return $this->themeId;
    }

    private function checkPermission($ciId = null, $userId = null)
    {
        $factory = new Util_AclFactory();

        $acl = $factory->createAcl($this->resourceId);

        return $acl->hasRole($this->themeId) && $acl->has($this->resourceId)
            && $acl->isAllowed($this->themeId, $this->resourceId);
    }

    public static function getRecourceIds($menu)
    {
        return MenuResources::getResourceIds($menu);
    }

    /*
     * Function to prevent XSS-attacks
     * Removes JS-Code of given string
     * @param string $data string to clean
     * @return string string without js
     */
    public function xssClean($data)
    {
        return Bootstrap::xssClean($data);
    }
}