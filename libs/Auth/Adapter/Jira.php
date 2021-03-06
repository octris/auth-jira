<?php

/*
 * This file is part of the 'octris/jira' package.
 *
 * (c) Harald Lapp <harald@octris.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Octris\Jira\Auth\Adapter;

use Jira\JiraClient;

/**
 * JIRA Authentication adapter.
 *
 * @copyright   copyright (c) 2014-2016 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
class Jira implements \Octris\Core\Auth\IAdapter
{
    /**
     * Username to authenticate with adapter.
     *
     * @type    string
     */
    protected $username = '';

    /**
     * Credential to authenticate with adapter.
     *
     * @type    string
     */
    protected $credential = '';

    /**
     * URL of Jira installation to connect with.
     *
     * @type    string
     */
    protected $url;

    /**
     * Constructor.
     *
     * @param   string          $url                URL of Jira installation to connect with.
     */
    public function __construct($url)
    {
        $this->url = $url;
    }

    /**
     * Set's a username to be authenticated.
     *
     * @param   string          $username           Username to authenticate.
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
     * Set's a credential to be authenticated.
     *
     * @param   string          $credential         Credential to authenticate.
     */
    public function setCredential($credential)
    {
        $this->credential = $credential;
    }

    /**
     * Authenticate.
     *
     * @return  \Octris\Core\Auth\Identity                  Instance of identity class.
     */
    public function authenticate()
    {
        $result = \Octris\Core\Auth::AUTH_FAILURE;
        $token = null;

        if (empty($this->username)) {
            throw new \Exception('Username cannot be empty');
        }
        if (empty($this->credential)) {
            throw new \Exception('Credential cannot be empty');
        }

        $jira = new JiraClient($this->url);

        try {
            $token = $jira->login($this->username, $this->credential);

            if (!$token) {
                $result = \Octris\Core\Auth::IDENTITY_UNKNOWN;
            } else {
                $result = \Octris\Core\Auth::AUTH_SUCCESS;
            }
        } catch(\SoapFault $e) {
            if (preg_match('/^com.atlassian.jira.rpc.exception.RemoteAuthenticationException: (.+)$/i', $e->getMessage(), $match)) {
                if (strcasecmp($match[1], 'Invalid username or password.') == 0) {
                    $result = \Octris\Core\Auth::IDENTITY_UNKNOWN;
                }
            } else {
                throw($e);
            }
        }

        return new \Octris\Core\Auth\Identity(
            $result,
            array(
                'username' => $this->username,
                'token' => $token
            )
        );
    }
}
