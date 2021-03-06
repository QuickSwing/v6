<?php
/**
 * @package		Joomla.Site
 * @subpackage	mod_cifre_suggestions
 * @copyright	Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;
?>

<div class="em-contact-request-module">

	<?php if (!empty($offers)) :?>
		<span class="em-contact-request col-md-12">
			<?php foreach ($offers as $offer) :?>

				<div class="col-md-4" id="<?php echo $offer->fnum; ?>">
                    <div class="em-contact-request-card">
                        <div class="em-bottom-space">
                            <div class="em-contact-request-heading"><?php echo JText::_('MOD_EMUNDUS_CIFRE_OFFERS_OFFER_NAME'); ?></div>
                            <?php if (!empty($offer->titre)) :?>
                                <?php echo '<b>'.$offer->titre.'</b>'; ?>
                            <?php else: ?>
                                <?php echo '<b>'.JText::_('NO_TITLE').'</b>'; ?>
                            <?php endif; ?>
                        </div>

                        <div class="em-bottom-space">
                            <div id="em-buttons-<?php echo $offer->fnum; ?>">
                                <a role="button" class="btn btn-primary" href="<?php echo JRoute::_(JURI::base()."/les-offres/consultez-les-offres/details/299/".$offer->search_engine_page); ?>">
                                    <?php echo JText::_('MOD_EMUNDUS_CIFRE_OFFERS_VIEW'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
				</div>
			<?php endforeach; ?>
		</span>
	<?php endif; ?>
</div>
