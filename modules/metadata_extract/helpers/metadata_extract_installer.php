<?php defined("SYSPATH") or die("No direct script access.");
/**
 * Metadata Extractor - Extracts metadata from multimedia items
 * Copyright (C) 2010 Chandrasekar Ganesan
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or (at
 * your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street - Fifth Floor, Boston, MA  02110-1301, USA.
 */

require_once(MODPATH . "metadata_extract/helpers/metadata_utils.php");

class metadata_extract_installer {
  static function install() {
    $db = Database::instance();
    $db->query("CREATE TABLE IF NOT EXISTS {" . METADATA_TABLE . "} (
      `id`            INTEGER(9)     NOT NULL   AUTO_INCREMENT,
      `item_id`       INTEGER(9)     NOT NULL,
      `camera_maker`  VARCHAR(64),
      `camera_model`  VARCHAR(255),
      `lens`          VARCHAR(255),
      `aperture`      FLOAT(6, 3), 
      `shutter_speed` VARCHAR(32),
      `iso`           MEDIUMINT(6)   UNSIGNED,
      `focal_length`  FLOAT(9, 5), 
      `rating`        TINYINT(4),
      `country_code`  VARCHAR(6),
      `country`       VARCHAR(64),
      `state`         VARCHAR(255),
      `city`          VARCHAR(255),
      `location`      VARCHAR(255),
      `latitude`      FLOAT(12, 8), 
      `longitude`     FLOAT(12, 8),
      `extra_data`    TEXT,
      `dirty`         BOOLEAN default 1,
      PRIMARY KEY (`id`),
      KEY(`item_id`))
      DEFAULT CHARSET=utf8;");
	  
    module::set_version(METADATA_MODULE_NAME, 1);
  }

  static function activate() {
    //exif::check_index();
  }

  static function deactivate() {
    //site_status::clear("exif_index_out_of_date");
  }

  static function uninstall() {
    Database::instance()->query("DROP TABLE IF EXISTS {" . METADATA_TABLE . "};");
  }
}
