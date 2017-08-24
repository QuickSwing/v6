<?php
/**
 * @version		$Id: data.php 14401 2014-09-16 14:10:00Z brivalland $
 * @package		Joomla
 * @subpackage	Emundus
 * @copyright	Copyright (C) 2005 - 2015 eMundus SAS. All rights reserved.
 * @license		GNU/GPL, see LICENSE.php
 * Joomla! is free software. This version may have been modified pursuant to the
 * GNU General Public License, and as distributed it includes or is derivative
 * of works licensed under the GNU General Public License or other free or open
 * source software licenses. See COPYRIGHT.php for copyright notices and
 * details.
 */
defined( '_JEXEC' ) or die( 'Restricted access' );
?>
<input type="hidden" id="view" name="view" value="admission">
<div class="panel panel-default">
	<?php if(is_array($this->datas)):?>
		<div>
			<?php echo $this->pagination->getResultsCounter(); ?>
		</div>
		<div class="em-data-container">
			<table class="table table-striped table-hover" id="em-data">
			<thead>
			<tr>
				<?php foreach($this->datas[0] as $kl => $v): ?>
					<?php if($kl == "jos_emundus_final_grade.user"): ?>
					<!-- Skips extra collumn -->
					<?php else :?>
					<th title="<?php echo JText::_($v)?>" id="<?php echo $kl?>" >
						<p class="em-cell">
							<?php if($kl == 'check'): ?>
								<label for="em-check-all">
									<input type="checkbox" value="-1" id="em-check-all" class="em-check" style="width:20px !important;"/>
									<span>#</span>
								</label>
								<label class="em-hide em-check-all-all" for="em-check-all-all">
									<input class="em-check-all-all em-hide" type="checkbox" name="check-all-all" value="all" id="em-check-all-all" style="width:20px !important;"/>
									<span class="em-hide em-check-all-all"><?php echo JText::_('COM_EMUNDUS_CHECK_ALL_ALL')?></span>
								</label>
							<?php elseif(@$this->lists['order'] == $kl):?>
								<?php if(@$this->lists['order_dir'] == 'desc'):?>
									<span class="glyphicon glyphicon-sort-by-attributes-alt"></span>
								<?php else:?>
									<span class="glyphicon glyphicon-sort-by-attributes"></span>
								<?php endif;?>
								<strong>
									<?php echo JText::_($v)?>
								</strong>
							<?php else:?>
								<?php echo JText::_($v)?>
							<?php endif;?>
						</p>
					</th>
					<?php endif; ?>
				<?php endforeach; ?>
			</tr>
			</thead>
			<tbody>
			
			<?php foreach ($this->datas as $key => $line):?>
				<?php if($key != 0): ?>
					<tr>
						<?php $cfnum = $line['fnum']->val; ?>
						<?php foreach ($line as $k => $value):?>

							<?php if($k != 'evaluation_id'): ?>

							<td <?php if($k == 'check' && $value->class != null) {echo 'class="'.$value->class.'"';}?>>
								<div class="em-cell" >
									<?php if($k == 'check'): ?>
										<label for = "<?php echo $line['fnum']->val ?>_check">
											<input type="checkbox" name="<?php echo $line['fnum']->val ?>_check" id="<?php echo $line['fnum']->val ?>_check" class='em-check' style="width:20px !important;"/>
											<?php 
												$tab = explode('-', $key);
												echo ($tab[1] + 1 + $this->pagination->limitstart);
												?>
										</label>
									<?php elseif ($k == 'status'):?>
                                        <span class="label label-<?php echo $value->status_class ?>" title="<?php echo $value->val ?>"><?php echo $value->val ?></span>
									<?php elseif ($k == 'fnum'):?>
										<a href="#<?php echo $value->val ?>|open" id="<?php echo $value->val ?>">
											<span class="glyphicon glyphicon-folder-open" title="<?php echo $value->val ?>">  <?php echo JFactory::getUser((int)substr($value->val, -7))->name; ?></span>
										</a>
									<?php elseif ($k == 'admission_id' || $k == 'recorded_by') :?>
									<?php else:?>
										
										<?php if ($value->type == 'text' ) :?>
											<?php echo strip_tags($value->val); ?>
										<?php elseif ($value->type == 'textarea') :?>
											<textarea rows="4" cols="2" id="<?php echo $cfnum.'-'.$value->id; ?>"><?php echo $value->val ?></textarea>
											<span class="glyphicon glyphicon-share-alt em-textarea" id="<?php echo $cfnum.'-'.$value->id.'-span'; ?>" aria-hidden="true" style="color:black;"></span>
										<?php elseif ($value->type == 'date') :?>
											<h5 class="em-date">
												<strong>
													<?php 
														if (!isset($value->val) || $value->val == "0000-00-00 00:00:00") {
															echo "No date";
														} else {
															$params = json_decode($value->params);
															$formatted_date = DateTime::createFromFormat('Y-m-d H:i:s', $value->val);
															echo $formatted_date->format($params->date_form_format);
														}
													?>
												</strong>
											</h5>
										<?php elseif ($value->type == 'radiobutton') :?>
											<?php if ($value->val == 'yes' || $value->val == '1') :?>
												<span class="glyphicon glyphicon-ok em-radio" id="<?php echo $cfnum.'-'.$value->id.'-'.$value->val; ?>" aria-hidden="true" style="color:green;"></span>
											<?php elseif ($value->val == 'no' || $value->val == '0') :?>
												<span class="glyphicon glyphicon-remove em-radio" id="<?php echo $cfnum.'-'.$value->id.'-'.$value->val; ?>" aria-hidden="true" style="color:red;"></span>
											<?php else :?>
												<span class="glyphicon glyphicon-warning-sign em-radio" id="<?php echo $cfnum.'-'.$value->id.'-'.$value->val; ?>" aria-hidden="true" style="color:orange;"></span>
											<?php endif; ?>
										<?php elseif ($value->type == 'field'):?>
											<input class="admission_input" type="text" id="<?php echo $cfnum.'-'.$value->id; ?>" name="<?php echo $value->val ?>" value="<?php echo $value->val ?>"></input>
											<span class="glyphicon glyphicon-share-alt em-field" id="<?php echo $cfnum.'-'.$value->id.'-span'; ?>" aria-hidden="true" style="color:black;"></span>
										<?php else :?>
											<?php echo $value->val; ?>
										<?php endif; ?>
									
									<?php endif; ?>
								</div>
							</td>
  						  <?php endif; ?>
						<?php endforeach; ?>
					</tr>
				<?php endif;?>
			<?php  endforeach;?>
			</tbody>
		</table>

		</div>
		<div class="well">
			<label for = "pager-select"><?php echo JText::_('DISPLAY')?></label>
			<select name="pager-select" class="chzn-select" id="pager-select">
				<option value="0" <?php if($this->pagination->limit == 0){echo "selected=true";}?>><?php echo JText::_('ALL')?></option>
				<option value="5" <?php if($this->pagination->limit == 5){echo "selected=true";}?>>5</option>
				<option value="10" <?php if($this->pagination->limit == 10){echo "selected=true";}?>>10</option>
				<option value="15" <?php if($this->pagination->limit == 15){echo "selected=true";}?>>15</option>
				<option value="20" <?php if($this->pagination->limit == 20){echo "selected=true";}?>>20</option>
				<option value="25" <?php if($this->pagination->limit == 25){echo "selected=true";}?>>25</option>
				<option value="30" <?php if($this->pagination->limit == 30){echo "selected=true";}?>>30</option>
				<option value="50" <?php if($this->pagination->limit == 50){echo "selected=true";}?>>50</option>
				<option value="100" <?php if($this->pagination->limit == 100){echo "selected=true";}?>>100</option>
			</select>
			<div>
				<ul class="pagination pagination-sm">
					<li><a href="#em-data" id="<?php echo $this->pagination->{'pagesStart'}?>"><<</a></li>
					<?php if($this->pagination->{'pagesTotal'} > 15):?>

						<?php for($i = 1; $i <= 5; $i++ ):?>
							<li <?php if($this->pagination->{'pagesCurrent'} == $i){echo 'class="active"';}?>><a id="<?php echo $i?>" href="#em-data"><?php echo $i?></a></li>
						<?php endfor;?>
						<li class="disabled"><span>...</span></li>
						<?php if($this->pagination->{'pagesCurrent'} <= 5):?>
							<?php for($i = 6; $i <= 10; $i++ ):?>
								<li <?php if($this->pagination->{'pagesCurrent'} == $i){echo 'class="active"';}?>><a id="<?php echo $i?>" href="#em-data"><?php echo $i?></a></li>
							<?php endfor;?>
						<?php else:?>
							<?php for($i = ($this->pagination->{'pagesCurrent'} - 2); $i <= ($this->pagination->{'pagesCurrent'} + 2); $i++ ):?>
								<li <?php if($this->pagination->{'pagesCurrent'} == $i){echo 'class="active"';}?>><a id="<?php echo $i?>" href="#em-data"><?php echo $i?></a></li>
							<?php endfor;?>
						<?php endif;?>
						<li class="disabled"><span>...</span></li>
						<?php for($i = ($this->pagination->{'pagesTotal'} - 4); $i <= $this->pagination->{'pagesTotal'}; $i++ ):?>
							<li <?php if($this->pagination->{'pagesCurrent'} == $i){echo 'class="active"';}?>><a id="<?php echo $i?>" href="#em-data"><?php echo $i?></a></li>
						<?php endfor;?>
					<?php else:?>
						<?php for($i = 1; $i <= $this->pagination->{'pagesStop'}; $i++ ):?>
							<li <?php if($this->pagination->{'pagesCurrent'} == $i){echo 'class="active"';}?>><a id="<?php echo $i?>" href="#em-data"><?php echo $i?></a></li>
						<?php endfor;?>
					<?php endif;?>
					<li><a href="#em-data" id="<?php echo $this->pagination->{'pagesTotal'}?>">>></a></li>
				</ul>
			</div>
		</div>
	<?php else:?>
		<?php echo $this->datas?>
	<?php endif;?>
</div>
<script type="text/javascript">
    //refreshFilter();
    function checkurl() {
        var url = $(location).attr('href');
        url = url.split("#");
        $('.alert.alert-warning').remove();
        if (url[1] != null && url[1].length >= 20) {
            url = url[1].split("|");
            var fnum = new Object();
            fnum.fnum = url[0];
            if (fnum != null && fnum.fnum != "close") {
                addDimmer();
                $.ajax({
                    type:'get',
                    url:'index.php?option=com_emundus&controller=files&task=getfnuminfos',
                    dataType:"json",
                    data:({fnum: fnum.fnum}),
                    success: function(result)
                    {
                        if (result.status && result.fnumInfos != null)
                        {
                            console.log(result);
                            var fnumInfos = result.fnumInfos;
                            fnum.name = fnumInfos.name;
                            fnum.label = fnumInfos.label;
                            openFiles(fnum);
                        } else {
                            console.log(result);
                            $('.em-dimmer').remove();
                            $(".panel.panel-default").prepend("<div class=\"alert alert-warning\"><?php echo JText::_('CANNOT_OPEN_FILE') ?></div>");
                        }
                    },
                    error: function (jqXHR, textStatus, errorThrown)
                    {
                        $('.em-dimmer').remove();
                        $("<div class=\"alert alert-warning\"><?php echo JText::_('CANNOT_OPEN_FILE') ?></div>").prepend($(".panel.panel-default"));
                        console.log(jqXHR.responseText);
                    }
                })
            }
        }

    }
    $(document).ready(function(){
        //checkurl();
        $('#rt-mainbody-surround').children().addClass('mainemundus');
        $('#rt-main').children().addClass('mainemundus');
        $('#rt-main').children().children().addClass('mainemundus');
        $('.em-data-container').doubleScroll();
    });
    window.parent.$("html, body").animate({scrollTop : 0}, 300);
</script>