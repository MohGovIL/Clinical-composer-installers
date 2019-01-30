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
    const VERTICAL_DOCUMENTS = 'clinikal-vertical-documents';


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

        $this->setEnvSettings();

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

        $this->buildLinks($package);

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

        $this->buildLinks($target);

        // change branch to track remote composer branch

        self::messageToCLI('----- UPDATE ' . strtoupper($target->getName()) . ' WAS FINISHED ------' . PHP_EOL);

    }

    private function buildLinks(PackageInterface $package) {

        //spacial actions per package type
        switch ($package->getType())
        {
            case self::FORMHANDLER_FORMS:
                FormhandlerActions::createLink($this, $this->getInstallPath($package), explode('/',$package->getName())[1]);
                FormhandlerActions::linkToCouchDbJson($this, explode('/',$package->getName())[1]);
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
                # link to json of vertical menu
                VerticalAddonsActions::createMenuLink($this,$package);
                # append cron jobs
                VerticalAddonsActions::appendCronJobs($this,$package);
                VerticalAddonsActions::createDocumentsLinks($this,$package);
                # links for sql and acl install
                VerticalAddonsActions::createSqlLinks($this,$package);
                VerticalAddonsActions::createAclLinks($this,$package);
                break;
            case self::VERTICAL_DOCUMENTS:

                break;


        }

        // $projectPath = strpos($this->getInstallPath($package), $this->basePath) !== false ? str_replace($this->basePath,'', $this->getInstallPath($package)) : $this->getInstallPath($package);
        $projectPath = $this->getInstallPath($package);

        //create links for git hooks
        if ($this->clinikalEnv == 'dev') {
            $targetDir = $this->clinikalPath . "ci/git-hooks/";
            $newReposTargetDir = $this->clinikalPath . "ci/git-hooks/new-repos/";
            $configFile = $this->clinikalPath . "config/";
            $linkDir = $projectPath . "/.git/hooks/";

            //create link to config in hooks dir
            symlink($configFile . "clinikal.cfg", $linkDir . "clinikal.cfg");

            //links for more "permissive" hooks fit for old repos
            $this->createHookLink($targetDir, $linkDir);

            //links for hooks fit for new repos (hook might overwrite a more "permissive" hook)
            $this->createHookLink($newReposTargetDir, $linkDir, true);

            shell_exec("cd $projectPath && git branch `git rev-parse --abbrev-ref HEAD` -u composer/`git rev-parse --abbrev-ref HEAD`");
        }

    }

    /**
     * add a package to .gitignore
     * @param $ignoreFile
     */
    public function appendToGitignore($file, $ignorePath = '')
    {
        $ignoreFile = $this->basePath.$ignorePath.'.gitignore';
        if (!is_file($ignorePath)) {
            touch($ignoreFile);
        }
        file_put_contents($ignoreFile, PHP_EOL . $file, FILE_APPEND);
        self::messageToCLI('Adding to .gitignore - ' . $file);
    }


    /**
     * get from DB a globals of environment settings, and set those to private properties.
     */
    private function setEnvSettings()
    {
        $this->clinikalEnv = $this->composer->getConfig()->get('clinikal-env');
        $this->installName = $this->composer->getConfig()->get('install-name');
    }


    /**
     *
     * @param $pathToPackage
     * @return string
     */
    private function getLastTag($pathToPackage)
    {
        $tag =  shell_exec("cd $pathToPackage && git describe --tags --abbrev=0");
        echo $tag;
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

    static function getRelativePathBetween($from, $to)
    {
        // some compatibility fixes for Windows paths
        $from = is_dir($from) ? rtrim($from, '\/') . '/' : $from;
        $to   = is_dir($to)   ? rtrim($to, '\/') . '/'   : $to;
        $from = str_replace('\\', '/', $from);
        $to   = str_replace('\\', '/', $to);

        $from     = explode('/', $from);
        $to       = explode('/', $to);
        $relPath  = $to;

        foreach($from as $depth => $dir) {
            // find first non-matching dir
            if($dir === $to[$depth]) {
                // ignore this directory
                array_shift($relPath);
            } else {
                // get number of remaining dirs to $from
                $remaining = count($from) - $depth;
                if($remaining > 1) {
                    // add traversals up to first matching dir
                    $padLength = (count($relPath) + $remaining - 1) * -1;
                    $relPath = array_pad($relPath, $padLength, '..');
                    break;
                } else {
                    $relPath[0] = './' . $relPath[0];
                }
            }
        }
        return implode('/', $relPath);
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


    private function createHookLink($hooksSourceDir, $hooksTargetPath, $overWrite=false) {
        $dirContents = scandir($hooksSourceDir);//get files
        foreach ($dirContents as $file) {
            $target = $hooksSourceDir . $file;
            $link = $hooksTargetPath . $file;
            if(is_file($target)) {//make sure not directory
                if(is_link($link) ) {//check if there is already a link
                    if($overWrite){
                        //overwrite link
                        unlink($link);
                        Installer::messageToCLI("Removed old link to git hook - $file");
                    }
                    else {
                        continue; //link exists and we do not want to overwrite it
                    }
                }
                symlink($target,$link);
                Installer::messageToCLI("Created link to git hook - $file");
            }
        }
    }

    /* end oomphinc extender */

}
