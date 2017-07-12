ALTER TABLE `#__securitycheckpro_file_manager` ADD `last_check_database` DATETIME AFTER `online_checked_hashes`;

DROP TABLE IF EXISTS `#__securitycheckpro_db`;
CREATE TABLE `#__securitycheckpro_db` (
`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
`Product` VARCHAR(35) NOT NULL,
`Type` VARCHAR(35),
`Vulnerableversion` VARCHAR(10) DEFAULT '---',
`modvulnversion` VARCHAR(2) DEFAULT '==',
`Joomlaversion` VARCHAR(10) DEFAULT 'Notdefined',
`modvulnjoomla` VARCHAR(2) DEFAULT '==',
`description` VARCHAR(90),
`class` VARCHAR(70),
`published` VARCHAR(35),
`vulnerable` VARCHAR(70),
`solution_type` VARCHAR(35) DEFAULT '???',
`solution` VARCHAR(70),
PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;
INSERT INTO `#__securitycheckpro_db` (`product`,`type`,`vulnerableversion`,`modvulnversion`,`Joomlaversion`,
`modvulnjoomla`,`description`,`class`,`published`,`vulnerable`,`solution_type`,`solution`) VALUES 
('Joomla!','core','3.0.0','==','3.0.0','==','Joomla! XSS Vulnerability','Typographical error','Oct 09 2012','Joomla! 3.0.0','update','3.0.1'),
('com_fss','component','1.9.1.1447','<=','3.0.0','>=','Joomla Freestyle Support Component','SQL Injection Vulnerability','Oct 19 2012','Versions prior to 1.9.1.1447','none','No details'),
('com_commedia','component','3.1','<=','3.0.0','>=','Joomla Commedia Component','SQL Injection Vulnerability','Oct 19 2012','Versions prior to 3.1','update','3.2'),
('Joomla!','core','3.0.1','<=','3.0.1','<=','Joomla! Core Clickjacking Vulnerability','Inadequate protection','Nov 08 2012','Joomla! 3.0.1 and 3.0.0 versions','update','3.0.2'),
('com_jnews','component','7.9.1','<','3.0.0','>=','Joomla jNews Component','Arbitrary File Creation Vulnerability','Nov 19 2012','Versions prior to 7.9.1','update','7.9.1'),
('com_bch','component','---','==','3.0.0','>=','Joomla Bch Component','Shell Upload Vulnerability','Dec 26 2012','Not especificed','none','No details'),
('com_aclassif','component','---','==','3.0.0','>=','Joomla Aclassif Component','Cross Site Scripting Vulnerability','Dec 26 2012','Not especificed','none','No details'),
('com_rsfiles','component','1.0.0 Rev 11','==','3.0.0','>=','Joomla RSFiles! Component','SQL Injection Vulnerability','Mar 19 2013','Version 1.0.0 Rev 11','update','1.0.0 Rev 12'),
('Joomla!','core','3.0.2','<=','3.0.2','<=','Joomla! XSS Vulnerability','Inadequate filtering','Apr 24 2013','Joomla! version 3.0.2 and earlier 3.0.x versions','update','3.1.0'),
('Joomla!','core','3.0.2','<=','3.0.2','<=','Joomla! DOS Vulnerability','Object unserialize method','Apr 24 2013','Joomla! version 3.0.2 and earlier 3.0.x versions','update','3.1.0'),
('Joomla!','core','3.0.2','<=','3.0.0','>=','Joomla! XSS Vulnerability','Inadequate filtering','Apr 24 2013','Joomla! version 3.0.2 and earlier 3.0.x versions','update','3.1.0'),
('Joomla!','core','3.0.2','<=','3.0.0','>=','Joomla! Information Disclosure Vulnerability','Inadequate permission checking','Apr 24 2013','Joomla! version 3.0.2 and earlier 3.0.x versions','update','3.1.0'),
('Joomla!','core','3.0.2','<=','3.0.0','>=','Joomla! XSS Vulnerability','Use of old version of Flash-based file uploader','Apr 24 2013','Joomla! version 3.0.2 and earlier 3.0.x versions','update','3.1.0'),
('Joomla!','core','3.0.2','<=','3.0.0','>=','Joomla! Privilege Escalation Vulnerability','Inadequate permission checking','Apr 24 2013','Joomla! version 3.0.2 and earlier 3.0.x versions','update','3.1.0'),
('Joomla!','core','3.0.2','<=','3.0.0','>=','Joomla! XSS Vulnerability','Inadequate filtering','Apr 24 2013','Joomla! version 3.0.2 and earlier 3.0.x versions','update','3.1.0'),
('com_jnews','component','8.0.1','<=','3.0.0','>=','Joomla Jnews Component','Cross Site Scripting Vulnerability','May 14 2013','Version 8.0.1 an earlier','update','8.1.x'),
('com_attachments','component','3.1.1','<','3.0.0','>=','Joomla Com_Attachments Component','Arbitrary File Upload Vulnerability','Jul 09 2013','Versions prior to 3.1.1','update','3.1.1'),
('System - Google Maps','plugin','3.1','<','3.0.0','>=','Joomla Googlemaps Plugin','XSS/XML Injection/Path Disclosure/DoS Vulnerabilities','Jul 17 2013','Version 3.1 and maybe above','update','3.1'),
('System - Google Maps','plugin','3.2','<=','3.0.0','>=','Joomla Googlemaps Plugin','XSS/DoS Vulnerabilities','Jul 26 2013','Version 3.2','update','3.x'),
('Joomla!','core','3.1.4','<=','3.0.0','>=','Joomla! Unauthorised Uploads Vulnerability','Inadequate filtering','Jul 31 2013','Joomla! 3.1.4 and earlier 3.x versions','update','3.1.5'),
('com_sectionex','component','2.5.96','<=','3.0.0','>=','Joomla SectionEx Component','SQL Injection Vulnerability','Aug 05 2013','Version 2.5.96 and maybe earlier','update','2.5.104'),
('com_joomsport','component','2.0.1','<','3.0.0','>=','Joomla joomsport Component','Multiple Vulnerabilities','Aug 20 2013','Versions prior to 2.0.1','update','2.0.1'),
('Joomla!','core','3.1.5','<=','3.0.0','>=','Joomla! XSS Vulnerability','Inadequate filtering','Nov 06 2013','Joomla! 3.1.5 and all earlier 3.x versions','update','3.2'),
('Joomla!','core','3.1.5','<=','3.0.0','>=','Joomla! XSS Vulnerability','Inadequate filtering','Nov 06 2013','Joomla! 3.1.5 and all earlier 3.x versions','update','3.2'),
('Joomla!','core','3.1.5','<=','3.0.0','>=','Joomla! XSS Vulnerability','Inadequate filtering','Nov 06 2013','Joomla! 3.1.5 and all earlier 3.x versions','update','3.2'),
('com_flexicontent','component','2.1.3','<=','3.0.0','>=','Joomla Flexicontent Component','Remote Code Execution Vulnerability','Dec 08 2013','Version 2.1.3 and earlier','none','No details'),
('com_mijosearch','component','2.0.1','<=','3.0.0','>=','Joomla MijoSearch Component','Cross Site Scripting/Exposure Vulnerability','Dec 16 2013','Version 2.0.1 and maybe earlier','update','2.0.4'),
('com_acesearch','component','3.0','==','3.0.0','>=','Joomla AceSearch Component','Cross Site Scripting Vulnerability','Jan 06 2014','Version 3.0','none','No details'),
('com_melody','component','1.6.25','<=','3.0.0','>=','Joomla Melody Component','Cross Site Scripting Vulnerability','Jan 10 2014','Version 1.6.25 and maybe earlier','none','No details'),
('com_sexypolling','component','1.0.8','<=','3.0.0','>=','Joomla Sexy Polling Component','SQL Injection Vulnerability','Jan 16 2014','Version 1.0.8 and maybe earlier','update','1.0.9'),
('com_komento','component','1.7.2','<=','2.5.0','>=','Joomla Komento Component','Cross Site Scripting Vulnerability','Jan 24 2014','Version 1.7.2 and maybe earlier','update','1.7.4'),
('com_komento','component','1.7.2','<=','3.0.0','>=','Joomla Komento Component','Cross Site Scripting Vulnerability','Jan 24 2014','Version 1.7.2 and maybe earlier','update','1.7.4'),
('com_community','component','2.6','==','3.0.0','>=','Joomla JomSocial Component','Code Execution Vulnerability','Jan 31 2014','Version 2.6','update','3.1.0'),
('Joomla!','core','3.2.2','<=','3.0.0','>=','Joomla! SQL Injection Vulnerability','Inadequate escaping','Mar 06 2014','Joomla! 3.1.0 through 3.2.2','update','3.2.3'),
('Joomla!','core','3.2.2','<=','3.0.0','>=','Joomla! XSS Vulnerability','Inadequate escaping','Mar 06 2014','Joomla! 3.1.2 through 3.2.2','update','3.2.3'),
('com_youtubegallery','component','3.4.0','==','3.0.0','>=','Joomla Youtube Gallery Component','Cross Site Scripting','Mar 15 2014','Version 3.4.0','update','3.8.3'),
('com_pbbooking','component','2.4','==','3.0.0','>=','Joomla Pbbooking Component','Cross Site Scripting','Mar 15 2014','Version 3.4.0','none','No details'),
('com_extplorer','component','2.1.3','==','3.0.0','>=','Joomla eXtplorer Component','Cross Site Scripting','Mar 15 2014','Version 2.1.3','update','2.1.5'),
('com_freichat','component','3.5','<=','3.0.0','>=','Joomla Freichat Component','Cross Site Scripting','Mar 15 2014','Version 3.4.0','none','No details'),
('com_multicalendar','component','4.0.2','==','3.0.0','>=','Joomla Multi Calendar Component','Cross Site Scripting','Mar 15 2014','Version 4.0.2','update','4.8.9'),
('com_kunena','component','3.0.4','==','3.0.0','>=','Joomla Kunena Component','Cross Site scripting Vulnerability','Mar 27 2014','Version 3.0.4','update','3.0.5'),
('com_jchat','component','2.2','==','3.0.0','>=','Joomla JChatSocial Component','Cross Site scripting Vulnerability','Jul 07 2014','Version 2.2 and maybe lower','update','2.3'),
('com_youtubegallery','component','4.1.7','<=','3.0.0','>=','Joomla Youtube Gallery Component','SQL Injection Vulnerability','Jul 17 2014','Version 4.1.7 and maybe lower','update','4.2.0'),
('com_kunena','component','3.0.5','==','3.0.0','>=','Joomla Kunena Component','Cross Site scripting Vulnerability','Jul 30 2014','Version 3.0.5','update','3.0.6'),
('com_kunena','component','3.0.5','==','3.0.0','>=','Joomla Kunena Component','SQL Injection Vulnerability','Jul 30 2014','Version 3.0.5','update','3.0.6'),
('com_spidervideoplayer','component','2.8.3','==','3.0.0','>=','Joomla Spider Video Player Component','SQL Injection Vulnerability','Aug 26 2014','Version 2.8.3','none','No details'),
('com_akeeba','component','3.11.4','<','3.0.0','>=','Joomla Akeeba Backup Component','Not specified','Aug 20 2014','Version 3.11.4 and lower','update','3.11.4'),
('com_spidercalendar','component','3.2.6','<=','3.0.0','>=','Joomla Spider Calendar Component','SQL Injection Vulnerability','Sept 08 2014','Version 3.2.6 and lower','update','3.2.7'),
('com_spidercontacts','component','1.3.6','<=','3.0.0','>=','Joomla Spider Contacts Component','SQL Injection Vulnerability','Sept 10 2014','Version 1.3.6 and lower','update','1.3.7'),
('com_formmaker','component','3.4.1','<','3.0.0','>=','Joomla Spider Form Maker Component','SQL Injection Vulnerability','Sept 12 2014','Version 3.4.0 and lower','update','3.4.1'),
('com_facegallery','component','1.0','==','3.0.0','>=','Joomla Face Gallery Component','SQL Injection / File Download Vulnerabilities','Sept 22 2014','Version 1.0','none','No details'),
('com_macgallery','component','1.5','<=','3.0.0','>=','Joomla Mac Gallery Component','Arbitrary File Download Vulnerability','Sept 22 2014','Version 1.5 and lower','none','No details'),
('Joomla!','core','3.3.4','<','3.0.0','>=','Joomla! XSS Vulnerability','Inadequate escaping','Sep 23 2014','Joomla! 3.3.0 through 3.3.3','update','3.3.4'),
('Joomla!','core','3.3.4','<','3.0.0','>=','Joomla! Unauthorised Logins Vulnerability','Inadequate checking','Sep 23 2014','Joomla! 3.3.0 through 3.3.4','update','3.3.4'),
('Joomla!','core','3.3.4','<=','3.0.0','>=','Joomla! Remote File Inclusion Vulnerability','Inadequate checking','Sep 30 2014','Joomla! 3.3.0 through 3.3.4','update','3.3.5'),
('Joomla!','core','3.3.4','<=','3.0.0','>=','Joomla! Denial of service Vulnerability','Inadequate checking','Sep 30 2014','Joomla! 3.3.0 through 3.3.4','update','3.3.5'),
('com_creativecontactform','component','2.0.0','<=','3.0.0','>=','Joomla Creative Contact Form Component','Shell Upload Vulnerability','Oct 23 2014','Version 2.0.0 and lower','none','No details'),
('com_xcloner-backupandrestore','component','3.5.1','==','3.0.0','>=','Joomla XCloner Component','Command Execution/Password Disclosure Vulnerabilities','Nov 07 2014','Version 3.5.1','update','3.5.2'),
('com_eventbooking','component','---','==','3.0.0','>=','Joomla EventBooking Component','Cross site scripting Vulnerability','Nov 13 2014','Not especificed','none','No details'),
('com_hdflvplayer','component','2.1.0.1','==','3.0.0','>=','Joomla HD FLV Component','SQL Injection Vulnerability','Nov 13 2014','Version 2.1.0.1','none','No details'),
('mod_simpleemailform','module','1.8.5','<=','3.0.0','>=','Simple Email Form Module','Cross site Scripting Vulnerability','Nov 19 2014','Version 1.8.5 and maybe lower','none','No details'),
('com_jclassifiedsmanager','component','2.0.0','<','3.0.0','>=','JClassifiedsManager Component','Cross Site Scripting/SQL Injection Vulnerabilities','Jan 26 2015','Versions prior to 2.0.0','update','2.0.0'),
('com_simplephotogallery','component','1.0','==','3.0.0','>=','Simple Photo Gallery Component','SQL Injection Vulnerability','Mar 16 2015','Version 1.0','update','1.1'),
('com_ecommercewd','component','1.2.5','==','3.0.0','>=','ECommerce-WD Component','SQL Injection Vulnerabilities','Mar 19 2015','Version 1.2.5','update','1.2.6'),
('com_spiderfaq','component','1.1','==','3.0.0','>=','Spider FAQ Component','SQL Injection Vulnerabilities','Mar 22 2015','Version 1.1','none','No details'),
('com_rand','component','1.5','==','3.0.0','>=','Spider Random Article Component','SQL Injection Vulnerabilities','Mar 25 2015','Version 1.5','none','No details'),
('com_gallery_wd','component','1.2.5','==','3.0.0','>=','Gallery WD Component','SQL Injection Vulnerabilities','Mar 30 2015','Version 1.2.5','none','No details'),
('com_contactformmaker','component','1.0.1','==','3.0.0','>=','Form Maker Component','SQL Injection Vulnerabilities','Mar 30 2015','Version 1.0.1','none','No details');

DROP TABLE IF EXISTS `#__securitycheckpro_sessions`;
CREATE TABLE IF NOT EXISTS `#__securitycheckpro_sessions` (
`userid` INT(4) UNSIGNED NOT NULL,
`session_id` VARCHAR(200) NOT NULL,
`username` VARCHAR(150) NOT NULL,
`ip` BIGINT NOT NULL,
`user_agent` VARCHAR(300) NOT NULL,
PRIMARY KEY (`userid`)
) ENGINE=MyISAM AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;