<?php
// Define application environment
defined('APPLICATION_ENV')
|| define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production'));

// Cron is not required in the testing environment as it is not predictable
if(APPLICATION_ENV === 'testing') {
    logPrint("Not running cron tasks in testing environment");
    exit(0);
}

require_once __DIR__ . "/../library/Cron/Autoloader.php";

define("LOCKFILE", __DIR__ . "/../data/tmp/infocmdb-cron.lock");
define("SPOOLFILE", __DIR__ . "/../data/logs/cron-state");
define("LOGFILE_OUT", __DIR__ . "/../data/logs/queue.log");

// Continue running script upon user abort (when running from web server)
ignore_user_abort(true);
$lock = lock();

$cmd = "php " . __DIR__ . "/index.php ";

$cronJobs = array(

    // listener
    "listener_file"          => array(
        'cron' => '@always',
        'job'  => $cmd . '/scheduler/listen/listener/file',
        'type' => 'sync',
    ),
    "listener_mail"          => array(
        'cron' => '@always',
        'job'  => $cmd . '/scheduler/listen/listener/mail',
        'type' => 'sync',
    ),
    "listener_workflow"      => array(
        'cron' => '@always',
        'job'  => $cmd . '/scheduler/listen/listener/workflow',
        'type' => 'sync',
    ),
    "listener_reporting"     => array(
        'cron' => '@always',
        'job'  => $cmd . '/scheduler/listen/listener/reporting',
        'type' => 'sync',
    ),

    // processor
    "processor_file"         => array(
        'cron' => '@always',
        'job'  => $cmd . '/scheduler/process/type/import/processor/file',
    ),
    "processor_mail"         => array(
        'cron' => '@always',
        'job'  => $cmd . '/scheduler/process/type/import/processor/mail',
    ),
    "processor_workflow"     => array(
        'cron' => '@always',
        'job'  => $cmd . '/scheduler/process/type/process/processor/workflow',
    ),
    "processor_reporting"    => array(
        'cron' => '@always',
        'job'  => $cmd . '/scheduler/process/type/process/processor/reporting',
    ),

    // session cleaner
    "processor_session"      => array(
        'cron' => '0 * * * *',
        'job'  => $cmd . '/scheduler/sprocess/type/process/processor/session',
    ),

    // application cleaner
    "processor_cleanup"      => array(
        'cron' => '0 * * * *',
        'job'  => $cmd . '/scheduler/sprocess/type/process/processor/cleanup',
    ),

    // search index
    "processor_search_index" => array(
        'cron' => '1 0 * * *',
        'job'  => $cmd . '/scheduler/sprocess/type/process/processor/filesearch',
    ),

    // logrotate
    "logrotate"              => array(
        'cron' => '14 * * * *',
        'job'  => 'logrotate /etc/logrotate.d/cmdb/*.conf --state /app/data/logs/logrotate-state',
    ),

);

if (!is_dir(dirname(SPOOLFILE))) {
    if (mkdir(dirname(SPOOLFILE), 0744, true)) {
        logPrint("failed to create spooling directory!", true);
        exit(2);
    }
}

$cronState = array();
if (is_file(SPOOLFILE)) {
    if (!$spoolContent = file_get_contents(SPOOLFILE)) {
        logPrint(sprintf("failed to read statefile(%s)!", SPOOLFILE), true);
        exit(2);
    }

    if (!$cronState = json_decode($spoolContent, true)) {
        logPrint(sprintf("failed to parse statefile(%s)!", SPOOLFILE), true);
        exit(2);
    }
}

// remove obsolete cronjobs from spoolFile
foreach ($cronState as $cronJobName => $cronJob) {
    if (array_key_exists($cronJobName, $cronJobs) === false) {
        unset($cronState[$cronJobName]);
    }
}

$jobCounter = 0;
foreach ($cronJobs as $cronJobName => $cronJob) {
    $cronState[$cronJobName]['cron']    = $cronJob['cron'];
    $cronState[$cronJobName]['lastrun'] = 'never';

    // some cronjob must run more frequently than by the minute
    // with '@always' it will run everytime the cron.php is called
    // listener scripts prevent multiple execution as they only insert tasks
    if ($cronJob['cron'] === '@always') {
        $cronState[$cronJobName]['lastrun']  = time();
        $cronState[$cronJobName]['lastexit'] = execute($cronJob['job']);
        $jobCounter++;
        continue;
    }

    $cron = Cron\CronExpression::factory($cronJob['cron']);

    if ((int)$cronState[$cronJobName]['lastrun'] > 0) {
        $lastRun = new Datetime();
        $lastRun->setTimestamp($cronState[$cronJobName]['lastrun']);
        $nextRun = $cron->getNextRunDate($lastRun);
    } else {
        $nextRun = "now";
    }

    if (
        ($nextRun instanceof DateTime && time() >= $nextRun->getTimestamp())
        || ($nextRun === 'now' && $cron->isDue())
    ) {
        $cronState[$cronJobName]['lastrun']  = $cron->getPreviousRunDate('now', 0, true)->getTimestamp();
        $cronState[$cronJobName]['lastexit'] = execute($cronJob['job']);
        $jobCounter++;
    }
}

if ($jobCounter > 0) {
    if (($json_data = json_encode($cronState, JSON_PRETTY_PRINT)) === false) {
        logPrint(sprintf("failed to encode cronState!"), true);
        exit(2);
    }

    if (file_put_contents(SPOOLFILE, $json_data, LOCK_EX) === false) {
        logPrint(sprintf("failed to write cronState(%s)!", SPOOLFILE), true);
        exit(2);
    }
}

cron_cleanup($lock, $jobCounter);

/**
 * @param $job
 *
 * @return bool|int return code of the run command
 */
function execute($job)
{
    // We must wrap the job in brackets to redirect all output of chained commands to stdout
    $job = sprintf('(%s >> %s 2>&1 &) &', $job, LOGFILE_OUT);

    $handle = popen($job, "r");
    if ($handle === false) {
        logPrint(sprintf("Cronjob '%s' could not be started.", $job), true);
        return 0;
    }

    return 1;
}

function logPrint($msg, $isError = false)
{
    $f      = "php://stdout";
    $msg    = chop($msg);
    $prefix = " " . date('Y-m-d H:i:s ');

    if ($isError) {
        $f      = "php://stderr";
        $prefix .= "[ERROR] ";
    }

    $msg = preg_replace('/(\n)(.*)$/m', "\n" . $prefix . "\$2\n", $msg);
    $msg = $prefix . $msg . "\n";

    if (!$fp = fopen($f, 'w')) {
        echo('failed to write to output!');
        exit(3);
    }

    fwrite($fp, $msg);
    fclose($fp);
}

function cron_cleanup($fp, $jobCount)
{
    // logPrint(sprintf("Cron finished successfully (%d jobs)\n", $jobCount));
    unlock($fp);
}

function lock()
{
    $fp = @fopen(LOCKFILE, "w+");
    if (!$fp) {
        logPrint(sprintf("Unable to open lock file: %s\n", LOCKFILE), true);
        exit(1);
    }

    $isLockSuccess = flock($fp, LOCK_EX | LOCK_NB);
    if (!$isLockSuccess) {
        logPrint("Another cron process is already open", true);
        exit(2);
    }

    fputs($fp, date('Y-m-d H:i:s ' . getmypid()));
    return $fp;
}

function unlock($fp)
{
    if (is_resource($fp)) {
        fclose($fp);
    }
    unlink(LOCKFILE);
}