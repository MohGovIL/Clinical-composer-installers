<?php
/**
 * Created by PhpStorm.
 * User: amiel
 * Date: 11/12/17
 * Time: 15:15
 */

namespace Clinikal\ComposerInstallersClinikalExtender;

use Composer\Installer\LibraryInstaller;
use Composer\Installers\Installer as ComposerInstaller;
use OomphInc\ComposerInstallersExtender\Installer as ExtenderInstaller;
use Composer\Package\PackageInterface;
use Composer\Repository\InstalledRepositoryInterface ;
use Clinikal\ComposerInstallersClinikalExtender\FormhandlerActions;

/**
 * Class Installer
 * This class extends a functionality of the install and update commands of composer
 * @package Clinikal\ComposerInstallersClinikalExtender
 */
class Installer extends ExtenderInstaller
{
    const VERTICAL_ADDONS = 'clinikal-vertical';
    const ZF_MODULES = 'openemr-zf-modules';
    const FORMHANDLER_FORMS = 'openemr-formhandler-forms';
    const RED="\033[31m";
    const NC="\033[0m";
    const CYAN="\033[36m";

    public $basePath;
    public $clinikalPath;
    public $isDevEnv;
    public $isZero;


    /**
     * init private properties and objects for clinikal project
     */
    private function initClinikal()
    {
        $this->basePath = dirname($this->vendorDir) .'/';
        $this->clinikalPath = $this->basePath . 'clinikal/';
        //require functions for db connection form 'clinikal' folder
        require $this->clinikalPath . 'scripts/dbConnect.php';
        require $this->clinikalPath . 'install/upgrade/functions/clinikal_sql_upgrade_fx.php';

        $this->setEnvSettings();
        // acl environment
        if ($this->isZero || $this->isDevEnv) {
            //for connection with ssl
            $GLOBALS['debug_ssl_mysql_connection'] = false;
            require $this->basePath . 'library/acl.inc';
            if (isset ($phpgacl_location)) {
                include_once("$phpgacl_location/gacl_api.class.php");
            }
            require $this->clinikalPath . 'install/upgrade/functions/acl_upgrade_fx_clinikal.php';
            require $this->clinikalPath . 'install/upgrade/functions/Roles_ids.php';
        }

    }
    
    /**
     * {@inheritDoc}
     */
    public function install(InstalledRepositoryInterface $repo, PackageInterface $package)
    {   
        $this->initClinikal();
        //composer install
        LibraryInstaller::install($repo,$package);
        $this->appendToGitignore($this->getInstallPath($package));

        //spacial actions per package type
        switch ($package->getType())
        {
            case self::FORMHANDLER_FORMS:
                FormhandlerActions::copyCouchDbJson($this, $package);
                FormhandlerActions::installTable($this, $this->getInstallPath($package));
                break;
        }

        //run sql queries for installation
        self::messageToCLI("Running sql queries for installation");
        upgradeFromSqlFile($this->basePath.$this->getInstallPath($package).'/sql/install.sql');


        // acl environment
        if ($this->isZero || $this->isDevEnv) {
            self::messageToCLI("Installing acl settings");
            require $this->basePath.$this->getInstallPath($package).'/acl/acl_install.php';
        }
    }


    /**
     * {@inheritDoc}
     */
    public function update(InstalledRepositoryInterface $repo, PackageInterface $initial, PackageInterface $target)
    {
        $this->initClinikal();

        $lastTag = $initial->isDev() ? $this->getLastTag($this->basePath.$this->getInstallPath($initial)) : $initial->getPrettyVersion();
        $lastTag = substr($lastTag,1,strlen($lastTag));

        // composer update
        LibraryInstaller::update($repo,$initial, $target);

        //spacial actions per package type
        switch ($target->getType())
        {
            case self::FORMHANDLER_FORMS:
                FormhandlerActions::copyCouchDbJson($this, $target);
                break;
        }
        echo $initial->version;
        echo $target->version;
        #acl upgrade
        $sqlFolder = $this->basePath.$this->getInstallPath($target).'/sql';
        $filesList = $this->getUpgradeFilesList($sqlFolder);

        /*$form_old_openemr_version = !empty($openemrLastVersion) ? $openemrLastVersion : '5.0.0';

        foreach ($filesList as $version => $filename) {
            //   print_r($form_old_version);
            if (strcmp($version, $form_old_openemr_version) < 0) continue;
            upgradeFromSqlFile($sqlFolder .'/'.$filename);
        }*/


        /*print_r($target->getName());
        echo '\n';
        print_r($target->getPrettyName());
        echo '\n';
        print_r($target->getId());
        echo '\n';
        print_r($target->getTargetDir());
        echo $target;
        print_r($this->vendorDir);
        print_r($initial->getType());
        print_r($this->getInstallPath($initial));*/

    }

    private function appendToGitignore($ignoreFile)
    {
        file_put_contents($this->basePath.'.gitignore', PHP_EOL . $ignoreFile, FILE_APPEND);
        self::messageToCLI('Adding to .gitignore - ' . $ignoreFile);
    }

    private function getFolderName($fullName)
    {
        list($prefixName, $folderName) = explode('/',$fullName);
        return !is_null($folderName) ? $folderName : $prefixName;
    }

    private function setEnvSettings()
    {
        $sql = "SELECT gl_name, gl_value FROM globals WHERE gl_name IN('clinikal_env', 'zero_installation_type')";
        $stmt = \DBconnect::getConnection()->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $this->isZero = false;
        foreach ($results as $result)
            switch ($result['gl_name']) {
                case 'clinikal_env':
                    $this->isDevEnv = ($result['gl_value'] && $result['gl_value'] == 'development') ? true : false;
                    break;
                case 'zero_installation_type':
                    $this->isZero = ($result['gl_value'] && !empty($result['gl_value'])) ? true : false;
                    break;
            }

    }

    private function getUpgradeFilesList($upgradeFolder)
    {
        $dh = opendir($upgradeFolder);
        $versions = array();
        while (false !== ($sfname = readdir($dh))) {
            if (substr($sfname, 0, 1) == '.') continue;
            if (preg_match('/^(\d+)_(\d+)_(\d+)-to-\d+_\d+_\d+_upgrade.sql$/', $sfname, $matches)) {
                $version = $matches[1] . '.' . $matches[2] . '.' . $matches[3];
                $versions[$version] = $sfname;
            }
        }
        closedir($dh);
        ksort($versions);

        return $versions;
    }

    private function getLastTag($pathToPackage)
    {
        $tag =  shell_exec("cd $pathToPackage && git describe --tags --abbrev=0");
        return is_null($tag) ? 'v0.1.0' : $tag;
    }

    static function messageToCLI($message)
    {
        fwrite(STDOUT," *" .self::CYAN . $message . self::NC . PHP_EOL);
    }


}