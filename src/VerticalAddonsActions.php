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

class VerticalAddonsActions
{

    const OPENEMR_CSS_PATH = 'interface/themes/';
    const OPENEMR_CSS_FILENAME = 'style_vertical.css';
    const CSS_ORIGIN_NAME='style_clinikal.css';
    const VERTICAL_CSS_FOLDER_PATH='css/';

    /**
     *
     * @param \Clinikal\ComposerInstallersClinikalExtender\Installer $installer
     * @param PackageInterface $package
     */
    static function createCssLink(Installer $installer, PackageInterface $package)
    {

        symlink($installer->getInstallPath($package).'/'. self::VERTICAL_CSS_FOLDER_PATH.self::CSS_ORIGIN_NAME ,$installer->basePath.self::OPENEMR_CSS_PATH.self::OPENEMR_CSS_FILENAME);
        symlink($installer->getInstallPath($package).'/'.'rtl_'. self::VERTICAL_CSS_FOLDER_PATH.self::CSS_ORIGIN_NAME ,$installer->basePath.self::OPENEMR_CSS_PATH.'rtl_'.self::OPENEMR_CSS_FILENAME);
        Installer::messageToCLI("Create link to css file of the vertical");
    }



}
