<?php

/**
 * // TODO: check if still used
 * Plugin_Console
 *
 *
 *
 */
class Plugin_Console extends Zend_Controller_Plugin_Abstract
{

    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
        $config        = new Zend_Config_Ini(APPLICATION_PATH . '/configs/console.ini', APPLICATION_ENV);
        $usernameInput = $config->console->login->username;

        $userDaoImpl     = new Dao_User();
        $userInformation = $userDaoImpl->getUserByUsername($usernameInput);

        $session             = Zend_Registry::get('session');
        $session->username   = $usernameInput;
        $session->lastAction = time();

        // retrieve user information
        $userDto = new Dto_UserDto();
        $userDto->setId($userInformation[Db_User::ID]);
        $userDto->setUsername($userInformation[Db_User::USERNAME]);
        $userDto->setPassword($userInformation[Db_User::PASSWORD]);
        $userDto->setFirstname($userInformation[Db_User::FIRSTNAME]);
        $userDto->setLastname($userInformation[Db_User::LASTNAME]);
        $userDto->setValid($userInformation[Db_User::IS_ACTIVE]);
        $userDto->setDescription($userInformation[Db_User::DESCRIPTION]);
        $userDto->setNote($userInformation[Db_User::NOTE]);
        $userDto->setThemeId($userInformation[Db_User::THEME_ID]);
        $userDto->setCiDelete($userInformation[Db_User::IS_CI_DELETE_ENABLED]);
        $userDto->setRelationEdit($userInformation[Db_User::IS_RELATION_EDIT_ENABLED]);
        $userDto->setLdapAuth($userInformation[Db_User::IS_LDAP_AUTH]);
        $userDto->setLanguage($userInformation[Db_User::LANGUAGE]);
        $userDto->setLayout($userInformation[Db_User::LAYOUT]);
        $userDto->setLastAction(time());
        $userDto->setIpAddress('localhost');


        // this is to avoid redirecting to abstract controller page
        $bootstrap          = Zend_Controller_Front::getInstance()->getParam('bootstrap');
        $options            = $bootstrap->getOptions();
        $sess               = new Zend_Session_Namespace($options['auth']['user']['namespace']);
        $sess->id           = $userDto->getId();
        $sess->username     = $userDto->getUsername();
        $sess->password     = $userDto->getPassword();
        $sess->lastname     = $userDto->getLastname();
        $sess->valid        = $userDto->getValid();
        $sess->description  = $userDto->getDescription();
        $sess->note         = $userDto->getNote();
        $sess->themeId      = $userDto->getThemeId();
        $sess->ciDelete     = $userDto->getCiDelete();
        $sess->relationEdit = $userDto->getRelationEdit();
        $sess->ldapAuth     = $userDto->getLdapAuth();
        $sess->language     = $userDto->getLanguage();
        $sess->layout       = $userDto->getLayout();
        $sess->lastAction   = $userDto->getLastAction();
    }
}