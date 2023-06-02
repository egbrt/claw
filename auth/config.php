<?php
/*
    Classification Workbench
    Copyright (c) 2020-2023, WONCA ICPC-3 Foundation

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <https://www.gnu.org/licenses/>.
*/

$BANNER_TITLE = "Classification Workbench";

$EMAIL_FROM = "<your@email.address>";
$EMAIL_SUBJECT = "Classification Workbench";
$EMAIL_GREETINGS = "Kind Regards,\nClassification Workbench\n";

define("DB_HOST", "localhost");
define("DB_NAME", 0);
define("DB_USER", 1);
define("DB_PASSWORD", 2);

define("DBASES", array(
    // use the line below to allow anonymous access to the browser of the workbench
    // "browser" => array("<database1_name>", "<database1_user_name>", "<database1_user_password>"),
    "alpha" => array("<database2_name>", "<database2_user_name>", "<database2_user_password>"),
    "beta" => array("<database3_name>", "<database3_user_name>", "<database3_user_password>")
));
?>
