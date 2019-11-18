<?php

/**
 * Class Util_Executable
 *
 * Interact with attribute script files
 */
class Util_Executable
{

    /**
     * @var Zend_Log
     */
    private $logger;

    /**
     * Util_Executable constructor.
     *
     * @param Zend_Log $logger
     */
    public function __construct($logger)
    {
        $this->logger = $logger;
    }

    /**
     * Start a executable script
     *
     * Script will be resolved from attribute defined in $ciAttribute
     *
     * @param array       $ciAttribute An array containing a ci_attribute-row
     * @param Dto_UserDto $user        Optional - An array containing a user-row
     * @param string|null $apiKey      Optional - If not given a apikey will be generated
     *
     * @return array
     * @throws Exception if new api key is generated but key already exists in db
     */
    public function startExecutable($ciId, $attributeId, $ciAttributeId = null, $user = null, $triggerType = 'executable')
    {
        $translator        = Zend_Registry::get('Zend_Translate');
        $attributeDaoImpl  = new Dao_Attribute();
        $utilWorkflow      = new Util_Workflow($this->logger);
        $historizationUtil = new Util_Historization();
        $triggerUtil       = new Util_Trigger($this->logger);
        $attribute         = $attributeDaoImpl->getAttribute($attributeId);

        if (is_null($ciAttributeId)) {
            $ciAttribute   = $attributeDaoImpl->getCiAttributesByCiIdAttributeID($ciId, $attribute[Db_Attribute::ID]);
            $ciAttributeId = $ciAttribute[Db_CiAttribute::ID];
        }

        if (is_null($user)) {
            $user = new Dto_UserDto();
            $user->setId(0);
            $user->setUsername('unknown');
        }

        $logMessage = sprintf('executing workflow with ID "%d" triggered by attribute "%s" and user "%s"',
            $attribute[Db_Attribute::WORKFLOW_ID],
            $attribute[Db_Attribute::NAME],
            $user->getUsername()
        );
        $this->logger->log($logMessage, Zend_Log::INFO);

        $workflowContext = array(
            "ciid"          => $ciId,
            "userId"        => $user->getId(),
            "ciAttributeId" => $ciAttributeId,
            "attributeId"   => $attributeId,
            "triggerType"   => $triggerType,
        );

        $output = $utilWorkflow->startWorkflow($attribute['workflow_id'], $user->getId(), $workflowContext, false);

        $lastLine         = '';
        $outputIdentifier = '[SCRIPT] ';
        if (isset($output['log'])) {
            foreach ($output['log'] as $log) {
                if (isset($log['message']) && strpos($log['message'], $outputIdentifier) !== false) {
                    $lastLine = str_replace($outputIdentifier, '', $log['message']);
                }
            }
        }

        $this->logger->log('script executed and returned "' . $lastLine . '"', Zend_Log::DEBUG);
        $historyId = $historizationUtil->createHistory($user->getId(), Util_Historization::MESSAGE_CI_SCRIPT_UPDATE);

        $data                             = array();
        $data[Db_CiAttribute::VALUE_TEXT] = $lastLine;
        $data[Db_CiAttribute::HISTORY_ID] = $historyId;

        $redirectUrl = '';
        if (strpos($lastLine, ':redirect:') !== false) {
            $redirectUrl = str_replace(':redirect:', '', $lastLine);
            unset($data[Db_CiAttribute::VALUE_TEXT]);
        }

        $attributeDaoImpl->updateCiAttribute($ciAttributeId, $data);
        $triggerUtil->updateAttribute($ciAttributeId, $user->getId());

        if ($output['status'] == 'FAILED') {
            $notification['error'] = $translator->translate('executeScriptError');
        } else {
            $notification['success'] = $translator->translate('executeScriptSuccess');
        }

        $result = array(
            'output'       => $output,
            'last_line'    => $lastLine,
            'redirect_url' => $redirectUrl,
            'notification' => $notification
        );

        return $result;
    }

    /**
     * Get the absolute path to the folder containing executables
     *
     * @return string path to executable folder
     */
    public static function getExecutablePath()
    {
        $configFileUpload = new Util_Config('fileupload.ini', APPLICATION_ENV);

        $useDefaultPath = $configFileUpload->getValue('file.upload.path.default', true);
        $executableDir  = $configFileUpload->getValue('file.upload.executeable.folder', 'executeable');

        if ($useDefaultPath) {
            $uploadsDir = $configFileUpload->getValue('file.upload.path.folder', '_uploads');
            $path       = APPLICATION_PUBLIC . $uploadsDir;
        } else {
            $path = $configFileUpload->getValue('file.upload.path.custom');
        }

        $path .= $executableDir;

        return $path;

    }

    /**
     * Get a new API key
     *
     * @param $userId
     *
     * @return bool|string
     * @throws Exception if new api key is generated but key already exists in db
     */
    public function getApikey($userId)
    {
        $bootstrap = Zend_Controller_Front::getInstance()->getParam('bootstrap');
        $options   = $bootstrap->getOptions();
        $timeout   = $options['auth']['login']['timeout'];

        $authInterface = new Dao_Authentication();
        $apikey        = $authInterface->setApiSession($userId, $timeout);
        return $apikey;
    }

}