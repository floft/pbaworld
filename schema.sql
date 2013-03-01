-- phpMyAdmin SQL Dump
-- version 3.5.7
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Feb 28, 2013 at 07:34 PM
-- Server version: 5.5.30-log
-- PHP Version: 5.4.12

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Database: `floft_pbadata`
--

-- --------------------------------------------------------

--
-- Table structure for table `account`
--

CREATE TABLE IF NOT EXISTS `account` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `group` int(11) NOT NULL,
  `name` varchar(30) NOT NULL,
  `email` varchar(100) NOT NULL,
  `new_email` varchar(100) NOT NULL,
  `password` varchar(64) NOT NULL,
  `hash` varchar(64) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=97 ;

-- --------------------------------------------------------

--
-- Table structure for table `bible`
--

CREATE TABLE IF NOT EXISTS `bible` (
  `id` int(255) NOT NULL AUTO_INCREMENT,
  `book` varchar(255) NOT NULL DEFAULT '',
  `chapter` int(255) NOT NULL DEFAULT '0',
  `verse` int(255) NOT NULL DEFAULT '0',
  `text` text NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `book` (`book`),
  KEY `chapter` (`chapter`),
  KEY `verse` (`verse`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=31114 ;

-- --------------------------------------------------------

--
-- Table structure for table `questions`
--

CREATE TABLE IF NOT EXISTS `questions` (
  `id` int(255) NOT NULL AUTO_INCREMENT,
  `qid` int(255) NOT NULL,
  `year` varchar(255) NOT NULL DEFAULT '',
  `book` varchar(255) NOT NULL DEFAULT '',
  `chapter` bigint(255) NOT NULL DEFAULT '0',
  `verse` bigint(255) NOT NULL DEFAULT '0',
  `question` text NOT NULL,
  `answer` text NOT NULL,
  `date` int(255) NOT NULL DEFAULT '0',
  `name` int(255) NOT NULL DEFAULT '0',
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  UNIQUE KEY `id` (`id`),
  KEY `qid` (`qid`),
  KEY `year` (`year`),
  KEY `chapter` (`chapter`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=7586 ;
