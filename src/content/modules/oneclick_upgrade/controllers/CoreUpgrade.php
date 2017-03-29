<?php
class CoreUpgrade {
	const DEFAULT_CHECK_URL = "https://www.ulicms.de/current_version.json";
	private $checkURL = null;
	public function __construct() {
		$this->checkURL = CoreUpgrade::DEFAULT_CHECK_URL;
	}
	public function getCheckURL() {
		return apply_filter ( $this->checkURL, "core_upgrade_check_url" );
	}
	public function setCheckURL($url) {
		$this->checkURL = $url;
	}
	private function getJSON() {
		$data = file_get_contents_wrapper ( $this->getCheckURL (), true );
		if (! $data) {
			return null;
		}
		$data = json_decode ( $data );
	}
	public function checkForUpgrades() {
		$data = $this->getJSON ();
		if (! $data) {
			return null;
		}
		$version = $data->version;
		$cfg = new ulicms_version ();
		$oldVersion = $cfg->getInternalVersionAsString ();
		if (version_compare ( $oldVersion, $data->version, ">" )) {
			return $data->version;
		}
		return null;
	}
	public function runCoreUpgrade() {
		$acl = new ACL ();
		if (! $acl->hasPermission ( "update_system" ) or is_admin_dir () or ! $this->checkForUpgrades ()) {
			return false;
		}
		
		$jsonData = $this->getJSON ();
		if (! $jsonData) {
			return null;
		}
		
		$tmpDir = Path::resolve ( "ULICMS_TMP/upgrade" );
		$tmpArchive = Path::resolve ( "$tmpDir/upgrade.zip" );
		
		if (! file_exists ( $tmpDir )) {
			mkdir ( $tmpDir, 0777, true );
		}
		
		$data = file_get_contents_wrapper ( $jsonData->file, false );
		file_put_contents ( $tmpArchive, $data );
		
		$zip = new ZipArchive ();
		if ($zip->open ( $tmpArchive ) === TRUE) {
			$zip->extractTo ( $tmpDir );
			$zip->close ();
		}
		unlink ( $tmpArchive );
		
		$upgradeCodeDir = Path::resolve ( "$tmpDir/ulicms" );
		
		recurse_copy ( $upgradeCodeDir, ULICMS_ROOT );
		
		sureRemoveDir ( $upgradeCodeDir, true );
		
		include_once Path::resolve ( "ULICMS_ROOT/update.php" );
		return true;
	}
}