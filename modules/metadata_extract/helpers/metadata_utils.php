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

// Constants
define("METADATA_MODULE_NAME", "metadata_extract");
define("METADATA_RECORD", "metadata_photo");
define("METADATA_TABLE", "metadata_photos");

class metadata_utils {

  static function ends_with($str, $search_str) {
    return (substr($str, strlen($str) - strlen($search_str)) === $search_str);
  }

}

