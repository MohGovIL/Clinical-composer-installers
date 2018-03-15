<?php
/* functions from oomphinc extender - https://github.com/oomphinc/composer-installers-extender */
namespace Clinikal\ComposerInstallersClinikalExtender;

use Composer\Installers\BaseInstaller;

class InstallerHelper extends BaseInstaller {

	function getLocations() {
		// it will be looking for a key of FALSE, which evaluates to 0, i.e. the first element
		// that element value being false signals the installer to use the default path
		return array( false );
	}

}
