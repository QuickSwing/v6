<?php
/**
 * Created by PhpStorm.
 * User: yoan
 * Date: 19/09/14
 * Time: 11:05
 */
?>

<form action = "index.php?option=com_emundus&controller=users&task=affectgroups" id="em-affect-groups" role="form" method="post">
	<h3>
		<?php echo JText::_('SELECT_A_GROUP')?>
	</h3>
	<fieldset>
		<div class="form-group">
			<label class="control-label" for="agroups"><?php echo JText::_('COM_EMUNDUS_GROUPES'); ?></label>
			<select name = "agroups" id = "agroups" data-placeholder="<?php echo JText::_("COM_EMUNDUS_CHOOSE_GROUPS")?>" multiple>
				<?php foreach($this->groups as $group):?>
					<option value = "<?php echo $group->id?>"><?php echo $group->label?></option>
				<?php endforeach?>
			</select>
		</div>
	</fieldset>
</form>
<script type="text/javascript">
	$(document).ready(function()
	  {
	      $('#agroups').chosen({width:'100%'});
	  });
</script>