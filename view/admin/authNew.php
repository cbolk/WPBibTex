<?php if (!defined ('ABSPATH')) die ('No direct access allowed'); ?>

<div class="wrap">
	<h2><?php _e ('BibTeX Plugin | New Author', 'BibTeX-plugin') ?></h2>
</div>

<div class="clear"></div>
<?php
  // There is only one row!
     foreach ( $rows as $row ){
?>  
<form action="<?php print $_SERVER['PHP_SELF'] . '?page=BibTeX-view-authors'?>" method="POST" name="adminForm">
	<table class="widefat page fixed" cellspacing="0">
		<tr>
			<td width="20%">
				Firstname:
			</td>
			<td width="80%">
				<input type="text" name="authFirst" value="<?php echo $row->first; ?>">
			</td>
		</tr>
		<tr>
			<td width="20%">
				Middlename:
			</td>
			<td width="80%">
				<input type="text" name="authMiddle" value="<?php echo $row->middle; ?>">
			</td>
		</tr>
		<tr>
			<td width="20%">
				Lastname:
			</td>
			<td width="80%">
				<input type="text" name="authLast" value="<?php echo $row->last; ?>">
			</td>
		</tr>
		<tr>
			<td width="20%">
				University personnel?
			</td>
			<td width="80%">
				<input type="checkbox" name="isInternal"  value="<?php echo $row->isInternal; ?>" <?php if($row->isInternal==1) echo "checked"; ?> />
			</td>
		</tr>
	</table>
  <input type="hidden" name="authid" value="<?php echo $row->authid; ?>" />
	<input type="hidden" name="pubid" value="<?php echo $_POST['id']; ?>" />
	<input type="hidden" name="task" value="authSave" />
	<input class="button-primary" type="submit" name="OK" value="<?php _e ('OK', 'BibTeX-plugin'); ?>"/>
</form>
<?php 
    } // ending foreach
?>