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

    static function addToApplicationConf(Installer $installer, $packageName)
    {
        $modName = explode('/',$packageName)[1];
        $configs = require $installer->basePath.self::APPLICATION_CONF_PATH;
        $configs['modules'][] = $modName;
        file_put_contents($installer->basePath.self::APPLICATION_CONF_PATH,'<?php return ' . var_export($configs,true) . ';');

    }


}