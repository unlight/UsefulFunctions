<?php if (!defined('APPLICATION')) die(); # …

$PluginInfo['PluginUtils'] = array(
	'Name' => 'Plugin utils',
	'Description' => 'Useful functions for plugin and application developers.',
	'Version' => '2.0.0',
	'Author' => 'Vanilla Fan'
	//'RequiredPlugins' => array('HtmlPurifier' => '*')
);

Gdn::FactoryInstall('Zip', 'PclZip', dirname(__FILE__).DS.'vendors'.DS.'pclzip.lib.php', Gdn::FactoryInstance);
Gdn::FactoryInstall('Mailbox', 'ImapMailbox', dirname(__FILE__).DS.'library'.DS.'class.imapmailbox.php', Gdn::FactorySingleton);
Gdn::FactoryInstall('Snoopy', 'Snoopy', dirname(__FILE__).DS.'vendors'.DS.'Snoopy.class.php', Gdn::FactorySingleton);
require dirname(__FILE__).DS.'library'.DS.'functions.php';

/*
CHANGELOG 
=========
1.999 (14 Aug 2010)
[new] ConsolidateDataSetValues()
[new] AltAttribute()
[new] Alt()
[alt] Crc32Value()

1.998 (4 Jul 2010)
[new] test replace PhpQuery By QueryPath
[rem] removed console error handler (bootstrap.console.php)
[add] function CompileFile()
[rem] function time_sleep_until()
[add] tick: cleanup (close database connection)
[fix] ProcessDirectory()
[new] YearSeconds()
[new] LocaleLanguageCode()
[alt] GetSimilarity()
[alt] Crc32Value()

1.997 (4 Jul 2010)
[alt] GetRealIpAddress()
[add] RandomValue()

1.996 (1 Jul 2010)
[alt] changed cron events to "ticks"

1.995 (30 Jun 2010)
[new] CronJob pluggable

1.993 (8 Jun 2010)
1.994 (25 Jun 2010)
[add] FancyZoomImage()

1.992 (5 Jun 2010)
[alt] replaced Translate() to T()
[fix] fixed error undefined index: SERVER_NAME

1.991 (30 May 2010)
[fix] cleanup

1.99 (22 May 2010)
[add] d()
[add] Crc32File

1.98 (11 May 2010)
[fix] cleaned, part of deprecated functions were removed
[add] GetSimilarity(), Camelize(), SetStatus(), SplitString(), CamelizeResult(), CombineArrays()
[fix] UploadFile()

1.96 (4 May 2010)
[fix] fixed FlashHtml()

1.95 (17 Apr 2010)
[new] UploadFile()

1.94 (15 Apr 2010)
[fix] NoIndex()
[add] ValidateDnsEmail()

1.93 (6 Apr 2010)
[new] changelog started*/











