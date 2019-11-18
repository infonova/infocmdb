<?php

// Based on: https://github.com/captbaritone/codeception-mailcatcher-module

namespace Helper;

use Codeception\Module;
use Codeception\Util\Email;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Uri;

class MailCatcher extends Module
{
    /**
     * @var Client
     */
    protected $mailcatcher;

    /**
     * @var array
     */
    protected $config = ['url', 'port', 'guzzleRequestOptions'];

    /**
     * @var array
     */
    protected $requiredFields = ['url', 'port'];

    public function _initialize()
    {
        $base_url = trim($this->config['url'], '/') . ':' . $this->config['port'];
        $url = new Uri($base_url);

        $checkPort = @fsockopen($url->getHost(), $url->getPort(), $errno, $errstr, 1);
        if($checkPort === false) {
            $message = sprintf('
                Warning: Mailcatcher not reachable at host "%s" port "%s"!
                All mail assertions will return true!
            ', $url->getHost(), $url->getPort());
            $output = new \Codeception\Lib\Console\Output(array());
            $output->writeln('<pending>' . $message . '</>');
            $this->mailcatcher = false;
        } else {
            fclose($checkPort);

            $guzzleConfig = [
                'base_uri' => $base_url
            ];
            if (isset($this->config['guzzleRequestOptions'])) {
                $guzzleConfig = array_merge($guzzleConfig, $this->config['guzzleRequestOptions']);
            }

            $this->mailcatcher = new Client($guzzleConfig);
        }
    }

    public function mailcatcherEnabled() {
        if($this->mailcatcher === false || $this->mailcatcher === null) {
            return false;
        }

        return true;
    }

    /**
     * Get unique email address
     *
     * Returns an email address based on backtrace and time
     *
     * @param array $parts optional
     * @param string $domain optional
     * @return string email address
     */
    public static function getUniqueEmailAddress($parts=array(), $domain='example.com')
    {
        $backtrace = debug_backtrace();
        $caller = next($backtrace);

        if(empty($parts)) {
            $parts = array(
                $caller['class'],
                $caller['function'],
                date('YmdHis'),
                microtime(true),
                rand(100, 999),
            );
        }

        $mailAddress = implode('-', $parts) . '@' . $domain;

        return $mailAddress;
    }


    /**
     * Reset emails
     *
     * Clear all emails from mailcatcher. You probably want to do this before
     * you do the thing that will send emails
     *
     * @return void
     * @author Jordan Eldredge <jordaneldredge@gmail.com>
     **/
    public function resetEmails()
    {
        if(!$this->mailcatcherEnabled()) {
            return;
        }

        $this->mailcatcher->delete('/messages');
    }


    /**
     * See In Last Email
     *
     * Look for a string in the most recent email
     *
     * @param string $expected
     * @return void
     * @author Jordan Eldredge <jordaneldredge@gmail.com>
     **/
    public function seeInLastEmail($expected)
    {
        if(!$this->mailcatcherEnabled()) {
            return;
        }

        $email = $this->lastMessage();
        $this->seeInEmail($email, $expected);
    }

    /**
     * See In Last Email subject
     *
     * Look for a string in the most recent email subject
     *
     * @param string $expected
     * @return void
     * @author Antoine Augusti <antoine.augusti@gmail.com>
     **/
    public function seeInLastEmailSubject($expected)
    {
        if(!$this->mailcatcherEnabled()) {
            return;
        }

        $email = $this->lastMessage();
        $this->seeInEmailSubject($email, $expected);
    }

    /**
     * Don't See In Last Email subject
     *
     * Look for the absence of a string in the most recent email subject
     *
     * @param string $expected
     * @return void
     **/
    public function dontSeeInLastEmailSubject($expected)
    {
        if(!$this->mailcatcherEnabled()) {
            return;
        }

        $email = $this->lastMessage();
        $this->dontSeeInEmailSubject($email, $expected);
    }

    /**
     * Don't See In Last Email
     *
     * Look for the absence of a string in the most recent email
     *
     * @param string $unexpected
     * @return void
     **/
    public function dontSeeInLastEmail($unexpected)
    {
        if(!$this->mailcatcherEnabled()) {
            return;
        }

        $email = $this->lastMessage();
        $this->dontSeeInEmail($email, $unexpected);
    }

    /**
     * See In Last Email To
     *
     * Look for a string in the most recent email sent to $address
     *
     * @param string $address
     * @param string $expected
     * @return void
     * @author Jordan Eldredge <jordaneldredge@gmail.com>
     **/
    public function seeInLastEmailTo($address, $expected)
    {
        if(!$this->mailcatcherEnabled()) {
            return;
        }

        $email = $this->lastMessageFrom($address);
        $this->seeInEmail($email, $expected);
    }

    /**
     * Don't See In Last Email To
     *
     * Look for the absence of a string in the most recent email sent to $address
     * @param string $address
     * @param string $unexpected
     * @return void
     **/
    public function dontSeeInLastEmailTo($address, $unexpected)
    {
        if(!$this->mailcatcherEnabled()) {
            return;
        }

        $email = $this->lastMessageFrom($address);
        $this->dontSeeInEmail($email, $unexpected);
    }

    /**
     * See In Last Email Subject To
     *
     * Look for a string in the most recent email subject sent to $address
     *
     * @param string $address
     * @param string $expected
     * @return void
     * @author Antoine Augusti <antoine.augusti@gmail.com>
     **/
    public function seeInLastEmailSubjectTo($address, $expected)
    {
        if(!$this->mailcatcherEnabled()) {
            return;
        }

        $email = $this->lastMessageFrom($address);
        $this->seeInEmailSubject($email, $expected);
    }

    /**
     * Don't See In Last Email Subject To
     *
     * Look for the absence of a string in the most recent email subject sent to $address
     *
     * @param string $address
     * @param string $unexpected
     * @return void
     **/
    public function dontSeeInLastEmailSubjectTo($address, $unexpected)
    {
        if(!$this->mailcatcherEnabled()) {
            return;
        }

        $email = $this->lastMessageFrom($address);
        $this->dontSeeInEmailSubject($email, $unexpected);
    }

    /**
     * @return Email
     */
    public function lastMessage()
    {
        if(!$this->mailcatcherEnabled()) {
            return new Email(null, array(), null, null );
        }

        $messages = $this->messages();
        if (empty($messages)) {
            $this->fail("No messages received");
        }

        $last = array_shift($messages);

        return $this->emailFromId($last['id']);
    }

    /**
     * @param $address
     * @return Email
     */
    public function lastMessageFrom($address)
    {
        if(!$this->mailcatcherEnabled()) {
            return new Email(null, array(), null, null );
        }
        $ids = [];
        $messages = $this->messages();
        if (empty($messages)) {
            $this->fail("No messages received");
        }

        foreach ($messages as $message) {
            foreach ($message['recipients'] as $recipient) {
                if (strpos($recipient, $address) !== false) {
                    $ids[] = $message['id'];
                }
            }
        }

        if (count($ids) === 0) {
            $this->fail("No messages sent to {$address}");
        }

        return $this->emailFromId(max($ids));
    }

    /**
     * Grab Matches From Last Email
     *
     * Look for a regex in the email source and return it's matches
     *
     * @param string $regex
     * @return array
     * @author Stephan Hochhaus <stephan@yauh.de>
     **/
    public function grabMatchesFromLastEmail($regex)
    {
        if(!$this->mailcatcherEnabled()) {
            return array();
        }

        $email = $this->lastMessage();
        $matches = $this->grabMatchesFromEmail($email, $regex);
        return $matches;
    }

    /**
     * Grab From Last Email
     *
     * Look for a regex in the email source and return it
     *
     * @param string $regex
     * @return string
     * @author Stephan Hochhaus <stephan@yauh.de>
     **/
    public function grabFromLastEmail($regex)
    {
        if(!$this->mailcatcherEnabled()) {
            return '';
        }

        $matches = $this->grabMatchesFromLastEmail($regex);
        return $matches[0];
    }

    /**
     * Grab Matches From Last Email To
     *
     * Look for a regex in most recent email sent to $addres email source and
     * return it's matches
     *
     * @param string $address
     * @param string $regex
     * @return array
     * @author Stephan Hochhaus <stephan@yauh.de>
     **/
    public function grabMatchesFromLastEmailTo($address, $regex)
    {
        if(!$this->mailcatcherEnabled()) {
            return array();
        }

        $email = $this->lastMessageFrom($address);
        $matches = $this->grabMatchesFromEmail($email, $regex);
        return $matches;
    }

    /**
     * Grab From Last Email To
     *
     * Look for a regex in most recent email sent to $addres email source and
     * return it
     *
     * @param string $address
     * @param string $regex
     * @return string
     * @author Stephan Hochhaus <stephan@yauh.de>
     **/
    public function grabFromLastEmailTo($address, $regex)
    {
        if(!$this->mailcatcherEnabled()) {
            return '';
        }

        $matches = $this->grabMatchesFromLastEmailTo($address, $regex);
        return $matches[0];
    }

    /**
     * Test email count equals expected value
     *
     * @param int $expected
     * @return void
     * @author Mike Crowe <drmikecrowe@gmail.com>
     **/
    public function seeEmailCount($expected)
    {
        if(!$this->mailcatcherEnabled()) {
            return;
        }

        $messages = $this->messages();
        $count = count($messages);
        $this->assertEquals($expected, $count);
    }

    // ----------- HELPER METHODS BELOW HERE -----------------------//

    /**
     * Messages
     *
     * Get an array of all the message objects
     *
     * @return array
     * @author Jordan Eldredge <jordaneldredge@gmail.com>
     **/
    protected function messages()
    {
        if(!$this->mailcatcherEnabled()) {
            return array();
        }

        $response = $this->mailcatcher->get('/messages', array('debug' => false));
        $messages = json_decode($response->getBody(), true);
        // Ensure messages are shown in the order they were recieved
        // https://github.com/sj26/mailcatcher/pull/184
        usort($messages, function ($messageA, $messageB) {
            $sortKeyA = $messageA['created_at'] . $messageA['id'];
            $sortKeyB = $messageB['created_at'] . $messageB['id'];
            return ($sortKeyA > $sortKeyB) ? -1 : 1;
        });
        return $messages;
    }

    /**
     * @param $id
     * @return Email
     */
    protected function emailFromId($id)
    {
        if(!$this->mailcatcherEnabled()) {
            return new Email(null, array(), null, null );
        }

        $response = $this->mailcatcher->get("/messages/{$id}.json", array('debug' => false));
        $messageData = json_decode($response->getBody(), true);
        $messageData['source'] = quoted_printable_decode($messageData['source']);

        return Email::createFromMailcatcherData($messageData);
    }

    /**
     * @param Email $email
     * @param $expected
     */
    protected function seeInEmailSubject(Email $email, $expected)
    {
        if(!$this->mailcatcherEnabled()) {
            return;
        }

        $this->assertContains($expected, $email->getSubject(), "Email Subject Contains");
    }

    /**
     * @param Email $email
     * @param $unexpected
     */
    protected function dontSeeInEmailSubject(Email $email, $unexpected)
    {
        if(!$this->mailcatcherEnabled()) {
            return;
        }

        $this->assertNotContains($unexpected, $email->getSubject(), "Email Subject Does Not Contain");
    }

    /**
     * @param Email $email
     * @param $expected
     */
    protected function seeInEmail(Email $email, $expected)
    {
        if(!$this->mailcatcherEnabled()) {
            return;
        }

        $this->assertContains($expected, $email->getSource(), "Email Contains");
    }

    /**
     * @param Email $email
     * @param $unexpected
     */
    protected function dontSeeInEmail(Email $email, $unexpected)
    {
        if(!$this->mailcatcherEnabled()) {
            return;
        }

        $this->assertNotContains($unexpected, $email->getSource(), "Email Does Not Contain");
    }

    /**
     * @param Email $email
     * @param $regex
     * @return array
     */
    protected function grabMatchesFromEmail(Email $email, $regex)
    {
        if(!$this->mailcatcherEnabled()) {
            return array();
        }

        preg_match($regex, $email->getSource(), $matches);
        $this->assertNotEmpty($matches, "No matches found for $regex");
        return $matches;
    }
}