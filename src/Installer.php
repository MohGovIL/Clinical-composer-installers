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


    /**
     * init private properties and object for this class
     */
    private function initClinikal()
    {
        $this->basePath = dirname($this->vendorDir) .'/';
        $this->clinikalPath = $this->basePath . 'clinikal/';
        //require functions for db connection form 'clinikal' folder
        require $this->clinikalPath . 'scripts/dbConnect.php';
        require $this->clinikalPath . 'install/upgrade/functions/clinikal_sql_upgrade_fx.php';
        require $this->clinikalPath . 'install/upgrade/functions/acl_upgrade_fx_clinikal.php';
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

    }


    /**
     * {@inheritDoc}
     */
    public function update(InstalledRepositoryInterface $repo, PackageInterface $initial, PackageInterface $target)
    {
        $this->initClinikal();
        LibraryInstaller::update($repo,$initial, $target);

        //spacial actions per package type
        switch ($target->getType())
        {
            case self::FORMHANDLER_FORMS:
                FormhandlerActions::copyCouchDbJson($this, $target);
                break;
        }

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

    static function messageToCLI($message)
    {
        fwrite(STDOUT,self::CYAN . $message . self::NC . PHP_EOL . PHP_EOL);
    }


}