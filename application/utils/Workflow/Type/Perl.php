<?php

/**
 * Class Util_Workflow_Type_Perl
 */
class Util_Workflow_Type_Perl extends Util_Workflow_Type_Abstract
{
    /**
     * Get name of workflow type
     *
     * @return string
     */
    public function getType()
    {
        return 'perl';
    }

    /**
     * Get file extension
     *
     * @return string
     */
    public function getExtension()
    {
        return 'pl';
    }

    /**
     * Get script template for new workflows
     *
     * @return string
     */
    public function getTemplate()
    {
        $view = new Zend_View();
        $view->setEscape('htmlentities');
        $view->addScriptPath(APPLICATION_PATH . '/modules/cmdb/views/scripts/workflow/template');

        $config                   = new Util_Config('individualization.ini', APPLICATION_ENV);
        $view->perlLibPath        = $config->getValue('perl.lib.path', '/app/library/perl/libs', Util_Config::STRING);
        $view->infocmdbConfigName = $config->getValue('perl.lib.config_name', 'infocmdb', Util_Config::STRING);

        return $view->render('perl.phtml');
    }

    /**
     * Save script content into workflow file
     *
     * @param string      $script
     * @param Dto_UserDto $user
     * @param string filePath
     * @return bool
     */
    public function saveScript($script, $user, $filePath = '')
    {
        // update comment section with workflow-information
        $docHeadParts = Util_Workflow::getDocHeadPartsForWorkflow($this->workflow, $user);
        $docHeader    = Util_Workflow::getDocHead($docHeadParts, '#');
        $script       = preg_replace('/#* DOCHEAD #*.+#* \/DOCHEAD #*/s', $docHeader, $script);

        return parent::saveScript($script, $user, $filePath);
    }

    /**
     * Execute workflow
     *
     * @param array    $parameters
     * @param Util_Log $logger
     * @return bool
     */
    public function execute($parameters = array(), $logger = null)
    {
        $scriptPath = $this->getWorkflowPath() .'/'. $this->getScriptName();
        $perl       = $this->individualizationConfig->getValue('perl.binary.path', 'perl', Util_Config::STRING);

        $command = $perl . ' ' . $scriptPath . ' ' . self::transformShellArgs($parameters, $this->getResponseFormat());

        $result = $this->runCommand($command, $logger);

        return $result;
    }

    /**
     * Validate given script and test content
     *
     * @param string $script     workflow script content
     * @param string $testScript test script content
     * @param array  $output     reference for getting validation output
     * @return bool true if script and test are valid
     */
    public function validate($script, $testScript, &$output = array())
    {
        $config   = new Util_Config('fileupload.ini', APPLICATION_ENV);
        $tmpPath  = realpath($config->getValue('file.upload.tmp', '/tmp', Util_Config::STRING));
        $fileName = md5($script) . ".pl";
        $filePath = $tmpPath .'/'. $fileName;
        $user     = new Dto_UserDto();
        $perl     = $this->individualizationConfig->getValue('perl.binary.path', 'perl', Util_Config::STRING);

        parent::saveScript($script, $user, $filePath);

        $command = $perl . ' -wc ' . $filePath;
        $result  = $this->runCommand($command);
        unlink($filePath);

        $outputCommand = array('$ ' . $command);
        $output        = array_merge($outputCommand, $result['stdout'], $result['stderr']);
        return $result['exit_code'] === 0;
    }
}