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

class metadata_helper {

  protected static $metadata_keys = array(
    // Photo properties 
    "DateTimeCreated"                 => "Capture Date",
    "Make"                            => "Camera Make",
    "Model"                           => "Camera Model",
    "LensID"                          => "Lens",
    "ShootingMode"                    => "Shooting Mode",
    "Aperture"                        => "Aperture",
    "ShutterSpeed"                    => "Shutter Speed",
    "ISO"                             => "ISO",
    "FocalLength"                     => "Focal Length",
    "FOV"                             => "Field of View",
    "MeteringMode"                    => "Metering Mode",
    "DriveMode"                       => "Drive Mode",
    "Flash"                           => "Flash",
    "ScaleFactor35efl"                => "Scale Factor To 35 mm Equivalent",
    "ExposureMode"                    => "Exposure Mode",
    "ExposureCompensation"            => "Exposure Compensation",
    "Orientation"                     => "Orientation",
    "WhiteBalance"                    => "White Balance",
    // Image properties
    "XResolution"                     => "X Resolution",
    "YResolution"                     => "Y Resolution",
    "ResolutionUnit"                  => "Resolution Unit",
    "Compression"                     => "Compression",
    "BitsPerSample"                   => "Bits Per Sample",
    "PhotometricInterpretation"       => "Photomertic Interpretation",
    "ICCProfileName"                  => "ICC Profile",
    "PlanarConfiguration"             => "Planar Configuration",
    // GPS data
    "GPSLatitude"                     => array("GPS Latitude",                "GPSLatitude#"             ),
    "GPSLongitude"                    => array("GPS Longitude",               "GPSLongitude#"            ),
    "GPSAltitude"                     => "GPS Altitude",
    // Photo description
    "Title"                           => "Title",
    "Description"                     => "Description",
    "Subject"                         => "Tags",
    "Rating"                          => "Rating",
    "Creator"                         => "Creator",
    "Rights"                          => "Copyright Notice",
    // Location info
    "CountryCode"                     => "Country Code",
    "Country"                         => "Country",
    "State"                           => "State",
    "City"                            => "City",
    "Location"                        => "Location",
    // Software info
    "Software"                        => "Creator Tool",
    "XMPToolKit"                      => "XMP Tool Kit",
  );

  public static function read($item) {
    $metadata = array();

    $command_line = "";
    foreach (self::$metadata_keys as $key => $value) { 
      if (is_array($value)) {
        // Format string specified for key. Use it as it is.
        $command_line .= " -$value[1]";
      } else {
        // Read key value using default formatting.
        $command_line .= " -$key";
      }
    }
    $exiftool_path = MODPATH . METADATA_MODULE_NAME . "/lib/Image-ExifTool/exiftool";
    $command_line = $exiftool_path . " -S -j " . $item->file_path() . $command_line;
    Kohana_Log::add("debug", METADATA_MODULE_NAME . ": Executing command \"$command_line\"");
    exec($command_line, $output, $return_value);
    Kohana_Log::add("debug", METADATA_MODULE_NAME . ": exiftool returnval=$return_value output=" . print_r($output));
    if ($return_value == 0) {
      $metadata = self::parse($output);
    }

    return $metadata;
  }

  private static function parse($array) {
    // Combine all lines from output to get 
    // back json serialized string.
    $json = "";
    foreach ($array as $meta_item) {
      $json .= $meta_item;
    }

    // Decode json string.
    // echo "<p>" . $json . "</p>";
    $metadata = json_decode($json, true);
    $metadata = $metadata[0];

    // Remove unwanted items.
    if (array_key_exists("SourceFile", $metadata)) {
      unset($metadata["SourceFile"]);
    }

    return $metadata;
  }

  public static function save($item, $metadata) {
    // Save metadata
    $record = ORM::factory(METADATA_RECORD)->where("item_id", "=", $item->id)->find();
    if (!$record->loaded()) {
      $record->item_id = $item->id;
    }

    // Extract and save searchable fields.
    if (isset($metadata["Make"])) {
      $record->camera_maker = $metadata["Make"];
    }

    if (isset($metadata["Model"])) {
      $record->camera_model = $metadata["Model"];
    }

    if (isset($metadata["LensID"])) {
      $record->lens = $metadata["LensID"];
    }

    if (isset($metadata["Aperture"])) {
      $record->aperture = $metadata["Aperture"];
    }

    if (isset($metadata["ShutterSpeed"])) {
      $record->shutter_speed = $metadata["ShutterSpeed"];
    }

    if (isset($metadata["ISO"])) {
      $record->iso = $metadata["ISO"];
    }

    if (isset($metadata["FocalLength"])) {
      $record->focal_length = $metadata["FocalLength"];
    }

    if (isset($metadata["Rating"])) {
      $record->rating = $metadata["Rating"];
    }

    if (isset($metadata["CountryCode"])) {
      $record->country_code = $metadata["CountryCode"];
    }

    if (isset($metadata["Country"])) {
      $record->country = $metadata["Country"];
    }

    if (isset($metadata["State"])) {
      $record->state = $metadata["State"];
    }

    if (isset($metadata["City"])) {
      $record->city = $metadata["City"];
    }

    if (isset($metadata["Location"])) {
      $record->location = $metadata["Location"];
    }

    if (isset($metadata["GPSLatitude"])) {
      $record->latitude = $metadata["GPSLatitude"];
    }

    if (isset($metadata["GPSLongitude"])) {
      $record->longitude = $metadata["GPSLongitude"];
    }

    // Save the entire metadata
    $record->extra_data = serialize($metadata);
    $record->dirty = 0;

    // Save data
    $record->save();

    // Extract title and description and set it to item
    $item_changed = false;
    if (isset($metadata["Title"]) && !$item->title) {
      $item->title = $metadata["Title"];
      $item_changed = true;
    }

    if (isset($metadata["Description"]) && !$item->description) {
      $item->description = $metadata["Description"];
      $item_changed = true;
    }

    if ($item_changed) {
      $item->save();
    }

    // Extract keywords and add to tag cloud
    if (module::is_active("tag") && isset($metadata["Subject"])) {
      // If there are more than one keyword,
      // it is decoded as an array, else appears as a string
      $tags = $metadata["Subject"];
      if (!is_array($tags)) {
        // Create an array with a single keyword        
        $tags = array($tags);
      }
  
      // Add tag to tag cloud
      foreach ($tags as $tag) {
        tag::add($item, $tag);
      }
    } // end - if - tag module is active and photo has keywords
  }

}
