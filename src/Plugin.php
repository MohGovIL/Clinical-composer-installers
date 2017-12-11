<?php
/**
 * Created by PhpStorm.
 * User: amiel
 * Date: 11/12/17
 * Time: 15:51
 */

namespace Clinikal\ComposerInstallersClinikalExtender;


use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

class Plugin implements PluginInterface {
    public function activate( Composer $composer, IOInterface $io ) {
        $installer = new Installer( $io, $composer );
        $composer->getInstallationManager()->addInstaller( $installer );
    }
}