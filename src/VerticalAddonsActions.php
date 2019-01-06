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
    const PATIENT_MENUS_FOLDER = 'patient_menus/';

    const VERTICAL_FORMS_FOLDER_PATH='forms/';
    const VERTICAL_MODULES_FOLDER_PATH='modules/';
    const VERTICAL_SQL_FOLDER_PATH='sql/';
    const VERTICAL_SQL_ZERO_FOLDER_PATH='sql/zero/';
    const VERTICAL_ACL_FOLDER_PATH='acl/';
    const VERTICAL_CRONJOB_FILE='cron/vertical_cron_jobs';
    const VERTICAL_MODULES_DOCUMENTS_PATH='doctemplates/';

    const CLINIKAL_SQL_INSTALL_FILE='install/sql/verticalAddons.sql';
    const CLINIKAL_SQL_UPGRADE_FOLDER='install/upgrade/vertical/sql/';
    const CLINIKAL_SQL_ZERO_UPGRADE_FOLDER='install/upgrade/vertical/zero_sql/';
    const CLINIKAL_ACL_INSTALL_FILE='install/acl/acl_vertical_addons.php';
    const CLINIKAL_ACL_UPGRADE_FILE='install/upgrade/vertical/acl/acl_upgrade_clinikal.php';
    const CLINIKAL_ACL_ROLES_FILE='install/upgrade/vertical/acl/Roles_ids.php';
    const CLINIKAL_CRONJOB_FILE='install/cron_jobs/clinikal_cron';
    const CLINIKAL_CRONJOB_LOG='logs/cron_jobs_log';
    const OPENEMR_DOCUMENTS_PATH = 'sites/default/documents/doctemplates/';


    /**
     * Create link from theme's folder in the project to the new style of the vertical
     * @param \Clinikal\ComposerInstallersClinikalExtender\Installer $installer
     * @param PackageInterface $package
     */
    static function createCssLink(Installer $installer, PackageInterface $package)
    {
        $baseTarget = Installer::getRelativePathBetween($installer->basePath.self::OPENEMR_CSS_PATH.self::OPENEMR_CSS_FILENAME, $installer->basePath);
        if (!is_link($installer->basePath.self::OPENEMR_CSS_PATH.self::OPENEMR_CSS_FILENAME)) {
            symlink($baseTarget.$installer->getRelativePath($package).'/'.self::VERTICAL_CSS_FOLDER_PATH.self::CSS_ORIGIN_NAME ,$installer->basePath.self::OPENEMR_CSS_PATH.self::OPENEMR_CSS_FILENAME);
            $installer->appendToGitignore(self::OPENEMR_CSS_FILENAME, self::OPENEMR_CSS_PATH);
            Installer::messageToCLI("Create links to css files of the vertical");
        }
        if (!is_link($installer->basePath.self::OPENEMR_CSS_PATH.self::ZERO_OPENEMR_CSS_FILENAME)) {
            symlink($baseTarget.$installer->getRelativePath($package).'/'.self::VERTICAL_CSS_FOLDER_PATH.self::ZERO_CSS_ORIGIN_NAME ,$installer->basePath.self::OPENEMR_CSS_PATH.self::ZERO_OPENEMR_CSS_FILENAME);
            $installer->appendToGitignore(self::ZERO_OPENEMR_CSS_FILENAME, self::OPENEMR_CSS_PATH);
            Installer::messageToCLI("Create links to css files of the zero vertical");
        }
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
            Zf2ModulesActions::createLink($installer, $installer->getRelativePath($package).'/'.self::VERTICAL_MODULES_FOLDER_PATH.$module, $module);
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
            FormhandlerActions::createLink($installer, $installer->getRelativePath($package).'/'.self::VERTICAL_FORMS_FOLDER_PATH.$form, $form);
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
        /*main menus*/
        $baseTarget = Installer::getRelativePathBetween($installer->basePath.self::OPENEMR_MENUS_PATH, $installer->basePath);
        $menus = glob($installer->getInstallPath($package).'/'.self::VERTICAL_MENUS_FOLDER_PATH.'*.json');
        foreach ($menus as $menu ) {
            $menuName = pathinfo($menu, PATHINFO_BASENAME);
            if (!is_link($installer->basePath.self::OPENEMR_MENUS_PATH.$menuName)) {
                symlink($baseTarget.$installer->getRelativePath($package).'/'.self::VERTICAL_MENUS_FOLDER_PATH.$menuName ,$installer->basePath.self::OPENEMR_MENUS_PATH.$menuName);
                $installer->appendToGitignore($menuName, self::OPENEMR_MENUS_PATH);
                Installer::messageToCLI("Create link to $menuName from menus folder");
            }
        }
        /*patient menus*/
        $baseTarget = Installer::getRelativePathBetween($installer->basePath.self::OPENEMR_MENUS_PATH . self::PATIENT_MENUS_FOLDER, $installer->basePath);
        $menus = glob($installer->getInstallPath($package).'/'.self::VERTICAL_MENUS_FOLDER_PATH.self::PATIENT_MENUS_FOLDER.'*.json');
        foreach ($menus as $menu ) {
            $menuName = pathinfo($menu, PATHINFO_BASENAME);

            if (!is_link($installer->basePath.self::OPENEMR_MENUS_PATH.self::PATIENT_MENUS_FOLDER.$menuName)) {
                symlink($baseTarget.$installer->getRelativePath($package).'/'.self::VERTICAL_MENUS_FOLDER_PATH.self::PATIENT_MENUS_FOLDER.$menuName ,$installer->basePath.self::OPENEMR_MENUS_PATH.self::PATIENT_MENUS_FOLDER.$menuName);
                $installer->appendToGitignore($menuName, self::OPENEMR_MENUS_PATH);
                Installer::messageToCLI("Create link to $menuName from patients menus folder");
            }
        }

    }
    /**
     * @deprecated - doc templates not in the git
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
                $installer->appendToGitignore($documentName, self::OPENEMR_DOCUMENTS_PATH);
                Installer::messageToCLI("Create link to $documentName from menus folder");
            }
        }

    }

    /**
     * Create links for vertical for every vertical's SQL file.
     * @param \Clinikal\ComposerInstallersClinikalExtender\Installer $installer
     * @param PackageInterface $package
     */
    static function createSqlLinks(Installer $installer, PackageInterface $package)
    {
        $baseTarget = Installer::getRelativePathBetween($installer->clinikalPath.self::CLINIKAL_SQL_INSTALL_FILE, $installer->basePath);
        if (!is_link($installer->clinikalPath.self::CLINIKAL_SQL_INSTALL_FILE)) {
            symlink($baseTarget.$installer->getRelativePath($package).'/'.self::VERTICAL_SQL_FOLDER_PATH.'install.sql' ,$installer->clinikalPath.self::CLINIKAL_SQL_INSTALL_FILE);
            $installer->appendToGitignore(self::CLINIKAL_SQL_INSTALL_FILE, 'clinikal/');
        }

        $baseTarget = Installer::getRelativePathBetween($installer->clinikalPath.self::CLINIKAL_SQL_UPGRADE_FOLDER, $installer->basePath);
        $sqlFiles = glob($installer->getInstallPath($package).'/'.self::VERTICAL_SQL_FOLDER_PATH.'*.sql');
        foreach($sqlFiles as $sqlFile) {
            $fileName = pathinfo($sqlFile, PATHINFO_BASENAME);
            if ($fileName === 'install.sql')continue;
            if (!is_link($installer->clinikalPath.self::CLINIKAL_SQL_UPGRADE_FOLDER.$fileName)) {
                symlink($baseTarget.$installer->getRelativePath($package).'/'.self::VERTICAL_SQL_FOLDER_PATH.$fileName ,$installer->clinikalPath.self::CLINIKAL_SQL_UPGRADE_FOLDER.$fileName);
                $installer->appendToGitignore(self::CLINIKAL_SQL_UPGRADE_FOLDER.$fileName, 'clinikal/');
                Installer::messageToCLI("Create link to $fileName from sql folder");
            }
        }

        $baseTarget = Installer::getRelativePathBetween($installer->clinikalPath.self::CLINIKAL_SQL_ZERO_UPGRADE_FOLDER, $installer->basePath);
        $sqlZeroFiles = glob($installer->getInstallPath($package).'/'.self::VERTICAL_SQL_ZERO_FOLDER_PATH.'*.sql');
        foreach($sqlZeroFiles as $sqlFile) {
            $fileName = pathinfo($sqlFile, PATHINFO_BASENAME);
            if (!is_link($installer->clinikalPath.self::CLINIKAL_SQL_ZERO_UPGRADE_FOLDER.$fileName)) {
                symlink($baseTarget.$installer->getRelativePath($package).'/'.self::VERTICAL_SQL_ZERO_FOLDER_PATH.$fileName  ,$installer->clinikalPath.self::CLINIKAL_SQL_ZERO_UPGRADE_FOLDER.$fileName);
                $installer->appendToGitignore(self::CLINIKAL_SQL_ZERO_UPGRADE_FOLDER.$fileName, 'clinikal/');
                Installer::messageToCLI("Create link to $fileName from zero sql folder");
            }
        }

    }

    /**
     * Create links for vertical for every vertical's SQL file.
     * @param \Clinikal\ComposerInstallersClinikalExtender\Installer $installer
     * @param PackageInterface $package
     */
    static function createAclLinks(Installer $installer, PackageInterface $package)
    {
        $baseTarget = Installer::getRelativePathBetween($installer->clinikalPath.self::CLINIKAL_ACL_INSTALL_FILE, $installer->basePath);
        if (!is_link($installer->clinikalPath . self::CLINIKAL_ACL_INSTALL_FILE)) {
            symlink($baseTarget.$installer->getRelativePath($package). '/' . self::VERTICAL_ACL_FOLDER_PATH . 'acl_install.php', $installer->clinikalPath . self::CLINIKAL_ACL_INSTALL_FILE);
            $installer->appendToGitignore(self::CLINIKAL_ACL_INSTALL_FILE, 'clinikal/');
            Installer::messageToCLI("Create link " . self::CLINIKAL_ACL_INSTALL_FILE);
        }

        $baseTarget = Installer::getRelativePathBetween($installer->clinikalPath.self::CLINIKAL_ACL_UPGRADE_FILE, $installer->basePath);
        if (!is_link($installer->clinikalPath . self::CLINIKAL_ACL_UPGRADE_FILE)) {
            symlink($baseTarget.$installer->getRelativePath($package) . '/' . self::VERTICAL_ACL_FOLDER_PATH . 'acl_upgrade.php', $installer->clinikalPath . self::CLINIKAL_ACL_UPGRADE_FILE);
            $installer->appendToGitignore(self::CLINIKAL_ACL_UPGRADE_FILE, 'clinikal/');
            Installer::messageToCLI("Create link " . self::CLINIKAL_ACL_UPGRADE_FILE);
        }

        $baseTarget = Installer::getRelativePathBetween($installer->clinikalPath.self::CLINIKAL_ACL_ROLES_FILE, $installer->basePath);
        if (!is_link($installer->clinikalPath . self::CLINIKAL_ACL_ROLES_FILE)) {
            symlink($baseTarget.$installer->getRelativePath($package) . '/' . self::VERTICAL_ACL_FOLDER_PATH . 'Roles_ids.php', $installer->clinikalPath . self::CLINIKAL_ACL_ROLES_FILE);
            $installer->appendToGitignore(self::CLINIKAL_ACL_ROLES_FILE, 'clinikal/');
            Installer::messageToCLI("Create link " . self::CLINIKAL_ACL_ROLES_FILE);
        }
    }

    static function appendCronJobs(Installer $installer, PackageInterface $package)
    {
        if (!is_file($installer->clinikalPath.self::CLINIKAL_CRONJOB_FILE)) {
            touch($installer->clinikalPath.self::CLINIKAL_CRONJOB_FILE);
        }
        if (!is_file($installer->clinikalPath.self::CLINIKAL_CRONJOB_LOG)) {
            touch($installer->clinikalPath.self::CLINIKAL_CRONJOB_LOG);
        }
        //load exist jobs into array
        if(empty($installer->installName))return;
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

            //create logs file if missing
            if (!is_file($installer->clinikalPath.self::CLINIKAL_CRONJOB_LOG)) {
                touch($installer->clinikalPath.self::CLINIKAL_CRONJOB_LOG);
                chmod($installer->clinikalPath.self::CLINIKAL_CRONJOB_LOG, 0766);
                $installer->appendToGitignore('clinikal/'.self::CLINIKAL_CRONJOB_LOG);
            }
        }
    }
}
