<?php

defined('_JEXEC') or die('Access Deny');
require_once(dirname(__FILE__).DS.'helper.php');

JHtml::stylesheet(JURI::base() . 'media/com_emundus/css/mod_emundus_campaign.css');


$mod_em_campaign_url=$params->get('mod_em_campaign_url');
/*$mod_em_campaign_period=$params->get('mod_em_campaign_period');
$mod_em_campaign_period=$params->get('mod_em_campaign_period');*/
$mod_em_campaign_class=$params->get('mod_em_campaign_class');
$mod_em_campaign_start_date=$params->get('mod_em_campaign_start_date');
$mod_em_campaign_end_date=$params->get('mod_em_campaign_end_date');
$mod_em_campaign_list_tab=$params->get('mod_em_campaign_list_tab');
$mod_em_campaign_param_tab=$params->get('mod_em_campaign_param_tab');
$mod_em_campaign_display_groupby=$params->get('mod_em_campaign_display_groupby');
$mod_em_campaign_groupby=$params->get('mod_em_campaign_groupby');
$mod_em_campaign_order=$params->get('mod_em_campaign_orderby');
$mod_em_campaign_order_type=$params->get('mod_em_campaign_order_type');
$mod_em_campaign_class=$params->get('mod_em_campaign_order_type');
$mod_em_campaign_itemid=$params->get('mod_em_campaign_itemid');
$showcampaign =$params->get('mod_em_campaign_param_showcampaign');
$showprogramme =$params->get('mod_em_campaign_param_showprogramme');

$condition ='';

$session = JFactory::getSession();

if (isset($_GET['order_date']) && !empty($_GET['order_date'])) {
    $session->set('order_date', $_GET['order_date']);
} else if(empty($order)){
    $session->set('order_date', $mod_em_campaign_order);
}
if (isset($_GET['order_time']) && !empty($_GET['order_time'])) {
    $session->set('order_time', $_GET['order_time']);
} else if(empty($order)){
    $session->set('order_time', $mod_em_campaign_order_type);
}
$order = $session->get('order_date');
$ordertime = $session->get('order_time');

if(isset($_POST['searchword']) && !empty($_POST['searchword'])) {
    $searchword=$_POST['searchword'];
    $condition = "AND CONCAT(pr.code,pr.notes,ca.label,ca.description) LIKE '%$searchword%'";
}


switch ($mod_em_campaign_groupby) {
    case 'month':
        if ($order=="start_date") {
            $condition .= ' ORDER BY start_date';
        } else {
            $condition .= ' ORDER BY end_date';
        }
        break;
    case 'program':
        if ($order=="start_date") {
            $condition .= ' ORDER BY training, start_date';
        } else {
            $condition .= ' ORDER BY training, end_date';
        }
        break;
}


switch ($ordertime) {
    case 'asc':
        $condition .=' ASC';
        break;
	case 'desc':
        $condition .=' DESC';
        break;
}

/*case 'out':
    $condition =' AND Now() >= ca.end_date and Now()<= ca.start_date';
    break;*/


$currentCampaign = modEmundusCampaignHelper::getCurrent($condition);
$pastCampaign = modEmundusCampaignHelper::getPast($condition);
$futurCampaign = modEmundusCampaignHelper::getFutur($condition);
$allCampaign = modEmundusCampaignHelper::getProgram($condition);

require(JModuleHelper::getLayoutPath('mod_emundus_campaign'));

function tronque($chaine, $longueur = 120)
{

    if (empty ($chaine))
    {
        return "";
    }
    elseif (strlen ($chaine) < $longueur)
    {
        return $chaine;
    }
    elseif (preg_match ("/(.{1,$longueur})\s./ms", $chaine, $match))
    {
        return $match [1] . "...";
    }
    else
    {
        return substr ($chaine, 0, $longueur) . "...";
    }
}


?>