<?php
/**
 * Created by PhpStorm.
 * User: yoan
 * Date: 16/09/14
 * Time: 09:15
 */
?>
<input type="hidden" id="view" name="view" value="users">
<?php if (!empty($this->users)) :?>
	<div>
		<?php echo $this->pagination->getResultsCounter();
		?>
	</div>
	<div class="em-data-container">
		<table class="table table-striped table-hover" id="em-data">
			<thead>
			<tr>
				<?php foreach ($this->users[0] as $key => $v) :?>
                    <?php if ($key === 'id') :?>
                        <th id="checkuser">
                            <p class="em-cell">
                                <label for="em-check-all">
                                    <input type="checkbox" value="-1" id="em-check-all" class="em-check"/>
                                </label>
                            </p>
                        </th>
                        <th id="<?php echo $key?>">
                            <p class="em-cell">
                                <label for="em-check-all">
                                    <?php if ($this->lists['order'] == $key) :?>
                                        <?php if ($this->lists['order_dir'] == 'desc') :?>
                                            <span class="glyphicon glyphicon-sort-by-attributes-alt"></span>
                                        <?php else :?>
                                            <span class="glyphicon glyphicon-sort-by-attributes"></span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <span>#</span>
                                </label>
                                <!--<label class="em-hide em-check-all-all" for="em-check-all-all">
                                    <input class="em-check-all-all em-hide" type="checkbox" name="check-all-all" value="all" id="em-check-all-all"/>
                                    <span class="em-hide em-check-all-all"><?php echo JText::_('COM_EMUNDUS_CHECK_ALL_ALL')?></span>
                                </label>-->
                            </p>
                        </th>
                    <?php else :?>
                        <th id="<?php echo $key?>">
                        <?php if ($this->lists['order'] == $key) :?>
                                <p class="em-cell">
                                <?php if ($this->lists['order_dir'] == 'desc') :?>
                                        <span class="glyphicon glyphicon-sort-by-attributes-alt"></span>
                                <?php else :?>
                                        <span class="glyphicon glyphicon-sort-by-attributes"></span>
                                <?php endif; ?>
                                <strong><?php echo JText::_(strtoupper($key))?></strong>
                                </p>
                        <?php else :?>
                            <p class="em-cell">
                                <strong><?php echo JText::_(strtoupper($key))?></strong>
                            </p>
                        <?php endif; ?>
                        </th>
                     <?php endif; ?>
				<?php endforeach; ?>
			</tr>
			</thead>
			<tbody>

			<?php foreach ($this->users as $l => $user) :?>
				<tr>
					<?php foreach ($user as $k => $value) :?>

								<?php if ($k == 'id') :?>
                                    <td>
                                        <div class="em-cell" >
                                            <input type="checkbox" name="<?php echo $value ?>_check" id="<?php echo $value?>_check" class='em-check'/>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="em-cell" >
                                            <label for = "<?php echo $value?>_check">
                                                <?php
                                                echo ($l * 1 + 1 + $this->pagination->limitstart) .'#'.$value;
                                                ?>
                                            </label>
                                        </div>
                                    </td>
								<?php elseif ($k == 'active') :?>
									<?php if ($value == 0) :?>
                                        <td>
                                            <div class="em-cell" >
										        <span class="glyphicon glyphicon-ok" style="color: #00c500"></span>
                                            </div>
                                        </td>
									<?php else:?>
                                        <td>
                                            <div class="em-cell" >
										        <span class="glyphicon glyphicon-ban-circle" style="color: #ff0000"></span>
                                            </div>
                                        </td>
									<?php endif; ?>
								<?php elseif ($k == 'newsletter') :?>
									<?php if ($value == 1) :?>
                                        <td>
                                            <div class="em-cell" >
										        <?php echo JText::_('JYES'); ?>
                                            </div>
                                        </td>
									<?php else:?>
                                        <td>
                                            <div class="em-cell" >
										        <?php echo JText::_('JNO'); ?>
                                            </div>
                                        </td>
									<?php endif;?>
								<?php else:?>
                                    <td>
                                        <div class="em-cell" >
									        <?php echo $value;?>
                                        </div>
                                    </td>
								<?php endif;?>
							</div>
						</td>
					<?php endforeach; ?>
				</tr>
			<?php  endforeach;?>
			</tbody>
		</table>
	</div>
	<div class="well">
        <label for = "pager-select"><?php echo JText::_('DISPLAY')?></label>
        <select name="pager-select" class="chzn-select" id="pager-select">
            <option value="0" <?php if($this->pagination->limit == 100000){echo "selected=true";}?>><?php echo JText::_('ALL')?></option>
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
                <?php if ($this->pagination->{'pagesTotal'} > 15) :?>

                    <?php for ($i = 1; $i <= 5; $i++ ) :?>
                        <li <?php if ($this->pagination->{'pagesCurrent'} == $i){echo 'class="active"';}?>><a id="<?php echo $i; ?>" href="#em-data"><?php echo $i; ?></a></li>
                    <?php endfor;?>
                    <li class="disabled"><span>...</span></li>
                    <?php if ($this->pagination->{'pagesCurrent'} <= 5) :?>
                        <?php for ($i = 6; $i <= 10; $i++ ) :?>
                            <li <?php if ($this->pagination->{'pagesCurrent'} == $i){echo 'class="active"';}?>><a id="<?php echo $i; ?>" href="#em-data"><?php echo $i; ?></a></li>
                        <?php endfor; ?>
                    <?php else :?>
                        <?php for ($i = ($this->pagination->{'pagesCurrent'} - 2); $i <= ($this->pagination->{'pagesCurrent'} + 2); $i++ ) :?>
                            <li <?php if ($this->pagination->{'pagesCurrent'} == $i){echo 'class="active"';}?>><a id="<?php echo $i; ?>" href="#em-data"><?php echo $i; ?></a></li>
                        <?php endfor; ?>
                    <?php endif; ?>
                    <li class="disabled"><span>...</span></li>
                    <?php for ($i = ($this->pagination->{'pagesTotal'} - 4); $i <= $this->pagination->{'pagesTotal'}; $i++ ) :?>
                        <li <?php if ($this->pagination->{'pagesCurrent'} == $i){echo 'class="active"';}?>><a id="<?php echo $i; ?>" href="#em-data"><?php echo $i; ?></a></li>
                    <?php endfor; ?>
                <?php else :?>
                    <?php for ($i = 1; $i <= $this->pagination->{'pagesStop'}; $i++ ) :?>
                        <li <?php if ($this->pagination->{'pagesCurrent'} == $i){echo 'class="active"';}?>><a id="<?php echo $i; ?>" href="#em-data"><?php echo $i; ?></a></li>
                    <?php endfor; ?>
                <?php endif; ?>
                <li><a href="#em-data" id="<?php echo $this->pagination->{'pagesTotal'}?>">>></a></li>
            </ul>
        </div>
    </div>

<?php else :?>
	<?php echo JText::_('NO_RESULT'); ?>
<?php endif; ?>
<script>
     $(document).ready(function(){
        $('.em-data-container').doubleScroll();
    });
</script>