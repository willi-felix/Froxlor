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

namespace Froxlor\System;

use Froxlor\Froxlor;
use Froxlor\Settings;
use Froxlor\Database\Database;

class Plugin
{

	/**
	 * plugin data from plugin.json and generated
	 *
	 * @var array
	 */
	private $pData = null;

	/**
	 * 
	 * @param string $plugin_json_file 
	 * @return self 
	 */
	public function __construct(string $plugin_json_file)
	{
		$pPath = str_replace(Froxlor::getInstallDir(), "", dirname($plugin_json_file));
		if (file_exists($plugin_json_file) && is_readable($plugin_json_file)) {
			$pJContent = json_decode(file_get_contents($plugin_json_file), true);
			if ($pJContent && !empty($pJContent['name'])) {
				$this->pData = array_merge(['_path' => dirname($plugin_json_file)], $pJContent);
				$pFile = $this->getPluginPath() . '/' . $this->getName() . '.php';
				if (file_exists($pFile) && is_readable($pFile)) {
					include_once $pFile;
					$pName = "\\Froxlor\\Plugins\\" . $this->getName();
					if (class_exists($pName) && in_array('Froxlor\System\FroxlorPluggable', class_implements($pName))) {
						return $this;
					}
					throw new \Exception("Unable to find plugin main class '" . $this->getName() . "' or missing 'FroxlorPluggable'-interface in '" . $this->getName() . ".php' in '" . $pPath . "'");
				}
				throw new \Exception("Unable to find plugin main file '" . $this->getName() . ".php' in '" . $pPath . "'");
			}
			throw new \Exception("Invalid plugin.json file in '" . $pPath . "'");
		}
		throw new \Exception("Unable to read plugin.json file in '" . $pPath . "'");
	}

	/**
	 * get dynamic data from plugin.json
	 * @param string $dindex 
	 * @return mixed 
	 */
	public function getData(string $dindex)
	{
		return $this->pData[$dindex] ?? null;
	}

	/**
	 * return readable name of the plugin
	 * @return string 
	 */
	public function getName(): string
	{
		return $this->pData['name'];
	}

	/**
	 * return version of plugin
	 * @return string 
	 */
	public function getVersion(): string
	{
		return $this->pData['version'] ?? "none";
	}

	/**
	 * return author of plugin
	 * @return string 
	 */
	public function getAuthor(): string
	{
		return $this->pData['author'] ?? "unknown";
	}

	/**
	 * return absolute path of the plugin base-directory
	 * @return string 
	 */
	public function getPluginPath(): string
	{
		return $this->pData['_path'];
	}

	/**
	 * return basename of plugin base-directory (_shortname of plugin)
	 * @return string
	 */
	public function getPluginBasename(): string
	{
		return basename($this->pData['_path']);
	}

	/**
	 * return whether the plugin has been installed
	 * @return bool 
	 */
	public function isInstalled(): bool
	{
		$installed = file_exists(Froxlor::getInstallDir() . '/lib/userdata.inc.php') && Settings::Get($this->getPluginBasename() . '.is_installed');
		return !empty($installed);
	}

	/**
	 * return whether the plugin is activated
	 * @return bool 
	 */
	public function isActive(): bool
	{
		$active = file_exists(Froxlor::getInstallDir() . '/lib/userdata.inc.php') && Settings::Get($this->getPluginBasename() . '.is_active');
		return !empty($active);
	}

	/**
	 * run installation method of plugin and set is_installed to true
	 * @return Plugin 
	 */
	public function doInstall(): self
	{
		if ($this->isInstalled() == false) {
			Settings::AddNew($this->getPluginBasename() . '.is_installed', 1);
			Settings::AddNew($this->getPluginBasename() . '.is_active', 0);
			// check for install() method of plugin
			$pFile = $this->getPluginPath() . '/' . $this->getName() . '.php';
			if (file_exists($pFile) && is_readable($pFile)) {
				include_once $pFile;
				$pName = "\\Froxlor\\Plugins\\" . $this->getName();
				if (class_exists($pName) && in_array('Froxlor\System\FroxlorPluggable', class_implements($pName))) {
					$pName::install();
				}
			}
		}
		return $this;
	}

	/**
	 * remove plugin from database and set installed to false
	 * @return Plugin 
	 */
	public function doUninstall(): self
	{
		if ($this->isInstalled()) {
			// check for uninstall() method of plugin
			$pFile = $this->getPluginPath() . '/' . $this->getName() . '.php';
			if (file_exists($pFile) && is_readable($pFile)) {
				include_once $pFile;
				$pName = "\\Froxlor\\Plugins\\" . $this->getName();
				if (class_exists($pName) && in_array('Froxlor\System\FroxlorPluggable', class_implements($pName))) {
					$pName::uninstall();
				}
			}
			$del_stmt = Database::prepare("DELETE FROM `" . TABLE_PANEL_SETTINGS . "` WHERE `settinggroup` = :plugin");
			Database::pexecute($del_stmt, ['plugin' => $this->getPluginBasename()]);
		}
		return $this;
	}

	/**
	 * Activate or deactivate the plugin
	 * @param bool $is_active 
	 * @return Plugin 
	 */
	public function setActive(bool $is_active = true): self
	{
		Settings::Set($this->getPluginBasename() . '.is_active', (int)$is_active);
		return $this;
	}

	/**
	 * return plugin data as array
	 * @return array
	 */
	public function asArray(): array
	{
		return array_merge($this->pData, ['is_installed' => $this->isInstalled(), 'is_active' => $this->isActive(), '_shortname' => $this->getPluginBasename()]);
	}

	/**
	 * return folder to navigation files for all plugins
	 * @return array
	 */
	public static function getNavigationArrays(): array
	{
		return self::getValidPluginFolder('navigation');
	}

	/**
	 * return folder to settings files for all plugins
	 * @return array
	 */
	public static function getSettingsArrays(): array
	{
		return self::getValidPluginFolder('settings');
	}

	/**
	 * return folder to templates for all plugins
	 * @return array
	 */
	public static function getTemplateFolders(): array
	{
		return self::getValidPluginFolder('templates');
	}

	/**
	 * return folder to language files for all plugins
	 * @return array
	 */
	public static function getLanguageFolders(): array
	{
		return self::getValidPluginFolder('lng');
	}

	/**
	 * return folder to language files for all plugins
	 * @return array
	 */
	public static function getCliCommands(): array
	{
		$arrdata = [];
		foreach (glob(Froxlor::getInstallDir() . "/plugins/*/plugin.json") as $plugindir) {
			try {
				$plugin = new Plugin($plugindir);
			} catch (\Exception $e) {
				continue;
			}
			if ($plugin->isInstalled() && $plugin->isActive()) {
				$commands = $plugin->getData('commands') ?? null;
				if (!empty($commands)) {
					foreach ($commands as $cliClass => $cliFile) {
						$arrdata[$cliClass] = $plugin->getPluginPath() . '/' . ltrim($cliFile, "/");
					}
				}
			}
		}
		return $arrdata;
	}

	/**
	 * return absolute path of all plugins and given folder
	 * @param string $folder 
	 * @return array
	 */
	private static function getValidPluginFolder(string $folder): array
	{
		$arrdata = [];
		foreach (glob(Froxlor::getInstallDir() . "/plugins/*/plugin.json") as $plugindir) {
			try {
				$plugin = new Plugin($plugindir);
			} catch (\Exception $e) {
				continue;
			}
			if ($plugin->isInstalled() && $plugin->isActive()) {
				$targetDir = $plugin->getData('dirs') != null ? ($plugin->getData('dirs')[$folder] ?? $folder) : $folder;
				if (is_dir($plugin->getPluginPath() . '/' . $targetDir)) {
					$arrdata[] = $plugin->getPluginPath() . '/' . $targetDir . '/';
				}
			}
		}
		return $arrdata;
	}
}
