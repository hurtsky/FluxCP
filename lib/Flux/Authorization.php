<?php

require_once 'Flux/Error.php';

/**
 * The authorization component allows you to find out whether or not the
 * the current user is allowed to perform a certain task based on his account
 * group level.
 */
class Flux_Authorization
{
    /**
     * Authorization instance.
     *
     * @var Flux_Authorization
     */
    private static $auth;

    /**
     * Access configuration.
     *
     * @var Flux_Config
     */
    private $config;

    /**
     * Session data object.
     *
     * @var Flux_SessionData
     */
    private $session;

    /**
     * Construct new Flux_Authorization instance.
     *
     * @param Flux_Config      $accessConfig
     * @param Flux_SessionData $sessionData
     */
    private function __construct(Flux_Config $accessConfig, Flux_SessionData $sessionData)
    {
        $this->config = $accessConfig;
        $this->session = $sessionData;
    }

    /**
     * Get authorization instance, creates one if it doesn't already exist.
     *
     * @param Flux_Config      $accessConfig
     * @param Flux_SessionData $sessionData
     *
     * @return Flux_Authorization
     */
    public static function getInstance($accessConfig = null, $sessionData = null)
    {
        if (!self::$auth) {
            self::$auth = new self($accessConfig, $sessionData);
        }

        return self::$auth;
    }

    /**
     * Checks whether or not the current user is able to perform a particular
     * action based on his/her group level and id.
     *
     * @param string $moduleName
     * @param string $actionName
     *
     * @return bool
     */
    public function actionAllowed($moduleName, $actionName = 'index')
    {
        $accessConfig = $this->config->get('modules');
        $accessKeys = ["$moduleName.$actionName", "$moduleName.*"];
        $accountLevel = $this->session->account->group_level;
        $existentKeys = [];

        if ($accessConfig instanceof Flux_Config) {
            foreach ($accessKeys as $accessKey) {
                $accessLevel = $accessConfig->get($accessKey);

                if (!is_null($accessLevel)) {
                    $existentKeys[] = $accessKey;

                    if ($accessLevel == AccountLevel::ANYONE || $accessLevel == $accountLevel ||
                        ($accessLevel != AccountLevel::UNAUTH && $accessLevel <= $accountLevel)) {
                        return true;
                    }
                }
            }
        }

        if (empty($existentKeys)) {
            return -1;
        } else {
            return false;
        }
    }

    /**
     * Checks whether or not the current user is allowed to use a particular
     * feature based on his/her group level and id.
     *
     * @param string $featureName
     *
     * @return bool
     */
    public function featureAllowed($featureName)
    {
        $accessConfig = $this->config->get('features');
        $accountLevel = $this->session->account->group_level;

        if (($accessConfig instanceof Flux_Config)) {
            $accessLevel = $accessConfig->get($featureName);

            if (!is_null($accessLevel) &&
                ($accessLevel == AccountLevel::ANYONE || $accessLevel == $accountLevel ||
                ($accessLevel != AccountLevel::UNAUTH && $accessLevel <= $accountLevel))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Provides convenient getters such as `allowedTo<FeatureName>' and
     * `getGroupLevelTo<FeatureName>'.
     */
    public function __get($prop)
    {
        if (preg_match('/^allowedTo(.+)/i', $prop, $m)) {
            return $this->featureAllowed($m[1]);
        } elseif (preg_match('/^getGroupLevelTo(.+)/i', $prop, $m)) {
            $accessConfig = $this->config->get('features');
            if ($accessConfig instanceof Flux_Config) {
                return $accessConfig->get($m[1]);
            }
        }
    }

    /**
     * Wrapper method for setting and getting values from the access config.
     *
     * @param string $key
     * @param mixed  $value
     * @param arary  $options
     */
    public function config($key, $value = null, $options = [])
    {
        if (!is_null($value)) {
            return $this->config->set($key, $value, $options);
        } else {
            return $this->config->get($key);
        }
    }
}
