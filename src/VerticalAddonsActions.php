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
use Clinikal\ComposerInstallersClinikalExtender\Zf2ModulesActions;
use Clinikal\ComposerInstallersClinikalExtender\FormhandlerActions;

class VerticalAddonsActions
{

    const OPENEMR_CSS_PATH = 'interface/themes/';
    const OPENEMR_CSS_FILENAME = 'style_vertical.css';
    const CSS_ORIGIN_NAME='style_clinikal.css';
    const VERTICAL_CSS_FOLDER_PATH='css/';

    const OPENEMR_MENUS_PATH = 'sites/default/documents/custom_menus/';
    const VERTICAL_MENUS_FOLDER_PATH='menus/';

    const VERTICAL_FORMS_FOLDER_PATH='forms/';
    const VERTICAL_MODULES_FOLDER_PATH='modules/';



    /**
     *
     * @param \Clinikal\ComposerInstallersClinikalExtender\Installer $installer
     * @param PackageInterface $package
     */
    static function createCssLink(Installer $installer, PackageInterface $package)
    {
        symlink($installer->getInstallPath($package).'/'.self::VERTICAL_CSS_FOLDER_PATH.self::CSS_ORIGIN_NAME ,$installer->basePath.self::OPENEMR_CSS_PATH.self::OPENEMR_CSS_FILENAME);
        symlink($installer->getInstallPath($package).'/'.self::VERTICAL_CSS_FOLDER_PATH.'rtl_'.self::CSS_ORIGIN_NAME ,$installer->basePath.self::OPENEMR_CSS_PATH.'rtl_'.self::OPENEMR_CSS_FILENAME);
        Installer::messageToCLI("Create link to css file of the vertical");
    }


    /**
     *
     * @param \Clinikal\ComposerInstallersClinikalExtender\Installer $installer
     * @param PackageInterface $package
     */
    static function installUpdateModules(Installer $installer, PackageInterface $package)
    {
        $modules = scandir($installer->getInstallPath($package).'/'.self::VERTICAL_MODULES_FOLDER_PATH);
        foreach($modules as $module) {
            if (!is_dir($installer->getInstallPath($package).'/'.self::VERTICAL_MODULES_FOLDER_PATH . $module) || $module === '.' || $module === '..')continue;
            Zf2ModulesActions::createLink($installer, $installer->getInstallPath($package).'/'.self::VERTICAL_MODULES_FOLDER_PATH.$module, $module);
        }

    }


    /**
     *
     * @param \Clinikal\ComposerInstallersClinikalExtender\Installer $installer
     * @param PackageInterface $package
     */
    static function installUpdateForms(Installer $installer, PackageInterface $package)
    {
        $forms = scandir($installer->getInstallPath($package).'/'.self::VERTICAL_FORMS_FOLDER_PATH);
        foreach($forms as $form) {
            if (!is_dir($installer->getInstallPath($package).'/'.self::VERTICAL_FORMS_FOLDER_PATH . $form) || $form === '.' || $form === '..')continue;
            FormhandlerActions::createLink($installer, $installer->getInstallPath($package).'/'.self::VERTICAL_FORMS_FOLDER_PATH.$form, $form);
            FormhandlerActions::copyCouchDbJson($installer, $form);
        }

    }


    /**
     *
     * @param \Clinikal\ComposerInstallersClinikalExtender\Installer $installer
     * @param PackageInterface $package
     */
    static function createMenuLink(Installer $installer, PackageInterface $package)
    {
        $menus = glob($installer->getInstallPath($package).'/'.self::VERTICAL_MENUS_FOLDER_PATH.'*.json');
        foreach ($menus as $menu ) {
            $menuName = pathinfo($menu, PATHINFO_BASENAME);
            if (!is_link($installer->basePath.self::OPENEMR_MENUS_PATH.$menuName)) {
                echo $menu;
                symlink($menu ,$installer->basePath.self::OPENEMR_MENUS_PATH.$menuName);
                Installer::messageToCLI("Create link to $menuName from menus folder");
            }
        }

    }






}
