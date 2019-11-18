<?php

/**
 * Class Util_Workflow_Type_Abstract
 */
abstract class Util_Workflow_Type_Abstract
{
    /**
     * @var array
     */
    protected $workflow = array();

    /**
     * @var array
     */
    protected $options = array();

    /**
     * @var array
     */
    protected $workflowFiles = array();

    /**
     * @var Util_Config
     */
    protected $individualizationConfig;

    /**
     * Util_Workflow_Type_Abstract constructor.
     *
     * @param array $workflow database row
     * @param array $options  additional options
     */
    public function __construct($workflow = null, $options = array())
    {
        $this->workflow = $workflow;
        $this->options  = $options;

        $workflowPath                  = $this->getWorkflowPath();
        $this->individualizationConfig = new Util_Config('individualization.ini', APPLICATION_ENV);

        $this->workflowFiles = array(
            $workflowPath . '/' . $this->getScriptName(),
            $workflowPath . '/' . $this->getTestName(),
        );
    }

    /**
     * Get name of workflow type
     *
     * @return string
     */
    public function getType()
    {
        return '';
    }

    /**
     * Get file extension
     *
     * @return string
     */
    public function getExtension()
    {
        return '';
    }

    /**
     * Get script template for new workflows
     *
     * @return string
     */
    public function getTemplate()
    {
        return '';
    }

    /**
     * Get test template for new workflow tests
     *
     * @return string
     */
    public function getTestTemplate()
    {
        return '';
    }

    /**
     * Shorthand for getting response_format of workflow
     *
     * @return string
     */
    public function getResponseFormat()
    {
        return $this->workflow[Db_Workflow::RESPONSE_FORMAT];
    }

    /**
     * Get location of workflow files in filesystem
     *
     * @return string
     */
    public function getWorkflowPath()
    {
        return Util_Workflow::getWorkflowPath();
    }

    /**
     * Get path to archive folder of workflow
     *
     * @return string
     */
    public function getArchivePath()
    {
        $date                  = date('Y-m-d_Hms');
        $workflowPath          = $this->getWorkflowPath();
        $workflowArchiveFolder = $this->workflow[Db_Workflow::ID] . '__' . $this->workflow[Db_Workflow::NAME];
        $archivePath           = $workflowPath . '/archive/' . $workflowArchiveFolder . '/' . $date;

        return $archivePath;
    }

    /**
     * Get environment variables for execution
     *
     * @return array
     */
    public function getEnvironmentVariables()
    {
        $config          = new Util_Config('individualization.ini', APPLICATION_ENV);
        $configVariables = $config->getValues();
        $workflowType    = $this->getType();

        $returnVariables = array(
            "APPLICATION_ENV"    => APPLICATION_ENV,
            "APPLICATION_PATH"   => APPLICATION_PATH,
            "APPLICATION_URL"    => APPLICATION_URL,
            "APPLICATION_DATA"   => APPLICATION_DATA,
            "APPLICATION_PUBLIC" => APPLICATION_PUBLIC,
        );

        // Set system settings first
        if (array_key_exists("system", $configVariables)
            && array_key_exists("env", $configVariables["system"])) {
            $variables       = $configVariables["system"]["env"];
            $returnVariables = array_merge($returnVariables, $variables);
        }

        // override with workflowType specifics
        if (array_key_exists($workflowType, $configVariables)
            && array_key_exists("env", $configVariables[$workflowType])) {
            $variables       = $configVariables[$workflowType]["env"];
            $returnVariables = array_merge($returnVariables, $variables);
        }

        return $returnVariables;
    }

    public function getEnvironmentVariablesAsExportString(array $env)
    {
        $envSettings = array();
        foreach ($env as $envKey => $envValue) {
            // remove username:password used for proxy or similar
            $envValue = preg_replace('#\/\/[\w-]+?(?:\:[\w-]+)?@#','//<credentials>@', $envValue);

            // to be able to copy and paste from the workflow-log wee need to put " around the value
            $envSetting    = join("=", array($envKey, "\"" . $envValue . "\""));
            $envSettings[] = $envSetting;
        }

        $result = "export " . implode(" ", $envSettings);

        return $result;
    }


    /**
     * Set environment variables for execution
     *
     * @return string
     */
    public function setEnvironmentVariables($environment)
    {
        foreach ($environment as $key => $value) {
            // When value is empty, only putenv the key to UNSET the environment variable
            putenv(join("=", array($key, $value)));
        }
        return true;
    }

    /**
     * Get filename of script
     *
     * @return string
     */
    public function getScriptName()
    {
        if (isset($this->workflow[Db_Workflow::ID])) {
            $dao   = new Dao_Workflow();
            $tasks = $dao->getWorkflowTasksByWorkflowId($this->workflow[Db_Workflow::ID]);

            $script = '';
            if (isset($tasks[0])) {
                $script = $tasks[0][Db_WorkflowTask::SCRIPT];
            }

            return $script;
        }

        return 'script' . $this->getExtension();
    }

    /**
     * Get filename of test
     *
     * @return string
     */
    public function getTestName()
    {
        $name = $this->getScriptNameWithoutExtension() . '_test.' . $this->getExtension();

        return $name;
    }

    /**
     * Get filename of script without extension
     *
     * @return string
     */
    public function getScriptNameWithoutExtension()
    {
        $scriptName = $this->getScriptName();
        $name       = str_replace('.' . $this->getExtension(), '', $scriptName);

        return $name;
    }

    /**
     * Get content of existing script
     *
     * @return string
     */
    public function getScriptContent()
    {
        $filename = $this->getScriptName();
        $basePath = $this->getWorkflowPath();
        $filePath = $basePath . $filename;

        $content = '';

        if (is_file($filePath)) {
            $handle  = fopen($filePath, 'r');
            $content = fread($handle, filesize($filePath));
            fclose($handle);
        }

        return $content;
    }

    /**
     * Get content of existing test
     *
     * @return string
     */
    public function getTestContent()
    {
        $filename = $this->getTestName();
        $basePath = $this->getWorkflowPath();
        $filePath = $basePath . $filename;

        $content = '';

        if (is_file($filePath)) {
            $handle  = fopen($filePath, 'r');
            $content = fread($handle, filesize($filePath));
            fclose($handle);
        }

        return $content;
    }

    /**
     * Run command in console
     *
     * @param string   $cmd
     * @param Util_Log $logger
     * @return array|bool
     */
    public function runCommand($cmd, $logger = null)
    {
        if ($logger) {
            $logger->log('executing script "' . $cmd . '"');
        }

        $descriptorSpec = array(
            0 => array('pipe', 'r'), // stdin
            1 => array('pipe', 'w'), // stdout
            2 => array('pipe', 'w'), // stderr
        );
        $stdout         = array();
        $stderr         = array();

        $proc = proc_open($cmd, $descriptorSpec, $pipes);

        if (!is_resource($proc)) {
            return false;
        }

        /*
         * HANDLE OUTPUT
         * Combining pipes is not possible without loosing information to differ err/no-err or the order of output.
         * So what can we do: fetch STDOUT immediately and STDOUT after process has finished
         */
        // STDOUT
        while (!feof($pipes[1])) {
            $outLine = fgets($pipes[1]);
            if (!empty($outLine)) {
                $stdout[] = $outLine;
                if ($logger) {
                    $logger->log('[SCRIPT] ' . $outLine);
                }
            }
        }

        fclose($pipes[1]);

        // STDERR
        $errLines = stream_get_contents($pipes[2]);
        if (!empty($errLines)) {
            $errLinesArr = explode("\n", $errLines);
            foreach ($errLinesArr as $errLine) {
                $stderr[] = $errLine;
                if ($logger) {
                    $logger->log('[SCRIPT][ERROR] ' . $errLine);
                }
            }
        }
        fclose($pipes[2]);

        $rtn = proc_close($proc);
        return array('stdout'    => $stdout,
                     'stderr'    => $stderr,
                     'exit_code' => $rtn,
        );
    }

    /**
     * Converts and escapes parameters for usage in console
     *
     * @param array  $parameters     associative array of console params
     * @param string $responseFormat json|plain
     * @return string escaped parameters in a single line
     */
    public static function transformShellArgs($parameters, $responseFormat = 'json')
    {
        if ($responseFormat == 'json') {
            $commandParamsString = escapeshellarg(json_encode($parameters));
            return $commandParamsString;
        }

        if ($responseFormat == 'plain') {
            $commandParamsString = '';
            foreach ($parameters as $paramName => $paramValue) {
                // keep parameter order!! - also add empty values as parameter!
                $paramValue          = (string)$paramValue;
                $commandParamsString .= ' ' . escapeshellarg($paramValue);
            }

            return $commandParamsString;
        }
    }

    /**
     * Write content into a specific file
     *
     * @param $path
     * @param $content
     * @return bool
     */
    public static function saveFile($path, $content)
    {
        $file         = fopen($path, 'w');
        $bytesWritten = fwrite($file, $content);

        if ($bytesWritten === false) {
            return false;
        }

        fclose($file);
        chmod($path, 0774);

        return true;
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
        $script = trim($script);

        if (!empty($script)) {
            // replacing windows line endings with unix line endings
            $script = preg_replace('~(*BSR_ANYCRLF)\R~', "\n", $script);

            if ($filePath === '') {
                $filename = $this->getScriptName();
                $basePath = $this->getWorkflowPath();
                if (!is_dir($basePath)) {
                    mkdir($basePath, 0775, true);
                }
                $filePath = $basePath . $filename;
            }

            return self::saveFile($filePath, $script);
        }
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
        $script = trim($script);

        if (!empty($script)) {
            // replacing windows line endings with unix line endings
            $script = preg_replace('~(*BSR_ANYCRLF)\R~', "\n", $script);

            if ($filePath === '') {
                $filename = $this->getTestName();
                $basePath = $this->getWorkflowPath();
                if (!is_dir($basePath)) {
                    mkdir($basePath, 0775, true);
                }
                $filePath = $basePath . $filename;
            }

            return self::saveFile($filePath, $script);
        }
    }

    /**
     * Move all workflow files into archive path
     *
     * @return bool
     */
    public function archive()
    {
        $archivePath = $this->getArchivePath();

        if (!is_dir($archivePath)) {
            mkdir($archivePath, 0775, true);
        }

        foreach ($this->workflowFiles as $file) {
            $fileName        = basename($file);
            $destinationPath = $archivePath . '/' . $fileName;
            if (file_exists($file)) {
                $renameResult = rename($file, $destinationPath);
                if ($renameResult === false) {
                    return false;
                }
            }
        }

        return true;
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
        return true;
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
        return true;
    }

    /**
     * Handle deletion of workflow files
     *
     * @return bool
     */
    public function delete()
    {
        $this->archive();
    }
}