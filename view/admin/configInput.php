<?php if (!defined ('ABSPATH')) die ('No direct access allowed'); ?>

<div class="wrap">
	<h2><?php _e ('BibTeX Plugin | Configuration', 'BibTeX-plugin') ?></h2>
</div>
<div class="clear"></div>

<form action="<?php print $_SERVER['PHP_SELF'] . '?page=BibTeX-configuration' ?>" method="POST" name="adminForm">
	<table class="widefat page fixed" cellspacing="0">
		<thead>
				<tr>
					<th width="200">
						Option
					</th>
					<th width="60">
						Value
					</th>
					<th>
						Description
					</th>
				</tr>
		</thead>
		<tbody>
		
<?php
			$k=0;
			foreach($sets as $variable=>$value)
			{
?>
				<tr>
					<td>
						<?php echo $names[$variable]?>
					</td>
					<td>
						<input type="checkbox" name="<?php echo $variable?>" <?php if($value=="on"){echo "checked";}?>/>
					</td>
					<td>
						<?php echo $tips[$variable];?>
					</td>
				</tr>
<?php
				$k++;
			}
?>
		</tbody>
	</table>
	<input type="hidden" name="task" value="confSave" />
	<input class="button-primary" type="submit" name="Save" value="<?php _e ('Save', 'BibTeX-plugin'); ?>"/>
</form>