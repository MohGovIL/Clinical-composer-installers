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

class FormhandlerActions
{

    const FORMS_JSON_PATH = 'clinikal/install/couchDB/forms/backup_data/';
    const OPENEMR_FORMS_PATH = 'interface/forms/';


    /**
     *
     * @param \Clinikal\ComposerInstallersClinikalExtender\Installer $installer
     * @param PackageInterface $package
     */
    static function createLink(Installer $installer, $target, $formName)
    {

        if (!is_link($installer->basePath.self::OPENEMR_FORMS_PATH.$formName)) {

            symlink($target,$installer->basePath.self::OPENEMR_FORMS_PATH.$formName);
            Installer::messageToCLI("Create link to form - $formName");

            $installer->appendToGitignore($formName, self::OPENEMR_FORMS_PATH);
        }

    }


    /**
     * copy form json to backup data folder - the upgrade script sends a json to couchdDB from there.
     * @param \Clinikal\ComposerInstallersClinikalExtender\Installer $installer
     * @param PackageInterface $package
     */
    static function linkToCouchDbJson(Installer $installer, $packageName)
    {
        //copy json to clinikal/install/couchDB/forms/backup_data/
        if (!is_link($installer->basePath . self::FORMS_JSON_PATH . $packageName.'.json')) {

            symlink($installer->basePath . self::OPENEMR_FORMS_PATH . $packageName . '/' . $packageName .'.json', $installer->basePath . self::FORMS_JSON_PATH .$packageName . '.json');
            Installer::messageToCLI("Coping $packageName.json to clinikal/install/couchDB/forms/backup_data");

            $installer->appendToGitignore($packageName . '.json', self::FORMS_JSON_PATH );
        }
    }


    static function installTable(Installer $installer,$packagePath)
    {
        Installer::messageToCLI("Installing new sql table");
        upgradeFromSqlFile($packagePath.'/table.sql');

    }



}