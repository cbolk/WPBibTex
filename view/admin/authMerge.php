<?php if (!defined ('ABSPATH')) die ('No direct access allowed'); ?>

<div class="wrap">
<h2><?php _e ('BibTeX Plugin | Bibliography Author Manager - Merge', 'BibTeX-plugin') ?></h2>
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

	$num_auths = ceil($total_rows / $per_page);
	$page_links = paginate_links( array(
			'base' => add_query_arg( 'pagenum', '%#%' ),
			'format' => '',
			'prev_text' => __('&laquo;'),
			'next_text' => __('&raquo;'),
			'total' => $num_auths,
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

<form action="<?php print $_SERVER['PHP_SELF'] . '?page=BibTeX-merge-authors' ?>" method="post" name="adminForm">
	<table  class="widefat page fixed" cellspacing="0">
		<thead>
			<tr>
				<th width="10%" align="center">
					Author to keep
				</th>
				<th width="5%" align="center">
					&nbsp;
				</th>
				<th width="15%" align="left" nowrap="nowrap">
					Firstname
				</th>
				<th width="15%" align="left" nowrap="nowrap">
					Middlename
				</th>
				<th width="15%" align="left">
					Lastname
				</th>
				<th width="10%" align="center">
					Internal
				</th>
				<th width="30%" align="center">
					&nbsp;
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
?>
					<tr>
						<td  align="center" width="10%" scope="row" class="check-column">
							<input type="radio" name="mainAuth" value="<?php echo $row->authid; ?>" />
						</td>
						<td  align="center" width="5%" scope="row" class="check-column">
							<input type="checkbox" name="post[]" value="<?php echo $row->authid; ?>" />
						</td>
						<td width="20%" align="left">
							<?php echo $row->first; ?>
						</td>
						<td width="20%" align="left">
							<?php echo $row->middle; ?>
						</td>
						<td width="20%" align="left">
							<?php echo $row->last; ?>
						</td>
						<td  align="center" width="5%" scope="row" class="check-column" valign="middle">
							<input type="checkbox" value="<?php echo $row->isInternal; ?>" <?php if($row->isInternal==1) echo "checked"; ?>/>
						</td>
						<td width="30%" align="left">
							<?php echo $row->isInternal; ?>
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
	<input type="hidden" name="task" value="authMerge" />
	<input class="button-primary" type="submit" name="Merge" value="<?php _e ('&nbsp;Merge Authors&nbsp;', 'BibTeX-plugin'); ?>"/>
	(<strong>Note:</strong> Specify in the first column the author to be kept, the others will be deleted.)
</form>


