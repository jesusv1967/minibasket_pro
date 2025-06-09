CREATE DATABASE  IF NOT EXISTS `minibasket` /*!40100 DEFAULT CHARACTER SET utf8 */;
USE `minibasket`;
-- MySQL dump 10.13  Distrib 5.7.17, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: minibasket
-- ------------------------------------------------------
-- Server version	5.7.37

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
-- Table structure for table `club`
--

DROP TABLE IF EXISTS `club`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `club` (
  `id` int(11) NOT NULL DEFAULT '1',
  `nombre` varchar(100) NOT NULL,
  `logotipo` varchar(255) DEFAULT NULL,
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `club`
--

LOCK TABLES `club` WRITE;
/*!40000 ALTER TABLE `club` DISABLE KEYS */;
INSERT INTO `club` VALUES (1,'Mi Club','','2025-05-30 07:00:19');
/*!40000 ALTER TABLE `club` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `equipos`
--

DROP TABLE IF EXISTS `equipos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `equipos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `temporada` varchar(20) DEFAULT NULL,
  `club_id` int(11) DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `club_id` (`club_id`),
  CONSTRAINT `equipos_ibfk_1` FOREIGN KEY (`club_id`) REFERENCES `club` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `equipos`
--

LOCK TABLES `equipos` WRITE;
/*!40000 ALTER TABLE `equipos` DISABLE KEYS */;
INSERT INTO `equipos` VALUES (1,'test','20024/25',1);
/*!40000 ALTER TABLE `equipos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jugadores`
--

DROP TABLE IF EXISTS `jugadores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jugadores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `edad` int(11) NOT NULL,
  `posicion` varchar(50) DEFAULT NULL,
  `equipo` varchar(100) DEFAULT NULL,
  `fecha_registro` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jugadores`
--

LOCK TABLES `jugadores` WRITE;
/*!40000 ALTER TABLE `jugadores` DISABLE KEYS */;
/*!40000 ALTER TABLE `jugadores` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jugadores_equipos`
--

DROP TABLE IF EXISTS `jugadores_equipos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jugadores_equipos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `jugador_id` int(11) DEFAULT NULL,
  `equipo_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `jugador_id` (`jugador_id`),
  KEY `equipo_id` (`equipo_id`),
  CONSTRAINT `jugadores_equipos_ibfk_1` FOREIGN KEY (`jugador_id`) REFERENCES `jugadores` (`id`),
  CONSTRAINT `jugadores_equipos_ibfk_2` FOREIGN KEY (`equipo_id`) REFERENCES `equipos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jugadores_equipos`
--

LOCK TABLES `jugadores_equipos` WRITE;
/*!40000 ALTER TABLE `jugadores_equipos` DISABLE KEYS */;
/*!40000 ALTER TABLE `jugadores_equipos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `participaciones`
--

DROP TABLE IF EXISTS `participaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `participaciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `partido_id` int(11) DEFAULT NULL,
  `jugador_id` int(11) DEFAULT NULL,
  `periodo` int(11) DEFAULT NULL,
  `puntos` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `partido_id` (`partido_id`),
  KEY `jugador_id` (`jugador_id`),
  CONSTRAINT `participaciones_ibfk_1` FOREIGN KEY (`partido_id`) REFERENCES `partidos` (`id`),
  CONSTRAINT `participaciones_ibfk_2` FOREIGN KEY (`jugador_id`) REFERENCES `jugadores` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `participaciones`
--

LOCK TABLES `participaciones` WRITE;
/*!40000 ALTER TABLE `participaciones` DISABLE KEYS */;
/*!40000 ALTER TABLE `participaciones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `partidos`
--

DROP TABLE IF EXISTS `partidos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `partidos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fecha` date NOT NULL,
  `equipo_local` varchar(100) NOT NULL,
  `equipo_visitante` varchar(100) NOT NULL,
  `resultado` varchar(50) DEFAULT NULL,
  `fecha_registro` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `partidos`
--

LOCK TABLES `partidos` WRITE;
/*!40000 ALTER TABLE `partidos` DISABLE KEYS */;
/*!40000 ALTER TABLE `partidos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reglas_participacion`
--

DROP TABLE IF EXISTS `reglas_participacion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reglas_participacion` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `descripcion` varchar(255) NOT NULL,
  `max_periodos_seguidos` int(11) DEFAULT NULL,
  `max_total_periodos` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reglas_participacion`
--

LOCK TABLES `reglas_participacion` WRITE;
/*!40000 ALTER TABLE `reglas_participacion` DISABLE KEYS */;
/*!40000 ALTER TABLE `reglas_participacion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('Admin','Entrenador') DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (5,'admin','$2y$10$8coZu8nxofaO1AlHA60Q..T2yI6MPrBljAkYt.0dfhFzRFi2YF.vm','Admin','2025-05-30 10:31:54'),(6,'entrenador','$2y$10$ocGwMHiRXaiAcEMIAnqbcu01XQZ3lASKL5HcLrtEtm9j./ZLtjHjy','Entrenador','2025-05-30 10:33:12'),(7,'entrenador2','$2y$10$ocGwMHiRXaiAcEMIAnqbcu01XQZ3lASKL5HcLrtEtm9j./ZLtjHjy','Entrenador','2025-05-30 10:33:19');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'minibasket'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-05-30 12:52:27
