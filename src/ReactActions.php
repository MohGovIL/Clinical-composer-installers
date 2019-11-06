<?php


namespace Clinikal\ComposerInstallersClinikalExtender;

use Clinikal\ComposerInstallersClinikalExtender\Installer;
use Composer\Package\PackageInterface;


class ReactActions
{
    const SOURCE_REACT = 'clinikal-react/';
    const DEST_REACT = '../clinikal-react/';

    static function createReactLInk(Installer $installer, PackageInterface $package)
    {
        if (!is_dir($installer->getInstallPath($package).'/' . self::SOURCE_REACT)) return;

       // $baseTarget = Installer::getRelativePathBetween($installer->basePath.self:: DEST_REACT, $installer->basePath);
        if (!is_link($installer->basePath.self::DEST_REACT)) {
            symlink($installer->getRelativePath($package).'/'.self::SOURCE_REACT ,$installer->basePath.self::DEST_REACT);
        }
    }
}