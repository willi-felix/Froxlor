<?php

/**
 * This file is part of the Froxlor project.
 * Copyright (c) 2010 the Froxlor Team (see authors).
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, you can also view it online at
 * https://files.froxlor.org/misc/COPYING.txt
 *
 * @copyright  the authors
 * @author     Froxlor team <team@froxlor.org>
 * @license    https://files.froxlor.org/misc/COPYING.txt GPLv2
 */

const AREA = 'admin';
require __DIR__ . '/lib/init.php';

use Froxlor\FroxlorLogger;
use Froxlor\UI\Panel\UI;
use Froxlor\UI\Listing;
use Froxlor\System\Plugin;
use Froxlor\UI\Response;
use Froxlor\UI\Request;
use Froxlor\Settings;

// check for plugins to be enabled
if (!Settings::Config('enable_plugins')) {
	Response::standardError('pluginsdisabled');
}

if ($page == 'overview') {

	if (!empty($action) && $action == 'edit') {
		$id = Request::get('id');
		$plugindir = __DIR__ . "/plugins/" . $id . "/plugin.json";
		$plugin = new Plugin($plugindir);

		if (isset($_POST['send']) && $_POST['send'] == 'send') {
			if ($_POST['is_installed'] == 1 && $plugin->isInstalled() == false) {
				$plugin->doInstall();
			}
			if ($_POST['is_installed'] == 0 && $plugin->isInstalled()) {
				$plugin->doUninstall();
				$_POST['is_active'] = 0;
			}
			$plugin->setActive((bool)$_POST['is_active']);

			Response::redirectTo($filename, [
				'page' => 'overview'
			]);
		}

		$formfield = [
			'plugin_edit' => [
				'title' => $plugin->getName(),
				'image' => 'fa-solid fa-robot',
				'self_overview' => ['section' => 'plugins', 'page' => 'overview'],
				'sections' => [
					'section_a' => [
						'title' => lng('plugin.edit'),
						'fields' => [
							'is_installed' => [
								'label' => ['title' => lng('plugin.install'), 'description' => lng('plugin.install_note')],
								'type' => 'checkbox',
								'value' => '1',
								'checked' => $plugin->isInstalled()
							],
							'is_active' => [
								'label' => ['title' => lng('plugin.activate'), 'description' => lng('plugin.activate_note')],
								'type' => 'checkbox',
								'value' => '1',
								'checked' => $plugin->isActive()
							]
						]
					]
				]
			]
		];

		UI::view('user/form.html.twig', [
			'formaction' => $linker->getLink(['section' => 'plugins', 'page' => 'overview', 'action' => 'edit', 'id' => $id]),
			'formdata' => $formfield['plugin_edit'],
			'editid' => $id
		]);
	} else {
		$log->logAction(FroxlorLogger::ADM_ACTION, LOG_NOTICE, "viewed plugins page");

		$plugins = ['data' => []];
		$plugin_errors = [];
		$errcnt = 0;
		foreach (glob(__DIR__ . "/plugins/*/plugin.json") as $plugindir) {
			try {
				$plugins['data'][] = (new Plugin($plugindir))->asArray();
			} catch (\Exception $e) {
				$plugin_errors[] = ++$errcnt . ": " . $e->getMessage();
			}
		}

		$plugin_list_data = include_once __DIR__ . '/lib/tablelisting/admin/tablelisting.plugins.php';

		$template = 'user/table.html.twig';
		$params['listing'] = Listing::formatFromArray($plugins, $plugin_list_data['plugin_list'], 'plugin_list');
		if (!empty($plugin_errors)) {
			$template = 'user/table-note.html.twig';
			$params['type'] = 'warning';
			$params['heading'] = 'Plugin issues';
			$params['alert_msg'] = implode("<br>", $plugin_errors);
		}
		UI::view($template, $params);
	}
} else {
	$plugin = Request::get('plugin');

	if (!empty($plugin)) {
		$pview = __DIR__ . '/plugins/' . $plugin . '/' . $page . '.php';
		if (file_exists($pview)) {
			include_once $pview;
		} else {
			Response::dynamicError("Unknown plugin page");
		}
	} else {
		Response::dynamicError("Invalid call");
	}
}
