<?php

/**
 * Class Util_Workflow_Type_Golang
 */
class Util_Workflow_Type_Golang extends Util_Workflow_Type_Abstract
{
    public function __construct(array $workflow = null, array $options = array())
    {
        parent::__construct($workflow, $options);
        $this->workflowFiles[] = $this->getWorkflowPath() .'/'. $this->getBinaryName();
    }

    /**
     * Get name of workflow type
     *
     * @return string
     */
    public function getType()
    {
        return 'golang';
    }

    /**
     * Get file extension
     *
     * @return string
     */
    public function getExtension()
    {
        return 'go';
    }

    /**
     * Get script template for new workflows
     *
     * @return string
     */
    public function getTemplate()
    {
        $view = new Zend_View();
        $view->addScriptPath(APPLICATION_PATH . '/modules/cmdb/views/scripts/workflow/template');

        return $view->render('golang.phtml');
    }

    /**
     * Get test template for new workflow tests
     *
     * @return string
     */
    public function getTestTemplate()
    {
        $view = new Zend_View();
        $view->addScriptPath(APPLICATION_PATH . '/modules/cmdb/views/scripts/workflow/template');

        return $view->render('golang_test.phtml');
    }

    /**
     * Get location of workflow files in filesystem
     *
     * @return string
     */
    public function getWorkflowPath()
    {
        return APPLICATION_DATA . '/workflows/golang/' . $this->workflow['name'] . '/';
    }

    /**
     * Get path to archive folder of workflow
     *
     * @return string
     */
    public function getArchivePath()
    {
        $date         = date('Y-m-d_Hms');
        $workflowPath = $this->getWorkflowPath();
        $archivePath  = $workflowPath . '/archive/' . $date;

        return $archivePath;
    }

    /**
     * Get filename of compiled binary file
     *
     * @return string
     */
    public function getBinaryName()
    {
        $scriptName = $this->getScriptName();
        $binName    = str_replace('.go', '', $scriptName);

        return $binName;
    }

    /**
     * Get default go environment variables as export command
     * @see https://golang.org/cmd/go/#hdr-Environment_variables
     *
     * @return string
     */
    public function getExportGoFlags()
    {
        $goPath = realpath(APPLICATION_PATH . '/../library/golang');
        $flags  = array(
            'GOPATH' => $goPath,
            'GOBIN'  => $goPath . '/bin',
        );

        $result = '';
        foreach ($flags as $flagName => $flagValue) {
            $result .= "export " . $flagName . '="' . $flagValue . '" ; ';
        }

        return $result;
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
        $gofmt = $this->individualizationConfig->getValue('golang.fmt.path', 'gofmt', Util_Config::STRING);

        // update comment section with workflow-information
        $docHeadParts = Util_Workflow::getDocHeadPartsForWorkflow($this->workflow, $user);
        $docHeader    = Util_Workflow::getDocHead($docHeadParts, '//');
        $script       = preg_replace('/\/\/* DOCHEAD \/\/*.+\/\/* \/DOCHEAD \/\/*/s', $docHeader, $script);

        if ($filePath === '') {
            $filename = $this->getScriptName();
            $basePath = $this->getWorkflowPath();
            if (!is_dir($basePath)) {
                mkdir($basePath, 0775, true);
            }
            $filePath = $basePath . $filename;
        }

        parent::saveScript($script, $user, $filePath);

        // format code
        $command = $gofmt . " -w " . $filePath;
        $this->runCommand($command);

        return $this->build();
    }

    /**
     * Save test content into test file
     *
     * @param string      $script
     * @param Dto_UserDto $user
     * @param string      $filePath
     * @return bool
     */
    public function saveTest($script, $user, $filePath = '')
    {
        if ($filePath === '') {
            $filename = $this->getTestName();
            $basePath = $this->getWorkflowPath();
            if (!is_dir($basePath)) {
                mkdir($basePath, 0775, true);
            }
            $filePath = $basePath . $filename;
        }

        return parent::saveTest($script, $user, $filePath);
    }

    /**
     * Build workflow script and save compiled file
     *
     * @return bool
     */
    public function build()
    {
        $workflowPath = $this->getWorkflowPath();
        $scriptName   = $this->getScriptName();
        $binName      = $this->getBinaryName();
        $scriptPath   = $workflowPath . $scriptName;
        $go           = $this->individualizationConfig->getValue('golang.binary.path', 'go', Util_Config::STRING);

        $env = $this->getEnvironmentVariables();
        $this->setEnvironmentVariables($env);

        $cmd    = $this->getExportGoFlags() . $go . ' build -o ' . $workflowPath . $binName . ' ' . $scriptPath;
        $result = $this->runCommand($cmd);

        if ($result['exit_code'] === 0) {
            return true;
        }

        return false;
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
        $scriptPath = $this->getWorkflowPath() .'/'. $this->getBinaryName();

        $command = $scriptPath . ' ' . self::transformShellArgs($parameters, $this->getResponseFormat());

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
        $identifier   = md5($script);
        $config       = new Util_Config('fileupload.ini', APPLICATION_ENV);
        $tmpPath      = realpath($config->getValue('file.upload.tmp', '/tmp', Util_Config::STRING)) . '/workflow-' .
            $identifier;
        $fileName     = "workflow.go";
        $testFileName = "workflow_test.go";
        $filePath     = $tmpPath .'/'. $fileName;
        $testFilePath = $tmpPath .'/'. $testFileName;
        $user         = new Dto_UserDto();
        $go           = $this->individualizationConfig->getValue('golang.binary.path', 'go', Util_Config::STRING);

        if(!is_dir($tmpPath)) {
            mkdir($tmpPath);
        }

        parent::saveScript($script, $user, $filePath);
        parent::saveTest($testScript, $user, $testFilePath);

        $env = $this->getEnvironmentVariables();
        $this->setEnvironmentVariables($env);

        $command = '# date ' . date('Y-m-d H:i:s')  . "\n"
                    . $this->getExportGoFlags()     . "\\\n"
                    . 'cd ' . $tmpPath              . ' && ' . "\\\n"
                    . $go . ' get -d -v -insecure ./... && ' . "\\\n"
                    . $go . ' test ' . $filePath . ' ' . $testFilePath;

        $result = $this->runCommand($command);
        @unlink($filePath);
        @unlink($testFilePath);
        if(is_dir($tmpPath)) {
            rmdir($tmpPath);
        }

        $outputCommand = array('$ ' . $command);
        $output        = array_merge($outputCommand, $result['stdout'], $result['stderr']);
        return $result['exit_code'] === 0;
    }
}