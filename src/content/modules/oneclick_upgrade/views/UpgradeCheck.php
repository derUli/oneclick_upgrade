<?php
$acl = new ACL ();
if ($acl->hasPermission ( "update_system" )) {
	$version = new ulicms_version ();
	$currentVersion = $version->getInternalVersionAsString ();
	$newVersion = ControllerRegistry::get ()->checkForUpgrades () ? ControllerRegistry::get ()->checkForUpgrades () : $currentVersion;
	?>
	<?php if($currentVersion == $newVersion){?>
<h1><?php translate("oneclick_upgrade")?></h1>
<p><?php translate("no_new_version_available");?></p>
<?php }?>
<form action="../?sClass=CoreUpgradeController&sMethod=runUpgrade"
	method=post>
	<?php csrf_token_html();?>
	<div class="row">
		<div class="col-xs-6 text-left">
			<strong><?php translate("installed_version");?></strong>
		</div>
		<div class="col-xs-6 text-right">
		<?php Template::escape($currentVersion);?>
	</div>

	</div>
	<div class="row">
		<div class="col-xs-6 text-left">
			<strong><?php translate("available_version");?></strong>
		</div>
		<div class="col-xs-6 text-right"><?php Template::escape($newVersion);?></div>
	</div>
<?php if($currentVersion != $newVersion){?>
<div class="alert alert-danger">
<?php translate("upgrade_warning_notice");?>
</div>
	<input type="submit" value="<?php translate("do_core_upgrade");?>">
<?php }?>
</form>
<!-- @TODO: Release Notes anzeigen, wenn verfügbar in der eingestellten Sprache -->
<?php
} else {
	noperms ();
}