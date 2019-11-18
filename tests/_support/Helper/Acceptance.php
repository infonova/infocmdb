<?php
namespace Helper;
use Codeception\Exception\ModuleException;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\StaleElementReferenceException;
use Facebook\WebDriver\Remote\RemoteWebElement;
use \Codeception\Util\Fixtures;
use Facebook\WebDriver\WebDriverBy;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class Acceptance extends \Codeception\Module
{
    /**
     * @var \Codeception\Module\WebDriver
     */
    private $webDriverModule = null;
    public $checkForErrors = true;
    protected $browser = '';
    public $maintenanceFile;

    public function _initialize()
    {
        parent::_initialize();
        putenv('APPLICATION_ENV=testing');
        $this->maintenanceFile = codecept_root_dir().'cmdb.lock';
    }

    public function _before(\Codeception\TestCase $test)
    {
        Phinx::prepareTestEnvironment();

        if (!$this->hasModule('WebDriver') && !$this->hasModule('Selenium2')) {
            throw new \Exception('TestCase uses the WebDriver. Please be sure that this module is activated.');
        }
        // Use WebDriver
        if ($this->hasModule('WebDriver')) {
            $this->webDriverModule = $this->getModule('WebDriver');
        }

        $this->browser = $test->getMetadata()->getCurrent('browser');
    }

    /**
     * @return \Codeception\Module\WebDriver
     */
    public function getWebDriverModule() {
        return $this->webDriverModule;
    }

    public function findElements($locator) {
        return $this->webDriverModule->_findElements($locator);
    }

    public function findElement($locator) {
        $elements = $this->findElements($locator);

        return reset($elements);
    }

    public function getBrowserName() {
        return $this->browser;
    }

    public function grabLivePageSource() {
        return $this->webDriverModule->executeJS('return document.documentElement.outerHTML');
    }

    public function seeInLivePageSource($content) {
        $errorMsg = sprintf('Failed asserting that page source contains "%s"', $content);
        $this->assertTrue(strpos($this->grabLivePageSource(), $content) !== false, $errorMsg);
    }

    public function grabJsonPageSource() {
        $browser = $this->getBrowserName();
        $originalResponse = $this->webDriverModule->grabPageSource();
        $response = $originalResponse;

        // chrome adds html tags --> remove tags
        if($browser == 'chrome') {
            $jsonStart = strpos($response, '{');
            $response = substr($response, $jsonStart);
            $jsonEnd = strrpos($response, '}') + 1;
            $response = substr($response, 0, $jsonEnd);
        }

        $response = json_decode($response, 1);

        return $response;
    }

    public function waitForAjaxLoad($timeout = 15)
    {
        $this->webDriverModule->waitForJS('return (window.jQuery === undefined) ? true : (!!window.jQuery && window.jQuery.active == 0);', $timeout);
        $this->webDriverModule->wait(1);
        $this->checkForErrors();
    }

    public function waitForPageLoad($timeout = 10)
    {
        $this->webDriverModule->waitForJs('return document.readyState == "complete"', $timeout);
        $this->waitForAjaxLoad($timeout);
    }

    public function dontSeeJsError()
    {
        $logs = $this->webDriverModule->webDriver->manage()->getLog('browser');
        foreach ($logs as $log) {
            if ($log['level'] == 'SEVERE') {
                throw new ModuleException($this, 'Some error in JavaScript: ' . json_encode($log));
            }
        }
    }

    public function checkForErrors() {
        if($this->checkForErrors === true) {
            $this->webDriverModule->dontSee('Exception');
            $this->webDriverModule->dontSee('PHP Fatal error');
        }
    }

    public function enableErrorChecks()
    {
        codecept_debug("enable error checks");
        $this->checkForErrors = false;
    }

    public function disableErrorChecks()
    {
        codecept_debug("disable error checks");
        $this->checkForErrors = false;
    }

    public function amOnPage($link, $timeout = 10)
    {
        $this->webDriverModule->amOnPage($link);
        $this->waitForPageLoad($timeout);
        $this->checkForErrors();
    }

    public function hasElement(RemoteWebElement $rootElem, WebDriverBy $selector) {
        try {
            $rootElem->findElement($selector);
        } catch (NoSuchElementException $e) {
            return false;
        }
        return true;
    }

    public function match($selector) {
        if(\Codeception\Util\Locator::isXPath($selector)) {
            $selector = WebDriverBy::xpath($selector);
        } elseif(\Codeception\Util\Locator::isCSS($selector)) {
            $selector = WebDriverBy::cssSelector($selector);
        } else {
            throw new \Codeception\Exception\MalformedLocatorException($selector, 'XPath or CSS');
        }

        return $selector;
    }

    public function seeRegexMatch($pattern) {
        $text = $this->webDriverModule->grabPageSource();

        $this->assertTrue(preg_match($pattern, $text) > 0, 'Failed asserting that following pattern matches page source: '.$pattern);
    }

    public function dontSeeRegexMatch($pattern, $selector) {
        $text = $this->webDriverModule->grabPageSource();

        $this->assertFalse(preg_match($pattern, $text) > 0, 'Failed asserting that following pattern does not match page source: '.$pattern);
    }

    public function outputInfo($title, $message, $prePadding=6)
    {
        $pre = str_repeat(' ', $prePadding);
        $output = new \Codeception\Lib\Console\Output(array());
        $output->writeln($pre . '<fg=yellow>' . str_pad($title, 15) . '</>' . '<fg=cyan>' . $message . '</>');
    }

    public function fillCkEditorById($element_id, $content) {
        $this->fillRteEditor(
            WebDriverBy::cssSelector(
                '#cke_' . $element_id . ' .cke_wysiwyg_frame'
            ),
            $content
        );
    }

    public function fillCkEditorByName($element_name, $content) {
        $this->fillRteEditor(
            WebDriverBy::cssSelector(
                'textarea[name="' . $element_name . '"] + .cke .cke_wysiwyg_frame'
            ),
            $content
        );
    }

    public function fillTinyMceEditorById($id, $content) {
        $this->fillTinyMceEditor('id', $id, $content);
    }

    public function fillTinyMceEditorByName($name, $content) {
        $this->fillTinyMceEditor('name', $name, $content);
    }

    private function fillTinyMceEditor($attribute, $value, $content) {
        $this->fillRteEditor(
            WebDriverBy::xpath(
                '//textarea[@' . $attribute . '=\'' . $value . '\']/../*[contains(@class, \'mceEditor\')]//iframe'
            ),
            $content
        );
    }

    private function fillRteEditor($selector, $content) {
        $this->webDriverModule->executeInSelenium(
            function (\Facebook\WebDriver\Remote\RemoteWebDriver $webDriver)
            use ($selector, $content) {

                $webDriver->switchTo()->frame(
                    $webDriver->findElement($selector)
                );

                $webDriver->executeScript(
                    'arguments[0].innerHTML = "' . addslashes($content) . '"',
                    [$webDriver->findElement(WebDriverBy::tagName('body'))]
                );

                $webDriver->switchTo()->defaultContent();
            }
        );
    }

    private function fillDatePicker($selector, $dateTime) {
        $date           = new \DateTime($dateTime);
        $yearToSet      = $date->format('Y');
        $monthToSet     = $date->format('n');
        $dayToSet       = $date->format('j');
        $hoursToSet     = $date->format('H');
        $minutesToSet   = $date->format('i');
        $secondsToSet   = $date->format('s');

        $attributeFormElement = $this->findElement($selector);
        $attributeFormElementId = $attributeFormElement->getAttribute('id');
        $attributeFormElement->click();

        $this->webDriverModule->executeJS('$("#' . $attributeFormElementId . '")[0]._flatpickr.changeYear('.($yearToSet).', false);');
        $this->webDriverModule->executeJS('$("#' . $attributeFormElementId . '")[0]._flatpickr.changeMonth('.($monthToSet-1).', false);');
        $this->webDriverModule->wait(1);
        $this->webDriverModule->click('//div[contains(@class, "flatpickr-calendar") and contains(@class, "open")]//span[contains(@class, \'flatpickr-day\') and contains(string(), "'.$dayToSet.'")]');

        if($hoursToSet !== '00' || $minutesToSet !== '00' || $secondsToSet !== '00') {
            $this->webDriverModule->wait(1);
            $this->findElement('.flatpickr-time .flatpickr-hour')->sendKeys($hoursToSet);
            $this->findElement('.flatpickr-time .flatpickr-minute')->click()->sendKeys($minutesToSet);
            $this->findElement('.flatpickr-time .flatpickr-second')->click()->sendKeys($secondsToSet);
        }

        $this->webDriverModule->executeJS('$("#' . $attributeFormElementId . '")[0]._flatpickr.close()');

    }

    public function searchMultiSelect($selector, $searchTerm) {

        $selectorElem = $this->match($selector);
        $this->webDriverModule->executeInSelenium(
            function (\Facebook\WebDriver\Remote\RemoteWebDriver $webDriver)
            use ($selectorElem, $searchTerm) {
                $selectElem         = $webDriver->findElement($selectorElem);
                $multiSelectElem = $selectElem->findElement(
                    WebDriverBy::xpath('./../div[contains(@class, "ui-multiselect")]')
                );

                $multiSelectElem->findElement(
                    WebDriverBy::xpath('.//input[contains(@class, "search")]')
                )->clear()->sendKeys($searchTerm);

                $selectId = $multiSelectElem->getAttribute('data-select-id');

                $webDriver->wait()->until(\WebDriverExpectedCondition::presenceOfElementLocated(\WebDriverBy::cssSelector('#' . $selectId)));
                $webDriver->wait()->until(\WebDriverExpectedCondition::presenceOfElementLocated(
                    WebDriverBy::xpath('//select[contains(@id, "'.$selectId.'")]/../div[contains(@class, "ui-multiselect")]//div[contains(@class, "option-text") and contains(string(), "' . addslashes($searchTerm) . '")]')
                ));
            }
        );
    }

    public function increaseMultiSelectOption($selector, $option) {
        $this->handleMultiSelectOptionAction($selector, $option, 'add');
    }

    public function decreaseMultiSelectOption($selector, $option, $numberOfClicks = 1) {
        $this->handleMultiSelectOptionAction($selector, $option, 'remove', $numberOfClicks);
    }

    public function getMultiSelectOptionAmount($selector, $option)
    {
        $result = $this->webDriverModule->executeInSelenium(
            function(\Facebook\WebDriver\Remote\RemoteWebDriver $webDriver)
            use ($selector, $option) {

                $selectorElem = $this->match($selector);
                try {
                    $el         = $webDriver->findElement($selectorElem);
                    $optionElem = $el->findElement(
                        WebDriverBy::xpath('./option[contains(string(), "' . addslashes($option) . '")]')
                    );
                } catch (NoSuchElementException $exception) {
                    return 0;
                }

                if ($optionElem === false) {
                    return 0;
                } else {
                    return $optionElem->getAttribute('data-amount');
                }
            }
        );

        return $result;
    }

    public function setMultiSelectOptionAmount($selector, $option, $amount) {
        $currentAmount = (integer) $this->getMultiSelectOptionAmount($selector, $option);

        if($currentAmount === $amount) {
            return true;
        } elseif($currentAmount < $amount) {
            $this->increaseMultiSelectOption($selector, $option);
        } elseif($currentAmount > $amount) {
            $this->decreaseMultiSelectOption($selector, $option);
        }

        $this->setMultiSelectOptionAmount($selector, $option, $amount);
    }

    private function handleMultiSelectOptionAction ($selector, $option, $action) {
        $this->webDriverModule->executeInSelenium(
            function (\Facebook\WebDriver\Remote\RemoteWebDriver $webDriver)
            use ($selector, $option, $action) {
                try {
                    $selectorElem = $this->match($selector);

                    if ($action == 'add') {
                        $this->searchMultiSelect($selector, $option);
                    }

                    $el         = $webDriver->findElement($selectorElem);
                    $optionElem = $el->findElement(
                        WebDriverBy::xpath('./../div[contains(@class, "ui-multiselect")]//div[contains(@class, "option-text") and contains(string(), "' . addslashes($option) . '")]/..')
                    );
                    $buttonElem = $optionElem->findElement(
                        WebDriverBy::xpath('.//a[contains(@class, "' . $action . '-action")]')
                    );
                    $buttonElem->click();
                    $webDriver->wait(1); // give the animation a second to complete
                } catch (StaleElementReferenceException $e) {
                    // the outer function will retry this function, it is not pretty, but will have to do for now.
                }
            }
        );
    }

    public function getCiAttributeFormRow($attributeName, $sequenceNumber = 0) {
        $elements = $this->findElements(WebDriverBy::cssSelector('tr[data-attributename="'.$attributeName.'"]'));
        if(isset($elements[$sequenceNumber])) {
            return $elements[$sequenceNumber];
        }

        return false;
    }

    public function getAttributeTypeName($attributeName) {
        $formRow = $this->getCiAttributeFormRow($attributeName);
        if($formRow !== false) {
            return $formRow->getAttribute('data-attributetypename');
        }

        return false;
    }

    public function fillCiAttributeValue($attributeName, $value, $sequenceNumber = 0) {
        $formRow = $this->getCiAttributeFormRow($attributeName, $sequenceNumber);
        if($formRow !== false) {
            $this->webDriverModule->scrollTo(WebDriverBy::cssSelector('body'), null, $formRow->getLocation()->getY() - 200);
            $attributeType          = $formRow->getAttribute('data-attributetypename');

            switch($attributeType) {
                case 'textarea':    $attributeFormElement = $formRow->findElement($this->match('.//textarea'));
                                    $attributeFormElement->clear()->click()->sendKeys($value);
                                    break;
                case 'textEdit':    $attributeFormElementId = $formRow->findElement($this->match('.//textarea'))->getAttribute('id');
                                    $this->fillTinyMceEditorById($attributeFormElementId, $value);
                                    break;
                case 'select':      $attributeFormElementName = $formRow->findElement($this->match('.//select'))->getAttribute('name');
                                    $this->webDriverModule->selectOption($attributeFormElementName, $value);
                                    break;
                case 'checkbox':    foreach($value as $val) {
                                        $optionInputId = $formRow->findElement($this->match('.//label[contains(text(), "'.$val.'")]/input'))->getAttribute('id');
                                        $this->webDriverModule->checkOption('#' . $optionInputId);
                                    }
                                    break;
                case 'radio':       $optionInputId = $formRow->findElement($this->match('//label[contains(text(), "'.$value.'")]/input'))->getAttribute('id');
                                    $this->webDriverModule->checkOption('#' . $optionInputId);
                                    break;
                case 'selectQuery': $attributeFormElement = $formRow->findElement($this->match('.//select'));
                                    $isMultiSelect = $attributeFormElement->getAttribute('data-ismultiselect');

                                    if($isMultiSelect == 0) {
                                        $this->webDriverModule->selectOption($attributeFormElement->getAttribute('name'), $value);
                                    } else {
                                        foreach($value as $optionName => $optionSettings) {
                                            $this->setMultiSelectOptionAmount('#' . $attributeFormElement->getAttribute('id'), $optionName, $optionSettings['amount']);
                                        }
                                    }
                                    break;
                case 'date':        $attributeFormElement = $formRow->findElement($this->match('input:not(.delete_icon)'));
                                    $attributeFormElementId = $attributeFormElement->getAttribute('id');
                                    $this->fillDatePicker(WebDriverBy::cssSelector('#' . $attributeFormElementId), $value);
                                    break;
                case 'dateTime':    $attributeFormElement = $formRow->findElement($this->match('input:not(.delete_icon)'));
                                    $attributeFormElementId = $attributeFormElement->getAttribute('id');
                                    $this->fillDatePicker(WebDriverBy::cssSelector('#' . $attributeFormElementId), $value);

                                    break;
                default:            $attributeFormElement = $formRow->findElement($this->match('input:not(.delete_icon)'));
                                    $attributeFormElement->clear()->click()->sendKeys($value);
            }

        }
    }

    public function loggingIn(\AcceptanceTester $I, $username='admin', $password='admin', $force=false) {
        $auth = Fixtures::get('auth_user');

        if($auth != $username or $force === true) {
            $url = $I->getWebDriverModule()->webDriver->getCurrentURL();
            $compare = 'login/login';
            $equals = strpos($url, $compare);

            if($equals === false){
                $I->amOnPage('/login/logout');
            }

            $I->fillField(\LoginControllerCest::inputUser, $username);
            $I->fillField(\LoginControllerCest::inputPassword, $password);
            $I->click(\LoginControllerCest::inputLoginSubmit);
            $this->waitForAjaxLoad(10);
            $I->waitForPageLoad(10);

            Fixtures::add('auth_user', $username);
            Fixtures::add('auth_cookie', $I->grabCookie('INFOCMDB'));
        } else {
            $I->setCookie('INFOCMDB', Fixtures::get('auth_cookie'));
        }
    }

    public function loggingOut(\AcceptanceTester $I) {
        $I->amOnPage('/login/logout');
        $I->waitForPageLoad(10);

        Fixtures::add('auth_user', '');
        Fixtures::add('auth_cookie', '');
    }

    public static function exec($command) {
        codecept_debug($command);
        exec($command, $ouput, $return);
        codecept_debug($ouput);

        return $return;
    }

    public function seeAttributeValueInCiDetail($attributeName, $value) {
        $attributeType = $this->getAttributeTypeName($attributeName);

        switch($attributeType) {
            case 'input':           $patternType = 'text';      break;
            case 'zahlungsmittel':  $patternType = 'text';      break;
            case 'password':        $patternType = 'text';      break;
            case 'link':            $patternType = 'text';      break;
            case 'textarea':        $patternType = 'text';      break;
            case 'textEdit':        $patternType = 'text';      break;
            case 'select':          $patternType = 'text';      break;
            case 'checkbox':        $patternType = 'checkbox';  break;
            case 'radio':           $patternType = 'text';      break;
            case 'date':            $patternType = 'text';      break;
            case 'dateTime':        $patternType = 'text';      break;
            case 'selectQuery':     $patternType = 'selectQuery'; break;
            default:                $patternType = 'not supported';
        }

        $patterns = array();
        if($patternType === 'text') {
            $patterns[] = '/<tr.*?data-attributename="' . preg_quote($attributeName, '/') . '".*?>.*?<div class="attributeValue.*?>.*?' . preg_quote($value, '/') . '.*?<\/div>.*?<\/tr>/s';
        } elseif($patternType === 'checkbox') {
            $patterns[] = '/<tr.*?data-attributename="' . preg_quote($attributeName, '/') . '".*?>.*?<div class="attributeValue.*?>.*?' . preg_quote(implode(', ', $value), '/') . '.*?<\/div>.*?<\/tr>/s';
        } elseif($patternType === 'selectQuery') {
            $formRow                = $this->getCiAttributeFormRow($attributeName);
            $isMultiSelectCounter   = $this->hasElement($formRow, $this->match('.counterLabel'));

            if(!is_array($value)) {
                $value = array($value => array('amount' => 1));
            }

            foreach($value as $optionName => $optionSettings) {
                $pattern = '/<tr.*?data-attributename="' . preg_quote($attributeName, '/') . '".*?>.*?<div class="attributeValue.*?>';

                if ($isMultiSelectCounter === true) {
                    $pattern .= '.*?<span class="counterLabel".*?>' . preg_quote($optionSettings['amount'], '/') . '<\/span>.*?';
                } else {
                    $pattern .= '.*?';
                }

                $pattern .= preg_quote($optionName, '/') . '.*?';
                $pattern .= '<\/div>.*?<\/tr>/s';

                $patterns[] = $pattern;
            }


        } else {
            $this->outputInfo($attributeName, ' check not supported');
            return false;
        }

        foreach($patterns as $pattern) {
            $this->seeRegexMatch($pattern);
        }

    }

    public function scrollToPosition($x, $y) {
        $js = sprintf('window.scrollTo(%d, %d)', $x, $y);
        $this->webDriverModule->executeJS($js);
    }

    public function scrollToTop() {
        $this->scrollToPosition(0, 0);
    }

    public function countRowsInTable($number = 25, $selectorType = 'class', $table = 'list'){

        $rows = $this->findElements(WebDriverBy::xpath('//table[@'.$selectorType.'="'.$table.'"]/tbody/tr'));

        // minus 1 because of tr headline
        if ((count($rows) - 1) !== $number) {

            throw new \Exception('Expected ' . $number . ' rows');
        }
    }

    public function grabAttributeFrom($locator, $attribute) {
        return $this->webDriverModule->grabAttributeFrom($locator, $attribute);
    }

    public function switchToLastWindow()
    {
        $handles = $this->webDriverModule->webDriver->getWindowHandles();
        $lastWindow = end($handles);
        $this->webDriverModule->switchToWindow($lastWindow);
    }

    public function switchToFirstWindow()
    {
        $this->webDriverModule->switchToWindow();
    }

    public function createMaintenance($Message = '') {
        $this->clearMaintenance();

        if($Message === '') {
            $Message = sprintf('Maintenance is active since %s', date("Y-m-d H:i:s"));
        }

        file_put_contents($this->maintenanceFile, $Message);

        return $Message;
    }

    public function clearMaintenance() {
        if(is_file($this->maintenanceFile) === true) {
            unlink($this->maintenanceFile);
        }
    }
}
