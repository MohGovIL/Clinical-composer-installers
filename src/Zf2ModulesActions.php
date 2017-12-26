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
        $oldConfig = require $installer->basePath.self::APPLICATION_CONF_PATH;
        $lastModuleKey = key(end($oldConfig['modules']));
        $oldConfig['modules']["'". $lastModuleKey + 1 ."'"] = $modName;
        print_r($oldConfig);
    }


}