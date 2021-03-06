<?php
/**
 * @version     1.0.0
 * @package     com_emundus
 * @copyright   Copyright (C) 2015. Tous droits réservés.
 * @license     GNU General Public License version 2 ou version ultérieure ; Voir LICENSE.txt
 * @author      emundus <dev@emundus.fr> - http://www.emundus.fr
 */
// no direct access
defined('_JEXEC') or die;

$doc = JFactory::getDocument();
$user = JFactory::getUser();

$doc->addStyleSheet('components/com_emundus/assets/css/item.css');
$doc->addStyleSheet('components/com_emundus/assets/css/list.css');
JHtml::stylesheet('media/com_emundus/lib/bootstrap-emundus/css/bootstrap.min.css');

$canEdit = $user->authorise('core.edit', 'com_emundus.' . $this->item->id);
if (!$canEdit && $user->authorise('core.edit.own', 'com_emundus' . $this->item->id)) {
    $canEdit = $user->id == $this->item->created_by;
}

?>
<?php if ($user->guest): ?>
    <div class="alert alert-warning">
        <b><?php echo JText::_('WARNING'); ?> : </b> <?php echo JText::_('COM_EMUNDUS_JOBS_PLEASE_CONNECT_OR_LOGIN_TO_APPLY'); ?>
    </div>
<?php endif; ?>
<?php if ($this->item) : ?>
    <h1><?php echo $this->item->intitule_poste; ?></h1>
    <div class="item_fields">
        <table class="table">
            <tr>
                <th><?php echo JText::_('COM_EMUNDUS_FORM_LBL_JOB_ETABLISSEMENT'); ?></th>
                <td><?php echo $this->item->etablissement; ?></td>
            </tr>
            <tr>
                <th><?php echo JText::_('COM_EMUNDUS_FORM_LBL_JOB_SERVICE'); ?></th>
                <td><?php echo $this->item->service; ?></td>
            </tr>
            <tr>
                <th><?php echo JText::_('COM_EMUNDUS_FORM_LBL_JOB_DOMAINE'); ?></th>
                <td><?php echo $this->item->domaine; ?></td>
            </tr>
            <tr>
                <th><?php echo JText::_('COM_EMUNDUS_FORM_LBL_JOB_LOCALISATION'); ?></th>
                <td><?php echo $this->item->localisation; ?></td>
            </tr>
            <tr>
                <th><?php echo JText::_('COM_EMUNDUS_FORM_LBL_JOB_DESCRIPTION'); ?></th>
                <td><?php echo $this->item->description; ?></td>
            </tr>
            <tr>
                <th><?php echo JText::_('COM_EMUNDUS_FORM_LBL_JOB_NIVEAU'); ?></th>
                <td><?php echo $this->item->niveau; ?></td>
            </tr>
            <tr>
                <th><?php echo JText::_('COM_EMUNDUS_FORM_LBL_JOB_DOMAINE_ETUDES'); ?></th>
                <td><?php echo $this->item->domaine_etudes; ?></td>
            </tr>
            <tr>
                <th><?php echo JText::_('COM_EMUNDUS_FORM_LBL_JOB_COMPETENCES'); ?></th>
                <td><?php echo $this->item->competences; ?></td>
            </tr>

            <tr>
                <th><?php echo JText::_('COM_EMUNDUS_FORM_LBL_JOB_MISSION'); ?></th>
                <td><?php echo $this->item->mission; ?></td>
            </tr>
            <tr>
                <th><?php echo JText::_('COM_EMUNDUS_FORM_LBL_JOB_NB_HEURES'); ?></th>
                <td><?php echo $this->item->nb_heures; ?></td>
            </tr>
            <tr>
                <th><?php echo JText::_('COM_EMUNDUS_FORM_LBL_JOB_DATE_DEBUT'); ?></th>
                <td><?php echo JHtml::_('date', $this->item->date_debut, JText::_('DATE_FORMAT_LC')); ?></td>
            </tr>
            <tr>
                <th><?php echo JText::_('COM_EMUNDUS_FORM_LBL_JOB_DATE_FIN'); ?></th>
                <td><?php echo JHtml::_('date', $this->item->date_fin, JText::_('DATE_FORMAT_LC')); ?></td>
            </tr>
            <tr>
                <th><?php echo JText::_('COM_EMUNDUS_FORM_LBL_JOB_DATE_LIMITE'); ?></th>
                <td><?php echo JHtml::_('date', $this->item->date_limite, JText::_('DATE_FORMAT_LC')); ?></td>
            </tr>
            <tr>
                <th><?php echo JText::_('COM_EMUNDUS_FORM_LBL_JOB_RESPONSABLE_EMAIL'); ?></th>
                <td><?php echo $this->item->responsable_email; ?></td>
            </tr>


        </table>
    </div>
    <?php if($canEdit): ?>
        <button type="button" onclick="window.location.href='<?php echo JRoute::_('index.php?option=com_emundus&task=job.edit&id='.$this->item->id); ?>';"><?php echo JText::_("COM_EMUNDUS_EDIT_ITEM"); ?></button>
    <?php endif; ?>
    <?php if($user->authorise('core.delete','com_emundus.job.'.$this->item->id)):?>
        <button type="button" onclick="window.location.href='<?php echo JRoute::_('index.php?option=com_emundus&task=job.remove&id=' . $this->item->id, false, 2); ?>';"><?php echo JText::_("COM_EMUNDUS_DELETE_ITEM"); ?></button>
    <?php endif; ?>
<?php
else:
    echo JText::_('COM_EMUNDUS_ITEM_NOT_LOADED');
endif;
?>
<script type="text/javascript">
    $('rt-top-surround').remove();
    $('rt-header').remove();
    $('rt-footer').remove();
    $('footer').remove();
    $('gf-menu-toggle').remove();
</script>