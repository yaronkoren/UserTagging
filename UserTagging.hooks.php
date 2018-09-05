<?php

class UserTaggingHooks {

	public static function setGlobalJSVariables( &$vars ) {
		global $wgInvalidUsernameCharacters;

		$vars['wgInvalidUsernameCharacters'] = $wgInvalidUsernameCharacters;

		return true;
	}

	public static function addModules( OutputPage &$out, Skin &$skin ) {
		$out->addModules( 'ext.usertagging' );
		return true;
	}
}
