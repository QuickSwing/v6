<?php
/**
 * @package   Joomla.Site
 * @subpackage  eMundus
 * @copyright Copyright (C) 2015 emundus.fr. All rights reserved.
 * @license   GNU General Public License version 2 or later; see LICENSE.txt
 */
// no direct access
defined('_JEXEC') or die;

echo $description;
?>
<?php if ($show_add_application && ($position_add_application == 0 || $position_add_application == 2) && $applicant_can_renew) : ?>
  <a class="btn btn-success" href="<?php echo JURI::Base(); ?>index.php?option=com_emundus&view=renew_application"><span class="icon-plus-sign"> <?php echo JText::_('ADD_APPLICATION_FILE'); ?></span></a>
<hr>
<?php endif; ?>
<?php if (!empty($applications)) : ?>
<div class="<?php echo $moduleclass_sfx ?>"> 
  <?php foreach($applications as $application) : ?>
  <div class="row" id="row<?php echo $application->fnum; ?>">
    <div class="col-xs-6 col-md-4">
      <p class="">
        <a href="<?php echo JRoute::_(JURI::Base().'index.php?option=com_emundus&task=openfile&fnum='.$application->fnum.'&Itemid='.$Itemid.'#em-panel'); ?>" >
          <?php
            echo ($application->fnum == $user->fnum)?'<span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span> <b>'.$application->label.'</b>':$application->label;
          ?>
        </a> 
    </div>

    <div class="col-xs-6 col-md-4">
      <p>
        <?php echo JText::_('FILE_NUMBER'); ?> : <i><?php echo $application->fnum; ?></i>
      </p>
      <a class="btn btn-warning" href="<?php echo JRoute::_(JURI::Base().'index.php?option=com_emundus&task=openfile&fnum='.$application->fnum.'&Itemid='.$Itemid.'#em-panel'); ?>"  role="button">
          <?php echo JText::_('OPEN_APPLICATION'); ?>
      </a>
      <?php if((int)($attachments[$application->fnum])>=100 && $application->status==0 && $application->fnum==$user->fnum) : ?>
        <a class="btn btn-mini" href="<?php echo $confirm_form_url; ?>&usekey=fnum&rowid=<?php echo $user->fnum; ?>" title="<?php echo JText::_('SEND_APPLICATION_FILE'); ?>"><i class="icon-envelope"></i> <?php echo JText::_('SEND_APPLICATION_FILE'); ?></a>
      <?php endif; ?>
    </div>

    <div class="col-xs-6 col-md-4">
      <?php echo JText::_('STATUS'); ?> : 
      <span class="label label-<?php echo $application->class; ?>"> 
        <?php echo $application->value; ?>
      </span>
      <section class="container" style="width:150px; float: left;">
      <?php if ($show_progress == 1) : ?>
        <div id="file<?php echo $application->fnum; ?>"></div>
        <script type="text/javascript">
          $( document ).ready(function() { 
              $("#file<?php echo $application->fnum; ?>").circliful({
                  animation: 1,
                  animationStep: 5,
                  foregroundBorderWidth: 15,
                  backgroundBorderWidth: 15,
                  percent: <?php echo (int)(($forms[$application->fnum]+$attachments[$application->fnum]))/2; ?>,
                  textStyle: 'font-size: 12px;',
                  textColor: '#000',
                  foregroundColor:'<?php echo $show_progress_color; ?>'
              });
          });
        </script>
      <?php endif; ?>

      <?php if ($show_progress_forms == 1) : ?>
        <div id="forms<?php echo $application->fnum; ?>"></div>
        <script type="text/javascript">
          $( document ).ready(function() { 
              $("#forms<?php echo $application->fnum; ?>").circliful({
                  animation: 1,
                  animationStep: 5,
                  foregroundBorderWidth: 15,
                  backgroundBorderWidth: 15,
                  percent: <?php echo (int)($forms[$application->fnum]); ?>,
                  text: '<?php echo JText::_("FORMS"); ?>',
                  textStyle: 'font-size: 12px;',
                  textColor: '#000',
                  foregroundColor:'<?php echo $show_progress_color_forms; ?>'
              });
          });
      </script>
      <?php endif; ?>

      <?php if ($show_progress_documents == 1) : ?>
        <div id="documents<?php echo $application->fnum; ?>"></div>
        <script type="text/javascript">
          $( document ).ready(function() { 
              $("#documents<?php echo $application->fnum; ?>").circliful({
                  animation: 1,
                  animationStep: 5,
                  foregroundBorderWidth: 15,
                  backgroundBorderWidth: 15,
                  percent: <?php echo (int)($attachments[$application->fnum]); ?>,
                  text: '<?php echo JText::_("DOCUMENTS"); ?>',
                  textStyle: 'font-size: 12px;',
                  textColor: '#000',
                  foregroundColor:'<?php echo $show_progress_color_documents; ?>'
              });
          });
        </script>
      <?php endif; ?>
    </section>
    </div>
  </div>
  <hr>
  <?php endforeach;  ?>
 </div> 
<?php endif; ?>

<?php if ($show_add_application && $position_add_application > 0 && $applicant_can_renew) : ?>
  <a class="btn btn-success" href="<?php echo JURI::Base(); ?>index.php?option=com_emundus&view=renew_application"><span class="icon-plus-sign"> <?php echo JText::_('ADD_APPLICATION_FILE'); ?></span></a>
<hr>
<?php endif; ?>