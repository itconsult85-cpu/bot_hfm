/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.11.14-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: db_hfm_bot
-- ------------------------------------------------------
-- Server version	10.11.14-MariaDB-0ubuntu0.24.04.1

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
-- Table structure for table `clients`
--

DROP TABLE IF EXISTS `clients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `clients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama` varchar(100) DEFAULT NULL,
  `no_wa` varchar(20) DEFAULT NULL,
  `last_active` date DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clients`
--

LOCK TABLES `clients` WRITE;
/*!40000 ALTER TABLE `clients` DISABLE KEYS */;
INSERT INTO `clients` VALUES
(1,'Budi Aktif','628111111111','2026-05-11'),
(2,'Andi Pasif','628222222222','2026-04-06'),
(3,'Siti Santai','628333333333','2026-04-01'),
(4,'Rudi Rebahan','628444444444','2026-03-27');
/*!40000 ALTER TABLE `clients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tb_admin`
--

DROP TABLE IF EXISTS `tb_admin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tb_admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tb_admin`
--

LOCK TABLES `tb_admin` WRITE;
/*!40000 ALTER TABLE `tb_admin` DISABLE KEYS */;
INSERT INTO `tb_admin` VALUES
(2,'admin','$2y$10$rLBG2wqpaG6O4CH56qUmV.vgpu1CDNIWt6PH6EMMcHkC0bA7/POXu','Administrator Utama','2026-05-15 22:48:45');
/*!40000 ALTER TABLE `tb_admin` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tb_member_vip`
--

DROP TABLE IF EXISTS `tb_member_vip`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tb_member_vip` (
  `id_hfm` varchar(50) NOT NULL,
  `no_wa` varchar(20) NOT NULL,
  `nama` varchar(100) DEFAULT NULL,
  `deposit` decimal(15,2) DEFAULT 0.00,
  `currency` varchar(10) DEFAULT 'USD',
  `status` varchar(20) DEFAULT 'aktif',
  `last_trade` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_hfm`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tb_member_vip`
--

LOCK TABLES `tb_member_vip` WRITE;
/*!40000 ALTER TABLE `tb_member_vip` DISABLE KEYS */;
INSERT INTO `tb_member_vip` VALUES
('135130391','6285233333718','Sandri Sandri',3000000.00,'IDR','aktif','2026-05-22 20:05:45','2026-05-22 01:17:23','2026-05-24 12:08:27'),
('198178841','6281298259963','Andry Widjaya',1000000.00,'IDR','aktif','2026-05-22 08:57:11','2026-05-23 04:30:42','2026-05-24 12:08:11'),
('198183440','6281906781791','Yoga Rasyid',100000.00,'IDR','aktif','2026-05-21 10:15:48','2026-05-21 05:29:29','2026-05-24 12:08:35'),
('198186025','6282288557229',NULL,100365.08,'USD','lepas_ib','0000-00-00 00:00:00','2026-05-21 14:04:03','2026-05-22 23:23:13'),
('198186630','6285330237373','Reo Herpandika',800000.00,'IDR','aktif','2026-05-22 21:47:51','2026-05-21 18:13:56','2026-05-24 12:08:29'),
('198186704','6282246689091','STEVEN -',150213.71,'IDR','aktif','0000-00-00 00:00:00','2026-05-22 05:52:45','2026-05-24 12:08:24'),
('198187120','6282313437725','Fikry Ardiansyah Wasilu',100000.00,'IDR','aktif','0000-00-00 00:00:00','2026-05-22 03:01:04','2026-05-24 12:08:26'),
('198188472','6282146365116','Budiono Budiono',16.95,'USD','aktif','0000-00-00 00:00:00','2026-05-22 12:40:15','2026-05-24 12:08:17'),
('198188902','6281331154970','aditya ferdie gale saputra',10.00,'USD','aktif','0000-00-00 00:00:00','2026-05-22 15:08:08','2026-05-24 12:08:14'),
('198188935','6282310113500','Fatwan Syawal',300000.00,'IDR','aktif','2026-05-22 19:34:03','2026-05-22 15:03:06','2026-05-24 12:08:15'),
('205028661','6281339775687','Helmi Ahmad Fauzy',1134.00,'USC','aktif','2026-05-21 07:26:04','2026-05-21 05:06:55','2026-05-24 12:08:36'),
('205030549','6281335579081','Salamun -',1145.00,'USC','aktif','2026-05-22 18:05:57','2026-05-21 10:24:40','2026-05-24 12:08:34'),
('205032876','6281244268731','Moh Faisal Hinelo',3959.00,'USC','aktif','2026-05-22 13:37:03','2026-05-22 10:24:04','2026-05-24 12:08:20'),
('205034618','628987790675','Mahrani Mahrani',1129.00,'USC','aktif','2026-05-22 19:48:12','2026-05-22 11:45:08','2026-05-24 12:08:18'),
('205036301','6281259430000','Mohammad Koharudin Nasution',1200.00,'USC','aktif','0000-00-00 00:00:00','2026-05-23 00:14:27','2026-05-24 12:08:13'),
('205037254','6281241880194','Bayusena Efendi',4249.00,'USC','aktif','0000-00-00 00:00:00','2026-05-23 12:08:10','2026-05-24 12:08:08'),
('205039438','6282199382699','Rezky Djafar',564.00,'USC','aktif','0000-00-00 00:00:00','2026-05-24 17:25:41','2026-05-25 00:25:41'),
('205040366','6281255334537','Zainal Fajri',1134.00,'USC','aktif','0000-00-00 00:00:00','2026-05-25 02:00:41','2026-05-25 09:00:41'),
('205041568','6283130283995','Nabhan Putra Laksa',619.00,'USC','aktif','0000-00-00 00:00:00','2026-05-25 07:15:30','2026-05-25 14:15:30'),
('205046166','6281703349999','FITRIANI Fitriani',1124.12,'USC','aktif','0000-00-00 00:00:00','2026-05-26 07:54:41','2026-05-26 14:54:41'),
('205046307','62882022649851','Habibah Habibah',562.06,'USC','aktif','0000-00-00 00:00:00','2026-05-26 08:49:52','2026-05-26 15:49:52'),
('211013637','6281910262861','Lalu Masri Habibullah',34.02,'USD','aktif','2026-05-22 04:40:52','2026-05-21 11:07:08','2026-05-24 12:08:32'),
('211017529','6281240414049','M Rohman',10.15,'USD','aktif','0000-00-00 00:00:00','2026-05-22 07:57:39','2026-05-24 12:08:23'),
('211017591','62811774414','Mirza Mosaddeq Shah',1260654.42,'IDR','aktif','2026-05-22 14:37:45','2026-05-22 08:43:46','2026-05-24 12:08:21'),
('211018956','6285739456981','MUHAMMAD NAJA SABILI',200009.55,'IDR','aktif','2026-05-24 16:51:04','2026-05-24 23:04:22','2026-05-25 06:04:22'),
('211019272','6285310009797','Ifan Herdiono',40.68,'USD','aktif','0000-00-00 00:00:00','2026-05-23 10:13:45','2026-05-24 12:08:09');
/*!40000 ALTER TABLE `tb_member_vip` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tb_panduan_media`
--

DROP TABLE IF EXISTS `tb_panduan_media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tb_panduan_media` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `keyword` varchar(50) NOT NULL,
  `teks_panduan` text DEFAULT NULL,
  `url_gambar` varchar(255) DEFAULT NULL,
  `url_video` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tb_panduan_media`
--

LOCK TABLES `tb_panduan_media` WRITE;
/*!40000 ALTER TABLE `tb_panduan_media` DISABLE KEYS */;
INSERT INTO `tb_panduan_media` VALUES
(1,'ktp','nih kak panduan cara verifikasi KTP-nya, diikutin pelan-pelan ya 👇','http://103.247.8.189/hfm_admin/public/media/gambar_ktp.jpg','http://103.247.8.189/hfm_admin/public/media/ktp.mp4'),
(3,'pindah_ib','gini kak step-by-step cara pindah IB di Website ya👇','http://103.247.8.189/hfm_admin/public/media/130223.jpg','http://103.247.8.189/hfm_admin/public/media/pindahib.mp4'),
(7,'buka_akun_trading_dan_konek_ke_mt5','biar gampang, liat panduan Buka Akun Trading dan cara konekin ke MT5 ini aja kak sampai selesai..','','http://103.247.8.189/hfm_admin/public/media/mt5.mp4'),
(8,'deposit','Tutorial Deposit \r\n','','http://103.247.8.189/hfm_admin/public/media/depo.mp4'),
(9,'wd','Tutorial WD','','http://103.247.8.189/hfm_admin/public/media/wd.mp4'),
(10,'arsipkan_akun','Berikut Video tutorial cara arsipkan akun trading lamanya.\r\n\r\nSaldo wajib 0 sebelum arsipkan akun (transfer sisa saldo ke wallet dulu) dan tidak boleh ada posisi yang teropen atau pending order','','http://103.247.8.189/hfm_admin/public/media/arsip.mp4'),
(11,'contoh_ss_pindah_ib','ini contoh pindah IB','http://103.247.8.189/hfm_admin/public/media/WhatsApp%20Image%202026-05-16%20at%2015.01.35.jpeg','');
/*!40000 ALTER TABLE `tb_panduan_media` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-26 17:15:41
