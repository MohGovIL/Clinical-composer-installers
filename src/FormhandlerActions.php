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

    /**
     * copy form json to backup data folder - the upgrade script sends a json to couchdDB from there.
     * @param \Clinikal\ComposerInstallersClinikalExtender\Installer $installer
     * @param PackageInterface $package
     */
    static function copyCouchDbJson(Installer $installer, PackageInterface $package)
    {
        $packageName = explode('/',$package->getName())[1];
        //copy json to clinikal/install/couchDB/forms/backup_data/
        copy($installer->getInstallPath($package).'/'. $packageName .'.json', $installer->basePath . self::FORMS_JSON_PATH . $packageName.'.json');
        Installer::messageToCLI("Coping $packageName.json to clinikal/install/couchDB/forms/backup_data");
    }

    static function installTable(Installer $installer,$packagePath)
    {
        Installer::messageToCLI("Installing new sql table");
        upgradeFromSqlFile($packagePath.'/table.sql');

    }



}