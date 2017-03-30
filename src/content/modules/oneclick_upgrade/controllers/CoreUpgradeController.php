<?php
class CoreUpgradeController extends Controller {
	const DEFAULT_CHECK_URL = "https://www.ulicms.de/current_version.json";
	private $checkURL = null;
	private $excludeFolders = array ();
	public function __construct() {
		$this->checkURL = self::DEFAULT_CHECK_URL;
	}
	public function excludeFolder($folder) {
		$this->excludeFolders [] = $folder;
	}
	public function getExcludedFolders() {
		return $this->excludeFolders;
	}
	public function setExcludedFolders($folders) {
		$this->excludeFolders = $folders;
	}
	public function removeExcludedFolder($folder) {
		if (($key = array_search ( $folder, $this->excludeFolders )) !== false) {
			unset ( $this->excludeFolders [$key] );
		}
	}
	public function getCheckURL() {
		return apply_filter ( $this->checkURL, "core_upgrade_check_url" );
	}
	public function setCheckURL($url) {
		$this->checkURL = $url;
	}
	public function getJSON() {
		$data = file_get_contents_wrapper ( $this->getCheckURL (), true );
		if (! $data) {
			return null;
		}
		$data = json_decode ( $data );
		return $data;
	}
	public function checkForUpgrades() {
		$data = $this->getJSON ();
		if (! $data) {
			return null;
		}
		$version = $data->version;
		$cfg = new ulicms_version ();
		$oldVersion = $cfg->getInternalVersionAsString ();
		if (version_compare ( $oldVersion, $data->version, "<" )) {
			return $data->version;
		}
		return null;
	}
	public function runUpgrade() {
		@set_time_limit ( 0 );
		@ignore_user_abort ( 1 );
		$acl = new ACL ();
		
		if (! $acl->hasPermission ( "update_system" ) or is_admin_dir () or ! $this->checkForUpgrades () or get_request_method () != "POST") {
			return false;
		}
		
		$jsonData = $this->getJSON ();
		if (! $jsonData) {
			return false;
		}
		
		$exclude_folders = (isset ( $_POST ["exclude_folders"] ) and isNotNullOrEmpty ( $_POST ["exclude_folders"] )) ? $_POST ["exclude_folders"] : null;
		if ($exclude_folders) {
			$exclude_folders = normalizeLN ( $exclude_folders, "\n" );
			$exclude_Folders = explode ( "\n", $exclude_folders );
			array_filter ( $exclude_Folders );
			$this->setExcludedFolders ( $exclude_Folders );
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
		
		$upgradeCodeDir = Path::resolve ( "$tmpDir/ulicms" );
		
		$excluded = $this->getExcludedFolders ();
		for($i = 0; $i < count ( $excluded ); $i ++) {
			$myFolder = $excluded [$i];
			if (startsWith ( $myFolder, "/" )) {
				$myFolder = ltrim ( $myFolder, "/" );
			}
			
			$myFolder = rtrim ( $myFolder, "/" );
			$fullPath = realpath ( Path::resolve ( "$upgradeCodeDir/$myFolder" ) );
			
			if (startsWith ( $fullPath, $upgradeCodeDir ) and file_exists ( $fullPath ) and is_dir ( $fullPath )) {
				SureRemoveDir ( $fullPath, true );
			}
		}
		recurse_copy ( $upgradeCodeDir, ULICMS_ROOT );
		
		sureRemoveDir ( $upgradeCodeDir, true );
		
		// include_once Path::resolve ( "ULICMS_ROOT/update.php" );
		return true;
	}
}