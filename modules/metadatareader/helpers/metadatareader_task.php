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

class metadatareader_task_Core {

  static function available_tasks() {
    // Delete orphaned metadata records
    db::build()
      ->delete(METADATA_TABLE)
      ->where("item_id", "NOT IN",
              db::build()->select("id")->from("items")->where("type", "=", "photo"))
      ->execute();

    list ($remaining, $total, $percent) = metadata_helper::stats();
    return array(Task_Definition::factory()
                 ->callback("metadata_extract_task::update_index")
                 ->name(t("Extract metadata"))
                 ->description($remaining
                               ? t2("1 photo needs to be scanned",
                                    "%count (%percent%) of your photos need to be scanned",
                                    $remaining, array("percent" => (100 - $percent)))
                               : t("Exif data is up-to-date"))
                 ->severity($remaining ? log::WARNING : log::SUCCESS));
  }

  static function update_index($task) {
    try {
      $completed = $task->get("completed", 0);

      $start = microtime(true);
      foreach (ORM::factory("item")
               ->join(METADATA_TABLE, "items.id", METADATA_TABLE.".item_id", "left")
               ->where("type", "=", "photo")
               ->and_open()
               ->where(METADATA_TABLE.".item_id", "IS", null)
               ->or_where(METADATA_TABLE.".dirty", "=", 1)
               ->close()
               ->find_all() as $item) {
        // The query above can take a long time, so start the timer after its done
        // to give ourselves a little time to actually process rows.
        if (!isset($start)) {
          $start = microtime(true);
        }

        // Extract metadata
        $metadata = metadatareader_helper::read($item);
        metadatareader_helper::save($item, $metadata);

        $completed++;

        if (microtime(true) - $start > 1.5) {
          break;
        }
      }

      list ($remaining, $total, $percent) = metadata_helper::stats();
      $task->set("completed", $completed);
      if ($remaining == 0 || !($remaining + $completed)) {
        $task->done = true;
        $task->state = "success";
        site_status::clear("metadata_out_of_date");
        $task->percent_complete = 100;
      } else {
        $task->percent_complete = round(100 * $completed / ($remaining + $completed));
      }
      $task->status = t2("one record updated, metadata is %percent% up-to-date",
                         "%count records updated, metadata is %percent% up-to-date",
                         $completed, array("percent" => $percent));
    } catch (Exception $e) {
      $task->done = true;
      $task->state = "error";
      $task->status = $e->getMessage();
      $task->log((string)$e);
    }
  }
}

