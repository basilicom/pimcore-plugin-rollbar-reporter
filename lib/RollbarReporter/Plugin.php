<?php
/**
 * RollbarReporter Pimcore Plugin
 */

namespace RollbarReporter;

use Pimcore\API\Plugin as PluginLib;

/**
 * Class Plugin
 *
 * @package RollbarReporter
 */
class Plugin extends PluginLib\AbstractPlugin implements PluginLib\PluginInterface {

    const SAMPLE_CONFIG_XML = "/RollbarReporter/rollbar.xml";
    const CONFIG_XML = '/var/config/rollbar.xml';

    const TRANSACTION_NAME_MAINTENANCE = 'Maintenance';

    /**
     * @var bool store enabled state - set in config xml
     */
    private static $isEnabled = false;

    /**
     * @var string cache apiKey - set in config xml
     */
    private static $accessToken = null;
    
    /**
     * Initialize Plugin
     *
     * Sets up RollbarReporter, apiKey, various config options
     */
    public function init()
    {
        parent::init();

        if (!self::isInstalled()) {
            return;
        }

        $config = new \Zend_Config_Xml(self::getConfigName());

        self::$isEnabled = ($config->rollbar->get('enabled', '0') == '1');
        if (!self::$isEnabled) {
            return;
        }

        $accessToken = $config->rollbar->get('accessToken', '');
        if ($accessToken == '') {
            return;
        }
        
        self::$accessToken = $accessToken;

        // exclude pimcore backend traces?
        if (!\Pimcore\Tool::isFrontend() && ($config->rollbar->get('excludeBackend', '1') == '1')) {
            return;
        }

        if (
            ($config->rollbar->get('excludeCli', '1') == '1')
            && (substr(php_sapi_name(), 0, 3) == 'cli')
        ) {
            return;
        }

        $environment = getenv("PIMCORE_ENVIRONMENT") ?: (getenv("REDIRECT_PIMCORE_ENVIRONMENT") ?: FALSE);
        if (!$environment) {
            $environment = 'default';
        }

        $rollbarSetup = array(
            // required
            'access_token' => self::$accessToken,
            // optional - environment name. any string will do.
            'environment' => $environment,
            // optional - path to directory your code is in. used for linking stack traces.
            //'root' => '/var/www/......'
        );

        $handler = $config->rollbar->get('handler', 'blocking');
        if ($handler == 'agent') {
            $rollbarSetup['agent_log_location'] = $config->rollbar->get('agentLogLocation', '/tmp');
        }

        \Rollbar::init($rollbarSetup);

        self::$isEnabled = true;

    }

    /**
     * Install plugin
     *
     * Copies sample XML to website config path if it does not exist yet.
     * Sets config file parameter "installed" to 1 and "enabled" to "1"
     *
     * @return string install success|failure message
     */
    public static function install()
    {
        if (!file_exists(self::getConfigName())) {

            $defaultConfig = new \Zend_Config_Xml(PIMCORE_PLUGINS_PATH . self::SAMPLE_CONFIG_XML);
            $configWriter = new \Zend_Config_Writer_Xml();
            $configWriter->setConfig($defaultConfig);
            $configWriter->write(self::getConfigName());
        }

        $config = new \Zend_Config_Xml(self::getConfigName(), null, array('allowModifications' => true));
        $config->rollbar->installed = 1;

        $configWriter = new \Zend_Config_Writer_Xml();
        $configWriter->setConfig($config);
        $configWriter->write(self::getConfigName());

        if (self::isInstalled()) {
            return "Successfully installed.";
        } else {
            return "Could not be installed";
        }
    }

    /**
     * Uninstall plugin
     *
     * Sets config file parameter "installed" to 0 (if config file exists)
     *
     * @return string uninstall success|failure message
     */
    public static function uninstall()
    {
        if (file_exists(self::getConfigName())) {

            $config = new \Zend_Config_Xml(self::getConfigName(), null, array('allowModifications' => true));
            $config->rollbar->installed = 0;

            $configWriter = new \Zend_Config_Writer_Xml();
            $configWriter->setConfig($config);
            $configWriter->write(self::getConfigName());
        }

        if (!self::isInstalled()) {
            return "Successfully uninstalled.";
        } else {
            return "Could not be uninstalled";
        }
    }

    /**
     * Determine plugin install state
     *
     * @return bool true if plugin is installed (option "installed" is "1" in config file)
     */
    public static function isInstalled()
    {
        if (!file_exists(self::getConfigName())) {
            return false;
        }

        $config = new \Zend_Config_Xml(self::getConfigName());
        if ($config->rollbar->installed != 1) {
            return false;
        }
        return true;
    }

    /**
     * Report an exception (only if installed & enabled)
     *
     * @param \Exception $exception
     */
    public static function exception(\Exception $exception)
    {
        if (self::$isEnabled) {
            \Rollbar::report_exception($exception);
        }
    }

    /**
     * Report a message (only if installed & enabled)
     *
     * @param string $message
     * @param string $level one of 'info'..
     * @param $extra array a hash of extra key-value data
     */
    public static function message($message, $level='info', $extra=null)
    {
        if (self::$isEnabled) {
            \Rollbar::report_message(
                $message,
                $level,
                $extra
            );
        }
    }
    
    /**
     * Flush the internal rollbar buffer
     */
    public static function flush()
    {
        if (self::$isEnabled) {
            \Rollbar::flush();
        }
    }

    /**
     * Return config file name
     *
     * @return string xml config filename
     */
    private static function getConfigName()
    {
        return PIMCORE_WEBSITE_PATH . self::CONFIG_XML;
    }

}
