<?php if (!defined ('ABSPATH')) die ('No direct access allowed'); ?>

<div class="wrap">
<h2><?php _e ('BibTeX Plugin | Bibliography Manager', 'BibTeX-plugin') ?></h2>
</div>

<div class="wrap">

<?php
	$total_rows=count($rows);
	$pagenum = isset( $_GET['pagenum'] ) ? absint( $_GET['pagenum'] ) : 0;
	if ( empty($pagenum) )
		$pagenum = 1;
	if ( empty( $per_page ) || $per_page < 1 )
		$per_page = 20;
		
	$start = ($pagenum - 1 ) * $per_page + 1;
	$end = min( $pagenum * $per_page, $total_rows );

	$num_cats = ceil($total_rows / $per_page);
	$page_links = paginate_links( array(
			'base' => add_query_arg( 'pagenum', '%#%' ),
			'format' => '',
			'prev_text' => __('&laquo;'),
			'next_text' => __('&raquo;'),
			'total' => $num_cats,
			'current' => $pagenum
	));
	if ( $page_links ) : 
?>
		<div class="tablenav-pages">
<?php 
			$page_links_text = sprintf( '<span class="displaying-num">' . __( 'Displaying %s&#8211;%s of %s' ) . '</span>%s',
				number_format_i18n( $start ),
				number_format_i18n( $end ),
				number_format_i18n( $total_rows ),
				$page_links
			);
			echo $page_links_text;
?>
		</div>
<?php 
		endif; 
?>
	<br class="clear" />
</div>

<div class="clear"></div>

<form action="<?php print $_SERVER['PHP_SELF'] . '?page=BibTeX-plugin' ?>" method="post" name="adminForm">
	<table  class="widefat page fixed" cellspacing="0">
		<thead>
			<tr>
				<th width="3%" align="center">
					<input type="checkbox" name="toggle" value="" onclick="checkAll(<?php echo $total_rows; ?>);" />
				</th>
				<th width="20%" align="left" >
					Authors
				</th>
				<th align="left" >
					Title
				</th>
				<th width="5%" align="left" >
					Year
				</th>
				<th width="25%" align="left" >
					DOI
				</th>
			</tr>
		</thead>
		<tbody>
<?php
			$count = 1;
			foreach ( $rows as $row )
			{
				if ( $count > $end )
					break;
				if ( $count >= $start )
				{
					//etal check
					$authstring = $row->authorsnames;
					if($sets['etal']=="on")
					{
						$authstring = $row->shortauthnames;
					}
				
?>
					<tr>
						<td scope="row" class="check-column" align="center" valign="middle">
							&nbsp;&nbsp;<input type="checkbox" name="post[]" value="<?php echo $row->pubid; ?>" />
						</td>
						<td align="left">
							<?php echo wp_specialchars_decode($authstring); ?>
						</td>
						<td align="left">
							<a href="admin.php?page=BibTeX-plugin&task=edit&id=<?php echo $row->pubid; ?>" title="Edit Reference">
								<?php echo wp_specialchars($row->title); ?>
							</a>
						</td>
						<td align="left">
							<?php echo $row->year; ?>
						</td>
						<td align="left">
							<?php if($row->doi!=""){
									echo "<a target='_blank' href='".$row->doi."' class='external'><img src='".$this->get_bt_pluginURL() . "/doi.png' height='14' alt='Go to document in another window'>" ;}
							?>
						</td>
					</tr>
<?php		
				}
				$count++;
			}
?>
		</tbody>
	</table>
<?php
	if ( $page_links )
		echo "<div class='tablenav-pages'>$page_links_text</div>";
?>
	<input type="hidden" name="task" value="remove" />
	<input class="button-primary" type="submit" name="Delete" value="<?php _e ('&nbsp;&nbsp;Delete Bibliography&nbsp;&nbsp;', 'BibTeX-plugin'); ?>"/>
</form>


<form action="<?php print $_SERVER['PHP_SELF'] . '?page=BibTeX-plugin' ?>" method="post" name="adminForm">
	<input type="hidden" name="task" value="allDelete" />
	<input class="button-primary" type="submit" name="DeleteALL" value="<?php _e ('Delete All Bibliography', 'BibTeX-plugin'); ?>"/>
</form>

<form action="<?php print $_SERVER['PHP_SELF'] . '?page=BibTeX-input-references' ?>" method="post" name="adminForm">
	<input type="hidden" name="task" value="" />
	<input class="button-primary" type="submit" name="New" value="<?php _e ('&nbsp;&nbsp;&nbsp;&nbsp;New Bibliography&nbsp;&nbsp;&nbsp;&nbsp;', 'BibTeX-plugin'); ?>"/>
</form>
