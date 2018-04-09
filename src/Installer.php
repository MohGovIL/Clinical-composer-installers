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
use Composer\Package\PackageInterface;
use Composer\Repository\InstalledRepositoryInterface ;
use Clinikal\ComposerInstallersClinikalExtender\FormhandlerActions;
use Clinikal\ComposerInstallersClinikalExtender\VerticalAddonsActions;
use Clinikal\ComposerInstallersClinikalExtender\Zf2ModulesActions;

/**
 * Class Installer
 * This class extends a functionality of the install and update commands of composer
 * @package Clinikal\ComposerInstallersClinikalExtender
 */
class Installer extends ComposerInstaller
{
    /* custom packages's types */
    const VERTICAL_PACKAGE = 'clinikal-vertical';
    const ZF_MODULES = 'clinikal-zf-modules';
    const FORMHANDLER_FORMS = 'clinikal-formhandler-forms';

    const RED="\033[31m";
    const NC="\033[0m";
    const CYAN="\033[36m";

    public $basePath;
    public $clinikalPath;
    public $clinikalEnv;
    public $isZero;
    private $isInit = false;

    protected $packageTypes;


    /**
     * init private properties and objects for clinikal project
     */
    private function initClinikal()
    {
        if($this->isInit)return;

        $this->basePath = dirname($this->vendorDir) .'/';
        $this->clinikalPath = $this->basePath . 'clinikal/';
        //require functions for db connection form 'clinikal' folder
        require $this->clinikalPath . 'scripts/dbConnect.php';
        require $this->clinikalPath . 'install/upgrade/functions/clinikal_sql_upgrade_fx.php';

        $this->setEnvSettings();

        // acl environment
        if ($this->isZero || $this->clinikalEnv === 'dev') {
            //for connection with ssl
            $GLOBALS['debug_ssl_mysql_connection'] = false;
            require $this->basePath . 'library/acl.inc';
            if (isset ($phpgacl_location)) {
                include_once("$phpgacl_location/gacl_api.class.php");
            }
            require $this->clinikalPath . 'install/upgrade/functions/acl_upgrade_fx_clinikal.php';
            require $this->clinikalPath . 'install/upgrade/functions/Roles_ids.php';
        }

        $this->isInit = true;
    }

    /**
     * {@inheritDoc}
     */
    public function install(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        $this->initClinikal();
        //composer install
        LibraryInstaller::install($repo,$package);

        if($this->getPrefix($package->getType()) !== 'clinikal') return;

        //spacial actions per package type
        switch ($package->getType())
        {
            case self::FORMHANDLER_FORMS:
                FormhandlerActions::createLink($this, $this->getInstallPath($package), explode('/',$package->getName())[1]);
                FormhandlerActions::copyCouchDbJson($this, explode('/',$package->getName())[1]);
                FormhandlerActions::installTable($this, $this->getInstallPath($package));
                break;
            case self::ZF_MODULES:
                Zf2ModulesActions::createLink($this, $this->getInstallPath($package), explode('/',$package->getName())[1]);
                break;
            case self::VERTICAL_PACKAGE;
                # install zf2 modules
                VerticalAddonsActions::installUpdateModules($this,$package);
                # install forms
                VerticalAddonsActions::installUpdateForms($this,$package);
                # link to css file
                VerticalAddonsActions::createCssLink($this,$package);
                $this->appendToGitignore(VerticalAddonsActions::OPENEMR_CSS_PATH.VerticalAddonsActions::OPENEMR_CSS_FILENAME);
                $this->appendToGitignore(VerticalAddonsActions::OPENEMR_CSS_PATH.'rtl_'.VerticalAddonsActions::OPENEMR_CSS_FILENAME);
                # link to json of vertical menu
                VerticalAddonsActions::createMenuLink($this,$package);
                break;

        }

       // $projectPath = strpos($this->getInstallPath($package), $this->basePath) !== false ? str_replace($this->basePath,'', $this->getInstallPath($package)) : $this->getInstallPath($package);
        $projectPath = $this->getInstallPath($package);

        if ( $this->clinikalEnv != 'prod') {
            $this->appendToGitignore($this->getRelativePath($package));
        } else {

            if (is_dir($projectPath ."/.git")){
                self::messageToCLI("Removing .git from packages");
               // $this->deleteDotGitFolder( $this->basePath.$projectPath ."/.git");
            }
            // remove .git from production version
            // put .git in the ignore
            $this->appendToGitignore($projectPath.'/.git');
            //adding all the package to special repository for production installation.
            //shell_exec("git add $projectPath");
        }

        //run sql queries for installation
        self::messageToCLI("Running sql queries for installation for package - " .$package->getPrettyName());
        upgradeFromSqlFile($projectPath.'/sql/install.sql');

        // acl environment
        if ($this->isZero || $this->clinikalEnv == 'dev') {
            self::messageToCLI("Installing acl for package - " .$package->getPrettyName());
            require $projectPath.'/acl/acl_install.php';
        }

        self::messageToCLI('----- INSTALL ' . strtoupper($package->getPrettyName()) . ' WAS FINISHED ------' . PHP_EOL);
    }


    /**
     * {@inheritDoc}
     */
    public function update(InstalledRepositoryInterface $repo, PackageInterface $initial, PackageInterface $target)
    {

        $this->initClinikal();


        // composer update
        LibraryInstaller::update($repo,$initial, $target);

        if($this->getPrefix($initial->getType()) !== 'clinikal') return;

        //$projectPath = strpos($this->getInstallPath($target), $this->basePath) !== false ? str_replace($this->basePath,'', $this->getInstallPath($target)) : $this->getInstallPath($target);
        $projectPath = $this->getInstallPath($target);

        //get a last version of the package before update, the upgrade sql/acl will begin from this point (for dev from git and for prod a version from composer json)
        $lastTag = $initial->isDev() ? $this->getLastTag($projectPath) : $initial->getPrettyVersion();
        $lastTag = substr($lastTag,1,strlen($lastTag));
        echo $target->getType();
        //spacial actions per package type
        switch ($target->getType())
        {
            case self::FORMHANDLER_FORMS:
                FormhandlerActions::copyCouchDbJson($this, $target);
                break;
            case self::VERTICAL_PACKAGE;
                # install zf2 modules
                VerticalAddonsActions::installUpdateModules($this,$target);
                # install forms
                VerticalAddonsActions::installUpdateForms($this,$target);
                # link to json of vertical menu
                VerticalAddonsActions::createMenuLink($this,$target);
                break;
        }

        #sql upgrade
        self::messageToCLI('Upgrading sql for package - ' .$target->getPrettyName() .' from version ' . $lastTag . '.');
        $sqlFolder = $projectPath.'/sql';
        $filesList = $this->getUpgradeFilesList($sqlFolder);

        foreach ($filesList as $version => $filename) {
            //   print_r($form_old_version);
            if (strcmp($version, $lastTag) < 0) continue;
            upgradeFromSqlFile($sqlFolder .'/'.$filename);
        }

        // acl environment
        if ($this->isZero || $this->clinikalEnv === 'dev') {
            self::messageToCLI('Upgrading acl for package - ' .$target->getPrettyName() .' from version ' . $lastTag . '.');
            $ACL_UPGRADE = require $projectPath.'/acl/acl_upgrade.php';
            foreach ($ACL_UPGRADE as $version => $function){
                if (strcmp($version, $lastTag) < 0) continue;
                    $function();
            }
        }

        self::messageToCLI('----- UPDATE ' . strtoupper($target->getName()) . ' WAS FINISHED ------' . PHP_EOL);

    }

    /**
     * add a package to .gitignore .
     * @param $ignoreFile
     */
    public function appendToGitignore($ignoreFile)
    {
        file_put_contents($this->basePath.'.gitignore', PHP_EOL . $ignoreFile, FILE_APPEND);
        self::messageToCLI('Adding to .gitignore - ' . $ignoreFile);
    }


    /**
     * get from DB a globals of environment settings, and set those to private properties.
     */
    private function setEnvSettings()
    {
        $sql = "SELECT gl_name, gl_value FROM globals WHERE gl_name IN('clinikal_env', 'zero_installation_type')";
        $stmt = \DBconnect::getConnection()->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $this->isZero = false;
        foreach ($results as $result)
            switch ($result['gl_name']) {
                case 'zero_installation_type':
                    $this->isZero = ($result['gl_value'] && !empty($result['gl_value'])) ? true : false;
                    break;
            }

        $this->clinikalEnv = $this->composer->getConfig()->get('clinikal-env');

    }

    /**
     * get list of sql upgrade files
     * @param $upgradeFolder
     * @return array
     */
    private function getUpgradeFilesList($upgradeFolder)
    {
        $dh = opendir($upgradeFolder);
        $versions = array();
        while (false !== ($sfname = readdir($dh))) {
            if (substr($sfname, 0, 1) == '.') continue;
            if (preg_match('/^\d+_\d+_\d+-to-(\d+)_(\d+)_(\d+)_upgrade.sql$/', $sfname, $matches)) {
                $version = $matches[1] . '.' . $matches[2] . '.' . $matches[3];
                $versions[$version] = $sfname;
            }
        }
        closedir($dh);
        ksort($versions);

        return $versions;
    }

    /**
     *
     * @param $pathToPackage
     * @return string
     */
    private function getLastTag($pathToPackage)
    {
        $tag =  shell_exec("cd $pathToPackage && git describe --tags --abbrev=0");
        return is_null($tag) ? 'v0.1.0' : $tag;
    }

    private function getPrefix($packageType)
    {
        return explode('-',$packageType)[0];
    }



    /*
     * php delete function that deals with directories recursively
     */
    private function deleteDotGitFolder($target) {
        if(is_dir($target)){
            $files = glob( $target . '*', GLOB_MARK ); //GLOB_MARK adds a slash to directories returned

            foreach( $files as $file )
            {
                $this->deleteDotGitFolder( $file );
            }

            rmdir( $target );
        } elseif(is_file($target)) {
            unlink( $target );
        }
    }

    /**
     * @param $message
     */
    static function messageToCLI($message)
    {
        fwrite(STDOUT,"*" .self::CYAN . $message . self::NC . PHP_EOL);
    }


    public function getRelativePath(PackageInterface $package)
    {
        $path = $this->getInstallPath($package);
        return str_replace($this->basePath,'', $path);
    }


    /* functions from oomphinc extender - https://github.com/oomphinc/composer-installers-extender */

    public function getInstallPath( PackageInterface $package ) {
        $installer = new InstallerHelper( $package, $this->composer, $this->io );
        $path = $installer->getInstallPath( $package, $package->getType() );
        // if the path is false, use the default installer path instead
        return $path !== false ? $this->basePath.$path : LibraryInstaller::getInstallPath( $package );
    }

    public function supports( $packageType ) {
        // grab the package types once
        if ( !isset( $this->packageTypes ) ) {
            $this->packageTypes = false;
            if ( $this->composer->getPackage() ) {
                // get data from the 'extra' field
                $extra = $this->composer->getPackage()->getExtra();
                if ( !empty( $extra['installer-types'] ) ) {
                    $this->packageTypes = (array) $extra['installer-types'];
                }
            }
        }
        return is_array( $this->packageTypes ) && in_array( $packageType, $this->packageTypes );
    }

    /* end oomphinc extender */

}
