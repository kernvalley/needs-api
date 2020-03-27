-- MySQL dump 10.17  Distrib 10.3.18-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: needsapi
-- ------------------------------------------------------
-- Server version	10.3.18-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `ImageObject`
--

DROP TABLE IF EXISTS `ImageObject`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ImageObject` (
  `identifier` varchar(60) CHARACTER SET utf8 NOT NULL,
  `url` varchar(120) CHARACTER SET utf8 NOT NULL,
  `height` int(10) unsigned DEFAULT NULL,
  `width` int(10) unsigned DEFAULT NULL,
  `author` varchar(60) CHARACTER SET utf8 DEFAULT NULL,
  `caption` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL,
  `uploadDate` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`identifier`),
  KEY `image_author_identifier` (`author`),
  CONSTRAINT `image_author_identifier` FOREIGN KEY (`author`) REFERENCES `Person` (`identifier`) ON DELETE SET NULL ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ImageObject`
--

LOCK TABLES `ImageObject` WRITE;
/*!40000 ALTER TABLE `ImageObject` DISABLE KEYS */;
/*!40000 ALTER TABLE `ImageObject` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Person`
--

DROP TABLE IF EXISTS `Person`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Person` (
  `identifier` varchar(60) CHARACTER SET utf8 NOT NULL,
  `name` varchar(140) CHARACTER SET utf8mb4 NOT NULL,
  `telephone` varchar(16) CHARACTER SET utf8 DEFAULT NULL,
  `email` varchar(80) CHARACTER SET utf8 DEFAULT NULL,
  `address` varchar(60) CHARACTER SET utf8 DEFAULT NULL,
  `image` varchar(60) CHARACTER SET utf8 DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`identifier`),
  UNIQUE KEY `name` (`name`),
  KEY `person_address_identifier` (`address`),
  KEY `person_image_identifier` (`image`),
  CONSTRAINT `person_address_identifier` FOREIGN KEY (`address`) REFERENCES `PostalAddress` (`identifier`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `person_image_identifier` FOREIGN KEY (`image`) REFERENCES `ImageObject` (`identifier`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Person`
--

LOCK TABLES `Person` WRITE;
/*!40000 ALTER TABLE `Person` DISABLE KEYS */;
/*!40000 ALTER TABLE `Person` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PostalAddress`
--

DROP TABLE IF EXISTS `PostalAddress`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `PostalAddress` (
  `identifier` varchar(60) CHARACTER SET utf8 NOT NULL,
  `streetAddress` varchar(20) CHARACTER SET utf8mb4 NOT NULL,
  `addressLocality` varchar(50) CHARACTER SET utf8mb4 NOT NULL,
  `addressRegion` varchar(50) CHARACTER SET utf8mb4 NOT NULL DEFAULT 'California',
  `postOfficeBoxNumber` varchar(12) CHARACTER SET utf8 DEFAULT NULL,
  `postalCode` varchar(10) CHARACTER SET utf8 NOT NULL,
  `addressCountry` varchar(2) CHARACTER SET utf8 NOT NULL DEFAULT 'US',
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`identifier`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PostalAddress`
--

LOCK TABLES `PostalAddress` WRITE;
/*!40000 ALTER TABLE `PostalAddress` DISABLE KEYS */;
/*!40000 ALTER TABLE `PostalAddress` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ServerErrors`
--

DROP TABLE IF EXISTS `ServerErrors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ServerErrors` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(35) CHARACTER SET utf8 NOT NULL,
  `message` varchar(255) CHARACTER SET utf8mb4 NOT NULL,
  `file` varchar(200) NOT NULL,
  `line` int(5) unsigned NOT NULL,
  `code` int(10) unsigned DEFAULT NULL,
  `datetime` timestamp NOT NULL DEFAULT current_timestamp(),
  `remoteIP` varchar(45) NOT NULL,
  `url` varchar(255) CHARACTER SET utf8 NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ServerErrors`
--

LOCK TABLES `ServerErrors` WRITE;
/*!40000 ALTER TABLE `ServerErrors` DISABLE KEYS */;
/*!40000 ALTER TABLE `ServerErrors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `messages`
--

DROP TABLE IF EXISTS `messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `messages` (
  `identifier` varchar(36) CHARACTER SET utf8 NOT NULL,
  `name` varchar(120) CHARACTER SET utf8mb4 NOT NULL,
  `email` varchar(80) CHARACTER SET utf8 NOT NULL,
  `telephone` varchar(16) CHARACTER SET utf8 NOT NULL,
  `message` mediumtext NOT NULL,
  `opened` tinyint(1) NOT NULL DEFAULT 0,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`identifier`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `messages`
--

LOCK TABLES `messages` WRITE;
/*!40000 ALTER TABLE `messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `needs`
--

DROP TABLE IF EXISTS `needs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `needs` (
  `identifier` varchar(60) CHARACTER SET utf8 NOT NULL,
  `user` varchar(60) CHARACTER SET utf8 NOT NULL,
  `assigned` varchar(60) CHARACTER SET utf8 DEFAULT NULL,
  `status` enum('open','active','closed','cancelled') CHARACTER SET utf8 NOT NULL DEFAULT 'open',
  `title` varchar(80) CHARACTER SET utf8 NOT NULL,
  `description` longtext CHARACTER SET utf8mb4 NOT NULL,
  `tags` varchar(120) CHARACTER SET utf8 DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`identifier`),
  KEY `assigned` (`assigned`),
  KEY `needs_user_identifier` (`user`),
  CONSTRAINT `needs_assigned_identifier` FOREIGN KEY (`assigned`) REFERENCES `Person` (`identifier`) ON DELETE SET NULL ON UPDATE NO ACTION,
  CONSTRAINT `needs_user_identifier` FOREIGN KEY (`user`) REFERENCES `Person` (`identifier`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `needs`
--

LOCK TABLES `needs` WRITE;
/*!40000 ALTER TABLE `needs` DISABLE KEYS */;
/*!40000 ALTER TABLE `needs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` int(1) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(20) NOT NULL,
  `createNeed` tinyint(1) NOT NULL DEFAULT 1,
  `editNeed` tinyint(1) NOT NULL DEFAULT 0,
  `listNeed` tinyint(1) NOT NULL DEFAULT 0,
  `deleteNeed` tinyint(1) NOT NULL DEFAULT 0,
  `listUser` tinyint(1) NOT NULL DEFAULT 0,
  `editUser` tinyint(1) NOT NULL DEFAULT 0,
  `deleteUser` tinyint(1) NOT NULL DEFAULT 0,
  `debug` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'admin',1,1,1,1,1,1,1,1),(2,'driver',1,1,1,0,1,0,0,0),(3,'guest',1,0,0,0,0,0,0,0),(4,'banned',0,0,0,0,0,0,0,0);
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `uploads`
--

DROP TABLE IF EXISTS `uploads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `uploads` (
  `identifier` varchar(60) CHARACTER SET utf8 NOT NULL,
  `image` varchar(60) CHARACTER SET utf8 NOT NULL,
  `uploader` varchar(60) CHARACTER SET utf8 DEFAULT NULL,
  `need` varchar(60) CHARACTER SET utf8 DEFAULT NULL,
  KEY `upload_image_identifer` (`image`),
  KEY `upload_uploader_identifier` (`uploader`),
  KEY `upload_need_identifier` (`need`),
  CONSTRAINT `upload_image_identifer` FOREIGN KEY (`image`) REFERENCES `ImageObject` (`identifier`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `upload_need_identifier` FOREIGN KEY (`need`) REFERENCES `needs` (`identifier`) ON DELETE SET NULL ON UPDATE NO ACTION,
  CONSTRAINT `upload_uploader_identifier` FOREIGN KEY (`uploader`) REFERENCES `users` (`identifier`) ON DELETE SET NULL ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `uploads`
--

LOCK TABLES `uploads` WRITE;
/*!40000 ALTER TABLE `uploads` DISABLE KEYS */;
/*!40000 ALTER TABLE `uploads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `identifier` varchar(60) CHARACTER SET utf8 NOT NULL,
  `password` varchar(60) CHARACTER SET utf8 NOT NULL,
  `person` varchar(60) CHARACTER SET utf8 NOT NULL,
  `role` int(1) unsigned NOT NULL DEFAULT 3,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`identifier`),
  UNIQUE KEY `person` (`person`),
  KEY `user_role_id` (`role`),
  CONSTRAINT `user_person_identifier` FOREIGN KEY (`person`) REFERENCES `Person` (`identifier`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `user_role_id` FOREIGN KEY (`role`) REFERENCES `roles` (`id`) ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2020-03-26 17:25:36
