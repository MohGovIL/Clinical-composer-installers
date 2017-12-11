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

        echo $this->packageTypes;
        echo $this->getInstallPath();
    }


    /**
     * {@inheritDoc}
     */
    public function update(InstalledRepositoryInterface $repo, PackageInterface $initial, PackageInterface $target)
    {
        LibraryInstaller::update($repo,$initial, $target);

        echo $this->packageTypes;
        echo $this->getInstallPath();

    }
}