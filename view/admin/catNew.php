<?php if (!defined ('ABSPATH')) die ('No direct access allowed'); ?>

<div class="wrap">
	<h2><?php _e ('BibTeX Plugin | New Category', 'BibTeX-plugin') ?></h2>
</div>

<div class="clear"></div>

<form action="<?php print $_SERVER['PHP_SELF'] . '?page=BibTeX-view-categories'?>" method="POST" name="adminForm">
	<table class="widefat page fixed" cellspacing="0">
		<tr>
			<td width="20%">
				Name:
			</td>
			<td width="80%">
				<input type="text" name="catName">
			</td>
		</tr>
		<tr>
			<td width="20%">
				Description:
			</td>
			<td width="80%">
				<TEXTAREA name="catDesc" rows="5" cols="120"></TEXTAREA>
			</td>
		</tr>
	</table>
	<input type="hidden" name="task" value="catSave" />
	<input class="button-primary" type="submit" name="OK" value="<?php _e ('OK', 'BibTeX-plugin'); ?>"/>
</form>