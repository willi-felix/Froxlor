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

use Froxlor\UI\Callbacks\Text;
use Froxlor\UI\Listing;

return [
	'plugin_list' => [
		'title' => lng('admin.plugins'),
		'icon' => 'fa-solid fa-robot',
		'columns' => [
			'name' => [
				'label' => lng('plugin.name'),
				'field' => 'name',
			],
			'version' => [
				'label' => lng('plugin.version'),
				'field' => 'version'
			],
			'author' => [
				'label' => lng('plugin.author'),
				'field' => 'author'
			],
			'is_installed' => [
				'label' => lng('plugin.is_installed'),
				'field' => 'is_installed',
				'callback' => [Text::class, 'boolean']
			],
			'is_active' => [
				'label' => lng('plugin.is_active'),
				'field' => 'is_active',
				'callback' => [Text::class, 'boolean']
			],
		],
		'visible_columns' => Listing::getVisibleColumnsForListing('plugin_list', [
			'name',
			'version',
			'author',
			'is_installed',
			'is_active',
		]),
		'actions' => [
			'edit' => [
				'icon' => 'fa-solid fa-edit',
				'title' => lng('panel.edit'),
				'href' => [
					'section' => 'plugins',
					'page' => 'overview',
					'action' => 'edit',
					'id' => ':_shortname'
				],
			]
		]
	]
];
