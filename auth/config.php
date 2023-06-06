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

$EMAIL_FROM = "info@icpc-3.info";
$EMAIL_SUBJECT = "ICPC-3 Workbench";
$EMAIL_GREETINGS = "Kind Regards,\nICPC-3 Foundation\n";

define("DB_HOST", "localhost");
define("DB_NAME", 0);
define("DB_USER", 1);
define("DB_PASSWORD", 2);

define("DBASES", array(
    // use the line below to allow anonymous access to the browser of the workbench
    // "browser" => array("icpc_3_info_claw_icpc", "icpc_3_info_claw_icpc", "AT01I0VeCFhU"),
    "alpha" => array("icpc_3_info_claw_icpc", "icpc_3_info_claw_icpc", "AT01I0VeCFhU"),
    "beta" => array("icpc_3_info_claw_beta", "icpc_3_info_claw_beta", "sdjkjsa7nsnn87kl"),
    "chinese" => array("icpc_3_info_claw_cn", "icpc_3_info_claw_cn", "XZqbnLtGgTbUPwKf8gSQ"),
    "danish" => array("icpc_3_info_claw_dk", "icpc_3_info_claw_dk", "ntSnEieZUqnxe5B2RFOA"),
    "dutch" => array("icpc_3_info_claw_nl", "icpc_3_info_claw_nl", "kdhdcjsusx7nsks"),
    "finnish" => array("icpc_3_info_claw_fi", "icpc_3_info_claw_fi", "QGpgnBnI2Gg2aiI4DjDi"),
    "french" => array("icpc_3_info_claw_fr", "icpc_3_info_claw_fr", "V9K1eDTelTnHNclpkL69"),
    "german" => array("icpc_3_info_claw_de", "icpc_3_info_claw_de", "JqztDNcgtYAfmUfKOnOW"),
    "greek" => array("icpc_3_info_claw_il", "icpc_3_info_claw_il", "uCuIeyVhOHl7lZNVH"),
    "portuguese" => array("icpc_3_info_claw_pt", "icpc_3_info_claw_pt", "fTfe6CZUoykArMBz"),
    "russian" => array("icpc_3_info_claw_kz", "icpc_3_info_claw_kz", "jshcn823Khags09"),
    "icpc1" => array("icpc_3_info_icpc_1", "icpc_3_info_icpc_1", "XJqgO5yxvJV1TfGSs"),
    "icpc2" => array("icpc_3_info_icpc_2", "icpc_3_info_icpc_2", "rCwo23BDrzdFJJTv")
));
?>
