-- MySQL dump 10.13  Distrib 5.7.26, for Linux (x86_64)
--
-- Host: localhost    Database: geonames
-- ------------------------------------------------------
-- Server version	5.7.26-0ubuntu0.18.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `post_code`
--

DROP TABLE IF EXISTS `post_code`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `post_code` (
  `post_code_id` int(8) unsigned NOT NULL AUTO_INCREMENT,
  `country_code` char(2) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `place_name` varchar(180) DEFAULT NULL,
  `admin_name1` varchar(100) DEFAULT NULL,
  `admin_code1` varchar(20) DEFAULT NULL,
  `admin_name2` varchar(100) DEFAULT NULL,
  `admin_code2` varchar(20) DEFAULT NULL,
  `admin_name3` varchar(100) DEFAULT NULL,
  `admin_code3` varchar(20) DEFAULT NULL,
  `latitude` decimal(10,4) DEFAULT NULL,
  `longitude` decimal(10,4) DEFAULT NULL,
  `accuracy` char(2) DEFAULT NULL,
  PRIMARY KEY (`post_code_id`),
  FULLTEXT KEY `post_code_country_code_idx` (`country_code`),
  FULLTEXT KEY `post_code_place_name_idx` (`place_name`)
) ENGINE=InnoDB AUTO_INCREMENT=3568100 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

