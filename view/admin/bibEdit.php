<?php if (!defined ('ABSPATH')) die ('No direct access allowed'); ?>

<div class="wrap">
	<h2><?php _e ('BibTeX Plugin | Edit Bibliography', 'BibTeX-plugin') ?></h2>
</div>

<div class="clear"></div>

<form action="<?php print $_SERVER['PHP_SELF'] . '?page=BibTeX-plugin'?>" method="POST" name="adminForm">
	<table class="widefat page fixed" cellspacing="0">
		<tr>
			<td valign="top" width="7%">
				Input Fields:	
			</td>
			<td align="left">
				<table class="widefat" cellspacing="0" width="93%">
<?php
					$k=0;
					foreach($fields as $field)
					{
						if($field!="abstract")
						{
							if($k==0){echo "<tr>";}
?>
							<td class="bibfield <?php echo sanitize_html_class($field)?>_LABEL">
								<?php echo $field?>
							</td>
							<td class="bibfield <?php echo sanitize_html_class($field)?>_VALUE">
<?php
								if($field=="type")
								{
?>
									<select name="type">
										<option value="inproceedings" <?php echo ($row[$field]== "inproceedings" ? SELECTED : '')?>>Conference proceedings</option>
										<option value="article"<?php echo ($row[$field]== "article" ? SELECTED : '')?>>Journal article</option>
										<option value="inbook"<?php echo ($row[$field]== "inbook" ? SELECTED : '')?>>Book chapter</option>
										<option value="book"<?php echo ($row[$field]== "book" ? SELECTED : '')?>>Book</option>
										<option value="booklet"<?php echo ($row[$field]== "booklet" ? SELECTED : '')?>>Booklet</option>
										<option value="incollection"<?php echo ($row[$field]== "incollection" ? SELECTED : '')?>>In collection</option>
										<option value="manual"<?php echo ($row[$field]== "manual" ? SELECTED : '')?>>Manual</option>
										<option value="masterthesis"<?php echo ($row[$field]== "masterthesis" ? SELECTED : '')?>>Master thesis</option>
										<option value="phdthesis"<?php echo ($row[$field]== "phdthesis" ? SELECTED : '')?>>PhD thesis</option>
										<option value="proceedings"<?php echo ($row[$field]== "proceedings" ? SELECTED : '')?>>Editor of proceedings</option>
										<option value="techreport"<?php echo ($row[$field]== "techreport" ? SELECTED : '')?>>Technical report</option>
										<option value="misc"<?php echo ($row[$field]== "misc" ? SELECTED : '')?>>Miscellanea</option>
									</select>												
<?php
								}
								else
								{ 
?>
									<input  type="text" name="<?php echo $field?>" value="<?php echo htmlentities($row[$field])?>"/>
<?php
								}
?>
							</td>
<?php
							if($k==1){echo "</tr>";}
							$k=1-$k;
						}
						else
						{
							if($k==1){echo "<td></td><td></td></tr>";}
								$k=1-$k;
?>
							<tr>
								<td class="bibfield <?php echo sanitize_html_class($field)?>_LABEL">
									<?php echo $field?>
								</td>
								<td colspan="3">
									<TEXTAREA class="bibfield" name="<?php echo $field?>" rows="5" ><?php echo wp_specialchars($row[$field])?></TEXTAREA>
								</td>
							</tr>
<?php
						}
					}
					if($k==1){echo "<td></td><td></td></tr>";}
						$k=1-$k;
					for($i=0;$i<count($authrows);$i++)
					{
?>
						<tr>
							<td colspan="2">
								Author No. <?php echo $i+1 ?>:&nbsp;&nbsp;
<?php
										foreach($authfields as $authfield)
										{
											if(($authfield == "authid")){
											  $authid = $authrows[$i][$authfield];
											} else if(($authfield != "isInternal")){
												echo " ".htmlentities($authrows[$i][$authfield]);
											}
										}
?>							
							</td>
							<td colspan="2">
								<a class="inputbutton" href="admin.php?page=BibTeX-plugin&task=deleteAuthorPublication&id=<?php echo $id;?>&authid=<?php echo $authid;?>" Title="Remove this author from this publication">Remove Author</a>
							</td>
						</tr>
<?php
					}
?>

				</table>
			</td>
		</tr>
		<tr>
			<td>
				Category
			</td>
			<td>
				<select name="category[]" multiple>
<?php 
					//CB
					$i=0;
					foreach ($catrows as $key=>$value)
					{
						$crows[$i] = $value->categories;
						$i++;
					}
					//CBend
					foreach ($cats as $caid=>$caname)
					{
						$match=0;
						//foreach ($catrows as $catrow) old FF
						foreach($crows as $catrow)
						{
							if($caid==$catrow)
							{
								$match=1;
							}
						}
//						echo $match;
						if($match==1)
						{
?>
							<option value="<?php echo $caid ?>" SELECTED><?php echo $caname ?></option>
<?php
						}
						else
						{
?>
							<option value="<?php echo $caid ?>"><?php echo $caname ?></option>
<?php
						}
					}
?>
				</select>
			</td>
		</tr>
		<tr>
			<td colspan="2">
				<input class="button-primary" type="Submit" name="update" value="<?php _e ('Update', 'BibTeX-plugin'); ?>"/>
				<input type="hidden" name="id" value="<?php echo $id; ?>" />
				<input type="hidden" name="task" value="saveEdit" />
				<input type="hidden" name="authornumber" value="<?php echo $authornumber; ?>" />
			</td>
		</tr>
	</table>
</form>
<form action="<?php print $_SERVER['PHP_SELF'] . '?page=BibTeX-view-authors'?>" method="POST" name="adminForm2">
	<table class="widefat page fixed" cellspacing="0">
		<tr>
			<td colspan="2">
				<input class="button-primary" type="Submit" name="Add" value="<?php _e ('Add Author', 'BibTeX-plugin'); ?>"/>
				<input type="hidden" name="id" value="<?php echo $id; ?>" />
				<input type="hidden" name="task" value="authNew" />
				<input type="hidden" name="authornumber" value="<?php echo $authornumber + 1; ?>" />
			</td>
		</tr>
	</table>
</form>
<form action="<?php print $_SERVER['PHP_SELF'] . '?page=BibTeX-plugin'?>" method="POST" name="adminForm2">
	<table class="widefat page fixed" cellspacing="0">
		<tr>
			<td colspan="2">
				<input class="button-primary" type="Submit" name="Cancel" value="<?php _e ('Cancel', 'BibTeX-plugin'); ?>"/>
				<input type="hidden" name="id" value="<?php echo $id; ?>" />
				<input type="hidden" name="task" value="cancel" />
			</td>
		</tr>
	</table>
</form>
