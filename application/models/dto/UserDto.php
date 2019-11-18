<?php

class Dto_UserDto
{

    private $id;
    private $username;
    private $password;
    private $firstname;
    private $lastname;
    private $root;
    private $valid;
    private $description;
    private $note;
    private $themeId;
    private $ciDelete;
    private $relationEdit;
    private $ldapAuth;
    private $language;
    private $layout;
    private $lastAction;
    private $ipAddress;
    private $twoFactorAuth;
    private $displayAnnouncement;

    public function __construct($row = [])
    {
        foreach ($row as $columnName => $columnValue) {
            $propertyName = Util_Config::camelize($columnName, '_');
            if (property_exists($this, $propertyName)) {
                $this->$propertyName = $columnValue;
            }
        }
    }

    // Setter

    public function setId($idToSet)
    {
        $this->id = $idToSet;
    }

    public function setUsername($usernameToSet)
    {
        $this->username = $usernameToSet;
    }

    public function setPassword($passwordToSet)
    {
        $this->password = $passwordToSet;
    }

    public function setFirstname($firstnameToSet)
    {
        $this->firstname = $firstnameToSet;
    }

    public function setLastname($lastnameToSet)
    {
        $this->lastname = $lastnameToSet;
    }

    public function setRoot($isRoot)
    {
        $this->root = $isRoot;
    }

    public function setValid($isValid)
    {
        $this->valid = $isValid;
    }

    public function setDescription($descriptionToSet)
    {
        $this->description = $descriptionToSet;
    }

    public function setNote($noteToSet)
    {
        $this->note = $noteToSet;
    }

    public function setThemeId($themeIdToSet)
    {
        $this->themeId = $themeIdToSet;
    }

    public function setCiDelete($ciDeleteToSet)
    {
        $this->ciDelete = $ciDeleteToSet;
    }

    public function setRelationEdit($relationEditToSet)
    {
        $this->relationEdit = $relationEditToSet;
    }

    public function setLdapAuth($ldapAuthToSet)
    {
        $this->ldapAuth = $ldapAuthToSet;
    }

    public function setLanguage($language)
    {
        $this->language = $language;
    }

    public function setLayout($layout)
    {
        $this->layout = $layout;
    }

    public function setLastAction($lastAction)
    {
        $this->lastAction = $lastAction;
    }

    public function setIpAddress($ipAddress)
    {
        $this->ipAddress = $ipAddress;
    }

    public function setTwoFactorAuth($twoFactorAuth)
    {
        $this->twoFactorAuth = $twoFactorAuth;
    }

    public function setDisplayAnnouncement($displayAnnouncementToSet)
    {
        $this->displayAnnouncement = $displayAnnouncementToSet;
    }

    // Getter

    public function getId()
    {
        return $this->id;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function getFirstname()
    {
        return $this->firstname;
    }

    public function getLastname()
    {
        return $this->lastname;
    }

    public function getRoot()
    {
        return $this->root;
    }

    public function getValid()
    {
        return $this->valid;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getNote()
    {
        return $this->note;
    }

    public function getThemeId()
    {
        return $this->themeId;
    }

    public function getCiDelete()
    {
        return $this->ciDelete;
    }

    public function getRelationEdit()
    {
        return $this->relationEdit;
    }

    public function getLdapAuth()
    {
        return $this->ldapAuth;
    }

    public function getLanguage()
    {
        return $this->language;
    }

    public function getLayout()
    {
        return $this->layout;
    }

    public function getLastAction()
    {
        return $this->lastAction;
    }

    public function getIpAddress()
    {
        return $this->ipAddress;
    }

    public function getTwoFactorAuth()
    {
        return $this->twoFactorAuth;
    }

    public function getDisplayAnnouncement()
    {
        return $this->displayAnnouncement;
    }
}
