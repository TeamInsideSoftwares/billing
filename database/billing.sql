-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Apr 23, 2026 at 07:10 AM
-- Server version: 8.4.7
-- PHP Version: 8.4.15

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `billing`
--

-- --------------------------------------------------------

--
-- Table structure for table `accounts`
--

DROP TABLE IF EXISTS `accounts`;
CREATE TABLE IF NOT EXISTS `accounts` (
  `accountid` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `legal_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tax_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `website` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currency_code` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'INR',
  `timezone` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Asia/Kolkata',
  `fy_startdate` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_line_1` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_line_2` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `postal_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logo_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `allow_multi_taxation` tinyint(1) NOT NULL DEFAULT '0',
  `have_users` tinyint(1) NOT NULL DEFAULT '0',
  `fixed_tax_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `fixed_tax_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'GST',
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`accountid`),
  UNIQUE KEY `accounts_slug_unique` (`slug`),
  UNIQUE KEY `accounts_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `accounts`
--

INSERT INTO `accounts` (`accountid`, `name`, `slug`, `status`, `legal_name`, `email`, `password`, `phone`, `tax_number`, `website`, `currency_code`, `timezone`, `fy_startdate`, `address_line_1`, `address_line_2`, `city`, `state`, `postal_code`, `country`, `logo_path`, `allow_multi_taxation`, `have_users`, `fixed_tax_rate`, `fixed_tax_type`, `remember_token`, `created_at`, `updated_at`) VALUES
('ACC0000001', 'Test', 'test', 'active', 'SkoolReady', 'team@insidesoftwares.com', '$2y$12$v5s/yjkQvMInf9qMVr/TX.vg5r.tcY9rerTdCYbdR.GrEy1iqnCvq', '4152414141', NULL, NULL, 'INR', 'Asia/Kolkata', '04-01', 'Shanti Vihar', NULL, 'Dehradun', 'Uttarakhand', '248001', 'India', 'storage/logos/LU4ZmBhUIu6dDY17qis4M2CHoIpCPtiaIWalDT0p.png', 0, 1, 18.00, 'GST', NULL, '2026-03-31 05:14:54', '2026-04-22 10:16:28');

-- --------------------------------------------------------

--
-- Table structure for table `account_billing_details`
--

DROP TABLE IF EXISTS `account_billing_details`;
CREATE TABLE IF NOT EXISTS `account_billing_details` (
  `account_bdid` varchar(6) COLLATE utf8mb4_unicode_ci NOT NULL,
  `accountid` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `billing_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `city` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `postal_code` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gstin` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tin` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `authorize_signatory` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `signature_upload` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `billing_from_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`account_bdid`),
  KEY `account_billing_details_accountid_index` (`accountid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `account_billing_details`
--

INSERT INTO `account_billing_details` (`account_bdid`, `accountid`, `billing_name`, `address`, `city`, `state`, `country`, `postal_code`, `gstin`, `tin`, `authorize_signatory`, `signature_upload`, `billing_from_email`, `created_at`, `updated_at`) VALUES
('VREEJ8', 'ACC0000001', 'Inside Softwares', 'Address 1:\r\nBuilding Number: 17\r\nStreet Name: Lodhi Estate\r\nStreet Address: 48, Lodhi Road, Near Sai Baba Mandir', 'New Delhi', 'Delhi', 'India', '110003', '415263', '1245 7878 9696', NULL, 'signatures/1775201011_Ims_logo_new .png', 'test@gmail.com', '2026-04-01 04:10:27', '2026-04-09 00:05:54');

-- --------------------------------------------------------

--
-- Table structure for table `account_quotation_details`
--

DROP TABLE IF EXISTS `account_quotation_details`;
CREATE TABLE IF NOT EXISTS `account_quotation_details` (
  `account_qdid` varchar(6) COLLATE utf8mb4_unicode_ci NOT NULL,
  `accountid` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quotation_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `city` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `postal_code` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gstin` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tin` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `authorize_signatory` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `signature_upload` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `billing_from_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`account_qdid`),
  KEY `account_quotation_details_accountid_index` (`accountid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `account_quotation_details`
--

INSERT INTO `account_quotation_details` (`account_qdid`, `accountid`, `quotation_name`, `address`, `city`, `state`, `country`, `postal_code`, `gstin`, `tin`, `authorize_signatory`, `signature_upload`, `billing_from_email`, `created_at`, `updated_at`) VALUES
('7WWKSX', 'ACC0000001', 'Inside Softwares[]', 'Address 1:\r\nBuilding Number: 17\r\nStreet Name: Lodhi Estate\r\nStreet Address: 48, Lodhi Road, Near Sai Baba Mandir', 'New Delhi', 'Delhi', 'India', '248001', '415263', '1245 7878 9696', 'INSIDE SOFTWARES', 'signatures/1775201790_axis-bank.png', 'test@gmail.com', '2026-04-01 04:16:03', '2026-04-07 06:20:06');

-- --------------------------------------------------------

--
-- Table structure for table `account_taxes`
--

DROP TABLE IF EXISTS `account_taxes`;
CREATE TABLE IF NOT EXISTS `account_taxes` (
  `taxid` varchar(6) COLLATE utf8mb4_unicode_ci NOT NULL,
  `accountid` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tax_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'GST',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sequence` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`taxid`),
  KEY `account_taxes_accountid_foreign` (`accountid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `account_taxes`
--

INSERT INTO `account_taxes` (`taxid`, `accountid`, `tax_name`, `rate`, `type`, `description`, `sequence`, `is_active`, `created_at`, `updated_at`) VALUES
('TAX4VU', 'ACC0000001', NULL, 28.00, 'GST', NULL, 4, 1, '2026-04-06 01:27:29', '2026-04-06 01:27:29'),
('TAX9SC', 'ACC0000001', NULL, 15.00, 'GST', NULL, 3, 1, '2026-04-06 01:27:11', '2026-04-06 01:27:11'),
('TAXCPQ', 'ACC0000001', NULL, 5.00, 'VAT', NULL, 6, 1, '2026-04-06 01:27:50', '2026-04-06 01:27:50'),
('TAXGEG', 'ACC0000001', NULL, 0.00, 'VAT', NULL, 5, 1, '2026-04-06 01:27:42', '2026-04-06 01:27:42'),
('TAXKFH', 'ACC0000001', NULL, 5.00, 'GST', NULL, 2, 1, '2026-04-06 01:27:03', '2026-04-06 23:45:03'),
('TAXLAT', 'ACC0000001', NULL, 28.00, 'VAT', NULL, 8, 1, '2026-04-06 01:28:04', '2026-04-06 01:28:04'),
('TAXNX5', 'ACC0000001', NULL, 15.00, 'VAT', NULL, 7, 1, '2026-04-06 01:27:55', '2026-04-06 01:27:55'),
('TAXYF5', 'ACC0000001', 'GST', 0.00, 'GST', NULL, 1, 1, '2026-04-06 01:12:48', '2026-04-06 01:12:55');

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
CREATE TABLE IF NOT EXISTS `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cache`
--

INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES
('laravel-cache-SYSTEM_DOMAIN_POINTED_DIRECTORY_98ccea17052d8529c114ec99030a7e97', 's:6:\"public\";', 2092041739);

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
CREATE TABLE IF NOT EXISTS `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

DROP TABLE IF EXISTS `clients`;
CREATE TABLE IF NOT EXISTS `clients` (
  `clientid` varchar(6) COLLATE utf8mb4_unicode_ci NOT NULL,
  `accountid` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `bd_id` varchar(6) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `groupid` varchar(6) COLLATE utf8mb4_unicode_ci NOT NULL,
  `business_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `whatsapp_number` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `billing_email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tax_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'INR',
  `address_line_1` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_line_2` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `postal_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `logo_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`clientid`),
  KEY `clients_accountid_status_index` (`accountid`,`status`),
  KEY `clients_bd_id_foreign` (`bd_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `clients`
--

INSERT INTO `clients` (`clientid`, `accountid`, `bd_id`, `groupid`, `business_name`, `contact_name`, `email`, `phone`, `whatsapp_number`, `billing_email`, `tax_number`, `status`, `currency`, `address_line_1`, `address_line_2`, `city`, `state`, `postal_code`, `country`, `notes`, `created_at`, `updated_at`, `logo_path`) VALUES
('NKI0GP', 'ACC0000001', 'MSCUIR', 'PGI0QX', 'Term Foundation', 'Amit', 'testing@insidesoftwares.com', NULL, '5241524152', NULL, NULL, 'active', 'INR', 'Address 1: \r\nBuilding Number: 9 \r\nStreet Name: Rowdon Street \r\nAddress: 4, Hungerford', NULL, 'Bangalore Rural', 'Karnataka', '248001', 'India', NULL, '2026-04-02 06:37:11', '2026-04-17 11:54:36', NULL),
('V642MR', 'ACC0000001', 'HPZESM', 'PGI0QX', 'Testing Foundation', 'Shubham', 'team@insidesoftwares.com', '4152414141', '5241524152', NULL, NULL, 'active', 'INR', 'Shanti Vihar, Kanwali', NULL, 'Dehradun', 'Uttarakhand', '248001', 'India', NULL, '2026-03-31 05:37:59', '2026-04-04 06:39:42', 'http://alpha.insidesoftwares.com/billing.skoolready.com/public/storage/logos/IbSgZv98VmSyvHIODX1cssdYUHjxmSHSDXkXOXWY.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `client_billing_details`
--

DROP TABLE IF EXISTS `client_billing_details`;
CREATE TABLE IF NOT EXISTS `client_billing_details` (
  `bd_id` varchar(6) COLLATE utf8mb4_unicode_ci NOT NULL,
  `accountid` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `business_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `gstin` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `billing_email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_line_1` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_line_2` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `postal_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `billing_phone` int NOT NULL,
  `country` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'India',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`bd_id`),
  KEY `client_billing_details_accountid_foreign` (`accountid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `client_billing_details`
--

INSERT INTO `client_billing_details` (`bd_id`, `accountid`, `business_name`, `gstin`, `billing_email`, `address_line_1`, `address_line_2`, `city`, `state`, `postal_code`, `billing_phone`, `country`, `created_at`, `updated_at`) VALUES
('HPZESM', 'ACC0000001', 'Testing Foundation', NULL, 'team@insidesoftwares.com', 'Shanti Vihar, Kanwali', NULL, 'Dehradun', 'Uttarakhand', NULL, 0, 'India', '2026-03-31 05:37:59', '2026-04-21 08:50:42'),
('MSCUIR', 'ACC0000001', 'Term Foundation', '1245784578', 'testing@insidesoftwares.com', 'Address 1: \r\nBuilding Number: 9 \r\nStreet Name: Rowdon Street \r\nAddress: 4, Hungerford', NULL, NULL, 'Karnataka', NULL, 1243123, 'India', '2026-04-02 06:37:11', '2026-04-06 23:59:58');

-- --------------------------------------------------------

--
-- Table structure for table `currency`
--

DROP TABLE IF EXISTS `currency`;
CREATE TABLE IF NOT EXISTS `currency` (
  `iso` char(3) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '',
  `name` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`iso`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Dumping data for table `currency`
--

INSERT INTO `currency` (`iso`, `name`) VALUES
('KRW', '(South) Korean Won'),
('AFA', 'Afghanistan Afghani'),
('ALL', 'Albanian Lek'),
('DZD', 'Algerian Dinar'),
('ADP', 'Andorran Peseta'),
('AOK', 'Angolan Kwanza'),
('ARS', 'Argentine Peso'),
('AMD', 'Armenian Dram'),
('AWG', 'Aruban Florin'),
('AUD', 'Australian Dollar'),
('BSD', 'Bahamian Dollar'),
('BHD', 'Bahraini Dinar'),
('BDT', 'Bangladeshi Taka'),
('BBD', 'Barbados Dollar'),
('BZD', 'Belize Dollar'),
('BMD', 'Bermudian Dollar'),
('BTN', 'Bhutan Ngultrum'),
('BOB', 'Bolivian Boliviano'),
('BWP', 'Botswanian Pula'),
('BRL', 'Brazilian Real'),
('GBP', 'British Pound'),
('BND', 'Brunei Dollar'),
('BGN', 'Bulgarian Lev'),
('BUK', 'Burma Kyat'),
('BIF', 'Burundi Franc'),
('CAD', 'Canadian Dollar'),
('CVE', 'Cape Verde Escudo'),
('KYD', 'Cayman Islands Dollar'),
('CLP', 'Chilean Peso'),
('CLF', 'Chilean Unidades de Fomento'),
('COP', 'Colombian Peso'),
('XOF', 'Communauté Financière Africaine BCEAO - Francs'),
('XAF', 'Communauté Financière Africaine BEAC, Francs'),
('KMF', 'Comoros Franc'),
('XPF', 'Comptoirs Français du Pacifique Francs'),
('CRC', 'Costa Rican Colon'),
('CUP', 'Cuban Peso'),
('CYP', 'Cyprus Pound'),
('CZK', 'Czech Republic Koruna'),
('DKK', 'Danish Krone'),
('YDD', 'Democratic Yemeni Dinar'),
('DOP', 'Dominican Peso'),
('XCD', 'East Caribbean Dollar'),
('TPE', 'East Timor Escudo'),
('ECS', 'Ecuador Sucre'),
('EGP', 'Egyptian Pound'),
('SVC', 'El Salvador Colon'),
('EEK', 'Estonian Kroon (EEK)'),
('ETB', 'Ethiopian Birr'),
('EUR', 'Euro'),
('FKP', 'Falkland Islands Pound'),
('FJD', 'Fiji Dollar'),
('GMD', 'Gambian Dalasi'),
('GHC', 'Ghanaian Cedi'),
('GIP', 'Gibraltar Pound'),
('XAU', 'Gold, Ounces'),
('GTQ', 'Guatemalan Quetzal'),
('GNF', 'Guinea Franc'),
('GWP', 'Guinea-Bissau Peso'),
('GYD', 'Guyanan Dollar'),
('HTG', 'Haitian Gourde'),
('HNL', 'Honduran Lempira'),
('HKD', 'Hong Kong Dollar'),
('HUF', 'Hungarian Forint'),
('INR', 'Indian Rupee'),
('IDR', 'Indonesian Rupiah'),
('XDR', 'International Monetary Fund (IMF) Special Drawing Rights'),
('IRR', 'Iranian Rial'),
('IQD', 'Iraqi Dinar'),
('IEP', 'Irish Punt'),
('ILS', 'Israeli Shekel'),
('JMD', 'Jamaican Dollar'),
('JPY', 'Japanese Yen'),
('JOD', 'Jordanian Dinar'),
('KHR', 'Kampuchean (Cambodian) Riel'),
('KES', 'Kenyan Schilling'),
('KWD', 'Kuwaiti Dinar'),
('LAK', 'Lao Kip'),
('LBP', 'Lebanese Pound'),
('LSL', 'Lesotho Loti'),
('LRD', 'Liberian Dollar'),
('LYD', 'Libyan Dinar'),
('MOP', 'Macau Pataca'),
('MGF', 'Malagasy Franc'),
('MWK', 'Malawi Kwacha'),
('MYR', 'Malaysian Ringgit'),
('MVR', 'Maldive Rufiyaa'),
('MTL', 'Maltese Lira'),
('MRO', 'Mauritanian Ouguiya'),
('MUR', 'Mauritius Rupee'),
('MXP', 'Mexican Peso'),
('MNT', 'Mongolian Tugrik'),
('MAD', 'Moroccan Dirham'),
('MZM', 'Mozambique Metical'),
('NAD', 'Namibian Dollar'),
('NPR', 'Nepalese Rupee'),
('ANG', 'Netherlands Antillian Guilder'),
('YUD', 'New Yugoslavia Dinar'),
('NZD', 'New Zealand Dollar'),
('NIO', 'Nicaraguan Cordoba'),
('NGN', 'Nigerian Naira'),
('KPW', 'North Korean Won'),
('NOK', 'Norwegian Kroner'),
('OMR', 'Omani Rial'),
('PKR', 'Pakistan Rupee'),
('XPD', 'Palladium Ounces'),
('PAB', 'Panamanian Balboa'),
('PGK', 'Papua New Guinea Kina'),
('PYG', 'Paraguay Guarani'),
('PEN', 'Peruvian Nuevo Sol'),
('PHP', 'Philippine Peso'),
('XPT', 'Platinum, Ounces'),
('PLN', 'Polish Zloty'),
('QAR', 'Qatari Rial'),
('RON', 'Romanian Leu'),
('RUB', 'Russian Ruble'),
('RWF', 'Rwanda Franc'),
('WST', 'Samoan Tala'),
('STD', 'Sao Tome and Principe Dobra'),
('SAR', 'Saudi Arabian Riyal'),
('SCR', 'Seychelles Rupee'),
('SLL', 'Sierra Leone Leone'),
('XAG', 'Silver, Ounces'),
('SGD', 'Singapore Dollar'),
('SKK', 'Slovak Koruna'),
('SBD', 'Solomon Islands Dollar'),
('SOS', 'Somali Schilling'),
('ZAR', 'South African Rand'),
('LKR', 'Sri Lanka Rupee'),
('SHP', 'St. Helena Pound'),
('SDP', 'Sudanese Pound'),
('SRG', 'Suriname Guilder'),
('SZL', 'Swaziland Lilangeni'),
('SEK', 'Swedish Krona'),
('CHF', 'Swiss Franc'),
('SYP', 'Syrian Potmd'),
('TWD', 'Taiwan Dollar'),
('TZS', 'Tanzanian Schilling'),
('THB', 'Thai Baht'),
('TOP', 'Tongan Paanga'),
('TTD', 'Trinidad and Tobago Dollar'),
('TND', 'Tunisian Dinar'),
('TRY', 'Turkish Lira'),
('UGX', 'Uganda Shilling'),
('AED', 'United Arab Emirates Dirham'),
('UYU', 'Uruguayan Peso'),
('USD', 'US Dollar'),
('VUV', 'Vanuatu Vatu'),
('VEF', 'Venezualan Bolivar'),
('VND', 'Vietnamese Dong'),
('YER', 'Yemeni Rial'),
('CNY', 'Yuan (Chinese) Renminbi'),
('ZRZ', 'Zaire Zaire'),
('ZMK', 'Zambian Kwacha'),
('ZWD', 'Zimbabwe Dollar');

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
CREATE TABLE IF NOT EXISTS `failed_jobs` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `financial_year`
--

DROP TABLE IF EXISTS `financial_year`;
CREATE TABLE IF NOT EXISTS `financial_year` (
  `fy_id` varchar(6) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `accountid` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `financial_year` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `default` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `duration_months` int UNSIGNED NOT NULL DEFAULT '12',
  PRIMARY KEY (`fy_id`),
  KEY `financial_year_accountid_financial_year_index` (`accountid`,`financial_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `financial_year`
--

INSERT INTO `financial_year` (`fy_id`, `accountid`, `financial_year`, `default`, `created_at`, `updated_at`, `duration_months`) VALUES
('BKISUK', 'ACC0000001', '2026-2027', 1, '2026-03-31 05:27:04', '2026-04-07 06:19:54', 12),
('CH68XY', 'ACC0000001', '2027-2028', 0, '2026-04-03 00:34:05', '2026-04-07 06:19:54', 12);

-- --------------------------------------------------------

--
-- Table structure for table `groups`
--

DROP TABLE IF EXISTS `groups`;
CREATE TABLE IF NOT EXISTS `groups` (
  `groupid` varchar(6) COLLATE utf8mb4_unicode_ci NOT NULL,
  `accountid` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `group_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_line_1` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_line_2` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `postal_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'India',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`groupid`),
  KEY `groups_accountid_foreign` (`accountid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `groups`
--

INSERT INTO `groups` (`groupid`, `accountid`, `group_name`, `email`, `address_line_1`, `address_line_2`, `city`, `state`, `postal_code`, `country`, `created_at`, `updated_at`) VALUES
('5YUCYV', 'ACC0000001', 'TT[]', 'TT@insidesoftwares.com', 'Shanti Vihar, Kanwali', NULL, NULL, NULL, '248001', 'India', '2026-04-06 23:49:31', '2026-04-18 05:25:16'),
('PGI0QX', 'ACC0000001', 'Test Foundation', 'team@insidesoftwares.com', 'Shanti Vihar, Kanwali', NULL, NULL, NULL, '248001', 'India', '2026-03-31 05:37:28', '2026-04-18 05:25:07');

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

DROP TABLE IF EXISTS `invoices`;
CREATE TABLE IF NOT EXISTS `invoices` (
  `invoiceid` varchar(6) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `accountid` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fy_id` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `clientid` varchar(6) COLLATE utf8mb4_unicode_ci NOT NULL,
  `orderid` varchar(6) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pi_number` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ti_number` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `invoice_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `issue_date` date NOT NULL,
  `due_date` date NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `terms` text COLLATE utf8mb4_unicode_ci,
  `created_by` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`invoiceid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`invoiceid`, `accountid`, `fy_id`, `clientid`, `orderid`, `pi_number`, `ti_number`, `invoice_title`, `status`, `issue_date`, `due_date`, `notes`, `terms`, `created_by`, `created_at`, `updated_at`) VALUES
('8MKDHC', 'ACC0000001', NULL, 'NKI0GP', 'W0BZQL', 'PI-CA3F-2026', '0', '', 'draft', '2026-04-22', '2026-04-29', NULL, NULL, NULL, '2026-04-22 05:24:36', '2026-04-22 05:24:36'),
('B9BWEF', 'ACC0000001', NULL, 'NKI0GP', NULL, 'PI-AQEU-2026', '0', '', 'draft', '2026-04-23', '2026-04-30', NULL, NULL, NULL, '2026-04-23 04:45:46', '2026-04-23 04:45:46'),
('CH2GDK', 'ACC0000001', NULL, 'V642MR', NULL, 'PI-XPWG-2026', '0', 'EMS+Journeys+3 Page Website', 'draft', '2026-04-22', '2026-04-29', NULL, NULL, NULL, '2026-04-22 04:57:53', '2026-04-22 04:57:53'),
('DSNBS7', 'ACC0000001', NULL, 'NKI0GP', 'UNWV46', 'PI-LU69-2026', '0', 'EMS', 'draft', '2026-04-23', '2026-04-30', NULL, NULL, NULL, '2026-04-23 05:29:38', '2026-04-23 05:29:38'),
('FNOMSP', 'ACC0000001', NULL, 'NKI0GP', NULL, 'PI-DAJM-2026', '0', 'tttestt', 'draft', '2026-04-23', '2026-04-30', NULL, NULL, NULL, '2026-04-23 04:46:39', '2026-04-23 04:46:39'),
('HAKXG0', 'ACC0000001', NULL, 'V642MR', 'FBXIZD', 'PI-MR4R-2026', '0', '', 'draft', '2026-04-23', '2026-04-30', NULL, NULL, NULL, '2026-04-23 04:26:14', '2026-04-23 04:26:14'),
('IAMMHJ', 'ACC0000001', NULL, 'V642MR', 'WOKYBP', 'PI-PSXN-2026', '0', 'EMS+Journeys', 'draft', '2026-04-22', '2026-04-29', NULL, NULL, NULL, '2026-04-22 06:24:47', '2026-04-22 06:24:47'),
('NCXC29', 'ACC0000001', NULL, 'NKI0GP', 'ALX4UN', 'INV/1001/2026', '0', 'By Orders', 'unpaid', '2026-04-10', '2026-04-17', NULL, NULL, NULL, '2026-04-10 00:43:21', '2026-04-15 11:00:50'),
('PXQSTP', 'ACC0000001', NULL, 'NKI0GP', NULL, 'PI-33KU-2026', '0', 'Without Orders', 'unpaid', '2026-04-13', '2026-04-20', NULL, NULL, NULL, '2026-04-13 01:32:56', '2026-04-22 05:14:23'),
('UMYNVP', 'ACC0000001', NULL, 'NKI0GP', NULL, 'PI-2GXE-2026', '0', 'Renewal PI from other invoices', 'unpaid', '2026-04-13', '2026-04-20', NULL, NULL, NULL, '2026-04-13 01:13:51', '2026-04-13 01:14:36'),
('WJNE9G', 'ACC0000001', NULL, 'NKI0GP', NULL, 'PI-5R8D-2026', '0', 'Renewal PI', 'unpaid', '2026-04-13', '2026-04-20', NULL, NULL, NULL, '2026-04-12 23:15:27', '2026-04-13 01:32:03');

-- --------------------------------------------------------

--
-- Table structure for table `invoice_items`
--

DROP TABLE IF EXISTS `invoice_items`;
CREATE TABLE IF NOT EXISTS `invoice_items` (
  `invoice_itemid` varchar(6) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `invoiceid` varchar(6) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `accountid` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `clientid` varchar(6) COLLATE utf8mb4_unicode_ci NOT NULL,
  `itemid` varchar(6) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `item_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `item_description` text COLLATE utf8mb4_unicode_ci,
  `quantity` decimal(10,2) NOT NULL DEFAULT '1.00',
  `unit_price` decimal(12,2) NOT NULL DEFAULT '0.00',
  `tax_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `discount_percent` decimal(5,2) NOT NULL DEFAULT '0.00',
  `discount_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `duration` int DEFAULT NULL,
  `frequency` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `no_of_users` int DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`invoice_itemid`),
  KEY `pi_items_proformaid_foreign` (`invoiceid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `invoice_items`
--

INSERT INTO `invoice_items` (`invoice_itemid`, `invoiceid`, `accountid`, `clientid`, `itemid`, `item_name`, `item_description`, `quantity`, `unit_price`, `tax_rate`, `discount_percent`, `discount_amount`, `duration`, `frequency`, `no_of_users`, `start_date`, `end_date`, `amount`, `created_at`, `updated_at`) VALUES
('2JCUDC', 'NCXC29', '', '', 'JNBIE9', '3 Page Website', NULL, 1.00, 14000.00, 18.00, 0.00, 0.00, NULL, 'daily', 1, '2026-04-09', '2026-04-10', 14000.00, '2026-04-15 11:00:50', '2026-04-15 11:00:50'),
('2NVIWV', 'WJNE9G', '', '', 'EMQTP5', 'Website Maintenance', NULL, 1.00, 12.00, 1.00, 0.00, 0.00, 1, 'one-time', 1, NULL, NULL, 12.00, '2026-04-13 01:32:03', '2026-04-13 01:32:03'),
('4LMEAA', 'NCXC29', '', '', '1JCMEB', 'Content Management System (CMS)', NULL, 1.00, 19000.00, 18.00, 0.00, 0.00, NULL, 'yearly', 1, '2026-04-27', '2026-05-01', 19000.00, '2026-04-15 11:00:50', '2026-04-15 11:00:50'),
('6BDGLZ', 'PXQSTP', '', '', 'V9IFFZ', 'EMS(Enrollment Management System)', NULL, 1.00, 22000.00, 18.00, 10.00, 8800.00, 2, 'yearly', 2, '2026-04-16', '2028-04-15', 88000.00, '2026-04-22 05:32:03', '2026-04-22 05:32:03'),
('AKLBQX', '5DXUVF', '', '', 'JNBIE9', '3 Page Website', NULL, 1.00, 14000.00, 18.00, 10.00, 1400.00, 1, 'daily', NULL, '2026-04-13', '2026-04-14', 14000.00, '2026-04-18 11:25:14', '2026-04-18 11:25:14'),
('B2MSHS', 'CH2GDK', '', '', 'JNBIE9', '3 Page Website', NULL, 1.00, 14000.00, 18.00, 10.00, 1400.00, NULL, 'one-time', NULL, NULL, NULL, 14000.00, '2026-04-22 04:57:53', '2026-04-22 04:57:53'),
('BFWLLP', 'FNOMSP', '', '', 'JNBIE9', '3 Page Website', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua', 1.00, 14000.00, 18.00, 10.00, 1400.00, NULL, 'one-time', NULL, NULL, NULL, 14000.00, '2026-04-23 04:46:39', '2026-04-23 04:46:39'),
('CXHDUR', 'WJNE9G', '', '', 'JNBIE9', '3 Page Website', NULL, 1.00, 14000.00, 18.00, 0.00, 0.00, 1, 'daily', 1, '2026-04-05', '2026-04-07', 14000.00, '2026-04-13 01:32:03', '2026-04-13 01:32:56'),
('FESVJE', 'DSNBS7', '', '', 'V9IFFZ', 'EMS(Enrollment Management System)', '', 1.00, 22000.00, 18.00, 10.00, 4400.00, 2, 'yearly', 1, '2026-04-15', '2028-04-14', 44000.00, '2026-04-23 05:29:38', '2026-04-23 05:29:38'),
('FQM3XI', 'TPBSAM', '', '', '1JCMEB', 'Content Management System', NULL, 1.00, 20000.00, 5.00, 0.00, 0.00, 2, 'yearly', 2, '2026-04-13', '2028-04-13', 80000.00, '2026-04-13 00:44:54', '2026-04-13 00:44:54'),
('G1PR4T', 'TPBSAM', '', '', 'JNBIE9', '3 Page Website', NULL, 1.00, 14000.00, 18.00, 0.00, 0.00, 1, 'daily', 1, '2026-04-13', '2026-04-14', 14000.00, '2026-04-13 00:44:54', '2026-04-13 00:44:54'),
('HFZD8G', 'WFPHJD', '', '', 'JNBIE9', '3 Page Website', NULL, 1.00, 14000.00, 18.00, 0.00, 0.00, NULL, 'daily', 1, '2026-04-09', '2026-04-10', 14000.00, '2026-04-22 11:42:54', '2026-04-22 11:42:54'),
('IUDUPE', '8MKDHC', '', '', 'V9IFFZ', 'EMS(Enrollment Management System)', NULL, 1.00, 22000.00, 18.00, 10.00, 8800.00, 2, 'yearly', 2, '2026-04-16', '2028-04-15', 88000.00, '2026-04-22 05:24:39', '2026-04-22 05:24:39'),
('JILFZY', 'CH2GDK', '', '', 'V9IFFZ', 'EMS(Enrollment Management System)', NULL, 1.00, 22000.00, 18.00, 10.00, 4400.00, 2, 'yearly', 1, '2026-05-20', '2028-05-19', 44000.00, '2026-04-22 04:57:53', '2026-04-22 04:57:53'),
('KRBPRC', 'IAMMHJ', '', '', 'V9IFFZ', 'EMS(Enrollment Management System)', 'EMS with user 1 and duration is 2 years', 1.00, 22000.00, 18.00, 10.00, 4400.00, 2, 'yearly', 1, '2026-05-20', '2028-05-19', 44000.00, '2026-04-22 12:31:23', '2026-04-22 12:31:23'),
('MJTKFI', 'FNOMSP', '', '', 'VSM00B', 'Customer Relationship Management', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua', 1.00, 123422.00, 18.00, 10.00, 12342.00, NULL, 'one-time', 1, NULL, NULL, 123422.00, '2026-04-23 04:46:39', '2026-04-23 04:46:39'),
('OKFK5G', '5DXUVF', '', '', 'VSM00B', 'Customer Relationship Management(CRM)', NULL, 1.00, 12000.00, 18.00, 10.00, 1200.00, 1, 'yearly', 1, '2025-04-02', '2026-04-02', 12000.00, '2026-04-18 11:25:14', '2026-04-18 11:25:14'),
('QVLQHB', '8MKDHC', '', '', '1JCMEB', 'Content Management System (CMS)', NULL, 1.00, 20000.00, 18.00, 10.00, 4000.00, 1, 'yearly', 2, '2026-04-16', '2027-04-15', 40000.00, '2026-04-22 05:24:39', '2026-04-22 05:24:39'),
('SD5JXJ', 'PXQSTP', '', '', '1JCMEB', 'Content Management System (CMS)', NULL, 1.00, 20000.00, 18.00, 10.00, 4000.00, 1, 'yearly', 2, '2026-04-16', '2027-04-15', 40000.00, '2026-04-22 05:32:03', '2026-04-22 05:32:03'),
('SGS0AT', 'HAKXG0', '', '', 'EMQTP5', 'Website Maintenance', NULL, 1.00, 12.00, 18.00, 0.00, 0.00, 3, 'monthly', 1, '2026-04-14', '2026-07-13', 36.00, '2026-04-23 04:26:14', '2026-04-23 04:26:14'),
('SWFESC', 'B9BWEF', '', '', 'JNBIE9', '3 Page Website', NULL, 1.00, 14000.00, 18.00, 0.00, 0.00, NULL, 'daily', 1, '2026-04-09', '2026-04-10', 14000.00, '2026-04-23 04:45:46', '2026-04-23 04:45:46'),
('SY7G1T', 'CH2GDK', '', '', 'CE4CRH', 'Journeys', NULL, 1.00, 25000.00, 18.00, 5.00, 2500.00, 2, 'yearly', NULL, '2026-05-20', '2028-05-19', 50000.00, '2026-04-22 04:57:53', '2026-04-22 04:57:53'),
('VSELBV', 'TV7CFM', '', '', 'V9IFFZ', 'EMS(Enrollment Management System)', NULL, 1.00, 22000.00, 18.00, 10.00, 4400.00, 2, 'yearly', 1, '2026-04-15', '2028-04-14', 44000.00, '2026-04-21 12:23:27', '2026-04-21 12:23:27'),
('WXK7TN', 'WFPHJD', '', '', '1JCMEB', 'Content Management System', NULL, 1.00, 20000.00, 5.00, 0.00, 0.00, 1, 'weekly', 2, '2026-04-05', '2026-04-06', 40000.00, '2026-04-22 11:42:54', '2026-04-22 11:42:54'),
('WXUKJI', 'IAMMHJ', '', '', 'CE4CRH', 'Journeys', 'create journeys and keep track of all emails and whatsapp', 1.00, 25000.00, 18.00, 5.00, 2500.00, 2, 'yearly', NULL, '2026-05-20', '2028-05-19', 50000.00, '2026-04-22 12:31:23', '2026-04-22 12:31:23'),
('XSTEWA', 'UMYNVP', '', '', '1JCMEB', 'Content Management System', NULL, 1.00, 20000.00, 5.00, 0.00, 0.00, 3, 'yearly', 2, '2026-04-13', '2029-04-13', 120000.00, '2026-04-13 01:14:36', '2026-04-13 01:14:36'),
('Z0PVUF', 'WJNE9G', '', '', '1JCMEB', 'Content Management System', NULL, 1.00, 20000.00, 5.00, 0.00, 0.00, 1, 'weekly', 2, '2026-04-05', '2026-04-06', 40000.00, '2026-04-13 01:32:03', '2026-04-13 01:32:03');

-- --------------------------------------------------------

--
-- Table structure for table `items`
--

DROP TABLE IF EXISTS `items`;
CREATE TABLE IF NOT EXISTS `items` (
  `itemid` varchar(6) COLLATE utf8mb4_unicode_ci NOT NULL,
  `accountid` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ps_catid` varchar(6) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `service_code` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` enum('product','service') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'service',
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'no',
  `user_wise` tinyint(1) NOT NULL DEFAULT '0',
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sequence` int UNSIGNED NOT NULL DEFAULT '0',
  `description` text COLLATE utf8mb4_unicode_ci,
  `addons` json DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `taxid` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`itemid`),
  UNIQUE KEY `services_service_code_unique` (`service_code`),
  KEY `services_accountid_billing_type_index` (`accountid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `items`
--

INSERT INTO `items` (`itemid`, `accountid`, `ps_catid`, `service_code`, `type`, `sync`, `user_wise`, `name`, `sequence`, `description`, `addons`, `is_active`, `created_at`, `updated_at`, `taxid`) VALUES
('1JCMEB', 'ACC0000001', 'SIRC3B', NULL, 'product', 'yes', 1, 'Content Management System (CMS)', 2, 'A content management system (CMS) is a computer software used to manage the creation and modification of digital content (content management). It is typically used for enterprise content management (ECM).', '[]', 1, '2026-03-31 22:40:51', '2026-04-16 05:11:22', NULL),
('CE4CRH', 'ACC0000001', 'SIRC3B', NULL, 'product', 'yes', 0, 'Journeys', 6, 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua', '[\"VSM00B\"]', 1, '2026-04-16 05:13:22', '2026-04-22 10:13:34', NULL),
('EDYHDB', 'ACC0000001', 'BG9L7D', NULL, 'service', 'no', 0, 'Term', 5, 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua', '[\"JNBIE9\"]', 1, '2026-04-06 04:35:11', '2026-04-22 10:18:25', NULL),
('EMQTP5', 'ACC0000001', 'BG9L7D', NULL, 'service', 'no', 0, 'Website Maintenance', 2, 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua', '[\"JNBIE9\"]', 1, '2026-04-03 06:20:35', '2026-04-22 10:18:18', NULL),
('JHKW5I', 'ACC0000001', 'RHPSCA', NULL, 'product', 'yes', 1, 'Test', 1, 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua', '[\"1JCMEB\", \"V9IFFZ\", \"EMQTP5\"]', 1, '2026-04-03 06:12:27', '2026-04-22 10:18:28', NULL),
('JNBIE9', 'ACC0000001', 'BG9L7D', NULL, 'service', 'no', 0, '3 Page Website', 1, 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua', '[]', 1, '2026-04-04 02:03:34', '2026-04-22 10:18:14', NULL),
('V9IFFZ', 'ACC0000001', 'SIRC3B', NULL, 'product', 'yes', 1, 'EMS(Enrollment Management System)', 3, 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua', '[\"VSM00B\"]', 1, '2026-04-02 06:38:34', '2026-04-22 10:13:15', NULL),
('VSM00B', 'ACC0000001', 'SIRC3B', NULL, 'product', 'no', 1, 'Customer Relationship Management(CRM)', 1, 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua', '[]', 1, '2026-03-31 05:50:20', '2026-04-22 10:13:04', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `item_costings`
--

DROP TABLE IF EXISTS `item_costings`;
CREATE TABLE IF NOT EXISTS `item_costings` (
  `costingid` varchar(6) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `accountid` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `itemid` varchar(6) COLLATE utf8mb4_unicode_ci NOT NULL,
  `currency_code` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cost_price` decimal(12,2) NOT NULL DEFAULT '0.00',
  `selling_price` decimal(12,2) NOT NULL DEFAULT '0.00',
  `tax_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `taxid` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tax_included` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `sac_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`costingid`),
  UNIQUE KEY `service_costings_serviceid_currency_code_unique` (`itemid`,`currency_code`),
  KEY `service_costings_accountid_serviceid_index` (`accountid`,`itemid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `item_costings`
--

INSERT INTO `item_costings` (`costingid`, `accountid`, `itemid`, `currency_code`, `cost_price`, `selling_price`, `tax_rate`, `taxid`, `tax_included`, `sac_code`, `created_at`, `updated_at`) VALUES
('0XX5HU', 'ACC0000001', '3PAJVX', 'INR', 12.00, 12.00, 18.00, NULL, 'no', '12', '2026-04-15 08:56:08', '2026-04-15 08:56:08'),
('11P1A8', 'ACC0000001', 'K28HWJ', 'INR', 12.00, 855.00, 18.00, NULL, 'no', '1245', '2026-04-15 09:28:42', '2026-04-15 09:28:42'),
('8IKJQB', 'ACC0000001', '1JCMEB', 'INR', 21000.00, 20000.00, 18.00, NULL, 'no', '258369', '2026-04-16 05:11:22', '2026-04-16 05:11:22'),
('BKTONJ', 'ACC0000001', 'JNBIE9', 'INR', 15000.00, 14000.00, 18.00, NULL, '0', '748596', '2026-04-22 10:18:21', '2026-04-22 10:18:21'),
('C9Z1RT', 'ACC0000001', '76PGVS', 'INR', 222222.00, 222.00, 18.00, NULL, 'no', '2212', '2026-04-15 09:03:01', '2026-04-15 09:03:01'),
('CBYETJ', 'ACC0000001', 'JHKW5I', 'INR', 12.00, 12.00, 18.00, NULL, '0', '24252', '2026-04-22 11:10:16', '2026-04-22 11:10:16'),
('GYZ5NX', 'ACC0000001', '76PGVS', 'IDR', 212.00, 1212.00, 18.00, NULL, 'no', '1212', '2026-04-15 09:03:01', '2026-04-15 09:03:01'),
('IOG1DH', 'ACC0000001', 'VSM00B', 'INR', 12.00, 123422.00, 18.00, NULL, '0', '123223', '2026-04-22 11:11:53', '2026-04-22 11:11:53'),
('MJZX3T', 'ACC0000001', 'V9IFFZ', 'INR', 25000.00, 22000.00, 18.00, NULL, '0', '526352', '2026-04-22 10:13:15', '2026-04-22 10:13:15'),
('MQCQZO', 'ACC0000001', 'ZPBK8A', 'INR', 2000.00, 1500.00, 18.00, NULL, 'no', '1425', '2026-04-15 09:47:52', '2026-04-15 09:47:52'),
('O7WWZR', 'ACC0000001', 'EDYHDB', 'INR', 12000.00, 10000.00, 18.00, NULL, '0', '12', '2026-04-22 10:18:25', '2026-04-22 10:18:25'),
('OBGCO5', 'ACC0000001', 'B2OP9S', 'INR', 12.00, 12.00, 18.00, NULL, 'no', '12', '2026-04-15 08:58:48', '2026-04-15 08:58:48'),
('ORULON', 'ACC0000001', 'K28HWJ', 'IDR', 2323.00, 222.00, 18.00, NULL, 'no', '1245', '2026-04-15 09:28:42', '2026-04-15 09:28:42'),
('QYLF5E', 'ACC0000001', 'EDHJBK', 'INR', 12.00, 1212.00, 18.00, NULL, 'no', '24252', '2026-04-15 08:53:36', '2026-04-15 08:53:36'),
('RN7EYW', 'ACC0000001', 'VSM00B', 'USD', 1212.00, 11123411.00, 18.00, NULL, '0', '1121', '2026-04-22 11:11:53', '2026-04-22 11:11:53'),
('TLKYUS', 'ACC0000001', 'EMQTP5', 'INR', 12.00, 12.00, 18.00, NULL, '0', '12', '2026-04-22 10:18:18', '2026-04-22 10:18:18'),
('USVADJ', 'ACC0000001', 'CE4CRH', 'INR', 25000.00, 22000.00, 18.00, NULL, '0', '415263', '2026-04-22 10:18:08', '2026-04-22 10:18:08');

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
CREATE TABLE IF NOT EXISTS `jobs` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint UNSIGNED NOT NULL,
  `reserved_at` int UNSIGNED DEFAULT NULL,
  `available_at` int UNSIGNED NOT NULL,
  `created_at` int UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
CREATE TABLE IF NOT EXISTS `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
CREATE TABLE IF NOT EXISTS `migrations` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=91 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2026_03_24_160500_create_billing_tables', 1),
(5, '2026_03_27_060425_update_clients_table_add_billing_fields', 1),
(6, '2026_03_27_100526_update_services_table_add_price_fields', 1),
(7, '2026_03_30_000000_restructure_client_billing_details', 1),
(8, '2026_03_30_000001_create_groups_table', 1),
(9, '2026_03_30_120600_create_service_costings_table', 1),
(10, '2024_10_15_000000_create_product_categories_table', 2),
(11, 'financial_year', 3),
(12, 'accounts', 4),
(13, 'tax_included_servingcostings', 5),
(14, 'client_details', 6),
(15, 'update_clients_details', 7),
(16, 'service_sequence', 8),
(17, '2026_03_31_120000_create_service_addons_table', 9),
(18, '2026_03_31_120100_create_service_addon_costings_table', 10),
(19, '2026_04_01_054440_create_account_billing_and_quotation_details_tables', 11),
(20, '2026_04_01_060000_add_address_fields_to_billing_and_quotation_details', 12),
(21, '2026_04_01_070000_add_numbering_settings_to_details_tables', 13),
(22, '2026_04_02_100000_add_serial_config_to_account_billing_and_quotation_details_tables', 14),
(23, '2026_04_03_120000_fix_serial_number_configuration', 15),
(24, '2026_04_03_130000_rename_estimates_to_quotations', 16),
(25, '2026_04_03_150000_create_terms_conditions_table', 17),
(26, '2026_04_04_000000_add_type_sync_to_services_table', 18),
(27, '2026_04_04_010000_add_signatory_fields_to_account_billing_details_table', 18),
(28, '2026_04_04_020000_add_signatory_fields_to_account_quotation_details_table', 19),
(29, '2026_04_03_040232_add_flexible_serial_config_to_billing_and_quotation_details_tables', 20),
(30, '2026_04_03_050504_add_length_to_prefix_and_suffix_in_serial_configs', 21),
(31, '2026_04_03_060000_add_fy_id_to_invoices_and_quotations_tables', 22),
(32, '2026_04_04_040000_add_separator_columns_to_billing_and_quotation_details', 23),
(33, '2026_04_04_050000_rename_services_to_items_and_add_addons_json', 24),
(34, '2026_04_04_060000_create_orders_tables', 25),
(35, '2026_04_04_105531_add_fields_to_orders_table', 26),
(36, '2026_04_04_111034_move_fields_to_order_items_table', 27),
(37, '2026_04_04_112156_add_duration_to_order_items_table', 28),
(38, '2026_04_06_045622_add_dates_to_order_items_table', 29),
(39, '2026_04_06_100000_create_account_taxes_table', 30),
(40, '2026_04_06_094326_add_taxid_to_costing_and_item_tables', 31),
(41, '2026_04_07_000000_add_invoice_type_to_invoices_table', 32),
(42, '2026_03_30_121601_add_flexible_fields_to_financial_year_table', 32),
(43, '2026_03_30_131500_add_sac_code_to_service_costings_table', 32),
(44, '2026_03_30_153000_add_sequence_to_services_and_ps_categories_tables', 32),
(45, '2026_03_30_200000_create_financial_year_table', 32),
(46, '2026_03_31_054125_update_financial_year_column_length', 32),
(47, '2026_04_03_052020_add_start_values_to_serial_configs', 32),
(48, '2026_04_03_052853_drop_start_values_from_serial_configs', 32),
(49, '2026_04_04_030000_add_separator_columns_to_financial_year_table', 33),
(50, '2026_03_33_095054_change_tax_included_to_string_in_service_costings', 33),
(51, '2026_03_33_093033_remove_redundant_pricing_columns_from_services_table', 33),
(53, '2026_03_33_074057_restructure_financial_year_logic', 33),
(54, '2026_03_31_074057_restructure_financial_year_logic', 34),
(56, '2026_03_31_093031_remove_redundant_pricing_columns_from_services_table', 34),
(58, '2026_03_31_074057_restructure_financial_year_logic', 34),
(60, '2026_03_31_093031_remove_redundant_pricing_columns_from_services_table', 34),
(62, '2026_03_34_090355_add_tax_included_to_service_costings_table', 34),
(63, '2026_03_31_090355_add_tax_included_to_service_costings_table', 34),
(64, '2026_03_31_095054_change_tax_included_to_string_in_service_costings', 34),
(65, '2026_04_07_000001_add_invoice_for_to_invoices_table', 35),
(66, '2026_04_07_000002_add_orderid_to_invoices_table', 36),
(67, '2026_04_07_000003_add_order_fields_to_invoice_items_table', 37),
(69, '2026_04_09_120000_add_converted_from_invoiceid_to_invoices_table', 38),
(70, '2026_04_09_130000_create_serial_configurations_table', 39),
(71, '2026_04_09_072642_add_invoice_title_to_invoices_table', 40),
(72, '2026_04_09_100000_add_multi_taxation_and_users_flags_to_accounts_table', 41),
(73, '2026_04_09_100001_add_fixed_tax_rate_to_accounts_table', 42),
(74, '2026_04_09_100002_add_fixed_tax_type_to_accounts_table', 43),
(77, '2026_04_10_120000_split_proforma_and_tax_invoice_tables', 44),
(78, '2026_04_10_130000_make_serial_number_nullable_in_billing_and_quotation_details', 45),
(79, '2026_04_10_140000_make_billing_name_nullable_in_details_tables', 46),
(80, '2026_04_13_060805_add_renewed_to_proformaid_to_pi_items_table', 47),
(81, '2026_04_13_071816_add_renewed_from_to_pi_items_table', 48),
(82, '2026_04_13_141821_fix_invoice_status_default', 49),
(83, '2026_04_14_125611_add_serial_parts_show_columns_to_serial_configurations_table', 50),
(84, '2026_04_14_131902_add_tax_total_to_orders_table', 51),
(85, '2026_04_14_150000_add_po_and_agreement_fields_to_orders_table', 52),
(86, '2026_04_14_154851_add_discount_columns_to_order_items_table', 53),
(87, '2026_04_14_155000_drop_all_foreign_keys', 54),
(88, '2026_04_14_174758_add_is_verified_to_orders_table', 55),
(89, '2026_04_15_000001_add_user_wise_to_items_table', 56),
(90, '2026_04_16_120000_add_discount_columns_to_invoice_item_tables', 57);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
CREATE TABLE IF NOT EXISTS `orders` (
  `orderid` varchar(6) COLLATE utf8mb4_unicode_ci NOT NULL,
  `accountid` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fy_id` varchar(6) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `clientid` varchar(6) COLLATE utf8mb4_unicode_ci NOT NULL,
  `order_number` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `po_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `po_date` date DEFAULT NULL,
  `po_file` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `agreement_ref` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `agreement_date` date DEFAULT NULL,
  `agreement_file` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `order_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `order_date` date NOT NULL,
  `delivery_date` date DEFAULT NULL,
  `is_verified` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'no',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `terms` text COLLATE utf8mb4_unicode_ci,
  `created_by` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sales_person_id` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`orderid`),
  UNIQUE KEY `orders_order_number_unique` (`order_number`),
  KEY `orders_clientid_foreign` (`clientid`),
  KEY `orders_created_by_foreign` (`created_by`),
  KEY `orders_accountid_status_index` (`accountid`,`status`),
  KEY `orders_sales_person_id_foreign` (`sales_person_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`orderid`, `accountid`, `fy_id`, `clientid`, `order_number`, `po_number`, `po_date`, `po_file`, `agreement_ref`, `agreement_date`, `agreement_file`, `order_title`, `status`, `order_date`, `delivery_date`, `is_verified`, `notes`, `terms`, `created_by`, `sales_person_id`, `created_at`, `updated_at`) VALUES
('5XOC7U', 'ACC0000001', 'BKISUK', 'V642MR', '8JTCRQPB', '2342', '2026-04-29', 'orders/po/taYh7rS0JJrsP39mBLYErV0LjinGYrVueyyVC43C.pdf', '1233412', '2026-04-14', 'orders/agreements/1SzSYWw0mclp9M6sSzC8VM2VujvkydKvcKnQ77RM.docx', 'Text', '', '2026-04-14', '2026-04-29', 'no', NULL, NULL, NULL, NULL, '2026-04-14 09:17:45', '2026-04-14 12:19:36'),
('BH7SES', 'ACC0000001', NULL, 'V642MR', 'ORD-20260406-933', NULL, NULL, NULL, NULL, NULL, NULL, 'Maintenance', '', '2026-04-06', '2026-05-15', 'yes', NULL, NULL, NULL, NULL, '2026-04-06 06:08:23', '2026-04-14 12:12:40'),
('CBF58J', 'ACC0000001', 'BKISUK', 'NKI0GP', 'H2PA78JQ', NULL, NULL, NULL, NULL, NULL, NULL, 'School Website', '', '2026-04-15', NULL, 'no', NULL, NULL, NULL, NULL, '2026-04-15 05:04:40', '2026-04-15 06:33:08'),
('DIAXET', 'ACC0000001', 'BKISUK', 'NKI0GP', 'B8ACS8BB', NULL, NULL, NULL, NULL, NULL, NULL, 'Designing[1]', '', '2026-04-15', '2026-04-30', 'yes', NULL, NULL, NULL, NULL, '2026-04-15 05:08:59', '2026-04-15 06:30:40'),
('EPZ1RF', 'ACC0000001', 'BKISUK', 'NKI0GP', '74MKC2BM', NULL, NULL, NULL, NULL, NULL, NULL, 'E-Commerce Static Website', 'unverified', '2026-04-16', '2026-08-31', 'no', NULL, NULL, NULL, NULL, '2026-04-16 06:26:55', '2026-04-22 10:40:43'),
('FBXIZD', 'ACC0000001', 'BKISUK', 'V642MR', '6Q4TBH7S', NULL, NULL, NULL, NULL, NULL, NULL, 'SSESEDE', '', '2026-04-14', '2026-04-30', 'yes', NULL, NULL, NULL, NULL, '2026-04-14 09:14:27', '2026-04-14 09:14:39'),
('H8OSBJ', 'ACC0000001', 'BKISUK', 'NKI0GP', 'NPL3Y9RA', '478569', '2026-04-23', NULL, NULL, NULL, NULL, '3 Page Website', 'unverified', '2026-04-23', NULL, 'yes', NULL, NULL, NULL, NULL, '2026-04-23 05:15:59', '2026-04-23 05:18:15'),
('JR70QJ', 'ACC0000001', NULL, 'NKI0GP', 'ORD-20260404-235', NULL, NULL, NULL, NULL, NULL, NULL, 'Maintenance/Products', '', '2026-04-04', '2026-04-11', 'yes', NULL, NULL, NULL, NULL, '2026-04-04 04:41:31', '2026-04-10 00:30:46'),
('QF1DVL', 'ACC0000001', NULL, 'V642MR', 'ORD-20260406-190', NULL, NULL, NULL, NULL, NULL, NULL, 'Designing[1]', '', '2026-04-06', '2027-04-07', 'no', NULL, NULL, NULL, NULL, '2026-04-06 01:34:28', '2026-04-14 12:12:43'),
('RKLQHJ', 'ACC0000001', 'BKISUK', 'NKI0GP', 'BJN6WVLJ', NULL, NULL, NULL, NULL, NULL, NULL, 'Maintenance+Products', '', '2026-04-15', NULL, 'yes', NULL, NULL, NULL, NULL, '2026-04-15 05:46:44', '2026-04-15 06:41:56'),
('SOGCLP', 'ACC0000001', 'BKISUK', 'NKI0GP', 'G6KBSY4H', NULL, NULL, NULL, NULL, NULL, NULL, 'cbxcbxcbxcvbc', 'unverified', '2026-04-22', '2026-04-16', 'yes', NULL, NULL, NULL, NULL, '2026-04-22 11:10:54', '2026-04-22 11:11:03'),
('UNWV46', 'ACC0000001', 'BKISUK', 'NKI0GP', '52ZPZTG9', NULL, NULL, NULL, NULL, NULL, NULL, '4e5r42', 'draft', '2026-04-15', NULL, 'yes', NULL, NULL, NULL, NULL, '2026-04-15 07:39:50', '2026-04-15 07:40:19'),
('VVZO85', 'ACC0000001', NULL, 'V642MR', 'ORD-20260404-431', NULL, NULL, NULL, NULL, NULL, NULL, '3 Page Website', '', '2026-04-04', NULL, 'yes', NULL, NULL, NULL, NULL, '2026-04-04 06:03:28', '2026-04-14 12:12:46'),
('W0BZQL', 'ACC0000001', 'BKISUK', 'NKI0GP', 'K4K6QTFY', NULL, NULL, NULL, NULL, NULL, NULL, 'School Website + EMS+CMS', '', '2026-04-15', NULL, 'yes', NULL, NULL, NULL, NULL, '2026-04-15 06:48:24', '2026-04-16 05:24:27'),
('W8LKL6', 'ACC0000001', 'BKISUK', 'V642MR', 'S6JXBGHV', '2342', '2026-04-14', NULL, '1233412', '2026-04-21', NULL, 'SEDS', '', '2026-04-14', '2026-04-24', 'yes', 'Notes', NULL, NULL, NULL, '2026-04-14 09:58:04', '2026-04-14 12:22:07'),
('WOKYBP', 'ACC0000001', 'BKISUK', 'V642MR', 'CEBCTMP3', '85YB56D', '2026-04-21', 'orders/po/yADZyf46UlVWoxGhNRt6iGT1zFdKWL2XsSnuzRwA.pdf', NULL, NULL, NULL, 'EMS + Journeys', 'unverified', '2026-04-21', '2026-05-20', 'yes', NULL, NULL, NULL, NULL, '2026-04-21 10:13:00', '2026-04-22 09:13:44'),
('WRYWIA', 'ACC0000001', 'BKISUK', 'V642MR', 'MAC5YGEQ', NULL, NULL, NULL, NULL, NULL, NULL, 'GST based[]', 'unverified', '2026-04-17', '2026-04-25', 'no', NULL, NULL, NULL, NULL, '2026-04-17 11:43:17', '2026-04-18 05:23:28'),
('ZMAYIK', 'ACC0000001', NULL, 'NKI0GP', 'ORD-20260404-656', NULL, NULL, NULL, NULL, NULL, NULL, 'Designing', '', '2026-04-04', '2026-04-11', 'yes', NULL, NULL, NULL, NULL, '2026-04-04 03:15:19', '2026-04-15 05:50:16');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
CREATE TABLE IF NOT EXISTS `order_items` (
  `orderitemid` varchar(6) COLLATE utf8mb4_unicode_ci NOT NULL,
  `orderid` varchar(6) COLLATE utf8mb4_unicode_ci NOT NULL,
  `itemid` varchar(6) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `item_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `item_description` text COLLATE utf8mb4_unicode_ci,
  `quantity` decimal(10,2) NOT NULL DEFAULT '1.00',
  `unit_price` decimal(12,2) NOT NULL DEFAULT '0.00',
  `tax_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `discount_percent` decimal(5,2) NOT NULL DEFAULT '0.00',
  `discount_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `frequency` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `duration` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `no_of_users` int UNSIGNED DEFAULT NULL,
  `line_total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `delivery_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`orderitemid`),
  KEY `order_items_orderid_foreign` (`orderid`),
  KEY `order_items_itemid_foreign` (`itemid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`orderitemid`, `orderid`, `itemid`, `item_name`, `item_description`, `quantity`, `unit_price`, `tax_rate`, `discount_percent`, `discount_amount`, `frequency`, `duration`, `no_of_users`, `line_total`, `start_date`, `end_date`, `delivery_date`, `created_at`, `updated_at`) VALUES
('2SAXVO', 'YBEWHH', 'EMQTP5', 'Website Maintenance', NULL, 1.00, 12000.00, 18.00, 10.00, 1016.95, 'one-time', '', NULL, 10169.49, NULL, NULL, NULL, '2026-04-16 11:11:48', '2026-04-16 11:11:48'),
('2TNOHI', 'H8OSBJ', 'JNBIE9', '3 Page Website', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua', 1.00, 14000.00, 18.00, 10.00, 1400.00, 'one-time', NULL, NULL, 14000.00, NULL, NULL, NULL, '2026-04-23 05:18:15', '2026-04-23 05:18:15'),
('4MAE6T', 'EPZ1RF', 'VSM00B', 'Customer Relationship Management(CRM)', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua', 1.00, 123422.00, 18.00, 10.00, 12342.00, 'one-time', NULL, 1, 123422.00, NULL, NULL, '2026-08-31', '2026-04-22 10:40:03', '2026-04-22 10:40:03'),
('6FXGUQ', 'JYNM8V', '1JCMEB', 'Content Management System (CMS)', NULL, 1.00, 20000.00, 18.00, 10.00, 8000.00, 'yearly', '2', 2, 80000.00, '2026-04-16', '2028-04-15', '2026-04-23', '2026-04-16 07:43:48', '2026-04-16 07:43:48'),
('76IKCJ', 'DIAXET', 'EMQTP5', 'Website Maintenance', NULL, 1.00, 12.00, 18.00, 10.00, 1.20, 'one-time', '', NULL, 12.00, NULL, NULL, '2026-04-30', '2026-04-15 08:43:09', '2026-04-15 08:43:09'),
('7O9TMI', 'W8LKL6', 'EMQTP5', 'Website Maintenance', NULL, 1.00, 12.00, 18.00, 10.00, 1.20, 'monthly', '1', 1, 12.00, '2026-04-14', '2026-05-13', '2026-04-24', '2026-04-14 12:24:01', '2026-04-14 12:24:01'),
('7V1GRA', 'CBF58J', '1JCMEB', 'Content Management System (CMS)', NULL, 1.00, 20000.00, 18.00, 10.00, 2000.00, 'one-time', '', 1, 20000.00, NULL, NULL, NULL, '2026-04-15 06:33:08', '2026-04-15 06:33:08'),
('8XOHVX', 'VVZO85', 'EMQTP5', 'Website Maintenance', NULL, 1.00, 12.00, 18.00, 0.00, 0.00, 'one-time', '', 1, 12.00, NULL, NULL, NULL, '2026-04-14 12:12:46', '2026-04-14 12:12:46'),
('A6UONB', 'DWN1CH', '1JCMEB', 'Content Management System (CMS)', NULL, 1.00, 20000.00, 18.00, 10.00, 4000.00, 'yearly', '2', 1, 40000.00, '2026-04-18', '2028-04-17', NULL, '2026-04-21 04:01:28', '2026-04-21 04:01:28'),
('AAFHRP', 'JR70QJ', '1JCMEB', 'Content Management System (CMS)', NULL, 1.00, 20000.00, 18.00, 0.00, 0.00, 'weekly', '2', 1, 40000.00, '2026-04-10', '2026-04-26', '2026-04-11', '2026-04-10 00:30:46', '2026-04-10 00:30:46'),
('AFBB2M', 'W0BZQL', 'V9IFFZ', 'EMS(Enrollment Management System)', NULL, 1.00, 22000.00, 18.00, 10.00, 8800.00, 'yearly', '2', 2, 88000.00, '2026-04-16', '2028-04-15', NULL, '2026-04-16 05:24:31', '2026-04-16 05:24:31'),
('AOTL2S', 'WOKYBP', 'V9IFFZ', 'EMS(Enrollment Management System)', NULL, 1.00, 22000.00, 18.00, 10.00, 4400.00, 'yearly', '2', 1, 44000.00, '2026-05-20', '2028-05-19', '2026-05-20', '2026-04-22 09:13:44', '2026-04-22 09:13:44'),
('ARJBUR', 'ALX4UN', '1JCMEB', 'Content Management System (CMS)', NULL, 1.00, 20000.00, 18.00, 0.00, 0.00, 'yearly', '2', 2, 80000.00, '2026-04-10', '2028-04-09', '2026-04-25', '2026-04-10 00:33:27', '2026-04-15 04:48:04'),
('AZ2ZB2', 'ZMAYIK', 'EMQTP5', 'Website Maintenance', NULL, 1.00, 12.00, 18.00, 10.00, 1.20, 'one-time', '', 1, 12.00, NULL, NULL, '2026-04-11', '2026-04-15 05:50:18', '2026-04-15 05:50:18'),
('C9HAPM', 'DWN1CH', 'EMQTP5', 'Website Maintenance', NULL, 1.00, 12.00, 18.00, 10.00, 1.20, 'one-time', NULL, NULL, 12.00, NULL, NULL, NULL, '2026-04-21 04:01:32', '2026-04-21 04:01:32'),
('CDPOGS', 'YBEWHH', 'EMQTP5', 'Website Maintenance', NULL, 1.00, 12000.00, 18.00, 10.00, 1016.95, 'one-time', '', NULL, 10169.49, NULL, NULL, NULL, '2026-04-16 11:11:48', '2026-04-16 11:11:48'),
('DABBNX', 'W8LKL6', '1JCMEB', 'Content Management System (CMS)', NULL, 1.00, 20000.00, 18.00, 10.00, 4000.00, 'weekly', '2', 1, 40000.00, '2026-04-14', '2026-04-27', '2026-04-24', '2026-04-14 12:24:01', '2026-04-14 12:24:01'),
('EHPPBY', 'W0BZQL', '1JCMEB', 'Content Management System (CMS)', NULL, 1.00, 20000.00, 18.00, 10.00, 4000.00, 'yearly', '1', 2, 40000.00, '2026-04-16', '2027-04-15', NULL, '2026-04-16 05:24:31', '2026-04-16 05:24:31'),
('FOHERA', 'ZMAYIK', 'JNBIE9', '3 Page Website', NULL, 1.00, 14000.00, 18.00, 2.00, 560.00, 'yearly', '2', 1, 28000.00, '2026-04-10', '2028-04-10', '2026-04-11', '2026-04-15 05:50:18', '2026-04-15 05:50:18'),
('FX0RBN', 'EPZ1RF', 'JNBIE9', '3 Page Website', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua', 1.00, 14000.00, 18.00, 10.00, 1400.00, 'one-time', NULL, NULL, 14000.00, NULL, NULL, '2026-08-31', '2026-04-22 10:40:09', '2026-04-22 10:40:09'),
('GBBEX7', 'BH7SES', 'JHKW5I', 'Test', NULL, 1.00, 12.00, 18.00, 0.00, 0.00, 'one-time', '', 1, 12.00, NULL, NULL, '2026-05-15', '2026-04-14 12:12:40', '2026-04-14 12:12:40'),
('GPKDIJ', 'BBDULJ', 'VSM00B', 'Customer Relationship Management(CRM)', NULL, 1.00, 123422.00, 18.00, 10.00, 123422.00, 'weekly', '5', 2, 1234220.00, '2026-04-15', '2026-05-19', NULL, '2026-04-15 08:42:54', '2026-04-15 08:42:54'),
('GTEJIL', 'DRV7JB', 'VSM00B', 'Customer Relationship Management(CRM)', NULL, 1.00, 123422.00, 18.00, 10.00, 12342.20, 'one-time', '', 1, 123422.00, NULL, NULL, NULL, '2026-04-15 06:32:25', '2026-04-15 06:32:25'),
('GY9QPV', 'JR70QJ', 'EMQTP5', 'Website Maintenance', NULL, 1.00, 12.00, 18.00, 0.00, 0.00, 'one-time', '', 1, 12.00, NULL, NULL, '2026-04-11', '2026-04-10 00:30:46', '2026-04-10 00:30:46'),
('H2OIC4', 'AB6LVF', 'EDYHDB', 'Term', NULL, 1.00, 10000.00, 18.00, 20.00, 2000.00, 'one-time', '', NULL, 10000.00, NULL, NULL, NULL, '2026-04-15 08:44:53', '2026-04-15 08:44:53'),
('HO9YUG', 'DIAXET', 'V9IFFZ', 'EMS(Enrollment Management System)', NULL, 1.00, 22000.00, 18.00, 10.00, 4400.00, 'yearly', '2', 1, 44000.00, '2026-04-27', '2028-04-26', '2026-04-04', '2026-04-15 08:43:09', '2026-04-15 08:43:09'),
('HUWOST', 'W0BZQL', 'JNBIE9', '3 Page Website', NULL, 1.00, 14000.00, 18.00, 10.00, 1400.00, 'one-time', '', NULL, 14000.00, NULL, NULL, NULL, '2026-04-16 05:24:31', '2026-04-16 05:24:31'),
('HVF9UN', 'JYNM8V', 'EMQTP5', 'Website Maintenance', NULL, 1.00, 12000.00, 18.00, 10.00, 1200.00, 'one-time', NULL, NULL, 12000.00, NULL, NULL, '2026-04-23', '2026-04-16 07:42:07', '2026-04-16 11:59:43'),
('JRUPTK', 'QF1DVL', 'V9IFFZ', 'EMS(Enrollment Management System)', NULL, 1.00, 22000.00, 18.00, 0.00, 0.00, 'yearly', '1', 1, 22000.00, '2026-04-07', '2027-05-06', '2027-05-05', '2026-04-14 12:12:43', '2026-04-14 12:12:43'),
('LDMNA7', 'UNWV46', 'V9IFFZ', 'EMS(Enrollment Management System)', NULL, 1.00, 22000.00, 18.00, 10.00, 4400.00, 'yearly', '2', 1, 44000.00, '2026-04-15', '2028-04-14', NULL, '2026-04-15 07:40:19', '2026-04-15 07:40:19'),
('MHPXMW', '0L4MBS', 'EDYHDB', 'Term', NULL, 1.00, 10000.00, 18.00, 10.00, 1000.00, NULL, NULL, NULL, 10000.00, NULL, NULL, '2026-04-18', '2026-04-17 06:01:08', '2026-04-17 06:01:08'),
('MZHYVT', 'QOEYXC', 'EDYHDB', 'Term', NULL, 1.00, 10000.00, 18.00, 10.00, 2000.00, 'yearly', '2', 1, 20000.00, '2026-04-22', '2028-04-21', NULL, '2026-04-15 06:05:43', '2026-04-15 06:05:43'),
('PAIP8T', 'FBXIZD', 'EMQTP5', 'Website Maintenance', NULL, 1.00, 12.00, 18.00, 0.00, 0.00, 'monthly', '3', 1, 36.00, '2026-04-14', '2026-07-13', '2026-04-30', '2026-04-14 09:14:39', '2026-04-14 09:14:39'),
('PKW2RT', 'VVZO85', 'JNBIE9', '3 Page Website', NULL, 1.00, 14000.00, 18.00, 0.00, 0.00, 'yearly', '1', 2, 14000.00, NULL, NULL, NULL, '2026-04-14 12:12:46', '2026-04-14 12:12:46'),
('QHF0JX', 'BBDULJ', 'JNBIE9', '3 Page Website', NULL, 1.00, 14000.00, 18.00, 10.00, 1400.00, 'one-time', '', NULL, 14000.00, NULL, NULL, NULL, '2026-04-15 08:42:54', '2026-04-15 08:42:54'),
('R3CDMW', 'DRV7JB', '1JCMEB', 'Content Management System (CMS)', NULL, 1.00, 20000.00, 18.00, 10.00, 4000.00, 'yearly', '2', 1, 40000.00, '2026-04-15', '2028-04-14', NULL, '2026-04-15 06:32:25', '2026-04-15 06:32:25'),
('R57BO3', 'WRYWIA', 'JNBIE9', '3 Page Website', NULL, 1.00, 14000.00, 18.00, 10.00, 1400.00, 'one-time', NULL, NULL, 14000.00, NULL, NULL, '2026-04-25', '2026-04-18 05:23:10', '2026-04-18 05:23:10'),
('SBLE08', '5XOC7U', 'EMQTP5', 'Website Maintenance', NULL, 1.00, 12.00, 18.00, 5.00, 1.20, 'semi-annually', '2', 1, 24.00, '2026-04-14', '2027-04-13', '2026-04-29', '2026-04-14 12:19:36', '2026-04-14 12:19:36'),
('SS6TJF', 'QF1DVL', '1JCMEB', 'Content Management System (CMS)', NULL, 1.00, 20000.00, 18.00, 0.00, 0.00, 'yearly', '1', 2, 40000.00, NULL, NULL, '2027-04-21', '2026-04-14 12:12:43', '2026-04-14 12:12:43'),
('T1DQOR', 'WRYWIA', 'VSM00B', 'Customer Relationship Management(CRM)', NULL, 1.00, 123422.00, 18.00, 10.00, 24684.40, 'yearly', '2', 1, 246844.00, '2026-04-17', '2028-04-16', '2026-04-25', '2026-04-17 11:43:40', '2026-04-17 11:43:40'),
('TAPVGZ', 'SOGCLP', 'VSM00B', 'Customer Relationship Management(CRM)', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua', 1.00, 123422.00, 18.00, 10.00, 12342.00, NULL, NULL, 1, 123422.00, NULL, NULL, '2026-04-16', '2026-04-22 11:11:03', '2026-04-22 11:11:03'),
('V6H8UR', 'DIAXET', 'VSM00B', 'Customer Relationship Management(CRM)', NULL, 1.00, 123422.00, 18.00, 10.00, 24684.40, 'yearly', '2', 1, 246844.00, '2026-04-15', '2028-04-14', '2026-05-02', '2026-04-15 08:43:09', '2026-04-15 08:43:09'),
('VCXTGT', 'OFNJ86', 'JNBIE9', '3 Page Website', NULL, 1.00, 14000.00, 18.00, 12.00, 1680.00, 'one-time', NULL, NULL, 14000.00, NULL, NULL, '2026-04-24', '2026-04-17 11:05:25', '2026-04-17 11:05:25'),
('VWHDKS', 'INIXWK', 'EMQTP5', 'Website Maintenance', NULL, 1.00, 10000.00, 18.00, 10.00, 1000.00, 'one-time', NULL, NULL, 10000.00, NULL, NULL, '2026-04-18', '2026-04-17 06:03:36', '2026-04-17 06:03:36'),
('X8VBQX', 'EPZ1RF', 'JHKW5I', 'Test', 'Lorem ipsum dolor sit amet', 1.00, 12.00, 18.00, 1.00, 0.00, 'one-time', NULL, 1, 12.00, NULL, NULL, '2026-08-17', '2026-04-22 10:40:43', '2026-04-22 10:40:43'),
('XFRZ2L', 'ALX4UN', 'JNBIE9', '3 Page Website', NULL, 1.00, 14000.00, 18.00, 0.00, 0.00, 'one-time', '1', 1, 14000.00, NULL, NULL, '2026-04-25', '2026-04-10 00:33:27', '2026-04-10 00:33:27'),
('YOYEU4', 'OFNJ86', '1JCMEB', 'Content Management System (CMS)', NULL, 1.00, 20000.00, 18.00, 10.00, 8000.00, 'weekly', '2', 2, 80000.00, '2026-04-17', '2026-04-30', '2026-04-24', '2026-04-17 10:56:12', '2026-04-17 10:56:12'),
('ZJ7GGC', 'RKLQHJ', 'EMQTP5', 'Website Maintenance', NULL, 1.00, 12000.00, 18.00, 10.00, 1200.00, 'yearly', '1', 1, 12000.00, '2026-04-15', '2027-04-14', NULL, '2026-04-15 06:42:41', '2026-04-15 06:42:41'),
('ZOTVQG', 'WOKYBP', 'CE4CRH', 'Journeys', NULL, 1.00, 25000.00, 0.00, 5.00, 2500.00, 'yearly', '2', NULL, 50000.00, '2026-05-20', '2028-05-19', '2026-05-20', '2026-04-22 08:41:08', '2026-04-22 08:41:08'),
('ZSYA2J', 'RKLQHJ', 'JNBIE9', '3 Page Website', NULL, 1.00, 14000.00, 18.00, 10.00, 1400.00, 'one-time', '', 1, 14000.00, NULL, NULL, NULL, '2026-04-15 06:42:41', '2026-04-15 06:42:41');

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
CREATE TABLE IF NOT EXISTS `payments` (
  `paymentid` varchar(6) COLLATE utf8mb4_unicode_ci NOT NULL,
  `accountid` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `clientid` varchar(6) COLLATE utf8mb4_unicode_ci NOT NULL,
  `invoiceid` varchar(6) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_number` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_date` date NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_method` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gateway_name` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gateway_transaction_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'completed',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `received_by` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`paymentid`),
  UNIQUE KEY `payments_payment_number_unique` (`payment_number`),
  KEY `payments_clientid_foreign` (`clientid`),
  KEY `payments_invoiceid_foreign` (`invoiceid`),
  KEY `payments_received_by_foreign` (`received_by`),
  KEY `payments_accountid_status_index` (`accountid`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ps_categories`
--

DROP TABLE IF EXISTS `ps_categories`;
CREATE TABLE IF NOT EXISTS `ps_categories` (
  `ps_catid` varchar(6) COLLATE utf8mb4_unicode_ci NOT NULL,
  `accountid` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sequence` int UNSIGNED NOT NULL DEFAULT '0',
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`ps_catid`),
  KEY `ps_categories_accountid_status_index` (`accountid`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ps_categories`
--

INSERT INTO `ps_categories` (`ps_catid`, `accountid`, `name`, `sequence`, `description`, `status`, `created_at`, `updated_at`) VALUES
('BG9L7D', 'ACC0000001', 'Static Web Designing', 2, NULL, 'active', '2026-03-31 05:42:25', '2026-03-31 05:42:25'),
('RHPSCA', 'ACC0000001', 'Customer Relationship Management (CRM)', 3, NULL, 'active', '2026-03-31 05:42:44', '2026-03-31 05:42:44'),
('SIRC3B', 'ACC0000001', 'PHP Development', 1, NULL, 'active', '2026-03-31 05:42:13', '2026-04-15 09:07:26');

-- --------------------------------------------------------

--
-- Table structure for table `quotations`
--

DROP TABLE IF EXISTS `quotations`;
CREATE TABLE IF NOT EXISTS `quotations` (
  `quotationid` varchar(6) COLLATE utf8mb4_unicode_ci NOT NULL,
  `accountid` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fy_id` varchar(6) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `clientid` varchar(6) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quotation_number` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `issue_date` date NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `subtotal` decimal(12,2) NOT NULL DEFAULT '0.00',
  `tax_total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `discount_total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `grand_total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `terms` text COLLATE utf8mb4_unicode_ci,
  `invoiceid` varchar(6) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`quotationid`),
  UNIQUE KEY `estimates_estimate_number_unique` (`quotation_number`),
  KEY `estimates_clientid_foreign` (`clientid`),
  KEY `estimates_invoiceid_foreign` (`invoiceid`),
  KEY `estimates_created_by_foreign` (`created_by`),
  KEY `estimates_accountid_status_index` (`accountid`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quotation_items`
--

DROP TABLE IF EXISTS `quotation_items`;
CREATE TABLE IF NOT EXISTS `quotation_items` (
  `quotationitemid` varchar(6) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quotationid` varchar(6) COLLATE utf8mb4_unicode_ci NOT NULL,
  `itemid` varchar(6) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `item_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `item_description` text COLLATE utf8mb4_unicode_ci,
  `quantity` decimal(10,2) NOT NULL DEFAULT '1.00',
  `unit_price` decimal(12,2) NOT NULL DEFAULT '0.00',
  `tax_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `line_total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `sort_order` int UNSIGNED NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`quotationitemid`),
  KEY `estimate_items_estimateid_foreign` (`quotationid`),
  KEY `quotation_items_itemid_foreign` (`itemid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `serial_configurations`
--

DROP TABLE IF EXISTS `serial_configurations`;
CREATE TABLE IF NOT EXISTS `serial_configurations` (
  `serial_configid` varchar(6) COLLATE utf8mb4_unicode_ci NOT NULL,
  `accountid` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `document_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `prefix_show` tinyint NOT NULL DEFAULT '1',
  `number_show` tinyint NOT NULL DEFAULT '1',
  `suffix_show` tinyint NOT NULL DEFAULT '1',
  `config_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `prefix_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manual text',
  `prefix_value` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prefix_length` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prefix_separator` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'none',
  `number_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'auto increment',
  `number_value` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `number_length` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `number_separator` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'none',
  `suffix_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manual text',
  `suffix_value` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `suffix_length` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `serial_mode` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'sequential',
  `reset_on_fy` tinyint(1) NOT NULL DEFAULT '0',
  `fy_id` varchar(6) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`serial_configid`),
  KEY `serial_configurations_accountid_foreign` (`accountid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `serial_configurations`
--

INSERT INTO `serial_configurations` (`serial_configid`, `accountid`, `document_type`, `prefix_show`, `number_show`, `suffix_show`, `config_name`, `is_default`, `is_active`, `prefix_type`, `prefix_value`, `prefix_length`, `prefix_separator`, `number_type`, `number_value`, `number_length`, `number_separator`, `suffix_type`, `suffix_value`, `suffix_length`, `serial_mode`, `reset_on_fy`, `fy_id`, `created_at`, `updated_at`) VALUES
('BJBTMV', 'ACC0000001', 'proforma_invoice', 1, 1, 1, NULL, 0, 1, 'manual text', 'PI', '4', '-', 'auto generate', NULL, '4', '-', 'year', NULL, '4', 'sequential', 0, NULL, '2026-04-09 02:18:53', '2026-04-09 02:29:09'),
('CB61QW', 'ACC0000001', 'order', 0, 1, 0, NULL, 0, 1, 'manual text', 'ORD', '4', '/', 'auto generate', '1001', '8', '/', 'manual text', NULL, '4', 'sequential', 0, NULL, '2026-04-14 06:53:50', '2026-04-14 09:11:10'),
('FPEAUS', 'ACC0000001', 'tax_invoice', 1, 1, 1, NULL, 0, 1, 'manual text', 'INV', '4', '/', 'auto increment', '1001', '4', '/', 'year', NULL, '4', 'sequential', 0, NULL, '2026-04-09 02:09:38', '2026-04-14 07:30:18'),
('W9PG6Q', 'ACC0000001', 'quotation', 1, 1, 1, NULL, 0, 1, 'manual text', 'QUO', '0', '/', 'auto increment', '1001', '4', '/', 'year', NULL, '0', 'sequential', 0, NULL, '2026-04-09 02:21:49', '2026-04-09 02:21:49');

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
CREATE TABLE IF NOT EXISTS `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('Myf7xkzgS1jIshHRwcU8izcx9e5FgaRBdkBzoE1b', NULL, '192.168.1.74', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', 'eyJfdG9rZW4iOiJZV0Vjc1lnYjVjRTNNdG9yRzJWSkZFZVhKQzVhcUFmRVY1cWt6djdtIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cL2FscGhhLmluc2lkZXNvZnR3YXJlcy5jb21cL2xvZ2luIiwicm91dGUiOiJsb2dpbiJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19', 1776748830),
('sHjJ2kEAEUMexRhD5ZWISwcw3ygjTG7CfjtWMLMM', 'ACC0000001', '192.168.1.56', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', 'eyJfdG9rZW4iOiJWcWZVdU9kSzNpWmtKRGpMMVdMc3FySTd2TG4xREtWOWs4ODljTkJBIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cL2FscGhhLmluc2lkZXNvZnR3YXJlcy5jb21cL2ludm9pY2VzXC9vcmRlci1pdGVtc1wvVlZaTzg1Iiwicm91dGUiOiJpbnZvaWNlcy5vcmRlci1pdGVtcyJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX0sImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjoiQUNDMDAwMDAwMSIsImludm9pY2VfZm9yIjoib3JkZXJzIn0=', 1776748831);

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
CREATE TABLE IF NOT EXISTS `settings` (
  `settingid` varchar(6) COLLATE utf8mb4_unicode_ci NOT NULL,
  `accountid` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`settingid`),
  UNIQUE KEY `settings_accountid_setting_key_unique` (`accountid`,`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`settingid`, `accountid`, `setting_key`, `setting_value`, `created_at`, `updated_at`) VALUES
('WPSYTV', 'ACC0000001', 'CASHFREE_APP_ID', 'dfsddfvxcvv', '2026-04-06 23:40:39', '2026-04-06 23:40:39');

-- --------------------------------------------------------

--
-- Table structure for table `terms_conditions`
--

DROP TABLE IF EXISTS `terms_conditions`;
CREATE TABLE IF NOT EXISTS `terms_conditions` (
  `tc_id` varchar(6) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `accountid` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('billing','quotation') COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sequence` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`tc_id`),
  KEY `terms_conditions_accountid_type_index` (`accountid`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `terms_conditions`
--

INSERT INTO `terms_conditions` (`tc_id`, `accountid`, `type`, `content`, `is_active`, `sequence`, `created_at`, `updated_at`) VALUES
('3PH1OQ', 'ACC0000001', 'quotation', 'There are many variations of passages of Lorem Ipsum available, but the majority have suffered alteration in some form, by injected humour,', 1, 3, '2026-04-03 03:15:05', '2026-04-03 03:15:05'),
('CUQTNL', 'ACC0000001', 'billing', 'Lorem Ipsum is simply dummy text of the printing and typesetting industry.Lorem Ipsum is simply dummy text of the printing and typesetting industry.', 1, 5, '2026-04-23 04:48:23', '2026-04-23 04:48:23'),
('GDES7G', 'ACC0000001', 'billing', 'It is a long established fact that a reader will be distracted by the readable', 1, 3, '2026-04-03 03:14:38', '2026-04-03 03:32:24'),
('KT6GSL', 'ACC0000001', 'billing', 'What is Lorem Ipsum?', 1, 2, '2026-04-03 03:13:54', '2026-04-03 03:32:19'),
('ORKISO', 'ACC0000001', 'quotation', 'The standard chunk of Lorem Ipsum used since the 1500s is reproduced below', 1, 1, '2026-04-03 03:14:57', '2026-04-03 03:32:28'),
('QFZJHT', 'ACC0000001', 'billing', 'Lorem Ipsum is simply dummy text of the printing and typesetting industry.', 1, 1, '2026-04-03 03:14:11', '2026-04-06 23:41:06'),
('VYDXH1', 'ACC0000001', 'billing', 'test', 1, 4, '2026-04-20 11:35:36', '2026-04-20 11:35:36'),
('WJ8AKQ', 'ACC0000001', 'quotation', 'Contrary to popular belief', 1, 2, '2026-04-03 03:14:49', '2026-04-03 03:32:28');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `accountid` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `clientid` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'staff',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_accountid_index` (`accountid`),
  KEY `users_clientid_index` (`clientid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
