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

/**
 * Class Installer
 * This class extends a functionality of the install and update command of composer
 * @package Clinikal\ComposerInstallersClinikalExtender
 */
class Installer extends ExtenderInstaller
{

    /**
     * {@inheritDoc}
     */
    public function install(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        LibraryInstaller::install($repo,$package);

    }


    /**
     * {@inheritDoc}
     */
    public function update(InstalledRepositoryInterface $repo, PackageInterface $initial, PackageInterface $target)
    {
        LibraryInstaller::update($repo,$initial, $target);

        if ($target->getType() === 'openemr-formhandler-forms') {
            copy($this->vendorDir .'/../' .$this->getInstallPath($target).'/'. explode('/',$target->getName())[1].'.json', $this->vendorDir .'/../clinikal/install/couchDB/forms/backup_data/'. explode('/',$target->getName())[1].'.json');
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


}