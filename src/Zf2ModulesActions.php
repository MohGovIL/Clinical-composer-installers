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
    const OPENEMR_MODULES_JS_PATH = 'interface/modules/zend_modules/public/js/';
    const OPENEMR_MODULES_CSS_PATH = 'interface/modules/zend_modules/public/css/';


    /**
     * Create link from
     * @param \Clinikal\ComposerInstallersClinikalExtender\Installer $installer
     * @param PackageInterface $package
     */
    static function createLink(Installer $installer, $relativeTarget , $moduleName)
    {

        $baseTarget = Installer::getRelativePathBetween($installer->basePath.self::OPENEMR_MODULES_PATH, $installer->basePath);

        if (!is_link($installer->basePath.self::OPENEMR_MODULES_PATH.$moduleName)) {
          //  $installer->getRelativePath($target);
            symlink($baseTarget . $relativeTarget ,$installer->basePath.self::OPENEMR_MODULES_PATH.$moduleName);
            Installer::messageToCLI("Create link to module - $moduleName");
            self::addToApplicationConf($installer,$moduleName);
            $installer->appendToGitignore($moduleName, self::OPENEMR_MODULES_PATH);
        }
    }

    static function createJsLink(Installer $installer, $relativeTarget , $jsFolder)
    {

        $baseTarget = Installer::getRelativePathBetween($installer->basePath.self::OPENEMR_MODULES_JS_PATH, $installer->basePath);

        if (!is_link($installer->basePath.self::OPENEMR_MODULES_JS_PATH.$jsFolder)) {
            //  $installer->getRelativePath($target);
            symlink($baseTarget . $relativeTarget ,$installer->basePath.self::OPENEMR_MODULES_JS_PATH.$jsFolder);
            Installer::messageToCLI("Create link to JS for module - $jsFolder");
            $installer->appendToGitignore($jsFolder, self::OPENEMR_MODULES_JS_PATH);
        }
    }

    static function createCssLink(Installer $installer, $relativeTarget , $cssFolder)
    {

        $baseTarget = Installer::getRelativePathBetween($installer->basePath.self::OPENEMR_MODULES_CSS_PATH, $installer->basePath);

        if (!is_link($installer->basePath.self::OPENEMR_MODULES_CSS_PATH.$cssFolder)) {
            //  $installer->getRelativePath($target);
            symlink($baseTarget . $relativeTarget ,$installer->basePath.self::OPENEMR_MODULES_CSS_PATH.$cssFolder);
            Installer::messageToCLI("Create link to JS for module - $cssFolder");
            $installer->appendToGitignore($cssFolder, self::OPENEMR_MODULES_CSS_PATH);
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
