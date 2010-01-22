<?php

/**
 * This file is part of the SysCP project.
 * Copyright (c) 2003-2009 the SysCP Team (see authors).
 *
 * For the full copyright and license information, please view the COPYING
 * file that was distributed with this source code. You can also view the
 * COPYING file online at http://files.syscp.org/misc/COPYING.txt
 *
 * @copyright  (c) the authors
 * @author     Florian Lippert <flo@syscp.org>
 * @license    GPLv2 http://files.syscp.org/misc/COPYING.txt
 * @package    Functions
 * @version    $Id: function.createAWStatsConf.php 2724 2009-06-07 14:18:02Z flo $
 */

/**
 * Create or modify the AWStats configuration file for the given domain.
 * Modified by Berend Dekens to allow custom configurations.
 *
 * @param logFile
 * @param siteDomain
 * @param hostAliases
 * @return
 * @author Michael Duergner
 * @author Berend Dekens
 */

function createAWStatsConf($logFile, $siteDomain, $hostAliases)
{
	global $pathtophpfiles;

	// Generation header

	$header = "## GENERATED BY SYSCP\n";
	$header2 = "## Do not remove the line above! This tells SysCP to update this configuration\n## If you wish to manually change this configuration file, remove the first line to make sure SysCP won't rebuild this file\n## Generated for domain {SITE_DOMAIN} on " . date('l dS \of F Y h:i:s A') . "\n";

	// These are the variables we will replace

	$regex = array(
		'/\{LOG_FILE\}/',
		'/\{SITE_DOMAIN\}/',
		'/\{HOST_ALIASES\}/'
	);
	$replace = array(
		$logFile,
		$siteDomain,
		$hostAliases
	);

	// File names

	$domain_file = '/etc/awstats/awstats.' . $siteDomain . '.conf';
	$model_file = '/etc/awstats/awstats.model.conf.froxlor';

	// Test if the file exists

	if(file_exists($domain_file))
	{
		// Check for the generated header - if this is a manual modification we won't update

		$awstats_domain_conf = fopen($domain_file, 'r');

		if(fgets($awstats_domain_conf, strlen($header)) != $header)
		{
			fclose($awstats_domain_conf);
			return;
		}

		// Close the file

		fclose($awstats_domain_conf);
	}

	$awstats_domain_conf = fopen($domain_file, 'w');
	$awstats_model_conf = fopen($model_file, 'r');

	// Write the header

	fwrite($awstats_domain_conf, $header);
	fwrite($awstats_domain_conf, preg_replace($regex, $replace, $header2));

	// Write the configuration file

	while(($line = fgets($awstats_model_conf, 4096)) !== false)
	{
		if(!preg_match('/^#/', $line)
		   && trim($line) != '')
		{
			fwrite($awstats_domain_conf, preg_replace($regex, $replace, $line));
		}
	}

	fclose($awstats_domain_conf);
	fclose($awstats_model_conf);
}
