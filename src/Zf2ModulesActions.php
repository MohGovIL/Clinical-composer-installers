<?php
/**
 * Created by PhpStorm.
 * User: amiel
 * Date: 21/12/17
 * Time: 13:15
 */

namespace Clinikal\ComposerInstallersClinikalExtender;

use Composer\Package\PackageInterface;
use Clinikal\ComposerInstallersClinikalExtender\Installer;

class Zf2ModulesActions
{

    const APPLICATION_CONF_PATH = 'interface/modules/zend_modules/config/application.config.php';
    const OPENEMR_MODULES_PATH = 'interface/modules/zend_modules/module/';


    /**
     * Create link from
     * @param \Clinikal\ComposerInstallersClinikalExtender\Installer $installer
     * @param PackageInterface $package
     */
    static function createLink(Installer $installer, $target , $moduleName)
    {
        if (!is_link($installer->basePath.self::OPENEMR_MODULES_PATH.$moduleName)) {

            symlink($target ,$installer->basePath.self::OPENEMR_MODULES_PATH.$moduleName);
            Installer::messageToCLI("Create link to module - $moduleName");
            self::addToApplicationConf($installer,$moduleName);
            $installer->appendToGitignore($installer->basePath.self::OPENEMR_MODULES_PATH.$moduleName);
        }

    }

    static function addToApplicationConf(Installer $installer, $moduleName)
    {

        $configs = require $installer->basePath.self::APPLICATION_CONF_PATH;
        $configs['modules'][] = $moduleName;
        file_put_contents($installer->basePath.self::APPLICATION_CONF_PATH,'<?php return ' . var_export($configs,true) . ';');
        $installer::messageToCLI('Adding module ' . $moduleName . ' to application.config.php');
    }

}