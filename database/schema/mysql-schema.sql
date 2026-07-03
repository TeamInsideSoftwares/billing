/*M!999999\- enable the sandbox mode */ 
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;
DROP TABLE IF EXISTS `account_billing_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `account_billing_details` (
  `account_bdid` varchar(6) NOT NULL,
  `accountid` varchar(10) DEFAULT NULL,
  `billing_name` varchar(150) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `postal_code` varchar(10) DEFAULT NULL,
  `gstin` varchar(15) DEFAULT NULL,
  `tin` varchar(50) DEFAULT NULL,
  `authorize_signatory` varchar(255) DEFAULT NULL,
  `signature_upload` varchar(500) DEFAULT NULL,
  `billing_from_email` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`account_bdid`),
  KEY `account_billing_details_accountid_index` (`accountid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `account_departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `account_departments` (
  `depid` varchar(10) NOT NULL,
  `accountid` varchar(10) NOT NULL,
  `name` varchar(50) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`depid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `account_quotation_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `account_quotation_details` (
  `account_qdid` varchar(6) NOT NULL,
  `accountid` varchar(10) DEFAULT NULL,
  `quotation_name` varchar(150) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `postal_code` varchar(10) DEFAULT NULL,
  `gstin` varchar(15) DEFAULT NULL,
  `tin` varchar(20) DEFAULT NULL,
  `authorize_signatory` varchar(255) DEFAULT NULL,
  `signature_upload` varchar(500) DEFAULT NULL,
  `billing_from_email` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`account_qdid`),
  KEY `account_quotation_details_accountid_index` (`accountid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `account_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `account_roles` (
  `roleid` varchar(10) NOT NULL,
  `accountid` varchar(10) NOT NULL,
  `name` varchar(50) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`roleid`),
  KEY `account_roles_accountid_foreign` (`accountid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `account_taxes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `account_taxes` (
  `taxid` varchar(6) NOT NULL,
  `accountid` varchar(10) NOT NULL,
  `tax_name` varchar(100) DEFAULT NULL,
  `rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `type` varchar(20) NOT NULL DEFAULT 'GST',
  `description` varchar(255) DEFAULT NULL,
  `sequence` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`taxid`),
  KEY `account_taxes_accountid_foreign` (`accountid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `account_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `account_templates` (
  `templateid` varchar(6) NOT NULL,
  `accountid` varchar(10) NOT NULL,
  `template_type` enum('pi','ti','quotation','renewal','expiry','reminder','payment_received') NOT NULL DEFAULT 'pi',
  `channel` enum('email','whatsapp','sms') NOT NULL,
  `name` varchar(120) NOT NULL,
  `template_id` varchar(120) DEFAULT NULL,
  `meta_template_id` varchar(160) DEFAULT NULL,
  `sender_id` varchar(120) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `body` text NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`templateid`),
  UNIQUE KEY `account_templates_unique_per_context` (`accountid`,`channel`,`template_type`),
  KEY `account_templates_accountid_channel_template_type_index` (`accountid`,`channel`,`template_type`),
  KEY `account_templates_sms_sender_idx` (`accountid`,`channel`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `account_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `account_users` (
  `userid` varchar(6) NOT NULL,
  `shiftid` varchar(6) DEFAULT NULL,
  `att_policyid` varchar(6) DEFAULT NULL,
  `leave_policyid` varchar(10) DEFAULT NULL,
  `accountid` varchar(10) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `depid` varchar(10) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `roleid` varchar(10) DEFAULT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`permissions`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `designation` varchar(255) DEFAULT NULL,
  `gender` varchar(255) DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`userid`),
  KEY `account_users_roleid_foreign` (`roleid`),
  KEY `account_users_depid_foreign` (`depid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `accounts` (
  `accountid` varchar(10) NOT NULL,
  `name` varchar(150) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `allow_sync` tinyint(1) NOT NULL DEFAULT 0,
  `expires_at` date DEFAULT NULL,
  `legal_name` varchar(150) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `tax_number` varchar(50) DEFAULT NULL,
  `website` varchar(150) DEFAULT NULL,
  `currency_code` varchar(3) NOT NULL DEFAULT 'INR',
  `timezone` varchar(64) NOT NULL DEFAULT 'Asia/Kolkata',
  `fy_startdate` varchar(10) DEFAULT NULL COMMENT 'MM-DD',
  `address_line_1` varchar(150) DEFAULT NULL,
  `address_line_2` varchar(150) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `allow_multi_taxation` tinyint(1) NOT NULL DEFAULT 0,
  `have_users` tinyint(1) NOT NULL DEFAULT 0,
  `has_team_management` tinyint(1) NOT NULL DEFAULT 0,
  `fixed_tax_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `fixed_tax_type` varchar(20) NOT NULL DEFAULT 'GST',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`accountid`),
  UNIQUE KEY `accounts_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `attendance_policies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `attendance_policies` (
  `att_policyid` varchar(6) NOT NULL,
  `accountid` varchar(10) NOT NULL,
  `policy_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `late_arrival_grace` int(11) NOT NULL DEFAULT 0,
  `early_departure_grace` int(11) NOT NULL DEFAULT 0,
  `overtime_rate` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`att_policyid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` bigint(20) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` bigint(20) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `client_billing_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `client_billing_details` (
  `bd_id` varchar(6) NOT NULL,
  `accountid` varchar(10) NOT NULL,
  `business_name` varchar(150) DEFAULT NULL,
  `gstin` varchar(15) DEFAULT NULL,
  `billing_email` varchar(200) DEFAULT NULL,
  `address_line_1` varchar(150) DEFAULT NULL,
  `address_line_2` varchar(150) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `billing_phone` varchar(100) DEFAULT NULL,
  `country` varchar(100) NOT NULL DEFAULT 'India',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`bd_id`),
  KEY `client_billing_details_accountid_foreign` (`accountid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `client_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `client_categories` (
  `categoryid` varchar(6) NOT NULL,
  `accountid` varchar(10) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `sequence` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`categoryid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `client_contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `client_contacts` (
  `contactid` varchar(6) NOT NULL,
  `accountid` varchar(10) NOT NULL,
  `clientid` varchar(10) NOT NULL,
  `name` varchar(150) NOT NULL,
  `phone` varchar(100) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `designation` varchar(100) DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`contactid`),
  KEY `client_contacts_accountid_clientid_index` (`accountid`,`clientid`),
  KEY `client_contacts_clientid_is_primary_index` (`clientid`,`is_primary`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `client_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `client_documents` (
  `client_docid` varchar(6) NOT NULL,
  `accountid` varchar(10) NOT NULL,
  `clientid` varchar(10) NOT NULL,
  `type` varchar(20) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `title` varchar(150) DEFAULT NULL,
  `document_number` varchar(100) DEFAULT NULL,
  `document_date` date DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`client_docid`),
  KEY `client_documents_lookup_idx` (`accountid`,`clientid`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `clients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `clients` (
  `clientid` varchar(10) NOT NULL,
  `accountid` varchar(10) NOT NULL,
  `bd_id` varchar(6) DEFAULT NULL,
  `groupid` varchar(6) DEFAULT NULL,
  `categoryid` varchar(6) DEFAULT NULL,
  `business_name` varchar(150) DEFAULT NULL,
  `primary_email` varchar(150) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `type` varchar(20) NOT NULL DEFAULT 'regular',
  `phone` varchar(50) DEFAULT NULL,
  `whatsapp_number` varchar(50) DEFAULT NULL,
  `tax_number` varchar(50) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `currency` varchar(3) NOT NULL DEFAULT 'INR',
  `address_line_1` varchar(150) DEFAULT NULL,
  `address_line_2` varchar(150) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`clientid`),
  KEY `clients_accountid_status_index` (`accountid`,`status`),
  KEY `clients_bd_id_foreign` (`bd_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `communication_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `communication_logs` (
  `logid` varchar(6) NOT NULL,
  `accountid` varchar(10) NOT NULL,
  `invoiceid` varchar(6) DEFAULT NULL,
  `quotationid` varchar(6) DEFAULT NULL,
  `clientid` varchar(10) DEFAULT NULL,
  `from_email` varchar(255) DEFAULT NULL,
  `to_email` varchar(500) DEFAULT NULL,
  `cc_email` varchar(500) DEFAULT NULL,
  `phone_number` varchar(50) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `body` longtext DEFAULT NULL,
  `attachment_type` varchar(50) DEFAULT NULL,
  `attachment_path` text DEFAULT NULL,
  `custom_attachment_path` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'draft',
  `channel` varchar(20) NOT NULL DEFAULT 'email',
  `created_by` varchar(12) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`logid`),
  KEY `communication_logs_accountid_channel_index` (`accountid`,`channel`),
  KEY `communication_logs_invoiceid_channel_attachment_type_index` (`invoiceid`,`channel`,`attachment_type`),
  KEY `communication_logs_quotationid_channel_index` (`quotationid`,`channel`),
  KEY `communication_logs_invoiceid_index` (`invoiceid`),
  KEY `communication_logs_quotationid_index` (`quotationid`),
  KEY `communication_logs_clientid_index` (`clientid`),
  KEY `communication_logs_status_index` (`status`),
  KEY `communication_logs_channel_index` (`channel`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `currency`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `currency` (
  `iso` char(3) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '',
  `name` varchar(200) NOT NULL,
  PRIMARY KEY (`iso`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `financial_year`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `financial_year` (
  `fy_id` varchar(6) NOT NULL,
  `accountid` varchar(10) NOT NULL,
  `financial_year` varchar(20) NOT NULL,
  `default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `duration_months` int(10) unsigned NOT NULL DEFAULT 12,
  PRIMARY KEY (`fy_id`),
  KEY `financial_year_accountid_financial_year_index` (`accountid`,`financial_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `groups` (
  `groupid` varchar(6) NOT NULL,
  `accountid` varchar(10) DEFAULT NULL,
  `group_name` varchar(150) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `registered_address` varchar(150) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `country` varchar(100) NOT NULL DEFAULT 'India',
  `business_address` varchar(150) DEFAULT NULL,
  `business_city` varchar(100) DEFAULT NULL,
  `business_state` varchar(100) DEFAULT NULL,
  `business_postal_code` varchar(20) DEFAULT NULL,
  `business_country` varchar(100) NOT NULL DEFAULT 'India',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`groupid`),
  KEY `groups_accountid_foreign` (`accountid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `invoice_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoice_items` (
  `invoice_itemid` varchar(6) NOT NULL,
  `invoiceid` varchar(6) DEFAULT NULL,
  `orderid` varchar(6) DEFAULT NULL,
  `accountid` varchar(10) DEFAULT NULL,
  `clientid` varchar(10) DEFAULT NULL,
  `itemid` varchar(6) DEFAULT NULL,
  `item_name` varchar(150) DEFAULT NULL,
  `item_description` text DEFAULT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT 1.00,
  `unit_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tax_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `discount_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `duration` int(11) DEFAULT NULL,
  `frequency` varchar(20) DEFAULT NULL,
  `no_of_users` int(11) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `sequence` int(10) unsigned NOT NULL DEFAULT 1,
  PRIMARY KEY (`invoice_itemid`),
  KEY `pi_items_proformaid_foreign` (`invoiceid`),
  KEY `invoice_items_status_end_date_idx` (`status`,`end_date`),
  KEY `invoice_items_orderid_index` (`orderid`),
  KEY `invoice_items_client_order_end_idx` (`clientid`,`orderid`,`end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoices` (
  `invoiceid` varchar(6) NOT NULL,
  `accountid` varchar(10) DEFAULT NULL,
  `fy_id` varchar(20) DEFAULT NULL,
  `clientid` varchar(10) DEFAULT NULL,
  `pi_number` varchar(30) DEFAULT NULL,
  `ti_number` varchar(30) DEFAULT NULL,
  `invoice_title` varchar(255) DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `payment_status` varchar(20) NOT NULL DEFAULT 'unpaid',
  `issue_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `terms` text DEFAULT NULL,
  `created_by` varchar(10) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`invoiceid`),
  UNIQUE KEY `pi_number` (`pi_number`),
  UNIQUE KEY `ti_number` (`ti_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `item_costings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `item_costings` (
  `costingid` varchar(6) NOT NULL,
  `accountid` varchar(10) DEFAULT NULL,
  `itemid` varchar(6) DEFAULT NULL,
  `currency_code` varchar(3) DEFAULT NULL,
  `cost_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `selling_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tax_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `tax_included` varchar(10) NOT NULL DEFAULT '0',
  `sac_code` varchar(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`costingid`),
  UNIQUE KEY `service_costings_serviceid_currency_code_unique` (`itemid`,`currency_code`),
  KEY `service_costings_accountid_serviceid_index` (`accountid`,`itemid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `items` (
  `itemid` varchar(6) NOT NULL,
  `accountid` varchar(10) DEFAULT NULL,
  `ps_catid` varchar(6) DEFAULT NULL,
  `type` enum('product','service') NOT NULL DEFAULT 'service',
  `sync` enum('yes','no') NOT NULL DEFAULT 'no',
  `user_wise` tinyint(1) NOT NULL DEFAULT 0,
  `name` varchar(150) DEFAULT NULL,
  `sequence` int(10) unsigned NOT NULL DEFAULT 0,
  `description` text DEFAULT NULL,
  `grace_period` int(10) unsigned NOT NULL DEFAULT 0,
  `addons` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`addons`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`itemid`),
  KEY `services_accountid_billing_type_index` (`accountid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `leave_policies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `leave_policies` (
  `leave_policyid` varchar(10) NOT NULL,
  `accountid` varchar(10) NOT NULL,
  `typeid` varchar(10) DEFAULT NULL,
  `policy_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `carry_forward_limit` int(11) NOT NULL DEFAULT 0,
  `min_days_per_application` int(11) NOT NULL DEFAULT 1,
  `max_days_per_application` int(11) NOT NULL DEFAULT 0,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `is_paid` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`leave_policyid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `leave_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `leave_types` (
  `typeid` varchar(10) NOT NULL,
  `accountid` varchar(10) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`typeid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ledger`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ledger` (
  `ledgerid` varchar(6) NOT NULL,
  `accountid` varchar(10) NOT NULL,
  `clientid` varchar(10) NOT NULL,
  `date` date NOT NULL,
  `invoiceid_paymentid` varchar(20) NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `type` enum('dr','cr') NOT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `mode` varchar(20) DEFAULT NULL,
  `reference_number` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`ledgerid`),
  UNIQUE KEY `ledger_invoice_payment_type_unique` (`invoiceid_paymentid`,`type`),
  KEY `ledger_accountid_date_index` (`accountid`,`date`),
  KEY `ledger_clientid_date_index` (`clientid`,`date`),
  KEY `ledger_type_status_index` (`type`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `order_timeline`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_timeline` (
  `timelineid` varchar(6) NOT NULL,
  `accountid` varchar(10) NOT NULL,
  `clientid` varchar(10) NOT NULL,
  `orderid` varchar(6) NOT NULL,
  `action_type` varchar(50) NOT NULL,
  `field_name` varchar(50) DEFAULT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `description` text NOT NULL,
  `created_by` varchar(12) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`timelineid`),
  KEY `order_timeline_accountid_orderid_index` (`accountid`,`orderid`),
  KEY `order_timeline_clientid_created_at_index` (`clientid`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `orders` (
  `orderid` varchar(6) NOT NULL,
  `accountid` varchar(10) NOT NULL,
  `clientid` varchar(10) NOT NULL,
  `order_number` varchar(30) NOT NULL,
  `status` varchar(20) DEFAULT NULL,
  `type` varchar(20) NOT NULL DEFAULT 'regular',
  `client_docid` varchar(6) DEFAULT NULL,
  `itemid` varchar(6) DEFAULT NULL,
  `item_name` varchar(150) NOT NULL,
  `item_description` text DEFAULT NULL,
  `quantity` int(10) unsigned NOT NULL DEFAULT 1,
  `no_of_users` int(10) unsigned DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `delivery_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`orderid`),
  UNIQUE KEY `orders_new_order_number_unique` (`order_number`),
  KEY `orders_new_accountid_status_index` (`accountid`,`status`),
  KEY `orders_new_clientid_created_at_index` (`clientid`,`created_at`),
  KEY `orders_accountid_type_index` (`accountid`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `payment_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_details` (
  `detailid` varchar(20) NOT NULL,
  `accountid` varchar(10) NOT NULL,
  `clientid` varchar(10) NOT NULL,
  `paymentid` varchar(6) NOT NULL,
  `invoiceid` varchar(6) NOT NULL,
  `received_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tds_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`detailid`),
  UNIQUE KEY `payment_details_payment_invoice_unique` (`paymentid`,`invoiceid`),
  KEY `payment_details_accountid_index` (`accountid`),
  KEY `payment_details_clientid_index` (`clientid`),
  KEY `payment_details_paymentid_index` (`paymentid`),
  KEY `payment_details_invoiceid_index` (`invoiceid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments` (
  `paymentid` varchar(6) NOT NULL,
  `accountid` varchar(10) NOT NULL,
  `fy_id` varchar(6) DEFAULT NULL,
  `clientid` varchar(10) NOT NULL,
  `receipt_number` varchar(100) DEFAULT NULL,
  `received_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tds_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tds_input_type` varchar(20) DEFAULT NULL,
  `payment_date` date NOT NULL,
  `mode` varchar(30) NOT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`paymentid`),
  KEY `payments_clientid_foreign` (`clientid`),
  KEY `payments_accountid_status_index` (`accountid`),
  KEY `payments_account_receipt_idx` (`accountid`,`receipt_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ps_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ps_categories` (
  `ps_catid` varchar(6) NOT NULL,
  `accountid` varchar(10) NOT NULL,
  `name` varchar(150) NOT NULL,
  `sequence` int(10) unsigned NOT NULL DEFAULT 0,
  `description` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`ps_catid`),
  KEY `ps_categories_accountid_status_index` (`accountid`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `quotation_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `quotation_items` (
  `quo_itemid` varchar(6) NOT NULL,
  `quotationid` varchar(6) NOT NULL,
  `orderid` varchar(6) DEFAULT NULL,
  `accountid` varchar(10) DEFAULT NULL,
  `clientid` varchar(10) DEFAULT NULL,
  `itemid` varchar(6) DEFAULT NULL,
  `item_name` varchar(150) NOT NULL,
  `item_description` text DEFAULT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT 1.00,
  `unit_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tax_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `discount_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `duration` int(11) DEFAULT NULL,
  `frequency` varchar(20) DEFAULT NULL,
  `no_of_users` int(11) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `sequence` int(10) unsigned NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`quo_itemid`),
  KEY `estimate_items_estimateid_foreign` (`quotationid`),
  KEY `quotation_items_itemid_foreign` (`itemid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `quotations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `quotations` (
  `quotationid` varchar(6) NOT NULL,
  `accountid` varchar(10) NOT NULL,
  `fy_id` varchar(6) DEFAULT NULL,
  `clientid` varchar(10) NOT NULL,
  `quo_number` varchar(30) NOT NULL,
  `quo_title` varchar(255) DEFAULT NULL,
  `status` varchar(20) NOT NULL,
  `issue_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `terms` text DEFAULT NULL,
  `created_by` varchar(10) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`quotationid`),
  KEY `estimates_clientid_foreign` (`clientid`),
  KEY `estimates_created_by_foreign` (`created_by`),
  KEY `estimates_accountid_status_index` (`accountid`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `serial_configurations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `serial_configurations` (
  `serial_configid` varchar(6) NOT NULL,
  `accountid` varchar(10) NOT NULL,
  `document_type` varchar(255) NOT NULL,
  `prefix_show` tinyint(4) NOT NULL DEFAULT 1,
  `number_show` tinyint(4) NOT NULL DEFAULT 1,
  `suffix_show` tinyint(4) NOT NULL DEFAULT 1,
  `config_name` varchar(255) DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `prefix_type` varchar(255) NOT NULL DEFAULT 'manual text',
  `prefix_value` varchar(255) DEFAULT NULL,
  `prefix_length` varchar(255) DEFAULT NULL,
  `prefix_separator` varchar(255) NOT NULL DEFAULT 'none',
  `number_type` varchar(255) NOT NULL DEFAULT 'auto increment',
  `number_value` varchar(255) DEFAULT NULL,
  `number_length` varchar(255) DEFAULT NULL,
  `number_separator` varchar(255) NOT NULL DEFAULT 'none',
  `suffix_type` varchar(255) NOT NULL DEFAULT 'manual text',
  `suffix_value` varchar(255) DEFAULT NULL,
  `suffix_length` varchar(255) DEFAULT NULL,
  `serial_mode` varchar(255) NOT NULL DEFAULT 'sequential',
  `reset_on_fy` tinyint(1) NOT NULL DEFAULT 0,
  `fy_id` varchar(6) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`serial_configid`),
  KEY `serial_configurations_accountid_foreign` (`accountid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` varchar(10) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `settingid` varchar(6) NOT NULL,
  `accountid` varchar(10) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`settingid`),
  UNIQUE KEY `settings_accountid_setting_key_unique` (`accountid`,`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `shifts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `shifts` (
  `shiftid` varchar(6) NOT NULL,
  `accountid` varchar(36) NOT NULL,
  `shift_name` varchar(255) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `break_duration` int(11) NOT NULL DEFAULT 0 COMMENT 'Break duration in minutes',
  `break_start_time` time DEFAULT NULL,
  `break_end_time` time DEFAULT NULL,
  `break_grace_period` int(11) NOT NULL DEFAULT 0 COMMENT 'Break grace period in minutes',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`shiftid`),
  KEY `shifts_accountid_index` (`accountid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `terms_conditions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `terms_conditions` (
  `tc_id` varchar(6) NOT NULL,
  `accountid` varchar(10) NOT NULL,
  `type` enum('billing','quotation','proforma') NOT NULL,
  `content` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `sequence` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`tc_id`),
  KEY `terms_conditions_accountid_type_index` (`accountid`,`type`),
  KEY `terms_account_type_default_idx` (`accountid`,`type`,`is_default`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users_doc`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users_doc` (
  `docid` varchar(6) NOT NULL,
  `profileid` varchar(6) NOT NULL,
  `doc_type` varchar(255) NOT NULL,
  `doc_path` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`docid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users_profile`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users_profile` (
  `profileid` varchar(6) NOT NULL,
  `accountid` varchar(10) DEFAULT NULL,
  `userid` varchar(6) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `state` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `zip_code` varchar(255) DEFAULT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  `account_name` varchar(255) DEFAULT NULL,
  `account_number` varchar(255) DEFAULT NULL,
  `routing_code` varchar(255) DEFAULT NULL,
  `bank_branch` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `reviewed_by` varchar(6) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`profileid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

/*M!999999\- enable the sandbox mode */ 
SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'0001_01_01_000000_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2026_03_24_160500_create_billing_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2026_03_27_060425_update_clients_table_add_billing_fields',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2026_03_27_100526_update_services_table_add_price_fields',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2026_03_30_000000_restructure_client_billing_details',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2026_03_30_000001_create_groups_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2026_03_30_120600_create_service_costings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2024_10_15_000000_create_product_categories_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'financial_year',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'accounts',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'tax_included_servingcostings',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'client_details',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'update_clients_details',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'service_sequence',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2026_03_31_120000_create_service_addons_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2026_03_31_120100_create_service_addon_costings_table',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2026_04_01_054440_create_account_billing_and_quotation_details_tables',11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20,'2026_04_01_060000_add_address_fields_to_billing_and_quotation_details',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2026_04_01_070000_add_numbering_settings_to_details_tables',13);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2026_04_02_100000_add_serial_config_to_account_billing_and_quotation_details_tables',14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2026_04_03_120000_fix_serial_number_configuration',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24,'2026_04_03_130000_rename_estimates_to_quotations',16);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25,'2026_04_03_150000_create_terms_conditions_table',17);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26,'2026_04_04_000000_add_type_sync_to_services_table',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27,'2026_04_04_010000_add_signatory_fields_to_account_billing_details_table',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28,'2026_04_04_020000_add_signatory_fields_to_account_quotation_details_table',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29,'2026_04_03_040232_add_flexible_serial_config_to_billing_and_quotation_details_tables',20);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30,'2026_04_03_050504_add_length_to_prefix_and_suffix_in_serial_configs',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31,'2026_04_03_060000_add_fy_id_to_invoices_and_quotations_tables',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32,'2026_04_04_040000_add_separator_columns_to_billing_and_quotation_details',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (33,'2026_04_04_050000_rename_services_to_items_and_add_addons_json',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34,'2026_04_04_060000_create_orders_tables',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (35,'2026_04_04_105531_add_fields_to_orders_table',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (36,'2026_04_04_111034_move_fields_to_order_items_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (37,'2026_04_04_112156_add_duration_to_order_items_table',28);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (38,'2026_04_06_045622_add_dates_to_order_items_table',29);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (39,'2026_04_06_100000_create_account_taxes_table',30);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (40,'2026_04_06_094326_add_taxid_to_costing_and_item_tables',31);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (41,'2026_04_07_000000_add_invoice_type_to_invoices_table',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (42,'2026_03_30_121601_add_flexible_fields_to_financial_year_table',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (43,'2026_03_30_131500_add_sac_code_to_service_costings_table',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (44,'2026_03_30_153000_add_sequence_to_services_and_ps_categories_tables',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (45,'2026_03_30_200000_create_financial_year_table',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (46,'2026_03_31_054125_update_financial_year_column_length',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (47,'2026_04_03_052020_add_start_values_to_serial_configs',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (48,'2026_04_03_052853_drop_start_values_from_serial_configs',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (49,'2026_04_04_030000_add_separator_columns_to_financial_year_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (50,'2026_03_33_095054_change_tax_included_to_string_in_service_costings',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (51,'2026_03_33_093033_remove_redundant_pricing_columns_from_services_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (53,'2026_03_33_074057_restructure_financial_year_logic',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (54,'2026_03_31_074057_restructure_financial_year_logic',34);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (56,'2026_03_31_093031_remove_redundant_pricing_columns_from_services_table',34);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (58,'2026_03_31_074057_restructure_financial_year_logic',34);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (60,'2026_03_31_093031_remove_redundant_pricing_columns_from_services_table',34);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (62,'2026_03_34_090355_add_tax_included_to_service_costings_table',34);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (63,'2026_03_31_090355_add_tax_included_to_service_costings_table',34);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (64,'2026_03_31_095054_change_tax_included_to_string_in_service_costings',34);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (65,'2026_04_07_000001_add_invoice_for_to_invoices_table',35);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (66,'2026_04_07_000002_add_orderid_to_invoices_table',36);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (67,'2026_04_07_000003_add_order_fields_to_invoice_items_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (69,'2026_04_09_120000_add_converted_from_invoiceid_to_invoices_table',38);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (70,'2026_04_09_130000_create_serial_configurations_table',39);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (71,'2026_04_09_072642_add_invoice_title_to_invoices_table',40);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (72,'2026_04_09_100000_add_multi_taxation_and_users_flags_to_accounts_table',41);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (73,'2026_04_09_100001_add_fixed_tax_rate_to_accounts_table',42);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (74,'2026_04_09_100002_add_fixed_tax_type_to_accounts_table',43);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (77,'2026_04_10_120000_split_proforma_and_tax_invoice_tables',44);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (78,'2026_04_10_130000_make_serial_number_nullable_in_billing_and_quotation_details',45);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (79,'2026_04_10_140000_make_billing_name_nullable_in_details_tables',46);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (80,'2026_04_13_060805_add_renewed_to_proformaid_to_pi_items_table',47);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (81,'2026_04_13_071816_add_renewed_from_to_pi_items_table',48);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (82,'2026_04_13_141821_fix_invoice_status_default',49);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (83,'2026_04_14_125611_add_serial_parts_show_columns_to_serial_configurations_table',50);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (84,'2026_04_14_131902_add_tax_total_to_orders_table',51);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (85,'2026_04_14_150000_add_po_and_agreement_fields_to_orders_table',52);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (86,'2026_04_14_154851_add_discount_columns_to_order_items_table',53);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (87,'2026_04_14_155000_drop_all_foreign_keys',54);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (88,'2026_04_14_174758_add_is_verified_to_orders_table',55);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (89,'2026_04_15_000001_add_user_wise_to_items_table',56);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (90,'2026_04_16_120000_add_discount_columns_to_invoice_item_tables',57);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (91,'2026_04_24_124700_make_ti_number_nullable_on_invoices',58);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (92,'2026_04_24_183000_drop_legacy_serial_columns_from_account_details',59);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (93,'2026_04_30_120000_add_is_default_to_terms_conditions_table',60);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (94,'2026_04_30_170000_create_invoice_emails_table',61);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (95,'2026_05_01_121500_update_payments_for_received_and_tds',62);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (96,'2026_05_02_100000_create_message_templates_table',63);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (97,'2026_05_02_120000_add_channel_and_phone_to_invoice_emails_table',64);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (98,'2026_05_02_145528_add_proforma_type_to_terms_conditions_table',65);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (99,'2026_05_04_170000_add_sms_sender_id_to_account_templates_table',66);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (100,'2026_05_05_120000_add_more_template_types',67);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (101,'2026_05_05_130000_add_channel_template_config_to_account_templates',68);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (102,'2026_05_06_153000_allow_multiple_templates_per_channel_type',69);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (103,'2026_05_06_170000_add_template_tracking_to_invoice_emails_table',70);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (104,'2026_05_06_171500_simplify_invoice_email_template_tracking',71);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (105,'2026_05_06_181000_drop_templateid_from_invoice_emails',72);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (106,'2026_05_06_182000_enforce_single_template_per_context',73);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (107,'2026_05_07_121500_add_reminder_automation_to_accounts_table',74);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (108,'2026_05_07_130000_update_payments_tds_flag_and_description',75);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (109,'2026_05_08_120000_create_ledger_table',76);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (110,'2026_05_08_130000_add_status_to_invoice_items_table',77);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (111,'2026_05_11_160000_update_ledger_reference_and_type_columns',78);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (112,'2026_05_11_170000_update_payments_type_and_ledger_mode_reference',79);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (113,'2026_05_11_180000_add_fy_id_to_payments_table',80);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (114,'2026_05_11_190000_add_payment_status_to_invoices_table',81);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (115,'2026_05_11_200000_create_client_documents_table',82);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (116,'2026_05_11_200100_drop_po_and_agreement_columns_from_orders_table',82);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (117,'2026_05_11_200200_rebuild_client_documents_with_client_docid',83);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (118,'2026_05_12_120000_collapse_orders_into_single_table',84);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (119,'2026_05_12_130000_add_title_to_client_documents_table',85);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (120,'2026_05_12_131000_add_status_to_client_documents_table',86);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (122,'2026_05_12_132000_normalize_client_document_status_values',87);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (123,'2026_05_13_121000_add_orderid_to_invoice_items_table',88);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (124,'2026_05_13_000001_add_status_to_ledger_and_normalize_payment_status',89);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (125,'2026_05_15_164000_drop_taxid_from_item_costings_table',90);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (126,'2026_05_19_110029_create_account_credentials_table',91);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (127,'2026_05_19_111500_drop_auth_columns_from_accounts_table',91);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (128,'2026_05_19_090000_add_cc_email_to_invoice_emails_table',92);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (129,'2026_05_20_120000_backfill_sort_order_on_invoice_items',93);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (130,'2026_05_21_000001_align_quotation_tables_with_invoice_schema',94);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (131,'2026_05_21_000000_create_quotation_emails_table',95);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (132,'2026_05_21_170237_rename_invoice_title_to_quo_title_on_quotations_table',96);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (133,'2026_05_25_000001_drop_payment_status_from_quotations_table',97);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (134,'2026_05_25_000002_add_quotation_to_account_templates_type_enum',98);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (135,'2026_05_25_000003_create_communication_logs_table',99);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (136,'2026_05_25_000004_backfill_communication_logs_from_existing_email_tables',99);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (137,'2026_05_25_000005_drop_legacy_email_tables',99);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (138,'2026_05_25_000006_drop_sent_at_from_communication_logs_table',100);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (139,'2026_05_25_180000_add_allow_sync_and_expires_at_to_accounts_table',101);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (140,'2026_05_25_235959_rename_users_to_account_users',102);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (141,'2026_05_26_000001_add_department_to_users_table',102);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (142,'2026_05_26_000002_add_profile_and_permissions_to_users_table',102);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (143,'2026_05_26_000003_add_profile_image_to_account_users_table',103);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (144,'2026_05_26_000004_change_userid_length_to_6_on_account_users',104);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (145,'2026_05_26_000006_change_clientid_length_to_10_everywhere',105);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (146,'2026_05_26_000007_add_primary_email_to_clients_table',106);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (147,'2026_05_26_000008_add_type_to_clients_table',107);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (148,'2026_05_26_000008_drop_unique_primary_email_on_clients',107);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (149,'2026_05_28_000009_drop_reminder_automation_columns_from_accounts_table',108);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (150,'2026_05_29_120000_drop_orderid_from_invoices_table',109);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (151,'2026_05_29_180000_add_grace_period_to_items_table',109);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (152,'2026_05_29_190000_rename_communication_logid_to_logid',110);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (153,'2026_05_30_090000_create_payment_details_and_tds_amount_for_payments',111);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (154,'2026_06_01_120000_add_receipt_number_to_payments_table',112);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (155,'2026_06_02_000001_move_account_credentials_to_account_users',113);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (156,'2026_06_03_000000_add_tds_input_type_to_payments_table',114);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (157,'2026_06_10_110157_add_type_to_orders_table',115);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (158,'2026_06_10_121500_create_client_contacts_table',116);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (159,'2026_06_10_143627_update_groups_table_for_registered_and_business_addresses',117);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (160,'2026_06_10_165536_create_order_timeline_table',118);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (161,'2026_06_10_170502_modify_order_timeline_table',119);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (162,'2026_06_23_114440_add_superadmin_productid_to_items_table',120);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (163,'2026_06_24_093229_create_client_categories_table',121);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (164,'2026_06_24_093256_add_categoryid_to_clients_table',121);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (165,'2026_06_24_094351_add_sequence_to_client_categories_table',122);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (166,'2026_06_24_124541_remove_foreign_keys_from_client_categories_and_clients_tables',123);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (167,'2026_06_25_130333_create_account_roles_table',124);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (168,'2026_06_25_130341_create_account_departments_table',124);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (169,'2026_06_25_130529_add_roleid_depid_to_account_users_table',124);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (170,'2026_06_25_141302_remove_designation_from_account_users',125);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (171,'2026_06_25_174211_drop_columns_and_fks_from_account_users_table',126);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (172,'2026_06_26_095300_drop_slug_from_accounts_table',127);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (173,'2026_06_26_155500_drop_accountid_foreign_keys_from_roles_and_departments',128);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (174,'2026_06_29_153136_add_has_team_management_to_accounts_table',129);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (176,'2026_06_29_154204_create_team_employees_table',130);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (177,'2026_06_29_162355_create_attendance_policies_table',131);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (178,'2026_06_29_164712_create_shifts_table',132);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (179,'2026_06_29_165016_add_policy_and_shift_to_team_employees_table',133);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (180,'2026_06_30_092722_add_auth_columns_to_team_employees_table',134);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (181,'2026_06_30_093346_change_employee_policy_shift_ids_to_alphanumeric',135);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (182,'2026_06_30_100323_add_shift_and_policy_to_account_users_table',136);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (183,'2026_06_30_100324_drop_team_employees_table',136);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (184,'2026_06_30_121941_create_user_profiles_table',137);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (185,'2026_06_30_131812_create_users_doc_table',138);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (186,'2026_06_30_143529_rename_id_to_docid_in_users_doc_table',139);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (187,'2026_06_30_143859_change_docid_to_string_in_users_doc_table',140);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (188,'2026_06_30_155644_create_leave_policies_table',141);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (189,'2026_06_30_162647_create_leave_applications_table',142);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (190,'2026_06_30_162648_create_attendance_logs_table',142);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (191,'2026_06_30_162952_alter_columns_in_leave_and_attendance_tables',143);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (192,'2026_06_30_163552_add_leave_policyid_to_account_users_table',144);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (193,'2026_06_30_164238_drop_leave_policyid_columns',145);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (194,'2026_06_30_173442_create_leave_types_table',146);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (195,'2026_06_30_173525_add_typeid_to_leave_policies_table',146);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (196,'2026_06_30_173558_add_leave_policyid_to_account_users_table',146);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (197,'2026_06_30_174509_add_is_paid_to_leave_policies_table',147);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (198,'2026_06_30_175333_add_designation_and_gender_to_account_users_table',148);
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
