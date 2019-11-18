<?php

/**
 * Configurable page to check the requirements of your PHP Application.
 * You can run this page in the browser or in the CLI.
 *
 * @author    Ignacio de Tomás <nacho@inacho.es>
 * @copyright 2013 Ignacio de Tomás (http://inacho.es)
 */


/*
 * DEFINE PATHS
 */
error_reporting(E_ALL);
ini_set('display_startup_errors', '1');
ini_set('display_errors', '1');

// Define application environment
defined('APPLICATION_ENV')
|| define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production'));

// Define the operating system directory separator \ /
define('DS', DIRECTORY_SEPARATOR);

// Define path to application directory
defined('APPLICATION_PATH')
|| define('APPLICATION_PATH', realpath(dirname(__FILE__) . DS . '..' . DS . 'application'));

defined('APPLICATION_FOLDER')
|| define('APPLICATION_FOLDER', str_replace('-', '', basename(realpath(dirname(__FILE__) . DS . '..' . DS))));

// Ensure library/ is on include_path
set_include_path(implode(PATH_SEPARATOR, array(
    realpath(APPLICATION_PATH . DS . '..' . DS . 'library'),
    realpath(APPLICATION_PATH . DS . '..' . DS . 'library' . DS . 'PHPExcel' . DS . 'Classes'),
    realpath(APPLICATION_PATH . '/../library/composer/diablomedia'),
    get_include_path(),
)));

require_once APPLICATION_PATH . '/../library/composer/autoload.php'; // loading library with composer autoloader


/*
 * RESTRICT ACCESS
 */
if (!(APPLICATION_ENV !== 'production' || PHP_SAPI === 'cli')) {
    echo '<h1>Restricted area</h1><h2>Access denied!</h2> ';
    exit;
}

/*
 * PREPARE CHECKS
 */

$connectionTest    = false;
$connectionMessage = '';

if (class_exists('Zend_Loader_Autoloader')) {
    Zend_Loader_Autoloader::getInstance();

    require_once APPLICATION_PATH . DS . 'plugins' . DS . 'Db.php';
    require_once APPLICATION_PATH . DS . 'plugins' . DS . 'Db' . DS . 'Profiler.php';
    require_once APPLICATION_PATH . DS . 'utils' . DS . 'Config.php';

    // MySQL Connection
    try {
        $dbConfig = new Util_Config('database.ini', APPLICATION_ENV);
        $db       = Zend_Db::factory($dbConfig->getValue('database.adapter', ''), $dbConfig->getValue('database.params', array(), Util_Config::ARR));
        $db->getConnection();
        $connectionTest = true;
    } catch (Zend_Db_Adapter_Exception $e) {
        $connectionMessage = '<pre>' . $e->getMessage() . '</pre>';
    }

}

/*
 * CHECKS
 */
$values = array(
    array(
        'desc' => 'PHP',
        'msg'  => '',
        'res'  => '',
    ),
    array(
        'desc' => '- Version >= 7.1.0',
        'msg'  => PHP_VERSION,
        'res'  => (version_compare(PHP_VERSION, '7.1.0') >= 0),
    ),
    array(
        'desc' => '- CLI',
        'msg'  => '',
        'res'  => (isAvailable('shell_exec') && isAvailable('exec') && isAvailable('proc_open')),
    ),
    array(
        'desc' => 'PHP-Extensions',
        'msg'  => '',
        'res'  => '',
    ),
    array(
        'desc' => '- dom',
        'msg'  => '',
        'res'  => extension_loaded('dom'),
    ),
    array(
        'desc' => '- json',
        'msg'  => '',
        'res'  => extension_loaded('json'),
    ),
    array(
        'desc' => '- mbstring',
        'msg'  => '',
        'res'  => extension_loaded('mbstring'),
    ),
    array(
        'desc' => '- PDO',
        'msg'  => '',
        'res'  => extension_loaded('PDO'),
    ),
    array(
        'desc' => '- pdo_mysql',
        'msg'  => '',
        'res'  => extension_loaded('pdo_mysql'),
    ),
    array(
        'desc' => '- simplexml',
        'msg'  => '',
        'res'  => extension_loaded('simplexml'),
    ),
    array(
        'desc' => '- soap',
        'msg'  => '',
        'res'  => extension_loaded('soap'),
    ),
    array(
        'desc' => '- xml',
        'msg'  => '',
        'res'  => extension_loaded('xml'),
    ),
    array(
        'desc' => '- zip',
        'msg'  => '',
        'res'  => extension_loaded('zip'),
    ),
    array(
        'desc'     => '- imap (optional)',
        'msg'      => '',
        'optional' => true,
        'res'      => extension_loaded('imap'),
    ),
    array(
        'desc'     => '- ldap (optional)',
        'msg'      => '',
        'optional' => true,
        'res'      => extension_loaded('ldap'),
    ),
    array(
        'desc'     => '- openssl (optional)',
        'msg'      => '',
        'optional' => true,
        'res'      => extension_loaded('openssl'),
    ),
    array(
        'desc' => 'MySQL',
        'msg'  => '',
        'res'  => '',
    ),
    array(
        'desc' => '- Version >= 5.5',
        'msg'  => getMysqlVersion(),
        'res'  => (version_compare(getMysqlVersion(), '5.5') >= 0),
    ),
    array(
        'desc' => '- Connection',
        'msg'  => $connectionMessage,
        'res'  => $connectionTest,
    ),
    array(
        'desc' => 'Perl',
        'msg'  => '',
        'res'  => '',
    ),
    array(
        'desc' => '- Version >= 5.0',
        'msg'  => getPerlVersion(),
        'res'  => (version_compare(getPerlVersion(), '5.0') >= 0),
    ),
    array(
        'desc' => 'Application',
        'msg'  => '',
        'res'  => '',
    ),
    array(
        'desc' => '- Zend Framework',
        'msg'  => (class_exists('Zend_Version') ? Zend_Version::VERSION : ''),
        'res'  => ((@include 'Zend/Application.php') === 1),
    ),
    array(
        'desc' => '- File: application/configs' . DS . 'application.ini',
        'msg'  => '',
        'res'  => is_file(APPLICATION_PATH . DS . 'configs' . DS . 'application.ini'),
    ),
    array(
        'desc' => '- File: application/configs' . DS . 'database.ini',
        'msg'  => '',
        'res'  => is_file(APPLICATION_PATH . DS . 'configs' . DS . 'database.ini'),
    ),
    array(
        'desc' => '- Dir: data' . DS . 'cache' . DS . 'config',
        'msg'  => '',
        'res'  => checkIfDirWritable(APPLICATION_PATH . DS . '..' . DS . 'data' . DS . 'cache' . DS . 'config'),
    ),
    array(
        'desc' => '- Dir: data' . DS . 'cache' . DS . 'locales',
        'msg'  => '',
        'res'  => checkIfDirWritable(APPLICATION_PATH . DS . '..' . DS . 'data' . DS . 'cache' . DS . 'locales'),
    ),
    array(
        'desc' => '- Dir: data' . DS . 'cache' . DS . 'metadata',
        'msg'  => '',
        'res'  => checkIfDirWritable(APPLICATION_PATH . DS . '..' . DS . 'data' . DS . 'cache' . DS . 'metadata'),
    ),
    array(
        'desc' => '- Dir: data' . DS . 'cache' . DS . 'search',
        'msg'  => '',
        'res'  => checkIfDirWritable(APPLICATION_PATH . DS . '..' . DS . 'data' . DS . 'cache' . DS . 'search'),
    ),
    array(
        'desc' => '- Dir: data' . DS . 'cache' . DS . 'session',
        'msg'  => '',
        'res'  => checkIfDirWritable(APPLICATION_PATH . DS . '..' . DS . 'data' . DS . 'cache' . DS . 'session'),
    ),
    array(
        'desc' => '- Dir: public' . DS . '_uploads',
        'msg'  => '',
        'res'  => checkIfDirWritable(APPLICATION_PATH . DS . '..' . DS . 'public' . DS . '_uploads'),
    ),
    array(
        'desc'     => '- Apache-Umask (0002)',
        'msg'      => shell_exec('umask'),
        'res'      => (trim(shell_exec('umask')) === '0002'),
        'optional' => true,
    ),
);

/*
 * FUNCTIONS
 */
function output($str)
{
    fwrite(STDOUT, $str);
}

function green($str)
{
    return chr(27) . '[32m' . $str . chr(27) . '[0m';
}

function orange($str)
{
    return chr(27) . '[33m' . $str . chr(27) . '[0m';
}

function red($str)
{
    return chr(27) . '[31m' . $str . chr(27) . '[0m';
}

function check($value)
{
    if ($value['res'] === '') {
        return '';
    } elseif ($value['res'] === true) {
        $result = green('OK');
    } elseif ($value['res'] == false && isset($value['optional']) && $value['optional'] === true) {
        $result = orange('WARNING');
    } else {
        $result = red('FAIL');
    }

    return '  ' . str_pad($result, 17);
}

function isAvailable($func)
{
    $disabled = ini_get('disable_functions');

    if ($disabled) {
        $disabled = explode(',', $disabled);
        $disabled = array_map('trim', $disabled);
        return !in_array($func, $disabled);
    }

    return true;
}

function shellCommandExists($cmd)
{
    $returnVal = shell_exec("which $cmd");
    return (empty($returnVal) ? false : true);
}


function getPerlVersion()
{
    $perlInstalled = shellCommandExists('perl');

    if ($perlInstalled === true) {
        $perlVersion = shell_exec("perl --version");
        preg_match('/v([0-9.]+)/', $perlVersion, $perlVersion);
        if (isset($perlVersion[1])) {
            return $perlVersion[1];
        }
    }

    return '';
}

function getMysqlVersion()
{
    $isInstalled = shellCommandExists('mysql');

    if ($isInstalled === true) {
        $version = shell_exec("mysql --version");
        preg_match('/Distrib (.*?),/', $version, $version);
        if (isset($version[1])) {
            return $version[1];
        }
    }

    return '';
}

function checkIfDirWritable($dir)
{
    if (is_dir($dir) && is_writable($dir)) {
        return true;
    }

    return false;
}

/*
 * CONSOLE OUTPUT
 */
if (PHP_SAPI == 'cli') {
    $hasErrors = false;

    foreach ($values as $value) {
        if ($value['res'] === '') {
            output(PHP_EOL);
        }
        output(check($value) . ' ' . $value['desc'] . PHP_EOL);

        if ($value['res'] === false && (!isset($value['optional']) || $value['optional'] === false)) {
            $hasErrors = true;
        }
    }

    output(PHP_EOL . 'You can also view this file in a browser' . PHP_EOL);

    if ($hasErrors) {
        exit(1);
    } else {
        exit(0);
    }
}

/*
 * HTML OUTPUT
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>InfoCMDB Requirements</title>

    <link rel="icon" type="image/png"
          href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAqJJREFUeNp0U0tPE1EU/ubV6QBtKQaQQnBhgiYmElKJJgaVECIxMZroxoULN7B05V6XygqjRl2ZqH9AXSigsjCRBMrCjZZECKUWqFpKmZl23p47nQ5q9Ey+ObnnnHse372Xw99ysQm40ry35jBC/+uEFKFAmIKHt3vucKMCXCBwZBIIUuh6fW346lnLsiCKIp7MPXtDtrGGU8TdBBAT/uzC9QCLdGD+9qOgNglKi+7oqu9j4rCfBx7/ExZoExxvcnppJitJEqaXZrNsHdh9v/iPrY8ocRr1Ar50JjtimqYhGW2NbWvl20FchjBRT1B1AbnRjJe+fPpSularwfM8H6ZpQlVVDB481qcoClzXxcv5VwEH/jxUqkZJxDpxu7u7qFQqYUs8z8OoauWFD7M5tj7SP9jrjxEmCGeunwtjnKEh0WgUnz9l8lVdu89aX/w4N46IlEFE9BOM0Kw3gk4mfeU4MC0Tuq5Dr+qQIzI0tWKQ63GQc4ICwCBgVJ4aHRo7l2hp3b+5WRhIxttilmvvK24XoZs6dWrDEzwYqlpybdsJEtyEIKYgSRkOd2KLQyeH06waI0g3dKyWVsFzPERBDA7Dg61WyzvrGz4HLd0dvWpJyzmW0y9SiQLHcWnTMNTMwnxWao7EYn3JPhSscml+PW+VDUNMROTm4+09rQMHjvonw77vao4RKeCMVFxbWeksbmysk/2WW3NOJQ63pUov1pbtinmPzesajmNtVQ/Jg/FORMhCMPL6lme6D0W8s9jDqD+OHroL3RxshebmvMZlqWt2TX57Y/6xZ20yvzejhDihHU9rXdix4UQdNJ1v76Ww8SB83F/TWwuhEp/PjRRjSWa8EOK+66uzvPMgL/nbFD7sQJ/5eQIzyDUa8HLuF1JdvwQYAL+KPe88BpnxAAAAAElFTkSuQmCC" />

    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="robots" content="noindex, nofollow" />


    <style>

        html,
        body {
            height: 100%;
            font-family: Verdana, Arial, Geneva, Helvetica, sans-serif;
            font-size: 20px;
        }

        #wrap {
            display: flex;
            align-content: center;
            justify-content: center;
        }

        table {
            border-collapse: collapse;
        }

        tr:nth-child(odd) {
            background-color: #eee
        }

        tr:hover {
            background-color: #CCCCCC
        }

        tr.table-header {
            background-color: #D52B1E !important;
        }

        tr.table-header td {
            color: #ffffff;
            padding: 2px 0 2px;
        }

        td {
            padding: 0 20px 0 20px;
            border: 1px solid #D52B1E;
        }

        #header {
            text-align: center;
            margin-bottom: 40px;
        }

        #header h1 {
            color: #D52B1E;
        }

    </style>
</head>
<body>

<div id="wrap">
    <div class="container">
        <div id="header">
            <h1>InfoCMDB</h1>
            <h2>Requirements</h2>
        </div>

        <table class="table">
            <tbody>
            <?php foreach ($values as $value): ?>
                <tr
                    <?php echo ($value['res'] === '') ? 'class="table-header"' : '' ?>
                >
                    <td>
                        <?php if ($value['res'] === true): ?>
                            <img alt="OK" width="16" height="16"
                                 src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAhxJREFUeNpi/P//PwMlgImBQsACIhhnCAFZjEAGkANy0B8gwQ00m5kRogpEMYHl5YF4LZBXw/D3/47/Ea/xuOD7PwaGX1AMMvDvfwGGf/8nprkkGQPZLUAVHqR4gY3hH0Ofoby+6ZcvXxjinWL0Gf4wtMC9QAAwAjXXK0jIO8gLyUl9/Pzx98Fjh24AXdOA7gIPoP/PwJwGdvoPoNP/MGQIcwkGq4mrKHz5+uXf0UtH7wBdMRGodgvCgP9ATX/+tyT5JxqCaDAfIu7DwcSWJckjqfzgwQOGU5dP3f3w4cNSoJq5DL//I0Xjn38tMX4xes+ePWOK8IowAPGBmnNZ/jPVS4vLqH7985Xl5YcXDz99+rAJGIDtQAxxIcKA/zVLViy8xM7J9uvU7VPMDnaOOkAb4sVkxTV+sPxgf/fhzdOP797vZ/gLjD4GBojObT8gAQRKiYx9/AxADaAwaDF2NtN6+vMZpwCnAMP7b+8Zfrz49vrj3fdHGJgZkhhYmT4w7P4J1wzWCyY8OBgY5JiBmBFsiLSdvMYP3l/cv1/+/PD57Psz/5kYEhgO/H4K1owEEAYwQlOcAtAQc2YPBmnGFi4TfpnvFz7d+f/wXyrD8T/XGR79w4hfZANAYcELxAJAzMdgzGTJoMOQxnDmfyfD1f9XgGIgq39AaRD+xQDyNBDADAA5gR2IOaA0MzRX/IPiP1D8F4n+BzeAEgAQYAC7HATaTnWSLQAAAABJRU5ErkJggg==" />
                        <?php elseif ($value['res'] === false && isset($value['optional']) && $value['optional'] === true) : ?>
                            <img alt="WARNING" width="16" height="16"
                                 src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAACXklEQVQ4jbWS3U9SARjGj6tVl170B7hmTDzAUYYfjKlImuOCLS+crc2hUzQNHWpEpBNy9rFZOb1JtlLJokRBMFc2MmuEqVFTl305W+QUUQZYR0sFnm6q1ZojL3q29+75/S7ePQTxP2NTUSZrLcemlx1g7Bg2K6iacS0PL7uLYKxkTe8IbjkSHe1szlhbfFiMsL8VlloqrC9h5P+zYEjFu+5sSgIW1IBbjfnhcvRWsXzGPGJXRPjq0ZiYiabkoN9RAHpGgWX7McCthq0xNWw4HncpomC4Pskx0yYAPPW41ZiGZhkLmC9GYKoGvZXkpqGUsX9b2FAUmzGmScTW60LAewYXZCS00jhgMRdwq+Fsz8GdcqZtW4Fdw5v71C0EPOcAdy4uy0mcL2ECHgnwVoLNj2fRpyBDhrJY/l+wqZwsHWtIAKbygTkp8EEMfQOFdhUFuMTAZDbwTorZHgn65Mz3f8BtYmLvMw13daFLgOB0AbYcIoReZCEwmgWvPQuYzEZwVISNR0KEpqSwKtmhmzKG7JdgsJqte65NBG3KAH1fhLV7aQjaM6FTkGgpY+LrYyHWH6SDtghAW4Rw6TNhlMd/6SyM2UcY88g9jjpqy9XKw3IHH8udyfAaUrHaL8ANFRcdSh78/QJ4e/hY0afAo0vCyjU+bHUJuF0R10s8VbPLHKdZYddFLha6UuC1CBB4IgTtPIRvbw5jYzYH66+y8XlCBJ8tHUs9fLiucDFZz4HxBJMmzBUHmdZqluvuSY5vQEn5B09FvgElx2et4SyZq+LHf74hamREuzviyn7Lj37Ud9PXdwp/rFdwAAAAAElFTkSuQmCC" />
                        <?php elseif ($value['res'] === false): ?>
                            <img alt="FAIL" width="16" height="16"
                                 src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAcJJREFUeNqkUz1PAkEQfStggjESejU0GozlGqn8SGywkYIYY0IsaLCwIBTQUN5fMLGm8S8QSWwslVAYjAlUBEJDhCgWwp3nzN6eHqIVl8zN7rx5b+dm9oRt25jlmcOMj59f10JAkPcBcXIGWdECyqYn6TfGdZ9S9d4K4gQYx4WCtJzE+G/sKJudwpQABUGnGSf5vKzX60jmctL8SYzz+iCdls1mEzuplMIsLSC4iSUh1ClUlpHIZGStVkM0GsVNqVRlIJZIyG63i1AohMdKpUrZRQqXz4j7LWA7VSiR/WRSNhsNRRgOh+i02wgGg3hrtRSZelLmI6cExs7nKJGVtTX50uupMn0+H157PUWmZpYDXLoWUFPo6MC87jivx4MBFtxOWZYS11VipNdT98DWDVsPh2XQNLFIMdc4xpg9OZ3JMdIpRowSXVKt36+yuXvGxn+N0XS+3zj0kG+JSPEi261H5FCLmN9lUyNWyZ+Qag54eA6Hbfa8j1A88g+2qrlqCkKIZdovbAG7m8D5E3B5D9xR7IPsk/u7DextABd14OrBwd6J23YFligQ0IPwXE7lbedXUAPya5yHMiLuq5j1d/4SYAAj3NATBGE4PgAAAABJRU5ErkJggg==" />
                        <?php endif ?>
                    </td>
                    <td><?php echo $value['desc'] ?></td>
                    <td><?php echo $value['msg'] ?></td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
</div>

<?php echo phpinfo(); ?>

</body>
</html>