<?php
/**
 * @package	HikaShop for Joomla!
 * @version	2.6.2
 * @author	hikashop.com
 * @copyright	(C) 2010-2016 HIKARI SOFTWARE. All rights reserved.
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');
?><?php

class Stripe_FileUpload extends Stripe_ApiResource
{
  public static function baseUrl()
  {
	return Stripe::$apiUploadBase;
  }

  public static function className($class)
  {
	return 'file';
  }

  public static function retrieve($id, $apiKey=null)
  {
	$class = get_class();
	return self::_scopedRetrieve($class, $id, $apiKey);
  }

  public static function create($params=null, $apiKey=null)
  {
	$class = get_class();
	return self::_scopedCreate($class, $params, $apiKey);
  }

  public static function all($params=null, $apiKey=null)
  {
	$class = get_class();
	return self::_scopedAll($class, $params, $apiKey);
  }
}
