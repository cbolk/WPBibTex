<?php if (!defined ('ABSPATH')) die ('No direct access allowed'); ?>

<div class="wrap">
	<h2><?php _e ('BibTeX Plugin | Error page', 'BibTeX-plugin') ?></h2>
</div>

<div class="wrap">

<p>
The bibliography selected is currently being edited by another administrator.
</p>

<form action="<?php print $_SERVER['PHP_SELF'] . '?page=BibTeX-plugin' ?>" method="post" name="adminForm">
	<input type="hidden" name="task" value="" />
	<input class="button-primary" type="submit" name="Back" value="<?php _e ('Back to Bibliography', 'BibTeX-plugin'); ?>"/>
</form>
