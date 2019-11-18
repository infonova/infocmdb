<?php

require_once __DIR__ . '/../services/Menu/MenuResources.php';

class Plugin_ControllerGuard extends Zend_Controller_Plugin_Abstract
{

    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
        $sess = self::getUserSessionStore();

        $themeId = (int)$sess->themeId;
        if ($themeId > 0) {
            $sess->needsPermissionCheck = false;

            $result = self::authorizeRequest($request, $themeId);
            if ($result === false) {
                $this->stopRequest($request);
                return;
            }
        } else {
            // can't fetch themeId - permission needs to be checked in controller
            $sess->needsPermissionCheck = true;
        }
    }

    public static function getUserSessionStore()
    {
        $bootstrap = Zend_Controller_Front::getInstance()->getParam('bootstrap');
        $options   = $bootstrap->getOptions();
        return new Zend_Session_Namespace($options['auth']['user']['namespace']);
    }

    /**
     * @param $request Zend_Controller_Request_Abstract
     *
     * @return string
     */
    public static function requestIdentifier($request) {
        $moduleName     = $request->getModuleName();
        $controllerName = $request->getControllerName();
        $actionName     = $request->getActionName();
        return $moduleName . '/' . $controllerName . '::' . $actionName;
    }

    /**
     * @param $request Zend_Controller_Request_Abstract
     * @param $themeId integer
     *
     * @return bool
     */
    public static function authorizeRequest($request, int $themeId)
    {
        $identifier = self::requestIdentifier($request);
        $resourceIds = MenuResources::getResourceIdsForAction($identifier);

        return Plugin_ControllerGuard::checkPermission($resourceIds, $themeId);
    }

    public static function checkPermission(array $resourceIds, int $themeId)
    {
        foreach ($resourceIds as $resourceId) {
            if ($resourceId === -1) {
                return false;
            }

            $factory = new Util_AclFactory();
            $acl     = $factory->createAcl($resourceId);

            if (
                $acl->hasRole($themeId) === false
                || $acl->has($resourceId) === false
                || $acl->isAllowed($themeId, $resourceId) === false
            ) {
                return false;
            }
        }

        return true;
    }

    protected function stopRequest(Zend_Controller_Request_Abstract $request)
    {
        $sess = self::getUserSessionStore();
        $logger = Zend_Registry::get('Log');
        $logger->log(
            sprintf('Authorization failed for User[%s] Path[%s] ThemeId[%d].',
                $sess->username,
                self::requestIdentifier($request),
                $sess->themeId
            ),
            Zend_Log::WARN
        );

        throw new Exception_AccessDenied();
    }

}