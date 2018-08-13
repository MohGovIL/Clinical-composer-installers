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
    const VERTICAL_CSS_FOLDER_PATH='css/';
    const OPENEMR_CSS_FILENAME = 'style_vertical.css';
    const CSS_ORIGIN_NAME='style_clinikal.css';
    const ZERO_OPENEMR_CSS_FILENAME = 'style_zero_vertical.css';
    const ZERO_CSS_ORIGIN_NAME='style_zero_clinikal.css';

    const OPENEMR_MENUS_PATH = 'sites/default/documents/custom_menus/';
    const VERTICAL_MENUS_FOLDER_PATH='menus/';

    const VERTICAL_FORMS_FOLDER_PATH='forms/';
    const VERTICAL_MODULES_FOLDER_PATH='modules/';

    const VERTICAL_CRONJOB_FILE='cron/vertical_cron_jobs';
    const CLINIKAL_CRONJOB_FILE='install/cron_jobs/clinikal_cron';



    /**
     * Create link from theme's folder in the project to the new style of the vertical
     * @param \Clinikal\ComposerInstallersClinikalExtender\Installer $installer
     * @param PackageInterface $package
     */
    static function createCssLink(Installer $installer, PackageInterface $package)
    {
        symlink($installer->getInstallPath($package).'/'.self::VERTICAL_CSS_FOLDER_PATH.self::CSS_ORIGIN_NAME ,$installer->basePath.self::OPENEMR_CSS_PATH.self::OPENEMR_CSS_FILENAME);
        symlink($installer->getInstallPath($package).'/'.self::VERTICAL_CSS_FOLDER_PATH.'rtl_'.self::CSS_ORIGIN_NAME ,$installer->basePath.self::OPENEMR_CSS_PATH.'rtl_'.self::OPENEMR_CSS_FILENAME);
        symlink($installer->getInstallPath($package).'/'.self::VERTICAL_CSS_FOLDER_PATH.self::ZERO_CSS_ORIGIN_NAME ,$installer->basePath.self::OPENEMR_CSS_PATH.self::ZERO_OPENEMR_CSS_FILENAME);
        symlink($installer->getInstallPath($package).'/'.self::VERTICAL_CSS_FOLDER_PATH.'rtl_'.self::ZERO_CSS_ORIGIN_NAME ,$installer->basePath.self::OPENEMR_CSS_PATH.'rtl_'.self::ZERO_OPENEMR_CSS_FILENAME);

        Installer::messageToCLI("Create links to css files of the vertical");
    }


    /**
     * Create link from zend modules's folder in the project for every vertical's module.
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
     * Create link from forms's folder in the project for every vertical's form.
     * @param \Clinikal\ComposerInstallersClinikalExtender\Installer $installer
     * @param PackageInterface $package
     */
    static function installUpdateForms(Installer $installer, PackageInterface $package)
    {
        $forms = scandir($installer->getInstallPath($package).'/'.self::VERTICAL_FORMS_FOLDER_PATH);
        foreach($forms as $form) {
            if (!is_dir($installer->getInstallPath($package).'/'.self::VERTICAL_FORMS_FOLDER_PATH . $form) || $form === '.' || $form === '..')continue;
            FormhandlerActions::createLink($installer, $installer->getInstallPath($package).'/'.self::VERTICAL_FORMS_FOLDER_PATH.$form, $form);
            FormhandlerActions::linkToCouchDbJson($installer, $form);
        }

    }


    /**
     * create link from menus's folder in the project to every vertical menu
     * @param \Clinikal\ComposerInstallersClinikalExtender\Installer $installer
     * @param PackageInterface $package
     */
    static function createMenuLink(Installer $installer, PackageInterface $package)
    {
        $menus = glob($installer->getInstallPath($package).'/'.self::VERTICAL_MENUS_FOLDER_PATH.'*.json');
        foreach ($menus as $menu ) {
            $menuName = pathinfo($menu, PATHINFO_BASENAME);
            /* change for patient file menus */
            if (strpos($menuName,'patient_') === 0) {
               $splitMenuName = explode('_', $menuName);
               $menuName = 'patient_menus/' . $splitMenuName[1];
            }
            if (!is_link($installer->basePath.self::OPENEMR_MENUS_PATH.$menuName)) {
                echo $menu;
                symlink($menu ,$installer->basePath.self::OPENEMR_MENUS_PATH.$menuName);
                $installer->appendToGitignore(self::OPENEMR_MENUS_PATH.$menuName);
                Installer::messageToCLI("Create link to $menuName from menus folder");
            }
        }

    }

    static function appendCronJobs(Installer $installer, PackageInterface $package)
    {
        //load exist jobs into array
        $existJobs = file($installer->clinikalPath.self::CLINIKAL_CRONJOB_FILE, FILE_SKIP_EMPTY_LINES);
        foreach ($existJobs as $key => $job)
        {   // clean comment lines
            if(strpos($job, '#') === 1)unset($existJobs[$key]);
        }
        $existJobs = array_values($existJobs);

        //load vertical jobs into array
        $verticalJobs = $installer->getInstallPath($package).'/' . self::VERTICAL_CRONJOB_FILE;
        foreach ($verticalJobs as $key => $job)
        {    // clean comment lines
            if(strpos($job, '#') === 1)continue;
            //append job if not exist
            if (!in_array($job, $existJobs)){

                if(strpos($job, '<INSTALLATION_URL>') !== false){
                    $job = str_replace('<INSTALLATION_URL>', $installer->installName, $job);
                }

                file_put_contents($installer->clinikalPath.self::CLINIKAL_CRONJOB_FILE, PHP_EOL . $job, FILE_APPEND);
            }
        }
    }

}
