<?php if (!defined ('ABSPATH')) die ('No direct access allowed'); ?>

<div class="wrap">
	<h2><?php _e ('BibTeX Plugin | BibTeX input', 'BibTeX-plugin') ?></h2>
</div>
<div class="clear"></div>

<?php
if($inputtype=="")
{
?>
	<form action="<?php print $_SERVER['PHP_SELF'] . '?page=BibTeX-input-references' ?>" method="POST" name="adminForm">
		<table class="widefat page fixed" cellspacing="0">
			<tr>
				<td width="10%">
					Input Method:
				</td>
				<td width="90%">
					<select name="inputtype">
						<option value="file">BibTeX File</option>
						<option value="string">Paste BibTeX String</option>
<?php
						if($sets['manualinput']=="on")
						{
?>
							<option value="fields">Manually by Fields</option>
<?php
						}
?>
					</select>
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<input class="button-primary" type="submit" name="Select" value="<?php _e ('Select', 'BibTeX-plugin'); ?>"/>
				</td>
			</tr>
		</table>
	</form>
<?php
}
else
{
?>
	<form action="<?php print $_SERVER['PHP_SELF'] . '?page=BibTeX-input-references' ?>" enctype="multipart/form-data" method="POST" name="adminForm">
		<table class="widefat page fixed" cellspacing="0">
<?php
			if($inputtype=="file")
			{
?>
				<tr>
					<td width="10%">
						Bibtex File:
					</td>
					<td width="00%">
						<input type="hidden" name="MAX_FILE_SIZE" value="1000000" />
						<input class="inputbox" name="userfile" type="file" />
					</td>
				</tr>
<?php
			}
			elseif($inputtype=="string")
			{
?>
				<tr>
					<td width="10%">
						Bibtex String
					</td>
					<td align="left"  width="90%">
						<TEXTAREA name="bib" rows="5" cols="80"></TEXTAREA>
					</td>
				</tr>
<?php
			}
			elseif($inputtype=="fields")
			{
?>
				<tr>
<?php
					if($authornumber=="")
					{
?>
						<td width="10%">
							Number of authors
						</td>
						<td align="left" width="90%">
							<select name="authornumber">
								<option value="1">1</option>
								<option value="2">2</option>
								<option value="3">3</option>
								<option value="4">4</option>
								<option value="5">5</option>
								<option value="6">6</option>
								<option value="7">7</option>
								<option value="8">8</option>
								<option value="9">9</option>
								<option value="10">10</option>
							</select>
							<input class="button-primary" type="submit" name="Select" value="<?php _e ('Select', 'BibTeX-plugin'); ?>"/>
							<input type="hidden" name="inputtype" value="<?php echo $inputtype; ?>" />
						</td>
					
<?php
					}
					else
					{
?>
						<td valign="top" width="7%">
							Input Fields
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
														<option value="inproceedings" SELECTED>Conference proceedings</option>
														<option value="article">Journal article</option>
														<option value="inbook">Book chapter</option>
														<option value="book">Book</option>
														<option value="booklet">Booklet</option>
														<option value="incollection">In collection</option>
														<option value="manual">Manual</option>
														<option value="masterthesis">Master thesis</option>
														<option value="phdthesis">PhD thesis</option>
														<option value="proceedings">Editor of proceedings</option>
														<option value="techreport">Technical report</option>
														<option value="misc">Miscellanea</option>
													</select>												
<?php
											}
											else
											{ 
?>
												<input type="text" name="<?php echo $field?>"/>
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
												<TEXTAREA class="bibfield" name="<?php echo $field?>" rows="5" cols="50"></TEXTAREA>
											</td>
										</tr>
<?php
									}
								}
								if($k==1){echo "<td></td><td></td></tr>";}
									$k=1-$k;
								for($i=0;$i<(int)$authornumber;$i++)
								{
?>
									<tr>
										<td colspan="4">
											Author No. <?php echo $i+1 ?>
										</td>
									</tr>
<?php
									foreach($authfields as $authfield)
									{
										//CB
										if($authfield != "von"){
?>
										<tr>
											<td class="<?php echo sanitize_html_class($field)?>_LABEL">
												<?php echo $authfield?>
											</td>
											<td class="<?php echo sanitize_html_class($field)?>_VALUE" colspan="3">
												<input type="text" name="<?php echo $authfield.$i?>"/>
											</td>
										</tr>
<?php
										}//CBend
									}
								}
?>
							</table>
						</td>
<?php
					}
?>
			
				</tr>
<?php
			}
			if($authornumber!=""||$inputtype=="file"||$inputtype=="string")
			{
?>
				<tr>
					<td>
						Category
					</td>
					<td>
						<select name="category[]" multiple size="5">
<?php
							for ($i=0, $n=count( $cats ); $i < $n; $i++)
							{
								$cat = &$cats[$i];
								if($i==0)
								{
?>
									<option value="<?php echo $cat->id ?>" selected="selected"><?php echo $cat->name ?></option>
<?php
								}
								else
								{
?>
									<option value="<?php echo $cat->id ?>"> <?php echo $cat->name ?></option>
<?php
								}
							}
?>
						</select>
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<input class="button-primary" type="Submit" name="Upload" value="<?php _e ('Upload', 'BibTeX-plugin'); ?>"/>
						<input type="hidden" name="task" value="save" />
						<input type="hidden" name="authornumber" value="<?php echo $authornumber; ?>" />
						<input type="hidden" name="inputtype" value="<?php echo $inputtype; ?>" />
					</td>
				</tr>
<?php
			}
?>
		</table>
	</form><form action="<?php print $_SERVER['PHP_SELF'] . '?page=BibTeX-input-references'?>" method="POST" name="adminForm2">
	<table class="widefat page fixed" cellspacing="0">
		<tr>
			<td colspan="2">
				<input class="button-primary" type="Submit" name="Cancel" value="<?php _e ('Cancel', 'BibTeX-plugin'); ?>"/>
				<input type="hidden" name="task" value="" />
			</td>
		</tr>
	</table>
	</form>
	
<?php
}
?>
