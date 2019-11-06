<?php


namespace Clinikal\ComposerInstallersClinikalExtender;


class ReactActions
{
    const SOURCE_REACT = 'react/';
    const DEST_REACT = '../react/';

    static function createReactLInk(Installer $installer, PackageInterface $package)
    {
        if(!is_dir($installer->getInstallPath($package).'/' . self::SOURCE_REACT)) return;

        $baseTarget = Installer::getRelativePathBetween($installer->basePath.self:: DEST_REACT, $installer->basePath);
        if (!is_link($installer->basePath.self::DEST_REACT)) {
            symlink($baseTarget.$installer->getRelativePath($package).'/'.self::SOURCE_REACT ,$installer->basePath.self::DEST_REACT);
        }
    }
}