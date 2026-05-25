-- MySQL dump 10.13  Distrib 8.0.45, for Win64 (x86_64)
--
-- Host: kodama.proxy.rlwy.net    Database: railway
-- ------------------------------------------------------
-- Server version	9.4.0

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categorias`
--

DROP TABLE IF EXISTS `categorias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categorias` (
  `id_categoria` int NOT NULL AUTO_INCREMENT,
  `cat_nombre` varchar(50) NOT NULL,
  `cat_descripcion` text,
  PRIMARY KEY (`id_categoria`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categorias`
--

LOCK TABLES `categorias` WRITE;
/*!40000 ALTER TABLE `categorias` DISABLE KEYS */;
INSERT INTO `categorias` VALUES (1,'Motor','Pistones, anillos, biela, empaques y componentes internos'),(2,'Frenos','Balatas, discos, mangueras y lÃ­quido de frenos'),(3,'ElÃ©ctrico','BaterÃ­as, bujÃ­as, estatores y cableado'),(4,'Accesorios','Cascos, guantes, espejos y elementos estÃ©ticos'),(5,'Mantenimiento General','Servicios preventivos y correctivos programados'),(6,'Nose','SI'),(7,'Si','a'),(8,'Manubrio','Epejos, empuÃ±aduras, manijas, contrapeso, abrazadera y soporte de celular'),(10,'Luces','Luces principales, traseras, reflectores y luces de giro');
/*!40000 ALTER TABLE `categorias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `citas`
--

DROP TABLE IF EXISTS `citas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `citas` (
  `id_cita` int NOT NULL AUTO_INCREMENT,
  `id_cliente` int DEFAULT NULL,
  `id_empleado` int DEFAULT NULL,
  `cita_fecha_programada` datetime NOT NULL,
  `cita_motivo` varchar(255) DEFAULT NULL,
  `cita_estado` enum('Pendiente','Confirmada','Cancelada','Realizada') DEFAULT 'Pendiente',
  `cita_tipo` enum('Servicio','Venta','Mantenimiento') DEFAULT 'Servicio',
  `cita_notas` text,
  PRIMARY KEY (`id_cita`),
  KEY `id_cliente` (`id_cliente`),
  KEY `id_empleado` (`id_empleado`),
  CONSTRAINT `citas_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`),
  CONSTRAINT `citas_ibfk_2` FOREIGN KEY (`id_empleado`) REFERENCES `empleados` (`id_empleados`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `citas`
--

LOCK TABLES `citas` WRITE;
/*!40000 ALTER TABLE `citas` DISABLE KEYS */;
INSERT INTO `citas` VALUES (1,3,5,'2026-05-25 10:30:00','Servicio Completo','Confirmada','Servicio','El cliente menciona que tironea en baja'),(2,4,2,'2026-05-21 23:49:39','Cambio de balatas','Pendiente','Servicio','Trae balatas propias, solo mano de obra'),(3,2,4,'2026-05-25 14:00:00',NULL,'Pendiente','Servicio',NULL),(4,2,1,'2026-05-27 10:00:00',NULL,'Pendiente','Servicio',NULL),(5,1,4,'2026-05-27 13:00:00',NULL,'Pendiente','Servicio',NULL),(6,1,5,'2026-05-26 10:00:00','NA','Confirmada','Servicio',NULL),(7,3,5,'2026-05-25 10:30:00','Servicio Completo','Cancelada','Servicio',NULL),(8,1,5,'2026-05-26 10:00:00','NA','Realizada','Servicio',NULL),(9,2,2,'2026-06-08 12:30:00','RevisiÃ³n Sistema ElÃ©ctrico','Pendiente','Servicio','20 minutos de tolerancia'),(10,2,1,'2026-06-09 12:15:00','AfinaciÃ³n a media','Pendiente','Servicio',NULL),(11,2,4,'2026-05-26 14:00:00','a','Confirmada','Servicio',NULL),(12,2,4,'2026-05-28 09:00:00','a','Pendiente','Servicio',NULL),(13,1,1,'2026-05-26 10:00:00','Prueba','Pendiente','Mantenimiento',NULL),(14,2,5,'2026-05-28 12:00:00','a','Pendiente','Mantenimiento',NULL);
/*!40000 ALTER TABLE `citas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clientes`
--

DROP TABLE IF EXISTS `clientes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clientes` (
  `id_cliente` int NOT NULL AUTO_INCREMENT,
  `cli_nombre` varchar(100) NOT NULL,
  `cli_apaterno` varchar(50) NOT NULL,
  `cli_amaterno` varchar(50) DEFAULT NULL,
  `cli_telefono` varchar(15) DEFAULT NULL,
  `cli_correo` varchar(100) NOT NULL,
  `cli_direccion` text,
  `cli_password` varchar(255) NOT NULL,
  `cli_telefonos_extra` text,
  `tipo_usuario` int DEFAULT '3',
  `cli_estado` enum('Activo','Inactivo') DEFAULT 'Activo',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_cliente`),
  UNIQUE KEY `cli_correo` (`cli_correo`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clientes`
--

LOCK TABLES `clientes` WRITE;
/*!40000 ALTER TABLE `clientes` DISABLE KEYS */;
INSERT INTO `clientes` VALUES (1,'Juan','LÃ³pez','HernÃ¡ndez','5551112233','juan.cliente@email.com','Calle Primavera #123, Col. Centro','$2y$12$gmXmspquQtBTgr1QdTEDsebKSLHQbD4j1Ow9b/iovdzGAJQF9kALC',NULL,3,'Activo','2026-05-20 08:00:25','2026-05-20 14:03:07'),(2,'MarÃ­a','GarcÃ­a','RodrÃ­guez','5554445566','maria.cliente@email.com','Av. Siempre Viva #456','$2y$12$hash_aqui_para_cliente123',NULL,3,'Activo','2026-05-20 08:00:25','2026-05-20 08:00:25'),(3,'Carlos','SÃ¡nchez','DÃ­az','5557778899','carlos.cliente@email.com','Blvd. Principal #789','$2y$12$hash_aqui_para_cliente123',NULL,3,'Activo','2026-05-20 08:00:25','2026-05-20 08:00:25'),(4,'ww','ihui','ihihio','456544565','kdvknvw@wldvskm.com','dknvnwskn','$2y$12$JGMh4W1EQMSNOX7ZQIA5mu5KWL7uXriCB6aEL1UR.y233inNHen82',NULL,3,'Activo','2026-05-21 08:56:11','2026-05-21 08:56:11'),(5,'ww','ihui','GarcÃ­a','2455466565','kdvknssvw@gmail.com','wdwwdw','$2y$12$ANTQwZ8yNmvgkq/cRDzf0.zn9tNEB1awT/nYNTXgFBravRKZvY/pi',NULL,3,'Activo','2026-05-21 09:11:58','2026-05-21 09:11:58'),(6,'Sergio','Jimenez','Sanchez','4444444444','sergio@gmail.com','Priv. 55, col los angeles','$2y$12$KN2ZSXHEs6306Ry2VA6JJOgmqpRgW5larP0YjYfBZzwn9zqco9neC',NULL,3,'Activo','2026-05-24 03:50:35','2026-05-24 03:50:35');
/*!40000 ALTER TABLE `clientes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `compras`
--

DROP TABLE IF EXISTS `compras`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `compras` (
  `id_compra` int NOT NULL AUTO_INCREMENT,
  `id_proveedor` int DEFAULT NULL,
  `id_empleado` int DEFAULT NULL,
  `com_fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `com_total` decimal(10,2) DEFAULT NULL,
  `com_factura_no` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id_compra`),
  KEY `id_proveedor` (`id_proveedor`),
  KEY `id_empleado` (`id_empleado`),
  CONSTRAINT `compras_ibfk_1` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedores` (`id_proveedor`),
  CONSTRAINT `compras_ibfk_2` FOREIGN KEY (`id_empleado`) REFERENCES `empleados` (`id_empleados`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `compras`
--

LOCK TABLES `compras` WRITE;
/*!40000 ALTER TABLE `compras` DISABLE KEYS */;
INSERT INTO `compras` VALUES (1,1,1,'2026-05-20 05:49:19',2150.00,'FACT-9921'),(2,1,1,'2026-05-21 02:31:35',250.00,'1222-dvs'),(3,1,NULL,'2026-05-21 02:32:21',100.00,'FAC-001'),(4,1,1,'2026-05-21 08:34:51',100.00,'FAC-001'),(5,1,1,'2026-05-21 08:35:07',2250.00,NULL),(10,1,5,'2026-05-23 07:32:38',3600.00,NULL),(11,1,5,'2026-05-23 08:17:54',9000.00,NULL),(12,1,5,'2026-05-24 04:43:24',29000.00,NULL),(13,1,5,'2026-05-24 23:44:52',5000.00,NULL),(14,1,5,'2026-05-25 00:38:19',12500.00,NULL),(15,1,5,'2026-05-25 01:16:13',7500.00,NULL),(16,1,5,'2026-05-25 01:47:10',25200.00,NULL);
/*!40000 ALTER TABLE `compras` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `control_caja`
--

DROP TABLE IF EXISTS `control_caja`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `control_caja` (
  `id_caja` int NOT NULL AUTO_INCREMENT,
  `id_empleado` int DEFAULT NULL,
  `fecha_apertura` datetime DEFAULT CURRENT_TIMESTAMP,
  `monto_inicial` decimal(10,2) NOT NULL,
  `fecha_cierre` datetime DEFAULT NULL,
  `monto_final_esperado` decimal(10,2) DEFAULT NULL,
  `monto_real_cierre` decimal(10,2) DEFAULT NULL,
  `estado` enum('Abierta','Cerrada') DEFAULT 'Abierta',
  PRIMARY KEY (`id_caja`),
  KEY `id_empleado` (`id_empleado`),
  CONSTRAINT `control_caja_ibfk_1` FOREIGN KEY (`id_empleado`) REFERENCES `empleados` (`id_empleados`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `control_caja`
--

LOCK TABLES `control_caja` WRITE;
/*!40000 ALTER TABLE `control_caja` DISABLE KEYS */;
INSERT INTO `control_caja` VALUES (1,1,'2026-05-18 09:00:00',1000.00,'2026-05-18 19:00:00',1430.00,1430.00,'Cerrada'),(2,1,'2026-05-19 23:49:01',1500.00,'2026-05-24 19:11:02',1500.00,1500.00,'Cerrada'),(3,1,'2026-05-24 19:43:37',500.00,'2026-05-24 19:44:33',500.00,500.00,'Cerrada'),(4,1,'2026-05-24 20:14:27',500.00,'2026-05-24 20:14:46',500.00,500.00,'Cerrada'),(5,1,'2026-05-24 20:15:09',233.00,'2026-05-24 20:15:13',233.00,233.00,'Cerrada'),(6,1,'2026-05-24 20:38:46',300.00,'2026-05-24 20:39:06',300.00,300.00,'Cerrada'),(7,1,'2026-05-24 21:14:25',200.00,'2026-05-25 02:18:22',200.00,200.00,'Cerrada'),(8,1,'2026-05-25 02:18:28',300.00,'2026-05-25 02:18:42',300.00,300.00,'Cerrada'),(9,1,'2026-05-25 02:18:57',23.00,'2026-05-25 02:36:12',23.00,23.00,'Cerrada'),(10,1,'2026-05-25 02:43:41',1000.00,'2026-05-25 02:43:42',1000.00,1000.00,'Cerrada'),(11,1,'2026-05-25 02:44:52',1000.00,'2026-05-25 02:48:04',1000.00,1000.00,'Cerrada'),(12,1,'2026-05-25 02:48:07',1000.00,'2026-05-25 03:38:08',1500.00,1500.00,'Cerrada'),(13,1,'2026-05-25 03:38:11',1000.00,'2026-05-25 03:41:00',2765.00,2765.00,'Cerrada'),(14,1,'2026-05-25 03:41:26',200.00,'2026-05-25 04:24:47',4215.00,4215.00,'Cerrada'),(15,1,'2026-05-25 04:24:54',200.00,'2026-05-25 04:25:22',200.00,200.00,'Cerrada'),(16,1,'2026-05-25 04:25:30',200.00,'2026-05-25 04:25:40',200.00,200.00,'Cerrada'),(17,1,'2026-05-25 04:25:48',200.00,NULL,NULL,NULL,'Abierta');
/*!40000 ALTER TABLE `control_caja` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cotizaciones`
--

DROP TABLE IF EXISTS `cotizaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cotizaciones` (
  `id_cotizacion` int NOT NULL AUTO_INCREMENT,
  `id_cliente` int DEFAULT NULL,
  `id_empleado` int DEFAULT NULL,
  `cot_fecha` datetime DEFAULT CURRENT_TIMESTAMP,
  `cot_vigencia_dias` int DEFAULT '15',
  `cot_estado` varchar(20) DEFAULT 'Vigente',
  `cot_total` decimal(10,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_cotizacion`),
  KEY `id_cliente` (`id_cliente`),
  KEY `id_empleado` (`id_empleado`),
  CONSTRAINT `cotizaciones_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`) ON DELETE SET NULL,
  CONSTRAINT `cotizaciones_ibfk_2` FOREIGN KEY (`id_empleado`) REFERENCES `empleados` (`id_empleados`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cotizaciones`
--

LOCK TABLES `cotizaciones` WRITE;
/*!40000 ALTER TABLE `cotizaciones` DISABLE KEYS */;
INSERT INTO `cotizaciones` VALUES (1,1,1,'2026-05-21 00:58:27',15,'Vigente',300.00,'2026-05-21 06:58:27','2026-05-21 06:58:27'),(2,1,1,'2026-05-21 01:04:48',2,'Vigente',2610.00,'2026-05-21 07:04:48','2026-05-21 07:04:48'),(3,2,5,'2026-05-25 04:42:44',1,'Vigente',1870.00,'2026-05-25 04:42:44','2026-05-25 04:42:44'),(4,6,1,'2026-05-24 23:03:29',15,'Vigente',3080.00,'2026-05-24 23:03:29','2026-05-24 23:03:29');
/*!40000 ALTER TABLE `cotizaciones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `detalle_cita_servicios`
--

DROP TABLE IF EXISTS `detalle_cita_servicios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `detalle_cita_servicios` (
  `id_det_cita` int NOT NULL AUTO_INCREMENT,
  `id_cita` int DEFAULT NULL,
  `id_servicio` int DEFAULT NULL,
  PRIMARY KEY (`id_det_cita`),
  KEY `id_cita` (`id_cita`),
  KEY `id_servicio` (`id_servicio`),
  CONSTRAINT `detalle_cita_servicios_ibfk_1` FOREIGN KEY (`id_cita`) REFERENCES `citas` (`id_cita`),
  CONSTRAINT `detalle_cita_servicios_ibfk_2` FOREIGN KEY (`id_servicio`) REFERENCES `servicios` (`id_servicio`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `detalle_cita_servicios`
--

LOCK TABLES `detalle_cita_servicios` WRITE;
/*!40000 ALTER TABLE `detalle_cita_servicios` DISABLE KEYS */;
INSERT INTO `detalle_cita_servicios` VALUES (1,1,1),(2,2,2);
/*!40000 ALTER TABLE `detalle_cita_servicios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `detalle_compras`
--

DROP TABLE IF EXISTS `detalle_compras`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `detalle_compras` (
  `id_det_compra` int NOT NULL AUTO_INCREMENT,
  `id_compra` int DEFAULT NULL,
  `id_producto` int DEFAULT NULL,
  `det_cantidad` int NOT NULL,
  `det_costo_unitario` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id_det_compra`),
  KEY `id_compra` (`id_compra`),
  KEY `id_producto` (`id_producto`),
  CONSTRAINT `detalle_compras_ibfk_1` FOREIGN KEY (`id_compra`) REFERENCES `compras` (`id_compra`),
  CONSTRAINT `detalle_compras_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `producto` (`id_producto`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `detalle_compras`
--

LOCK TABLES `detalle_compras` WRITE;
/*!40000 ALTER TABLE `detalle_compras` DISABLE KEYS */;
INSERT INTO `detalle_compras` VALUES (1,1,1,10,150.00),(2,1,2,5,130.00),(3,4,1,2,50.00),(4,5,1,9,250.00),(9,10,2,20,180.00),(10,11,2,50,180.00),(11,12,4,20,1450.00),(12,13,1,20,250.00),(13,14,1,50,250.00),(14,15,1,30,250.00),(15,16,5,60,420.00);
/*!40000 ALTER TABLE `detalle_compras` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `detalle_cotizaciones`
--

DROP TABLE IF EXISTS `detalle_cotizaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `detalle_cotizaciones` (
  `id_detalle_cotizacion` int NOT NULL AUTO_INCREMENT,
  `id_cotizacion` int NOT NULL,
  `id_producto` int NOT NULL,
  `det_cantidad` int NOT NULL,
  `det_precio_unitario` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id_detalle_cotizacion`),
  KEY `id_cotizacion` (`id_cotizacion`),
  KEY `id_producto` (`id_producto`),
  CONSTRAINT `detalle_cotizaciones_ibfk_1` FOREIGN KEY (`id_cotizacion`) REFERENCES `cotizaciones` (`id_cotizacion`) ON DELETE CASCADE,
  CONSTRAINT `detalle_cotizaciones_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `producto` (`id_producto`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `detalle_cotizaciones`
--

LOCK TABLES `detalle_cotizaciones` WRITE;
/*!40000 ALTER TABLE `detalle_cotizaciones` DISABLE KEYS */;
INSERT INTO `detalle_cotizaciones` VALUES (1,1,1,2,150.00),(2,2,2,1,180.00),(3,2,3,7,140.00),(4,2,4,1,1450.00),(5,3,5,1,420.00),(6,3,4,1,1450.00),(7,4,4,1,1450.00),(8,4,2,1,180.00),(9,4,4,1,1450.00);
/*!40000 ALTER TABLE `detalle_cotizaciones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `detalle_mantenimiento_insumos`
--

DROP TABLE IF EXISTS `detalle_mantenimiento_insumos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `detalle_mantenimiento_insumos` (
  `id_det_mant` int NOT NULL AUTO_INCREMENT,
  `id_mantenimiento` int DEFAULT NULL,
  `id_producto` int DEFAULT NULL,
  `insumo_cantidad` int DEFAULT NULL,
  `insumo_precio_unitario` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id_det_mant`),
  KEY `id_mantenimiento` (`id_mantenimiento`),
  KEY `id_producto` (`id_producto`),
  CONSTRAINT `detalle_mantenimiento_insumos_ibfk_1` FOREIGN KEY (`id_mantenimiento`) REFERENCES `mantenimiento` (`id_mantenimiento`),
  CONSTRAINT `detalle_mantenimiento_insumos_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `producto` (`id_producto`)
) ENGINE=InnoDB AUTO_INCREMENT=106 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `detalle_mantenimiento_insumos`
--

LOCK TABLES `detalle_mantenimiento_insumos` WRITE;
/*!40000 ALTER TABLE `detalle_mantenimiento_insumos` DISABLE KEYS */;
INSERT INTO `detalle_mantenimiento_insumos` VALUES (52,6,6,1,45.00),(53,6,1,5,250.00),(54,5,6,1,45.00),(55,5,1,5,250.00),(85,1,2,1,180.00),(86,1,3,1,140.00),(87,1,5,1,420.00),(89,8,5,1,420.00),(90,9,5,1,420.00),(91,13,5,1,420.00),(93,4,5,2,420.00),(102,14,5,5,420.00),(105,15,5,7,420.00);
/*!40000 ALTER TABLE `detalle_mantenimiento_insumos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `detalle_mantenimiento_servicios`
--

DROP TABLE IF EXISTS `detalle_mantenimiento_servicios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `detalle_mantenimiento_servicios` (
  `id_det_mant_ser` int NOT NULL AUTO_INCREMENT,
  `id_mantenimiento` int DEFAULT NULL,
  `id_servicio` int DEFAULT NULL,
  `precio_aplicado` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id_det_mant_ser`),
  KEY `id_mantenimiento` (`id_mantenimiento`),
  KEY `id_servicio` (`id_servicio`),
  CONSTRAINT `detalle_mantenimiento_servicios_ibfk_1` FOREIGN KEY (`id_mantenimiento`) REFERENCES `mantenimiento` (`id_mantenimiento`),
  CONSTRAINT `detalle_mantenimiento_servicios_ibfk_2` FOREIGN KEY (`id_servicio`) REFERENCES `servicios` (`id_servicio`)
) ENGINE=InnoDB AUTO_INCREMENT=71 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `detalle_mantenimiento_servicios`
--

LOCK TABLES `detalle_mantenimiento_servicios` WRITE;
/*!40000 ALTER TABLE `detalle_mantenimiento_servicios` DISABLE KEYS */;
INSERT INTO `detalle_mantenimiento_servicios` VALUES (33,6,1,350.00),(34,5,2,788.00),(59,1,1,350.00),(60,7,2,10.00),(61,8,1,11.00),(62,9,1,300.00),(63,10,2,500.00),(64,13,2,11.00),(70,14,1,111.00);
/*!40000 ALTER TABLE `detalle_mantenimiento_servicios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `detalle_ventas`
--

DROP TABLE IF EXISTS `detalle_ventas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `detalle_ventas` (
  `id_detalle` int NOT NULL AUTO_INCREMENT,
  `id_venta` int DEFAULT NULL,
  `id_producto` int DEFAULT NULL,
  `det_cantidad` int NOT NULL,
  `det_precio_unitario` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id_detalle`),
  KEY `id_venta` (`id_venta`),
  KEY `id_producto` (`id_producto`),
  CONSTRAINT `detalle_ventas_ibfk_1` FOREIGN KEY (`id_venta`) REFERENCES `ventas` (`id_venta`),
  CONSTRAINT `detalle_ventas_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `producto` (`id_producto`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `detalle_ventas`
--

LOCK TABLES `detalle_ventas` WRITE;
/*!40000 ALTER TABLE `detalle_ventas` DISABLE KEYS */;
INSERT INTO `detalle_ventas` VALUES (1,1,1,1,250.00),(2,1,2,1,180.00),(3,2,4,1,1450.00),(4,3,1,1,100.00),(5,4,2,1,180.00),(6,4,2,1,180.00),(7,5,2,1,180.00),(8,5,3,7,140.00),(9,5,4,1,1450.00),(10,6,2,1,180.00),(11,6,3,7,140.00),(12,6,4,1,1450.00),(13,7,2,20,180.00),(14,8,2,50,180.00),(15,9,4,1,1450.00),(16,10,2,28,180.00),(17,11,6,1,45.00),(18,12,1,7,250.00),(19,13,3,4,140.00),(20,14,1,1,250.00),(21,15,1,1,250.00),(22,16,6,1,45.00),(23,17,6,6,45.00),(24,18,4,1,1450.00),(25,19,4,1,1450.00),(26,20,5,1,420.00),(27,21,5,1,420.00),(28,22,5,1,420.00),(29,23,5,1,420.00),(30,24,6,1,45.00),(31,25,5,1,420.00),(32,26,5,1,420.00),(33,27,5,1,420.00),(34,27,4,1,1450.00);
/*!40000 ALTER TABLE `detalle_ventas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `empleados`
--

DROP TABLE IF EXISTS `empleados`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `empleados` (
  `id_empleados` int NOT NULL AUTO_INCREMENT,
  `emp_nombre` varchar(50) NOT NULL,
  `emp_apaterno` varchar(50) NOT NULL,
  `emp_amaterno` varchar(50) DEFAULT NULL,
  `emp_telefono` varchar(15) DEFAULT NULL,
  `emp_correo` varchar(100) NOT NULL,
  `emp_direccion` text,
  `emp_rol` enum('Administrador','Vendedor','Mecanico') NOT NULL,
  `emp_password` varchar(255) NOT NULL,
  `emp_estado` enum('Activo','Inactivo') DEFAULT 'Activo',
  PRIMARY KEY (`id_empleados`),
  UNIQUE KEY `emp_correo` (`emp_correo`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `empleados`
--

LOCK TABLES `empleados` WRITE;
/*!40000 ALTER TABLE `empleados` DISABLE KEYS */;
INSERT INTO `empleados` VALUES (1,'Administrador','General','Sistema','5550001111','admin@tallermoto.com','Av. Principal 123','Administrador','$2y$12$ER/PiQOfm6kl.oKWbE.uMO8op8Vhwu82LIqnbD6OMSob45wjM/gRu','Activo'),(2,'Pedro','MartÃ­nez','SÃ¡nchez','5552223333','pedro.vendedor@tallermoto.com','Calle 5 de Mayo #45','Vendedor','$2y$12$2.GR6yrOYk4JdXb4RaKkg.ietgE.WxyRuAZqiunEfTM1.daT23St.','Activo'),(3,'Jorge','RamÃ­rez','Castro','5554445555','jorge.mecanico@tallermoto.com','Col. Centro, Calle 3','Mecanico','$2y$12$l/xLzQMB86uAtKpfNjhas./gM/ePJLmPgjx/DfpTRiCDSGcW1iTmq','Activo'),(4,'Luis','FernÃ¡ndez','GarcÃ­a','5556667777','luis.mecanico@tallermoto.com','Av. Las Torres #89','Mecanico','$2y$12$qyTTQqvXainvf9npSn9bOe2MNTRUaO3yOCQDPXk5vyDj.5Eye.wOW','Activo'),(5,'Alexa','chilchoa','muÃ±oz','2481760556','thelosxtnation3467@gmail.com','si','Administrador','$2y$12$83cCJ.HPsvl7kaDYwtPume5zrqhgIXzmJtqUjtyE2GifYPy9S2L46','Activo'),(7,'Ana Karent','Moreno','GarcÃ­a','1234567890','ana@gmail.com','Tlaxcala, MÃ©xico','Vendedor','$2y$12$JNpc17lDC7InhwK8euhpdOqjxlctMKiJavyphUfD6lZ98xlnnUrNW','Activo'),(8,'Ana Karent','Moreno','GarcÃ­a','1234567890','iv@gmail.com','Tlaxcala, MÃ©xico','Vendedor','$2y$12$OFe24NcrRYkLH13LnHWWDOGmALBEOEyroHvtn5J4M8SU86somN6I.','Activo');
/*!40000 ALTER TABLE `empleados` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventarios`
--

DROP TABLE IF EXISTS `inventarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventarios` (
  `id_inventario` int NOT NULL AUTO_INCREMENT,
  `id_producto` int DEFAULT NULL,
  `codigo_producto` varchar(50) DEFAULT NULL,
  `nombre_producto` varchar(100) DEFAULT NULL,
  `marca` varchar(50) DEFAULT NULL,
  `categoria` varchar(50) DEFAULT NULL,
  `stock` int DEFAULT '0',
  `precio_unitario` decimal(10,2) DEFAULT '0.00',
  `iva` decimal(10,2) DEFAULT '0.00',
  `precio_total` decimal(10,2) DEFAULT '0.00',
  `id_proveedor` int DEFAULT NULL,
  `proveedor` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_inventario`),
  KEY `id_producto` (`id_producto`),
  KEY `id_proveedor` (`id_proveedor`),
  CONSTRAINT `inventarios_ibfk_1` FOREIGN KEY (`id_producto`) REFERENCES `producto` (`id_producto`) ON DELETE SET NULL,
  CONSTRAINT `inventarios_ibfk_2` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedores` (`id_proveedor`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventarios`
--

LOCK TABLES `inventarios` WRITE;
/*!40000 ALTER TABLE `inventarios` DISABLE KEYS */;
INSERT INTO `inventarios` VALUES (4,1,NULL,'Producto',NULL,NULL,0,0.00,0.00,0.00,NULL,NULL,'2026-05-21 08:31:35','2026-05-21 08:31:35');
/*!40000 ALTER TABLE `inventarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mantenimiento`
--

DROP TABLE IF EXISTS `mantenimiento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mantenimiento` (
  `id_mantenimiento` int NOT NULL AUTO_INCREMENT,
  `id_cliente` int DEFAULT NULL,
  `id_mecanico` int DEFAULT NULL,
  `id_cita` int DEFAULT NULL,
  `moto_modelo` varchar(100) DEFAULT NULL,
  `moto_llegada_descripcion` text,
  `trabajo_realizado` text,
  `fecha_inicio` datetime DEFAULT CURRENT_TIMESTAMP,
  `fecha_termino` datetime DEFAULT NULL,
  `mantenimiento_total` decimal(10,2) DEFAULT NULL,
  `estado_servicio` enum('En Proceso','Terminado','Entregado') DEFAULT 'En Proceso',
  `tipo` enum('Preventivo','Correctivo') DEFAULT 'Correctivo',
  PRIMARY KEY (`id_mantenimiento`),
  KEY `id_cliente` (`id_cliente`),
  KEY `id_mecanico` (`id_mecanico`),
  KEY `id_cita` (`id_cita`),
  CONSTRAINT `mantenimiento_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`),
  CONSTRAINT `mantenimiento_ibfk_2` FOREIGN KEY (`id_mecanico`) REFERENCES `empleados` (`id_empleados`),
  CONSTRAINT `mantenimiento_ibfk_3` FOREIGN KEY (`id_cita`) REFERENCES `citas` (`id_cita`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mantenimiento`
--

LOCK TABLES `mantenimiento` WRITE;
/*!40000 ALTER TABLE `mantenimiento` DISABLE KEYS */;
INSERT INTO `mantenimiento` VALUES (1,3,3,1,'Italika 150Z 2024','Sucia, no enciende marcha elÃ©ctrica, ruidos en motor.','Se realizÃ³ cambio de bujÃ­a, cambio de aceite y lavado de carburador.','2026-05-19 23:49:52',NULL,1090.00,'Terminado','Correctivo'),(4,3,4,NULL,'si','Servicio Completo','Se realizo limpiado de motor',NULL,NULL,840.00,'En Proceso','Correctivo'),(5,4,4,NULL,'si','sss',NULL,'2026-05-24 02:06:59',NULL,2083.00,'En Proceso','Correctivo'),(6,2,4,NULL,'s','wsi','so','2026-05-24 04:34:37',NULL,1645.00,'En Proceso','Correctivo'),(7,5,3,NULL,'Italika 150Z 2024','si','na','2026-05-25 01:50:36',NULL,2110.00,'En Proceso','Correctivo'),(8,4,4,NULL,'Italika 150Z 2024ss','fsdvs','cascac','2026-05-25 01:57:41',NULL,431.00,'En Proceso','Correctivo'),(9,5,3,NULL,'Italika 150Z 2024','wwcw','w','2026-05-25 02:01:00',NULL,720.00,'En Proceso','Correctivo'),(10,2,3,NULL,'Italika 150Z 2024','ssw','wdw','2026-05-25 02:15:55',NULL,500.00,'En Proceso','Correctivo'),(11,2,4,NULL,'Italika 150Z 2024','mm','mm','2026-05-25 02:16:57',NULL,0.00,'En Proceso','Correctivo'),(12,2,3,NULL,'ikkk','dvd','vdsv','2026-05-25 02:17:56',NULL,0.00,'En Proceso','Correctivo'),(13,2,4,NULL,'Italika 150Z 2024','111','11','2026-05-25 02:29:34',NULL,431.00,'En Proceso','Correctivo'),(14,4,4,NULL,'gyjf','dbb','dbdb',NULL,NULL,2211.00,'En Proceso','Correctivo'),(15,4,4,NULL,'2','22','2',NULL,NULL,2940.00,'En Proceso','Correctivo');
/*!40000 ALTER TABLE `mantenimiento` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mantenimiento_productos`
--

DROP TABLE IF EXISTS `mantenimiento_productos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mantenimiento_productos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_mantenimiento` bigint unsigned NOT NULL,
  `id_producto` bigint unsigned NOT NULL,
  `cantidad` int NOT NULL,
  `precio_venta` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mantenimiento_productos`
--

LOCK TABLES `mantenimiento_productos` WRITE;
/*!40000 ALTER TABLE `mantenimiento_productos` DISABLE KEYS */;
/*!40000 ALTER TABLE `mantenimiento_productos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `marcas`
--

DROP TABLE IF EXISTS `marcas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `marcas` (
  `id_marca` int NOT NULL AUTO_INCREMENT,
  `mar_nombre` varchar(50) NOT NULL,
  `mar_descripcion` text,
  `mar_estado` enum('Activo','Inactivo') DEFAULT 'Activo',
  PRIMARY KEY (`id_marca`),
  UNIQUE KEY `mar_nombre` (`mar_nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `marcas`
--

LOCK TABLES `marcas` WRITE;
/*!40000 ALTER TABLE `marcas` DISABLE KEYS */;
INSERT INTO `marcas` VALUES (1,'Honda','Fabricante japonÃ©s de motocicletas','Activo'),(2,'Yamaha','Fabricante japonÃ©s de motocicletas','Activo'),(3,'Suzuki','Fabricante japonÃ©s de motocicletas','Activo'),(4,'Kawasaki','Fabricante japonÃ©s de motocicletas','Activo'),(5,'Italika','Fabricante mexicano de motocicletas','Activo'),(6,'Vortex','Marca de refacciones genÃ©ricas','Activo'),(7,'Brembo','Fabricante italiano de sistemas de freno','Activo'),(8,'DID','Fabricante de cadenas y transmisiones','Activo'),(9,'NGK','Fabricante de bujÃ­as','Activo'),(10,'Motul','Fabricante de aceites y lubricantes','Activo');
/*!40000 ALTER TABLE `marcas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
INSERT INTO `personal_access_tokens` VALUES (1,'App\\Models\\Empleado',4,'auth_token','7b3a3b61b4bd47e21295b3e05e361968e778269df65b343050ce089f1e240960','[\"*\"]',NULL,NULL,'2026-05-20 13:05:02','2026-05-20 13:05:02'),(2,'App\\Models\\Empleado',2,'auth_token','5225b8a0dee9d474aef89f8d76a7f6ace58f38695829848238b89475229ed7e8','[\"*\"]',NULL,NULL,'2026-05-20 13:10:29','2026-05-20 13:10:29'),(5,'App\\Models\\Empleado',2,'auth_token','d390cc4b3f1ffe07ae987e11d5b5141e4e048227fcf81f707697b18fcddca864','[\"*\"]',NULL,NULL,'2026-05-20 13:44:01','2026-05-20 13:44:01'),(6,'App\\Models\\Empleado',2,'auth_token','fada46680f27633c0b53e86ec648fdec1b07516f7fe19799dbea2533ef2c9e27','[\"*\"]',NULL,NULL,'2026-05-20 13:45:48','2026-05-20 13:45:48'),(7,'App\\Models\\Empleado',1,'auth_token','a0f7fddee4f58af1099bbdd846953c2d895118dabc2fa6dbec88f8b894aeeec7','[\"*\"]',NULL,NULL,'2026-05-20 13:46:22','2026-05-20 13:46:22'),(9,'App\\Models\\Cliente',1,'auth_token','7cf1000f736f184399872f2abc95130ee1001a07f513e698fbd5107c7bfeba23','[\"*\"]',NULL,NULL,'2026-05-20 14:03:07','2026-05-20 14:03:07'),(10,'App\\Models\\Empleado',3,'auth_token','3bfc860c38bc66278539a21e575cf6fea4f50cf394673ee6d67bb6fc59689045','[\"*\"]',NULL,NULL,'2026-05-20 14:38:01','2026-05-20 14:38:01'),(11,'App\\Models\\Empleado',5,'auth_token','eca4f7281020ec1f2f012e9dd2b10a901137f380d3447caa05780407519bd2e1','[\"*\"]',NULL,NULL,'2026-05-21 10:36:02','2026-05-21 10:36:02'),(14,'App\\Models\\Empleado',5,'auth_token','7a2d61571270ce8908a6e445ccd0babafaac0ccd328710517a7ad1e618029ded','[\"*\"]',NULL,NULL,'2026-05-24 03:39:47','2026-05-24 03:39:47');
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `producto`
--

DROP TABLE IF EXISTS `producto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `producto` (
  `id_producto` int NOT NULL AUTO_INCREMENT,
  `pro_codigo` varchar(50) NOT NULL,
  `pro_nombre` varchar(100) NOT NULL,
  `pro_tipo` varchar(50) DEFAULT NULL,
  `id_marca` int DEFAULT NULL,
  `pro_marca` varchar(50) DEFAULT NULL,
  `pro_descripcion` text,
  `pro_precio_venta` decimal(10,2) NOT NULL,
  `pro_stock` int DEFAULT '0',
  `id_categoria` int DEFAULT NULL,
  `id_proveedor` int DEFAULT NULL,
  PRIMARY KEY (`id_producto`),
  UNIQUE KEY `pro_codigo` (`pro_codigo`),
  KEY `id_categoria` (`id_categoria`),
  KEY `id_proveedor` (`id_proveedor`),
  KEY `id_marca` (`id_marca`),
  CONSTRAINT `producto_ibfk_1` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id_categoria`),
  CONSTRAINT `producto_ibfk_2` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedores` (`id_proveedor`),
  CONSTRAINT `producto_ibfk_3` FOREIGN KEY (`id_marca`) REFERENCES `marcas` (`id_marca`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `producto`
--

LOCK TABLES `producto` WRITE;
/*!40000 ALTER TABLE `producto` DISABLE KEYS */;
INSERT INTO `producto` VALUES (1,'PROD-001','Balatas Delanteras HD','RefacciÃ³n',NULL,'Brembo','Balatas de alta duraciÃ³n para scooter y motos de trabajo',250.00,70,2,1),(2,'PROD-002','Aceite SintÃ©tico 10W40 1L','Insumo',NULL,'Motul','Aceite sintÃ©tico para motor de 4 tiempos',180.00,1,1,1),(3,'PROD-003','BujÃ­a Iridium CR7HIX','RefacciÃ³n',NULL,'NGK','BujÃ­a de alta eficiencia para mejor combustiÃ³n',140.00,4,3,3),(4,'PROD-004','Casco Certificado Certus','Accesorio',NULL,'SHAFT','Casco integral con certificaciÃ³n DOT',1450.00,17,4,2),(5,'PROD-005','Kit de Arrastre Completo','RefacciÃ³n',NULL,'Choho','Cadena, piÃ±Ã³n y corona para moto 150cc',420.00,27,1,3),(6,'1233-SCC','Balata azul','Refaccion',NULL,'DINAMO','si',45.00,106,4,1);
/*!40000 ALTER TABLE `producto` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `productos`
--

DROP TABLE IF EXISTS `productos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `productos` (
  `id_producto` int unsigned NOT NULL AUTO_INCREMENT,
  `pro_codigo` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pro_nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pro_tipo` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_marca` int unsigned DEFAULT NULL,
  `pro_marca` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pro_descripcion` text COLLATE utf8mb4_unicode_ci,
  `pro_precio_venta` decimal(10,2) NOT NULL DEFAULT '0.00',
  `pro_stock` int NOT NULL DEFAULT '0',
  `id_categoria` int unsigned DEFAULT NULL,
  `id_proveedor` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_producto`),
  UNIQUE KEY `productos_pro_codigo_unique` (`pro_codigo`),
  KEY `productos_pro_codigo_index` (`pro_codigo`),
  KEY `productos_pro_stock_index` (`pro_stock`),
  KEY `productos_id_marca_index` (`id_marca`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `productos`
--

LOCK TABLES `productos` WRITE;
/*!40000 ALTER TABLE `productos` DISABLE KEYS */;
/*!40000 ALTER TABLE `productos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `proveedor_visitas`
--

DROP TABLE IF EXISTS `proveedor_visitas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `proveedor_visitas` (
  `id_visita` int NOT NULL AUTO_INCREMENT,
  `id_proveedor` int NOT NULL,
  `dia_semana` tinyint NOT NULL COMMENT '0=Domingo, 1=Lunes, 2=Martes, 3=MiÃ©rcoles, 4=Jueves, 5=Viernes, 6=SÃ¡bado',
  `hora_visita` time NOT NULL DEFAULT '09:00:00',
  `activo` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id_visita`),
  KEY `id_proveedor` (`id_proveedor`),
  CONSTRAINT `proveedor_visitas_ibfk_1` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedores` (`id_proveedor`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `proveedor_visitas`
--

LOCK TABLES `proveedor_visitas` WRITE;
/*!40000 ALTER TABLE `proveedor_visitas` DISABLE KEYS */;
INSERT INTO `proveedor_visitas` VALUES (1,1,1,'09:00:00',1);
/*!40000 ALTER TABLE `proveedor_visitas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `proveedores`
--

DROP TABLE IF EXISTS `proveedores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `proveedores` (
  `id_proveedor` int NOT NULL AUTO_INCREMENT,
  `prov_nombre` varchar(100) NOT NULL,
  `prov_contacto` varchar(100) DEFAULT NULL,
  `prov_telefono` varchar(15) DEFAULT NULL,
  `prov_email` varchar(100) DEFAULT NULL,
  `prov_direccion` text,
  PRIMARY KEY (`id_proveedor`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `proveedores`
--

LOCK TABLES `proveedores` WRITE;
/*!40000 ALTER TABLE `proveedores` DISABLE KEYS */;
INSERT INTO `proveedores` VALUES (1,'Refaccionaria MotoExpress','Ing. Javier Arce','5551112222','ventas@motoexpress.com','Av. Central #45, Col. Centro'),(2,'Accesorios BikerZone','Lic. Elena Ramos','5553334444','contacto@bikerzone.com','Calle Victoria #102, Industrial'),(3,'Distribuidora Italika Oficial','Soporte Comercial','5555556666','mayoreo@italika.mx','Parque Industrial Norte, Bodega 4'),(5,'Mauricio RodrÃ­guez','mauri@gmail.com','5165489156','mau@gmail.com','Tlaxcala, Puebla'),(6,'VentoExpress','vento@express.com','0123654987','vent@express.com','Av. Siempre Viva #5, Puebla'),(7,'Dinamo','lic','6564888989','SAWSsssOV@gmail.com','saca');
/*!40000 ALTER TABLE `proveedores` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `servicios`
--

DROP TABLE IF EXISTS `servicios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `servicios` (
  `id_servicio` int NOT NULL AUTO_INCREMENT,
  `ser_nombre` varchar(100) NOT NULL,
  `ser_descripcion` text,
  `ser_precio_mano_obra` decimal(10,2) NOT NULL,
  `id_categoria` int DEFAULT NULL,
  PRIMARY KEY (`id_servicio`),
  KEY `id_categoria` (`id_categoria`),
  CONSTRAINT `servicios_ibfk_1` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id_categoria`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `servicios`
--

LOCK TABLES `servicios` WRITE;
/*!40000 ALTER TABLE `servicios` DISABLE KEYS */;
INSERT INTO `servicios` VALUES (1,'AfinaciÃ³n Completa','Lavado de carburador/inyector, cambio de bujÃ­a, cambio de aceite y filtro',350.00,5),(2,'Cambio de Balatas Delanteras','Desmontaje, limpieza de cÃ¡liper y montaje de balatas',120.00,4),(3,'RevisiÃ³n Sistema ElÃ©ctrico','DiagnÃ³stico de baterÃ­a, estator y regulador de voltaje',200.00,3),(4,'AfinaciÃ³n a media','Un chequeo breve',350.00,1);
/*!40000 ALTER TABLE `servicios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ventas`
--

DROP TABLE IF EXISTS `ventas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ventas` (
  `id_venta` int NOT NULL AUTO_INCREMENT,
  `id_cliente` int DEFAULT NULL,
  `id_empleado` int DEFAULT NULL,
  `id_caja` int DEFAULT NULL,
  `ven_fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ven_total` decimal(10,2) NOT NULL,
  `tipo_pago` enum('Efectivo','Tarjeta','Transferencia') DEFAULT NULL,
  PRIMARY KEY (`id_venta`),
  KEY `id_cliente` (`id_cliente`),
  KEY `id_empleado` (`id_empleado`),
  KEY `id_caja` (`id_caja`),
  CONSTRAINT `ventas_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`),
  CONSTRAINT `ventas_ibfk_2` FOREIGN KEY (`id_empleado`) REFERENCES `empleados` (`id_empleados`),
  CONSTRAINT `ventas_ibfk_3` FOREIGN KEY (`id_caja`) REFERENCES `control_caja` (`id_caja`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ventas`
--

LOCK TABLES `ventas` WRITE;
/*!40000 ALTER TABLE `ventas` DISABLE KEYS */;
INSERT INTO `ventas` VALUES (1,1,2,1,'2026-05-18 20:30:00',430.00,'Efectivo'),(2,2,2,2,'2026-05-20 05:49:10',1450.00,'Tarjeta'),(3,NULL,1,1,'2026-05-21 08:26:06',100.00,'Efectivo'),(4,NULL,1,1,'2026-05-21 08:26:11',360.00,'Efectivo'),(5,1,1,1,'2026-05-21 08:28:14',2610.00,'Efectivo'),(6,1,1,1,'2026-05-21 08:28:35',2610.00,'Efectivo'),(7,NULL,1,1,'2026-05-23 08:16:24',3600.00,'Efectivo'),(8,NULL,1,1,'2026-05-23 08:18:19',9000.00,'Efectivo'),(9,NULL,1,1,'2026-05-24 03:06:11',1450.00,'Efectivo'),(10,NULL,1,1,'2026-05-24 05:10:58',5040.00,'Efectivo'),(11,NULL,1,1,'2026-05-24 18:52:06',45.00,'Efectivo'),(12,NULL,1,1,'2026-05-24 18:52:25',1750.00,'Efectivo'),(13,NULL,1,1,'2026-05-25 01:55:43',560.00,'Efectivo'),(14,6,4,12,'2026-05-25 02:48:49',250.00,'Tarjeta'),(15,6,4,12,'2026-05-25 02:48:50',250.00,'Tarjeta'),(16,2,4,13,'2026-05-25 03:39:04',45.00,'Efectivo'),(17,2,4,13,'2026-05-25 03:39:16',270.00,'Efectivo'),(18,2,4,13,'2026-05-25 03:40:56',1450.00,'Efectivo'),(19,2,4,14,'2026-05-25 03:45:35',1450.00,'Efectivo'),(20,NULL,1,14,'2026-05-25 03:48:12',420.00,'Efectivo'),(21,NULL,1,14,'2026-05-25 03:48:36',420.00,'Efectivo'),(22,NULL,1,14,'2026-05-25 03:57:01',420.00,'Efectivo'),(23,NULL,1,14,'2026-05-25 03:57:29',420.00,'Efectivo'),(24,2,4,14,'2026-05-25 04:00:12',45.00,'Efectivo'),(25,NULL,1,14,'2026-05-25 04:01:00',420.00,'Efectivo'),(26,NULL,1,14,'2026-05-25 04:05:02',420.00,'Efectivo'),(27,2,5,17,'2026-05-25 04:46:53',1870.00,'Efectivo');
/*!40000 ALTER TABLE `ventas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'railway'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-25  0:34:58

