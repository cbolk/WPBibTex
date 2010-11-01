<?php if (!defined ('ABSPATH')) die ('No direct access allowed'); ?>

<div class="wrap">
	<h2><?php _e ('BibTeX Plugin | New Author', 'BibTeX-plugin') ?></h2>
</div>

<div class="clear"></div>

<form action="<?php print $_SERVER['PHP_SELF'] . '?page=BibTeX-view-author'?>" method="POST" name="adminForm">
	<table class="widefat page fixed" cellspacing="0">
		<tr>
			<td width="20%">
				Firstname:
			</td>
			<td width="80%">
				<input type="text" name="firstName">
			</td>
		</tr>
		<tr>
			<td width="20%">
				Middlename:
			</td>
			<td width="80%">
				<input type="text" name="middleName">
			</td>
		</tr>
		<tr>
			<td width="20%">
				Lastname:
			</td>
			<td width="80%">
				<input type="text" name="lastName">
			</td>
		</tr>
		<tr>
			<td width="20%">
				University personnel?
			</td>
			<td width="80%">
				<input type="checkbox" name="isInternal" value="isInternal" />
			</td>
		</tr>
	</table>
	<input type="hidden" name="task" value="authSave" />
	<input class="button-primary" type="submit" name="OK" value="<?php _e ('OK', 'BibTeX-plugin'); ?>"/>
</form>