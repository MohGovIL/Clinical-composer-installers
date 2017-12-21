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

    static function copyCouchDbJson(Installer $installer, PackageInterface $package)
    {
        copy($installer->basePath . $installer->getInstallPath($package).'/'. explode('/',$package->getName())[1].'.json', $installer->basePath . self::FORMS_JSON_PATH . explode('/',$package->getName())[1].'.json');
    }



}