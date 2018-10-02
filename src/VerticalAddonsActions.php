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
    const OPENEMR_CSS_FILENAME = 'clinikal_fixes.scss';
    const CSS_ORIGIN_NAME='clinikal_fixes.scss';
    const ZERO_OPENEMR_CSS_FILENAME = 'clinikal_zero_fixes.scss';
    const ZERO_CSS_ORIGIN_NAME='clinikal_zero_fixes.scss';

    const OPENEMR_MENUS_PATH = 'sites/default/documents/custom_menus/';
    const VERTICAL_MENUS_FOLDER_PATH='menus/';

    const VERTICAL_FORMS_FOLDER_PATH='forms/';
    const VERTICAL_MODULES_FOLDER_PATH='modules/';

    const VERTICAL_CRONJOB_FILE='cron/vertical_cron_jobs';
    const CLINIKAL_CRONJOB_FILE='install/cron_jobs/clinikal_cron';
    const CLINIKAL_CRONJOB_LOG='install/cron_jobs/cron_jobs_log';
    const VERTICAL_MODULES_DOCUMENTS_PATH='doctemplates/';
    const OPENEMR_DOCUMENTS_PATH = 'sites/default/documents/doctemplates/';



    /**
     * Create link from theme's folder in the project to the new style of the vertical
     * @param \Clinikal\ComposerInstallersClinikalExtender\Installer $installer
     * @param PackageInterface $package
     */
    static function createCssLink(Installer $installer, PackageInterface $package)
    {
        if (!is_link($installer->basePath.self::OPENEMR_CSS_PATH.self::OPENEMR_CSS_FILENAME)) {
            symlink($installer->getInstallPath($package).'/'.self::VERTICAL_CSS_FOLDER_PATH.self::CSS_ORIGIN_NAME ,$installer->basePath.self::OPENEMR_CSS_PATH.self::OPENEMR_CSS_FILENAME);
            symlink($installer->getInstallPath($package).'/'.self::VERTICAL_CSS_FOLDER_PATH.self::ZERO_CSS_ORIGIN_NAME ,$installer->basePath.self::OPENEMR_CSS_PATH.self::ZERO_OPENEMR_CSS_FILENAME);
        }

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
    /**
     * Create links in openemr's doctemplates folder for every vertical's document.
     * @param \Clinikal\ComposerInstallersClinikalExtender\Installer $installer
     * @param PackageInterface $package
     */
    static function createDocumentsLinks(Installer $installer, PackageInterface $package)
    {
        $documents = glob($installer->getInstallPath($package).'/'.self::VERTICAL_MODULES_DOCUMENTS_PATH.'*.docx');
        foreach($documents as $document) {
            $documentName = pathinfo($document, PATHINFO_BASENAME);

            if (!is_link($installer->basePath.self::OPENEMR_DOCUMENTS_PATH.$documentName)) {
                echo $documentName;
                symlink($document ,$installer->basePath.self::OPENEMR_DOCUMENTS_PATH.$documentName);
                $installer->appendToGitignore(self::OPENEMR_DOCUMENTS_PATH.$documentName);
                Installer::messageToCLI("Create link to $documentName from menus folder");
            }
        }

    }

    static function appendCronJobs(Installer $installer, PackageInterface $package)
    {
        //load exist jobs into array
        if(empty($installer->installName) || !is_file($installer->clinikalPath.self::CLINIKAL_CRONJOB_FILE)) return;
        $existJobs = file($installer->clinikalPath.self::CLINIKAL_CRONJOB_FILE, FILE_SKIP_EMPTY_LINES);

        foreach ($existJobs as $key => $job)
        {   // clean comment lines
            //remove \n
            $job = trim($job);
            if(strpos($job, '#') === 0 || empty($job))unset($existJobs[$key]);
        }
        $existJobs = !empty($existJobs) ? array_values($existJobs) : array();

        //load vertical jobs into array
        if(!is_file($installer->getInstallPath($package).'/' . self::VERTICAL_CRONJOB_FILE)) return;
        $verticalJobs = file($installer->getInstallPath($package).'/' . self::VERTICAL_CRONJOB_FILE, FILE_SKIP_EMPTY_LINES);

        //remove \n
        $ubuntuUser = trim(shell_exec('whoami'));

        foreach ($verticalJobs as $key => $job)
        {    // clean comment lines
            $job = trim($job);
            if(strpos($job, '#') === 0 || empty($job))continue;
            //append job if not exist

            if(strpos($job, '<INSTALLATION_URL>') !== false){
                $job = str_replace('<INSTALLATION_URL>', $installer->installName, $job);
            }

            if(strpos($job, '<UBUNTU_USER>') !== false){
                $job = str_replace('<UBUNTU_USER>', $ubuntuUser, $job);
            }

            if(strpos($job, '<ZF2_INDEX_PHP>') !== false){
                $job = str_replace('<ZF2_INDEX_PHP>', $installer->basePath.'interface/modules/zend_modules/public/index.php', $job);
            }

            $job = $job . ' >> ' . $installer->clinikalPath.self::CLINIKAL_CRONJOB_LOG;

            if (!in_array($job, $existJobs)){

                file_put_contents($installer->clinikalPath.self::CLINIKAL_CRONJOB_FILE, PHP_EOL . $job, FILE_APPEND);
            }
        }
    }
}
