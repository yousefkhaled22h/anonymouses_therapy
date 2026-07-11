-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 02, 2026 at 01:27 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `anonymous-therapy`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `admin_id` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`admin_id`, `email`, `password_hash`, `created_at`) VALUES
('A_1', 'admin@safehaven.com', '$2y$10$.BW0OgH0.3wL9PQk44xOBOLSOXOh0i3MQ2GtYZf3gpzjSiPEbaA5e', '2026-05-01 14:51:33');

-- --------------------------------------------------------

--
-- Table structure for table `client`
--

CREATE TABLE `client` (
  `client_id` varchar(50) NOT NULL,
  `user_id` varchar(50) NOT NULL,
  `anonymous_id` varchar(100) DEFAULT NULL,
  `avatar_path` varchar(255) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `daily_challenge_id` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `wallet_balance` decimal(10,2) DEFAULT 0.00,
  `points` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `client`
--

INSERT INTO `client` (`client_id`, `user_id`, `anonymous_id`, `avatar_path`, `name`, `daily_challenge_id`, `created_at`, `wallet_balance`, `points`) VALUES
('cli_06db6e5b93e92de4', 'usr_743e2d45fc43c3ae', NULL, NULL, 'yousef', NULL, '2026-02-22 19:53:01', 0.00, 0),
('cli_23b0e7fa840125c3', 'usr_48a8dcd8d689f0ea', 'BlueRiver6962', NULL, 'shaimaa', NULL, '2026-05-01 19:05:37', 0.00, 0),
('cli_2dd3e317d2a8494d', 'usr_f6450e3705a12c57', 'HappyRiver7960', 'assets/uploads/avatars/avatar_69a1b9a8735bf5.48464934.jpg', 'yousef', NULL, '2026-02-27 15:34:16', 0.00, 0),
('cli_2fbd15698d72d267', 'usr_8ed8a4f54e296918', 'SilentLeaf8813', 'assets/uploads/avatars/avatar_69f4d8918cb8c4.67057071.png', 'Hana', NULL, '2026-05-01 14:44:00', 0.00, 60),
('cli_320980beb9598e4c', 'usr_da0773319daa07c4', 'SilentMoon7570', NULL, 'Hana', NULL, '2026-05-01 15:02:57', 0.00, 0),
('cli_326e2bc0553618e5', 'usr_013895c066979f4e', 'BrightStar7757', 'assets/uploads/avatars/avatar_69b676d9e76460.68937345.jpg', 'Abdelfattah', NULL, '2026-03-15 07:46:12', 0.00, 0),
('cli_35354de7eb829290', 'usr_077cd07f414c6839', NULL, NULL, 'Test User', NULL, '2026-02-27 16:20:36', 0.00, 0),
('cli_4db500baff58fc57', 'usr_8086415b731d55d8', 'GentleOcean6138', 'assets/uploads/avatars/avatar_69e14365695843.21814003.jpg', 'gwgw1212', NULL, '2026-04-16 20:14:50', 0.00, 0),
('cli_5c1162780d814b76', 'usr_6cd7eeb838b03c34', 'SilentStar3434', NULL, 'gwgw121', NULL, '2026-04-16 20:14:01', 0.00, 0),
('cli_8520c136b006fdc8', 'usr_34abbc5071b86b1d', 'BrightSugar9212', NULL, 'yousef', NULL, '2026-04-10 12:50:31', 0.00, 0),
('cli_c190a2d461be5f2d', 'usr_9c5bba13735500e7', 'KindLeaf9023', NULL, 'ghada', NULL, '2026-03-16 08:20:21', 0.00, 0),
('cli_c617cddca0b879f1', 'usr_8fde1dd42e25b886', 'ImpartialLeaf9598', NULL, 'yousef1', NULL, '2026-02-27 16:51:00', 0.00, 0),
('cli_cf2b58051902fbc7', 'usr_16d05188b1e5b97d', 'SilentEagle6542', NULL, 'Hana', NULL, '2026-05-01 19:37:50', 0.00, 0),
('cli_dbc4e8f953f2be87', 'usr_40291154928e8140', 'BraveRiver9119', NULL, 'yousef', NULL, '2026-04-10 12:57:34', 220.00, 0),
('cli_e2de83317bbe727f', 'usr_f8a63ffd6716c178', 'KindSky2751', 'assets/uploads/avatars/avatar_69d8ef500c53f7.30778340.jpg', 'yousef', NULL, '2026-04-10 12:37:26', 0.00, 0),
('cli_ecbd72a1757d1d16', 'usr_25ba297fa3547e0b', 'CalmOcean7072', NULL, 'hana', NULL, '2026-02-27 17:21:06', 0.00, 0),
('cli_f7b84aea247e1231', 'usr_325a2c68d3cac1f5', 'SilentMountain1111', NULL, 'yousef', NULL, '2026-04-10 12:49:36', 0.00, 0);

-- --------------------------------------------------------

--
-- Table structure for table `community_comment`
--

CREATE TABLE `community_comment` (
  `comment_id` varchar(50) NOT NULL,
  `post_id` varchar(50) NOT NULL,
  `user_id` varchar(50) NOT NULL,
  `content` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `community_comment`
--

INSERT INTO `community_comment` (`comment_id`, `post_id`, `user_id`, `content`, `created_at`) VALUES
('cmt_14445777809f0d9a', 'post_8b443629626f5b14', 'usr_09ebc27845afe826', 'hi', '2026-02-27 17:52:12'),
('cmt_1566ca4bad62ca09', 'post_fe45c548b65edfb7', 'usr_40291154928e8140', 'hi', '2026-04-10 22:20:40'),
('cmt_1a5c9648e5c2a54c', 'post_fe45c548b65edfb7', 'usr_8fde1dd42e25b886', 'i dont know', '2026-03-16 08:07:51'),
('cmt_340747b95f912002', 'post_87074e243f817bd8', 'usr_09ebc27845afe826', 'hi', '2026-02-27 17:54:54'),
('cmt_543a109e691bb1ca', 'post_8b443629626f5b14', 'usr_8fde1dd42e25b886', 'hi', '2026-02-27 17:02:40'),
('cmt_5f8d2d2f07a5aa40', 'post_fe45c548b65edfb7', 'usr_8fde1dd42e25b886', 'hi', '2026-04-09 09:28:14'),
('cmt_69bb2bb61fd5e817', 'post_8b443629626f5b14', 'usr_25ba297fa3547e0b', 'hi', '2026-02-27 17:21:29'),
('cmt_7593d49484b740b2', 'post_87074e243f817bd8', 'usr_09ebc27845afe826', 'yy', '2026-02-27 17:53:17'),
('cmt_82abb27f24f2806e', 'post_20db517475f3e1bc', 'usr_48a8dcd8d689f0ea', 'jj', '2026-05-01 19:08:59'),
('cmt_8ac0f141c0d5d190', 'post_8b443629626f5b14', 'usr_8fde1dd42e25b886', 'hi', '2026-02-27 17:02:14'),
('cmt_f22231b223c83160', 'post_8b443629626f5b14', 'usr_09ebc27845afe826', 'hi', '2026-02-27 17:47:39');

-- --------------------------------------------------------

--
-- Table structure for table `community_qna`
--

CREATE TABLE `community_qna` (
  `post_id` varchar(50) NOT NULL,
  `user_id` varchar(50) DEFAULT NULL,
  `post_type` varchar(50) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `report_status` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `answer_content` text DEFAULT NULL,
  `answered_by_therapist_id` varchar(50) DEFAULT NULL,
  `answered_at` timestamp NULL DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `community_qna`
--

INSERT INTO `community_qna` (`post_id`, `user_id`, `post_type`, `content`, `category`, `report_status`, `created_at`, `answer_content`, `answered_by_therapist_id`, `answered_at`, `is_public`) VALUES
('post_06cd32ff1c1d9392', 'usr_9a29462131ceb00b', 'Anxiety', 'nnnnnn', NULL, NULL, '2026-05-02 11:03:42', NULL, NULL, NULL, 1),
('post_20db517475f3e1bc', 'usr_09ebc27845afe826', 'Autism Spectrum', 'hi', NULL, NULL, '2026-02-27 17:55:01', NULL, NULL, NULL, 1),
('post_2dcd87a84241534f', 'usr_8ed8a4f54e296918', 'Adoption/Foster Care', 'mmmmmmm', NULL, NULL, '2026-05-02 11:19:04', NULL, NULL, NULL, 1),
('post_87074e243f817bd8', 'usr_25ba297fa3547e0b', 'Alcohol/Drug Abuse', 'hi', NULL, NULL, '2026-02-27 17:21:47', NULL, NULL, NULL, 1),
('post_8b443629626f5b14', 'usr_077cd07f414c6839', 'ADHD', 'hi', NULL, NULL, '2026-02-27 16:38:47', NULL, NULL, NULL, 1),
('post_92e0ee741e47204d', 'usr_8ed8a4f54e296918', 'Alcohol/Drug Abuse', 'xxxmmm', NULL, NULL, '2026-05-01 15:53:17', NULL, NULL, NULL, 1),
('post_fe45c548b65edfb7', 'usr_09ebc27845afe826', 'ADHD', 'what are symptoms of ADHD?', NULL, NULL, '2026-03-16 08:07:12', NULL, NULL, NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `completed_challenges`
--

CREATE TABLE `completed_challenges` (
  `id` int(11) NOT NULL,
  `client_id` varchar(50) NOT NULL,
  `challenge_id` varchar(50) NOT NULL,
  `completed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `completed_challenges`
--

INSERT INTO `completed_challenges` (`id`, `client_id`, `challenge_id`, `completed_at`) VALUES
(1, 'cli_2fbd15698d72d267', 'ch_01', '2026-05-01 18:39:38'),
(2, 'cli_2fbd15698d72d267', 'ch_03', '2026-05-01 18:45:35'),
(3, 'cli_2fbd15698d72d267', 'ch_02', '2026-05-01 19:03:24');

-- --------------------------------------------------------

--
-- Table structure for table `custom_challenges`
--

CREATE TABLE `custom_challenges` (
  `id` int(11) NOT NULL,
  `client_id` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `points` int(11) DEFAULT 10,
  `completed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `custom_challenges`
--

INSERT INTO `custom_challenges` (`id`, `client_id`, `description`, `points`, `completed_at`) VALUES
(1, 'cli_2fbd15698d72d267', '..blah blah blah', 10, '2026-05-01 18:45:04'),
(2, 'cli_2fbd15698d72d267', 'cggcgcgcg', 10, '2026-05-01 18:45:50');

-- --------------------------------------------------------

--
-- Table structure for table `daily_challenge`
--

CREATE TABLE `daily_challenge` (
  `challenge_id` varchar(50) NOT NULL,
  `title` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `difficulty` varchar(50) DEFAULT NULL,
  `points` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `daily_challenge`
--

INSERT INTO `daily_challenge` (`challenge_id`, `title`, `description`, `difficulty`, `points`, `created_at`) VALUES
('ch_01', 'Drink Water', 'Drink 8 glasses of water today.', 'Easy', 10, '2026-05-01 18:36:57'),
('ch_02', 'Deep Breathing', 'Perform 5 minutes of deep breathing.', 'Easy', 10, '2026-05-01 18:36:57'),
('ch_03', 'Walk 4,000 steps', 'Walk a total of 4,000 steps today.', 'Medium', 20, '2026-05-01 18:36:57'),
('ch_04', 'Note 3 daily wins', 'Write down three things you achieved today.', 'Easy', 10, '2026-05-01 18:36:57'),
('ch_05', 'Do 20 air squats', 'Complete 20 repetitions of air squats.', 'Medium', 15, '2026-05-01 18:36:57'),
('ch_06', 'Read 5 book pages', 'Read at least 5 pages of a book.', 'Easy', 10, '2026-05-01 18:36:57'),
('ch_07', 'Mindfulness (10 mins)', 'Practice mindfulness or meditation for 10 minutes.', 'Medium', 20, '2026-05-01 18:36:57'),
('ch_08', 'Call a relative (1 time)', 'Reach out to a relative for a quick chat.', 'Medium', 15, '2026-05-01 18:36:57'),
('ch_09', 'Tidy your desk (1 time)', 'Organize and clean your workspace.', 'Easy', 10, '2026-05-01 18:36:57'),
('ch_10', 'Eat 2 fruit pieces', 'Include two portions of fruit in your diet.', 'Easy', 10, '2026-05-01 18:36:57'),
('ch_11', 'Take stairs (5 floors)', 'Choose the stairs over the elevator for at least 5 floors.', 'Medium', 15, '2026-05-01 18:41:42'),
('ch_12', 'Drink herbal tea (1 cup)', 'Enjoy a soothing cup of herbal tea.', 'Easy', 5, '2026-05-01 18:41:42'),
('ch_13', 'Morning sunlight (10 mins)', 'Spend 10 minutes in the morning sun for Vitamin D.', 'Easy', 10, '2026-05-01 18:41:42'),
('ch_14', 'Evening walk (15 mins)', 'Take a relaxing 15-minute walk in the evening.', 'Medium', 15, '2026-05-01 18:41:42'),
('ch_15', 'Limit caffeine (1 cup)', 'Stick to just one cup of caffeine today.', 'Medium', 10, '2026-05-01 18:41:42'),
('ch_16', 'Write gratitude (3 things)', 'List 3 things you are grateful for today.', 'Easy', 5, '2026-05-01 18:41:42'),
('ch_17', 'No sugar snacks (1 day)', 'Avoid sugary snacks for the entire day.', 'Hard', 20, '2026-05-01 18:41:42'),
('ch_18', 'Clean your room (10 mins)', 'Spend 10 minutes tidying up your personal space.', 'Easy', 10, '2026-05-01 18:41:42'),
('ch_19', 'Listen to podcast (10 mins)', 'Listen to an educational or uplifting podcast.', 'Easy', 10, '2026-05-01 18:41:42'),
('ch_20', 'Practice mindfulness (5 mins)', 'Take 5 minutes to be present and mindful.', 'Easy', 5, '2026-05-01 18:41:42'),
('ch_21', 'Compliment someone (1 time)', 'Give a genuine compliment to someone.', 'Easy', 5, '2026-05-01 18:41:42'),
('ch_22', 'Drink no soda (1 day)', 'Avoid soda and sugary drinks for 24 hours.', 'Medium', 15, '2026-05-01 18:41:42'),
('ch_23', 'Stretch neck/back (5 mins)', 'Relieve tension with 5 minutes of stretching.', 'Easy', 5, '2026-05-01 18:41:42'),
('ch_24', 'Plan tomorrow (5 mins)', 'Spend 5 minutes planning your next day.', 'Easy', 5, '2026-05-01 18:41:42'),
('ch_25', 'Declutter desk (5 mins)', 'Clear off your desk for a fresh start.', 'Easy', 5, '2026-05-01 18:41:42'),
('ch_26', 'Practice breathing (3 mins)', 'Do a 3-minute focused breathing exercise.', 'Easy', 5, '2026-05-01 18:41:42'),
('ch_27', 'No phone meals (1 meal)', 'Eat one meal without using your phone.', 'Medium', 10, '2026-05-01 18:41:42'),
('ch_28', 'Smile at others (3 times)', 'Share a smile with three different people.', 'Easy', 5, '2026-05-01 18:41:42'),
('ch_29', 'Drink warm water (1 glass)', 'Sip a glass of warm water for digestion.', 'Easy', 5, '2026-05-01 18:41:42'),
('ch_30', 'Do nothing (5 mins)', 'Sit in silence and do absolutely nothing for 5 minutes.', 'Medium', 5, '2026-05-01 18:41:42');

-- --------------------------------------------------------

--
-- Table structure for table `emotional_dashboard`
--

CREATE TABLE `emotional_dashboard` (
  `dashboard_id` varchar(50) NOT NULL,
  `client_id` varchar(50) NOT NULL,
  `type` varchar(50) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `mood_rating` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `recorded_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `emotional_dashboard`
--

INSERT INTO `emotional_dashboard` (`dashboard_id`, `client_id`, `type`, `content`, `mood_rating`, `notes`, `recorded_date`, `created_at`) VALUES
('emo_69f4f91381590', 'cli_2fbd15698d72d267', 'mood', 'Happy', NULL, NULL, '2026-05-01', '2026-05-01 19:03:47'),
('emo_69f5da66a15f2', 'cli_2fbd15698d72d267', 'mood', 'Stressed', NULL, NULL, '2026-05-02', '2026-05-02 11:05:10');

-- --------------------------------------------------------

--
-- Table structure for table `game_session`
--

CREATE TABLE `game_session` (
  `session_id` int(11) NOT NULL,
  `user_id` varchar(50) NOT NULL,
  `game_type` varchar(50) NOT NULL,
  `duration_seconds` int(11) NOT NULL,
  `played_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grounding_session`
--

CREATE TABLE `grounding_session` (
  `grounding_id` int(11) NOT NULL,
  `user_id` varchar(50) NOT NULL,
  `responses` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`responses`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `group_session`
--

CREATE TABLE `group_session` (
  `group_session_id` varchar(50) NOT NULL,
  `room_name` varchar(255) DEFAULT NULL,
  `topic` varchar(100) DEFAULT NULL,
  `session_date` datetime NOT NULL,
  `status` varchar(50) DEFAULT NULL,
  `admin_id` varchar(50) DEFAULT NULL,
  `therapist_id` varchar(50) DEFAULT NULL,
  `max_participants` int(11) DEFAULT 15,
  `duration_minutes` int(11) DEFAULT 60,
  `elapsed_seconds` int(11) DEFAULT 0,
  `timer_last_started_at` datetime DEFAULT NULL,
  `admin_comments` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `group_session`
--

INSERT INTO `group_session` (`group_session_id`, `room_name`, `topic`, `session_date`, `status`, `admin_id`, `therapist_id`, `max_participants`, `duration_minutes`, `elapsed_seconds`, `timer_last_started_at`, `admin_comments`, `created_at`) VALUES
('gs_0b080f6cd259c173', NULL, 'qeqe', '2026-02-28 23:45:51', 'active', NULL, 'the_b019a811909b12ab', 15, 60, 0, NULL, NULL, '2026-02-28 21:45:49'),
('gs_3fe355c00d054d57', '12', '1212', '2026-03-01 00:19:32', 'scheduled', NULL, 'the_b019a811909b12ab', 10, 60, 0, NULL, NULL, '2026-02-28 22:19:32'),
('gs_65c31103237c4eef', '12', 'new2', '2026-03-01 01:10:29', 'active', NULL, 'the_b019a811909b12ab', 15, 60, 0, NULL, NULL, '2026-02-28 23:10:21'),
('gs_6c9a0ca8472677a5', 'test', 'adhd', '2026-03-16 10:11:45', 'active', NULL, 'the_b019a811909b12ab', 10, 60, 0, NULL, NULL, '2026-03-16 08:11:35'),
('gs_86173d2eeac00f27', NULL, 'new', '2026-02-28 22:56:00', 'scheduled', NULL, 'the_b019a811909b12ab', 15, 60, 0, NULL, NULL, '2026-02-28 21:56:31'),
('gs_a03dd15f9ceb8f60', 'new', 'kk', '2026-03-05 23:27:22', 'active', NULL, 'the_b019a811909b12ab', 10, 60, 0, NULL, NULL, '2026-03-05 21:27:17'),
('gs_b0d330d46bfc92c6', 'Ana', 'over', '2026-03-15 09:54:34', 'active', NULL, 'the_b019a811909b12ab', 20, 60, 0, NULL, NULL, '2026-03-15 07:54:23'),
('gs_cf87ce83e314435a', 'qwe', 'asd', '2026-03-07 20:27:35', 'active', NULL, 'the_b019a811909b12ab', 10, 60, 0, NULL, NULL, '2026-03-07 18:27:31');

-- --------------------------------------------------------

--
-- Table structure for table `group_session_message`
--

CREATE TABLE `group_session_message` (
  `message_id` varchar(50) NOT NULL,
  `group_session_id` varchar(50) NOT NULL,
  `sender_user_id` varchar(50) NOT NULL,
  `message_text` text NOT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `group_session_message`
--

INSERT INTO `group_session_message` (`message_id`, `group_session_id`, `sender_user_id`, `message_text`, `sent_at`) VALUES
('msg_09a93bdae65ac7cc', 'gs_0b080f6cd259c173', 'usr_8ed8a4f54e296918', 'h', '2026-05-01 17:09:19'),
('msg_1035d800918432d4', 'gs_6c9a0ca8472677a5', 'usr_8fde1dd42e25b886', '😔', '2026-04-09 09:35:36'),
('msg_104e125bf10da229', 'gs_cf87ce83e314435a', 'usr_8fde1dd42e25b886', 'qeq', '2026-03-07 18:28:10'),
('msg_171ff2912f511a76', 'gs_b0d330d46bfc92c6', 'usr_013895c066979f4e', 'jnk', '2026-03-15 07:55:06'),
('msg_1f3b6fb155e2cc35', 'gs_0b080f6cd259c173', 'usr_8ed8a4f54e296918', 'l', '2026-05-01 17:09:21'),
('msg_230bbfa3eb88199d', 'gs_65c31103237c4eef', 'usr_8ed8a4f54e296918', 'h', '2026-05-01 17:11:43'),
('msg_241a6d78ebae91ba', 'gs_65c31103237c4eef', 'usr_8ed8a4f54e296918', 'h', '2026-05-01 17:11:44'),
('msg_25fdbc9d72ebdf3c', 'gs_0b080f6cd259c173', 'usr_8ed8a4f54e296918', 'l', '2026-05-01 17:09:21'),
('msg_28596c65182794ae', 'gs_65c31103237c4eef', 'usr_8ed8a4f54e296918', 'h', '2026-05-01 17:11:45'),
('msg_2f72309756dbcdc4', 'gs_0b080f6cd259c173', 'usr_8ed8a4f54e296918', 'l', '2026-05-01 17:09:23'),
('msg_3c8c18ab4595f0ef', 'gs_0b080f6cd259c173', 'usr_8ed8a4f54e296918', 'hello', '2026-05-01 17:09:13'),
('msg_4593846c44de21f0', 'gs_65c31103237c4eef', 'usr_8ed8a4f54e296918', 'h', '2026-05-01 17:11:44'),
('msg_46551edd8b59d04f', 'gs_a03dd15f9ceb8f60', 'usr_09ebc27845afe826', 'qrq', '2026-03-05 21:27:23'),
('msg_47e92fedd839c186', 'gs_65c31103237c4eef', 'usr_8ed8a4f54e296918', 'h', '2026-05-01 17:11:45'),
('msg_597c08084b4ae1b1', 'gs_65c31103237c4eef', 'usr_8ed8a4f54e296918', 'h', '2026-05-01 17:11:45'),
('msg_5a1a8dd93e06c2b1', 'gs_65c31103237c4eef', 'usr_8ed8a4f54e296918', 'h', '2026-05-01 17:11:44'),
('msg_5b1e54592beae758', 'gs_b0d330d46bfc92c6', 'usr_013895c066979f4e', 'mn ,.', '2026-03-15 07:55:09'),
('msg_5b4119c6b06cc376', 'gs_0b080f6cd259c173', 'usr_8ed8a4f54e296918', 'l', '2026-05-01 17:09:23'),
('msg_657c6eaecbfddd31', 'gs_0b080f6cd259c173', 'usr_8ed8a4f54e296918', 'l', '2026-05-01 17:09:19'),
('msg_684712ab0e6e10bb', 'gs_0b080f6cd259c173', 'usr_8ed8a4f54e296918', 'l', '2026-05-01 17:09:21'),
('msg_909dc223de4435ba', 'gs_65c31103237c4eef', 'usr_8ed8a4f54e296918', 'h', '2026-05-01 17:11:45'),
('msg_998513922a11e9e1', 'gs_0b080f6cd259c173', 'usr_8ed8a4f54e296918', 'l', '2026-05-01 17:09:22'),
('msg_a11e1bc7ffdfc315', 'gs_cf87ce83e314435a', 'usr_8fde1dd42e25b886', '😇', '2026-03-07 18:28:18'),
('msg_bbd73a1f472dc5c9', 'gs_b0d330d46bfc92c6', 'usr_013895c066979f4e', 'ghkjlk;', '2026-03-15 07:55:04'),
('msg_c5ad3f6a96a62c37', 'gs_65c31103237c4eef', 'usr_8ed8a4f54e296918', 'h', '2026-05-01 17:11:45'),
('msg_cb9965362ae4fa61', 'gs_0b080f6cd259c173', 'usr_8ed8a4f54e296918', 'l', '2026-05-01 17:09:20'),
('msg_d405e0d64b5fcbe0', 'gs_b0d330d46bfc92c6', 'usr_013895c066979f4e', '😶‍🌫️', '2026-03-15 07:55:02'),
('msg_d4657c615000a810', 'gs_b0d330d46bfc92c6', 'usr_013895c066979f4e', 'hgjkl', '2026-03-15 07:54:57'),
('msg_d9c7d279548f1c56', 'gs_0b080f6cd259c173', 'usr_8ed8a4f54e296918', 'l', '2026-05-01 17:09:20'),
('msg_dafae6ab3b0cb571', 'gs_b0d330d46bfc92c6', 'usr_013895c066979f4e', 'm,.', '2026-03-15 07:55:10'),
('msg_db3d79779da5a1cb', 'gs_65c31103237c4eef', 'usr_8ed8a4f54e296918', 'h', '2026-05-01 17:11:46'),
('msg_dd3c3b016f5c7632', 'gs_0b080f6cd259c173', 'usr_8ed8a4f54e296918', 'l', '2026-05-01 17:09:22'),
('msg_e3fb3b6bf7da64d1', 'gs_0b080f6cd259c173', 'usr_8ed8a4f54e296918', 'l', '2026-05-01 17:09:23'),
('msg_f1e4287f5e590c78', 'gs_cf87ce83e314435a', 'usr_09ebc27845afe826', 'twtw', '2026-03-07 18:27:47'),
('msg_f58630349d3be67b', 'gs_b0d330d46bfc92c6', 'usr_013895c066979f4e', 'h', '2026-03-15 07:55:08');

-- --------------------------------------------------------

--
-- Table structure for table `group_session_user`
--

CREATE TABLE `group_session_user` (
  `group_session_id` varchar(50) NOT NULL,
  `user_id` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `group_session_user`
--

INSERT INTO `group_session_user` (`group_session_id`, `user_id`) VALUES
('gs_0b080f6cd259c173', 'usr_09ebc27845afe826'),
('gs_0b080f6cd259c173', 'usr_8fde1dd42e25b886'),
('gs_3fe355c00d054d57', 'usr_09ebc27845afe826'),
('gs_3fe355c00d054d57', 'usr_8ed8a4f54e296918'),
('gs_3fe355c00d054d57', 'usr_8fde1dd42e25b886'),
('gs_65c31103237c4eef', 'usr_09ebc27845afe826'),
('gs_65c31103237c4eef', 'usr_8fde1dd42e25b886'),
('gs_65c31103237c4eef', 'usr_9a29462131ceb00b'),
('gs_6c9a0ca8472677a5', 'usr_09ebc27845afe826'),
('gs_6c9a0ca8472677a5', 'usr_8fde1dd42e25b886'),
('gs_86173d2eeac00f27', 'usr_09ebc27845afe826'),
('gs_86173d2eeac00f27', 'usr_48a8dcd8d689f0ea'),
('gs_86173d2eeac00f27', 'usr_8ed8a4f54e296918'),
('gs_86173d2eeac00f27', 'usr_8fde1dd42e25b886'),
('gs_86173d2eeac00f27', 'usr_9a29462131ceb00b'),
('gs_a03dd15f9ceb8f60', 'usr_09ebc27845afe826'),
('gs_a03dd15f9ceb8f60', 'usr_8fde1dd42e25b886'),
('gs_b0d330d46bfc92c6', 'usr_013895c066979f4e'),
('gs_b0d330d46bfc92c6', 'usr_09ebc27845afe826'),
('gs_b0d330d46bfc92c6', 'usr_8ed8a4f54e296918'),
('gs_cf87ce83e314435a', 'usr_09ebc27845afe826'),
('gs_cf87ce83e314435a', 'usr_8fde1dd42e25b886');

-- --------------------------------------------------------

--
-- Table structure for table `helpful_votes`
--

CREATE TABLE `helpful_votes` (
  `vote_id` varchar(32) NOT NULL,
  `user_id` varchar(40) NOT NULL,
  `item_id` varchar(60) NOT NULL,
  `item_type` varchar(10) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `helpful_votes`
--

INSERT INTO `helpful_votes` (`vote_id`, `user_id`, `item_id`, `item_type`, `created_at`) VALUES
('v_14ecfa558b671a13', 'usr_8ed8a4f54e296918', 'post_c6a154b9c182beac', 'post', '2026-05-01 18:28:15'),
('v_4e8974372147dd53', 'usr_8fde1dd42e25b886', 'post_fe45c548b65edfb7', 'post', '2026-04-16 22:06:52'),
('v_59f5cacb9a3d2143', 'usr_8ed8a4f54e296918', 'post_c89627805c60c327', 'post', '2026-05-01 18:01:43'),
('v_79f81af9c423c8b4', 'usr_09ebc27845afe826', 'post_fe45c548b65edfb7', 'post', '2026-04-10 23:16:43'),
('v_b8e11952b7e79b2d', 'usr_9a29462131ceb00b', 'post_20db517475f3e1bc', 'post', '2026-05-01 19:40:18'),
('v_d0600dbd16a00055', 'usr_09ebc27845afe826', 'post_c89627805c60c327', 'post', '2026-04-10 23:16:43'),
('v_eb3a7d5d4da02671', 'usr_40291154928e8140', 'post_fe45c548b65edfb7', 'post', '2026-04-11 00:20:11');

-- --------------------------------------------------------

--
-- Table structure for table `journal_entry`
--

CREATE TABLE `journal_entry` (
  `entry_id` int(11) NOT NULL,
  `client_id` varchar(50) NOT NULL,
  `title` varchar(255) DEFAULT 'Untitled',
  `content` longtext DEFAULT NULL,
  `mood` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `journal_entry`
--

INSERT INTO `journal_entry` (`entry_id`, `client_id`, `title`, `content`, `mood`, `created_at`, `updated_at`) VALUES
(4, 'cli_2fbd15698d72d267', 'notes', '', '', '2026-05-01 14:45:03', '2026-05-01 14:45:03'),
(5, 'cli_2fbd15698d72d267', 'bbb', '', '', '2026-05-01 14:45:21', '2026-05-01 14:45:21'),
(6, 'cli_2fbd15698d72d267', 'Diary', '<b>AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA</b>', '😰', '2026-05-01 15:29:21', '2026-05-01 15:29:21'),
(7, 'cli_23b0e7fa840125c3', 'notes', '<b><i>fiftiy</i></b>', '', '2026-05-01 19:09:23', '2026-05-01 19:09:23');

-- --------------------------------------------------------

--
-- Table structure for table `negative_thought`
--

CREATE TABLE `negative_thought` (
  `thought_id` int(11) NOT NULL,
  `thought_text` text NOT NULL,
  `positive_transformation` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `negative_thought`
--

INSERT INTO `negative_thought` (`thought_id`, `thought_text`, `positive_transformation`, `created_at`) VALUES
(1, 'I am not good enough', 'I am doing my best and that is enough.', '2026-05-01 20:12:01'),
(2, 'Everything is going wrong', 'I can handle challenges one step at a time.', '2026-05-01 20:12:01'),
(3, 'I am all alone', 'There are people who care about me, even if they are not here right now.', '2026-05-01 20:12:01'),
(4, 'I should have done better', 'I am learning and growing every day.', '2026-05-01 20:12:01'),
(5, 'It is too late for me', 'It is never too late to start a fresh chapter.', '2026-05-01 20:12:01'),
(6, 'I am a failure', 'Mistakes are just opportunities to learn.', '2026-05-01 20:12:01'),
(7, 'Nobody understands me', 'My feelings are valid, and I can express them to others.', '2026-05-01 20:12:01'),
(8, 'I will never be happy', 'Happiness comes in small moments, and I can find them.', '2026-05-01 20:12:01'),
(9, 'I am a burden', 'The people who love me are happy to support me.', '2026-05-01 20:12:01'),
(10, 'I am not worthy of love', 'I am worthy of love and respect exactly as I am.', '2026-05-01 20:12:01');

-- --------------------------------------------------------

--
-- Table structure for table `paid_session`
--

CREATE TABLE `paid_session` (
  `paid_session_id` varchar(50) NOT NULL,
  `client_id` varchar(50) NOT NULL,
  `therapist_id` varchar(50) NOT NULL,
  `session_date` datetime NOT NULL,
  `communication_method` varchar(50) DEFAULT NULL,
  `duration_minutes` int(11) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `zoom_meeting_id` varchar(255) DEFAULT NULL,
  `zoom_join_url` varchar(500) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `payment_status` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `early_start_requested` tinyint(1) DEFAULT 0,
  `reschedule_requested` tinyint(1) DEFAULT 0,
  `early_start_from` time DEFAULT NULL,
  `early_start_to` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `paid_session`
--

INSERT INTO `paid_session` (`paid_session_id`, `client_id`, `therapist_id`, `session_date`, `communication_method`, `duration_minutes`, `amount`, `zoom_meeting_id`, `zoom_join_url`, `status`, `payment_status`, `created_at`, `early_start_requested`, `reschedule_requested`, `early_start_from`, `early_start_to`) VALUES
('PS-69d7a110662cf', 'cli_c617cddca0b879f1', 'the_b019a811909b12ab', '2026-04-09 14:54:15', 'Video Session', 60, 20.00, NULL, NULL, 'cancelled', 'paid', '2026-04-09 12:52:32', 0, 0, NULL, NULL),
('PS-69d8d8c925fe1', 'cli_c617cddca0b879f1', 'the_b019a811909b12ab', '2026-04-11 09:00:00', 'Video Session', 60, 20.00, NULL, NULL, 'cancelled', 'paid', '2026-04-10 11:02:33', 0, 0, NULL, NULL),
('PS-69d8db6b2387b', 'cli_c617cddca0b879f1', 'the_b019a811909b12ab', '2026-04-13 09:00:00', 'Video Session', 60, 20.00, NULL, NULL, 'cancelled', 'paid', '2026-04-10 11:13:47', 0, 1, NULL, NULL),
('PS-69d8e1f8507da', 'cli_c617cddca0b879f1', 'the_b019a811909b12ab', '2026-04-13 13:00:00', 'Video Session', 60, 20.00, NULL, NULL, 'cancelled', 'paid', '2026-04-10 11:41:44', 0, 1, '13:00:00', '14:00:00'),
('PS-69d8e27149b53', 'cli_c617cddca0b879f1', 'the_b019a811909b12ab', '2026-04-13 13:30:00', 'Video Session', 60, 20.00, NULL, NULL, 'cancelled', 'paid', '2026-04-10 11:43:45', 0, 0, '13:00:00', '16:44:00'),
('PS-69d8e3ca38dc5', 'cli_c617cddca0b879f1', 'the_b019a811909b12ab', '2026-04-13 13:00:00', 'Video Session', 60, 20.00, NULL, NULL, 'cancelled', 'paid', '2026-04-10 11:49:30', 0, 0, '13:00:00', '16:49:00'),
('PS-69d8e4059cbb9', 'cli_c617cddca0b879f1', 'the_b019a811909b12ab', '2026-04-13 14:00:00', 'Voice Call', 60, 20.00, NULL, NULL, 'Completed', 'paid', '2026-04-10 11:50:29', 0, 0, '13:00:00', '16:50:00'),
('PS-69d8edc28af4b', 'cli_c617cddca0b879f1', 'the_b019a811909b12ab', '2026-04-13 13:33:00', 'Text Chat', 60, 20.00, NULL, NULL, 'Completed', 'paid', '2026-04-10 12:32:02', 0, 0, '12:33:00', '15:32:00'),
('PS-69d96bd2d9297', 'cli_dbc4e8f953f2be87', 'the_b019a811909b12ab', '2026-04-28 03:00:00', 'Video Session', 60, 220.00, NULL, NULL, 'cancelled', 'paid', '2026-04-10 21:29:54', 0, 1, NULL, NULL),
('PS-69d977866e815', 'cli_dbc4e8f953f2be87', 'the_b019a811909b12ab', '2026-04-13 01:00:00', 'Voice Call', 60, 220.00, NULL, NULL, 'cancelled', 'paid', '2026-04-10 22:19:50', 0, 0, NULL, NULL),
('PS-69e14268ba83f', 'cli_c617cddca0b879f1', 'the_b019a811909b12ab', '2026-04-20 22:12:00', 'Video Session', 60, 220.00, NULL, NULL, 'Completed', 'paid', '2026-04-16 20:11:20', 0, 0, '22:12:00', '23:12:00'),
('PS-69f4bd44802b4', 'cli_2fbd15698d72d267', 'the_b019a811909b12ab', '2026-05-28 04:00:00', 'Video Session', 60, 220.00, '9091569722', 'https://zoom.us/j/9091569722?pwd=69f4bd448056c', 'RESERVED', 'paid', '2026-05-01 14:48:36', 0, 0, NULL, NULL),
('PS-69f4c6e221712', 'cli_2fbd15698d72d267', 'the_b019a811909b12ab', '2026-05-07 07:00:00', 'Voice & Text', 60, 220.00, NULL, NULL, 'RESERVED', 'paid', '2026-05-01 15:29:38', 0, 0, NULL, NULL),
('PS-69f4c8fb081c4', 'cli_2fbd15698d72d267', 'the_9283a5311e2bdd1e', '2026-05-14 12:00:00', 'Video Session', 60, 0.00, '9909551860', 'https://zoom.us/j/9909551860?pwd=69f4c8fb082e2', 'cancelled', 'paid', '2026-05-01 15:38:35', 0, 0, NULL, NULL),
('PS-69f4c9d4003eb', 'cli_2fbd15698d72d267', 'the_b019a811909b12ab', '2026-05-14 14:00:00', 'Video Session', 60, 220.00, '2660302877', 'https://zoom.us/j/2660302877?pwd=69f4c9d400512', 'RESERVED', 'paid', '2026-05-01 15:42:12', 0, 0, NULL, NULL),
('PS-69f4d8f67ddc7', 'cli_2fbd15698d72d267', 'the_9283a5311e2bdd1e', '2026-05-02 09:00:00', 'Voice & Text', 60, 275.00, NULL, NULL, 'RESERVED', 'paid', '2026-05-01 16:46:46', 0, 0, NULL, NULL),
('PS-69f4d936c6af9', 'cli_2fbd15698d72d267', 'the_9283a5311e2bdd1e', '2026-05-01 16:00:00', 'Voice & Text', 60, 275.00, NULL, NULL, 'Completed', 'paid', '2026-05-01 16:47:50', 0, 0, NULL, NULL),
('PS-69f4fa226e846', 'cli_23b0e7fa840125c3', 'the_9283a5311e2bdd1e', '2026-05-02 10:00:00', 'Video Session', 60, 275.00, '9567353614', 'https://zoom.us/j/9567353614?pwd=69f4fa226ea07', 'RESERVED', 'paid', '2026-05-01 19:08:18', 0, 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `payment_id` varchar(50) NOT NULL,
  `paid_session_id` varchar(50) NOT NULL,
  `client_id` varchar(50) NOT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `payment_status` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment`
--

INSERT INTO `payment` (`payment_id`, `paid_session_id`, `client_id`, `amount`, `payment_method`, `payment_date`, `payment_status`, `created_at`) VALUES
('PAY-69d7a110688bf', 'PS-69d7a110662cf', 'cli_c617cddca0b879f1', 20.00, 'Wallet', '2026-04-09 12:52:32', 'completed', '2026-04-09 12:52:32'),
('PAY-69d8d8c926482', 'PS-69d8d8c925fe1', 'cli_c617cddca0b879f1', 20.00, 'Wallet', '2026-04-10 11:02:33', 'completed', '2026-04-10 11:02:33'),
('PAY-69d8db6b25296', 'PS-69d8db6b2387b', 'cli_c617cddca0b879f1', 20.00, 'Wallet', '2026-04-10 11:13:47', 'completed', '2026-04-10 11:13:47'),
('PAY-69d8e1f851f48', 'PS-69d8e1f8507da', 'cli_c617cddca0b879f1', 20.00, 'Wallet', '2026-04-10 11:41:44', 'completed', '2026-04-10 11:41:44'),
('PAY-69d8e27149eca', 'PS-69d8e27149b53', 'cli_c617cddca0b879f1', 20.00, 'Wallet', '2026-04-10 11:43:45', 'completed', '2026-04-10 11:43:45'),
('PAY-69d8e3ca39f7a', 'PS-69d8e3ca38dc5', 'cli_c617cddca0b879f1', 20.00, 'Wallet', '2026-04-10 11:49:30', 'completed', '2026-04-10 11:49:30'),
('PAY-69d8e4059cf97', 'PS-69d8e4059cbb9', 'cli_c617cddca0b879f1', 20.00, 'Wallet', '2026-04-10 11:50:29', 'completed', '2026-04-10 11:50:29'),
('PAY-69d8edc28c228', 'PS-69d8edc28af4b', 'cli_c617cddca0b879f1', 20.00, 'Wallet', '2026-04-10 12:32:02', 'completed', '2026-04-10 12:32:02'),
('PAY-69d96bd2dcdaf', 'PS-69d96bd2d9297', 'cli_dbc4e8f953f2be87', 220.00, 'Card + Wallet', '2026-04-10 21:29:54', 'completed', '2026-04-10 21:29:54'),
('PAY-69d977866f98f', 'PS-69d977866e815', 'cli_dbc4e8f953f2be87', 220.00, 'Wallet', '2026-04-10 22:19:50', 'completed', '2026-04-10 22:19:50'),
('PAY-69e14268bacca', 'PS-69e14268ba83f', 'cli_c617cddca0b879f1', 220.00, 'Card + Wallet', '2026-04-16 20:11:20', 'completed', '2026-04-16 20:11:20'),
('PAY-69f4bd44808f1', 'PS-69f4bd44802b4', 'cli_2fbd15698d72d267', 220.00, 'Card + Wallet', '2026-05-01 14:48:36', 'completed', '2026-05-01 14:48:36'),
('PAY-69f4c6e2224c1', 'PS-69f4c6e221712', 'cli_2fbd15698d72d267', 220.00, 'Card + Wallet', '2026-05-01 15:29:38', 'completed', '2026-05-01 15:29:38'),
('PAY-69f4c8fb087db', 'PS-69f4c8fb081c4', 'cli_2fbd15698d72d267', 0.00, 'Wallet', '2026-05-01 15:38:35', 'completed', '2026-05-01 15:38:35'),
('PAY-69f4c9d400814', 'PS-69f4c9d4003eb', 'cli_2fbd15698d72d267', 220.00, 'Card + Wallet', '2026-05-01 15:42:12', 'completed', '2026-05-01 15:42:12'),
('PAY-69f4d8f67ec52', 'PS-69f4d8f67ddc7', 'cli_2fbd15698d72d267', 275.00, 'Card + Wallet', '2026-05-01 16:46:46', 'completed', '2026-05-01 16:46:46'),
('PAY-69f4d936c7003', 'PS-69f4d936c6af9', 'cli_2fbd15698d72d267', 275.00, 'InstaPay + Wallet', '2026-05-01 16:47:50', 'completed', '2026-05-01 16:47:50'),
('PAY-69f4fa226ee79', 'PS-69f4fa226e846', 'cli_23b0e7fa840125c3', 275.00, 'Card + Wallet', '2026-05-01 19:08:18', 'completed', '2026-05-01 19:08:18');

-- --------------------------------------------------------

--
-- Table structure for table `private_session_message`
--

CREATE TABLE `private_session_message` (
  `message_id` int(11) NOT NULL,
  `paid_session_id` varchar(50) NOT NULL,
  `user_id` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `private_session_message`
--

INSERT INTO `private_session_message` (`message_id`, `paid_session_id`, `user_id`, `message`, `created_at`) VALUES
(1, '0', '0', 'hi', '2026-04-10 11:59:11'),
(2, '0', '0', 'hi', '2026-04-10 11:59:22'),
(3, '0', '0', 'qt', '2026-04-10 13:16:52'),
(4, 'PS-69f4d936c6af9', 'usr_8ed8a4f54e296918', 'hello', '2026-05-01 16:48:10');

-- --------------------------------------------------------

--
-- Table structure for table `report`
--

CREATE TABLE `report` (
  `report_id` varchar(50) NOT NULL,
  `reporter_user_id` varchar(50) NOT NULL,
  `reported_user_id` varchar(50) DEFAULT NULL,
  `admin_id` varchar(50) DEFAULT NULL,
  `report_type` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `action_taken` text DEFAULT NULL,
  `reviewed_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `resource`
--

CREATE TABLE `resource` (
  `resource_id` varchar(50) NOT NULL,
  `title` varchar(100) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `added_by_admin_id` varchar(50) DEFAULT NULL,
  `added_by_therapist_id` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `session_participants`
--

CREATE TABLE `session_participants` (
  `id` int(11) NOT NULL,
  `session_id` varchar(50) NOT NULL,
  `user_id` varchar(50) NOT NULL,
  `joined_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `session_participants`
--

INSERT INTO `session_participants` (`id`, `session_id`, `user_id`, `joined_at`) VALUES
(1, 'PS-69f4d936c6af9', 'usr_8ed8a4f54e296918', '2026-05-01 19:47:55');

-- --------------------------------------------------------

--
-- Table structure for table `session_summary`
--

CREATE TABLE `session_summary` (
  `summary_id` varchar(50) NOT NULL,
  `paid_session_id` varchar(50) NOT NULL,
  `client_id` varchar(50) NOT NULL,
  `therapist_id` varchar(50) NOT NULL,
  `summary_content` text DEFAULT NULL,
  `generated_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `therapist`
--

CREATE TABLE `therapist` (
  `therapist_id` varchar(50) NOT NULL,
  `user_id` varchar(50) NOT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `specialties` text DEFAULT NULL,
  `hourly_rate` decimal(10,2) DEFAULT NULL,
  `years_experience` int(11) DEFAULT NULL,
  `review_count` int(11) DEFAULT 0,
  `rating` decimal(3,2) DEFAULT NULL,
  `verified` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `profile_photo` varchar(255) DEFAULT NULL,
  `education` text DEFAULT NULL,
  `experience_details` text DEFAULT NULL,
  `languages` text DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT 'assets/images/default_avatar.jpg',
  `zoom_link` varchar(255) DEFAULT 'https://zoom.us/test'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `therapist`
--

INSERT INTO `therapist` (`therapist_id`, `user_id`, `first_name`, `last_name`, `bio`, `specialties`, `hourly_rate`, `years_experience`, `review_count`, `rating`, `verified`, `created_at`, `profile_photo`, `education`, `experience_details`, `languages`, `profile_image`, `zoom_link`) VALUES
('the_09e052a3bcd74db2', 'usr_bab18427cdc77990', '', '', '', 'Depression', 250.00, 7, 0, NULL, 1, '2026-05-01 19:17:42', NULL, '', '', 'Arabic, English, French, Spanish', 'assets/images/default_avatar.jpg', ''),
('the_9283a5311e2bdd1e', 'usr_9a29462131ceb00b', 'Hana', 'Abdulkareem', '', 'Depression', 250.00, 10, 0, NULL, 1, '2026-05-01 15:31:11', NULL, '', '', 'Arabic, English, French, Spanish', 'assets/uploads/profiles/profile_the_9283a5311e2bdd1e_1777719809.png', ''),
('the_b019a811909b12ab', 'usr_09ebc27845afe826', '', '', 'hi', 'depression', 200.00, 2, 0, NULL, 1, '2026-02-22 19:50:02', NULL, 'Ph.D. in Clinical Psychology, Stanford University', '10+ years of private practice in Cognitive Behavioral Therapy. Former lead counselor at Mental Health Clinic.', 'English, Spanish', 'assets/uploads/profiles/profile_the_b019a811909b12ab_1773648034.png', ''),
('the_d8dcc63f0a195492', 'usr_9c8a4c789136c8ec', 'Hana', 'Abdulkareem', NULL, 'Anxiety', NULL, NULL, 0, NULL, 1, '2026-05-02 11:07:01', NULL, NULL, NULL, NULL, 'assets/images/default_avatar.jpg', 'https://zoom.us/test');

-- --------------------------------------------------------

--
-- Table structure for table `therapist_availability`
--

CREATE TABLE `therapist_availability` (
  `availability_id` int(11) NOT NULL,
  `therapist_id` varchar(50) NOT NULL,
  `day_of_week` varchar(20) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `therapist_availability`
--

INSERT INTO `therapist_availability` (`availability_id`, `therapist_id`, `day_of_week`, `start_time`, `end_time`, `is_available`, `created_at`) VALUES
(79, 'the_b019a811909b12ab', 'Monday', '01:00:00', '14:00:00', 1, '2026-04-16 20:10:03'),
(80, 'the_b019a811909b12ab', 'Tuesday', '15:00:00', '14:00:00', 1, '2026-04-16 20:10:03'),
(81, 'the_b019a811909b12ab', 'Wednesday', '09:00:00', '17:00:00', 0, '2026-04-16 20:10:03'),
(82, 'the_b019a811909b12ab', 'Thursday', '03:00:00', '16:00:00', 1, '2026-04-16 20:10:03'),
(83, 'the_b019a811909b12ab', 'Friday', '04:00:00', '17:00:00', 0, '2026-04-16 20:10:03'),
(84, 'the_b019a811909b12ab', 'Saturday', '09:00:00', '17:00:00', 0, '2026-04-16 20:10:03'),
(85, 'the_b019a811909b12ab', 'Sunday', '09:00:00', '17:00:00', 0, '2026-04-16 20:10:03'),
(100, 'the_9283a5311e2bdd1e', 'Monday', '09:00:00', '17:00:00', 1, '2026-05-01 20:06:33'),
(101, 'the_9283a5311e2bdd1e', 'Tuesday', '09:00:00', '17:00:00', 1, '2026-05-01 20:06:33'),
(102, 'the_9283a5311e2bdd1e', 'Wednesday', '09:00:00', '17:00:00', 1, '2026-05-01 20:06:33'),
(103, 'the_9283a5311e2bdd1e', 'Thursday', '09:00:00', '17:00:00', 1, '2026-05-01 20:06:33'),
(104, 'the_9283a5311e2bdd1e', 'Friday', '09:00:00', '17:00:00', 1, '2026-05-01 20:06:33'),
(105, 'the_9283a5311e2bdd1e', 'Saturday', '09:00:00', '17:00:00', 1, '2026-05-01 20:06:33'),
(106, 'the_9283a5311e2bdd1e', 'Sunday', '09:00:00', '17:00:00', 1, '2026-05-01 20:06:33');

-- --------------------------------------------------------

--
-- Table structure for table `therapist_cv_section`
--

CREATE TABLE `therapist_cv_section` (
  `section_id` int(11) NOT NULL,
  `therapist_id` varchar(50) NOT NULL,
  `title` varchar(100) NOT NULL,
  `content` text NOT NULL,
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `therapist_verification`
--

CREATE TABLE `therapist_verification` (
  `verification_id` varchar(50) NOT NULL,
  `therapist_id` varchar(50) NOT NULL,
  `admin_id` varchar(50) DEFAULT NULL,
  `license_file_path` varchar(255) DEFAULT NULL,
  `verification_status` varchar(50) DEFAULT NULL,
  `admin_comments` text DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `therapist_verification`
--

INSERT INTO `therapist_verification` (`verification_id`, `therapist_id`, `admin_id`, `license_file_path`, `verification_status`, `admin_comments`, `submitted_at`, `reviewed_at`, `created_at`, `updated_at`) VALUES
('ver_0cfb29a4e3732681', 'the_d8dcc63f0a195492', NULL, 'assets/uploads/licenses/license_the_d8dcc63f0a195492_1777720021.png', 'Approved', NULL, '2026-05-02 11:07:01', '2026-05-02 11:07:40', '2026-05-02 11:07:01', '2026-05-02 11:07:40'),
('ver_231c06aef61857fd', 'the_09e052a3bcd74db2', NULL, 'assets/uploads/licenses/license_the_09e052a3bcd74db2_1777663062.png', 'Approved', NULL, '2026-05-01 19:17:42', '2026-05-01 19:33:41', '2026-05-01 19:17:42', '2026-05-01 19:33:41'),
('ver_47ae470c853ae859', 'the_b019a811909b12ab', NULL, 'assets/uploads/licenses/license_the_b019a811909b12ab_1771789802.jpg', 'pending', NULL, '2026-02-22 19:50:02', NULL, '2026-02-22 19:50:02', '2026-02-22 19:51:34'),
('ver_a1e6eeb084fd5282', 'the_9283a5311e2bdd1e', NULL, 'assets/uploads/licenses/license_the_9283a5311e2bdd1e_1777649471.png', 'Pending', NULL, '2026-05-01 15:31:11', NULL, '2026-05-01 15:31:11', '2026-05-01 15:31:11');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `user_id` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('client','therapist','volunteer','admin') NOT NULL,
  `privacy_settings` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`user_id`, `email`, `password_hash`, `role`, `privacy_settings`, `created_at`, `reset_token`, `reset_expires`) VALUES
('usr_013895c066979f4e', 'abdelfattah@eru.com', '$2y$10$VFSUoqLiRHbFRbEIc4ilIOmO9U71sDCgooPXd/vcPQ4YRH/SosPFi', 'client', NULL, '2026-03-15 07:46:12', NULL, NULL),
('usr_077cd07f414c6839', 'testuser@example.com', '$2y$10$4S6fiiJZR1n/RBNfnXoPP.CxzVt43d8EsW1NRImY7nlqYS3jBteNW', 'client', NULL, '2026-02-27 16:20:36', NULL, NULL),
('usr_09ebc27845afe826', 'yousef_khaled@gmail.com', '$2y$10$j3J9jMHVCJKn47dYF7/d1.q9j4QSbhFWC8T2yx6cC4FixtPj/csEm', 'therapist', NULL, '2026-02-22 19:50:02', 'c75477e8e30bf04f14459eda6f8ef09eabf7cabb8489cb75fa3013a8528defba', '2026-02-22 23:17:27'),
('usr_16d05188b1e5b97d', 'hana2@gmail.com', '$2y$10$TOpM4RVXFIf4C7kCBco2MeC2e8hwWqXuuyQgYoNkR6Y.00yS2MaGK', 'client', '{\"display_mode\":\"Anonymous\"}', '2026-05-01 19:37:50', NULL, NULL),
('usr_246faac20cbfac4c', 'yousef_khaled11111@gmail.com', '$2y$10$a/Y5uJrxszzWBRFUvrkvMOvrL1JKK7Wm63DQ/SV2BBCBNk.1JmSzC', 'volunteer', NULL, '2026-04-19 20:02:49', NULL, NULL),
('usr_25ba297fa3547e0b', 'hana@gmail.com', '$2y$10$dPAHOxBin.smizfLgQTTtOSMs19Fs2t/tzhpXekSdr8HvebnKfLYq', 'client', NULL, '2026-02-27 17:21:06', NULL, NULL),
('usr_325a2c68d3cac1f5', 'yousef_khaled13131@gmail.com', '$2y$10$cm/22BXYn91RWcEKA6vj3uMpWteP0gu2Pc9XPJwaJfiec7rmZzreG', 'client', NULL, '2026-04-10 12:49:36', NULL, NULL),
('usr_34abbc5071b86b1d', 'yousef_khaled131311@gmail.com', '$2y$10$tzGDMf4FZjpmTr6Rez2nrOZWahhRye2KKQkyuSCB0c8UrkHNn7G4G', 'client', NULL, '2026-04-10 12:50:31', NULL, NULL),
('usr_40291154928e8140', 'yousef_khaled1313141@gmail.com', '$2y$10$D1g4w15uXd.JetuHo7dVX.g94yiASwrebF9nW9lTZR7Exgevsb7q6', 'client', NULL, '2026-04-10 12:57:34', NULL, NULL),
('usr_46f4772ca0481b87', 'cozy@gmail.com', '$2y$10$Q7QqyQlDzx/JWyXKSJ0wTO7GPhnLOtF7TXlVz8bq.yV4juXCCXJS.', 'therapist', NULL, '2026-05-01 18:08:28', NULL, NULL),
('usr_48a8dcd8d689f0ea', 'shaimaa@gmail.com', '$2y$10$vfFmdDYIQNxhR7ayegLNj.X/g6JfzzKkqpKaTgCPX/RXC2s9/ERVy', 'client', '{\"display_mode\":\"Anonymous\"}', '2026-05-01 19:05:37', NULL, NULL),
('usr_4ae4440d73c11ea5', 'hanaabdulkareem10@gmail.com', '$2y$10$tILgu952WtyF/ut6dNd76eOl7jDkO1nLY/9dhjoMGRLKOHXxbEHSC', 'therapist', NULL, '2026-05-01 17:50:14', NULL, NULL),
('usr_60580d82176fccc8', 'testuser@example.com', '$2y$10$IwMMLuaQ9xVCWghqzg4qJeV0npfRU6atld1vSKUe4AOaOUjNDGYRC', 'volunteer', NULL, '2026-04-19 19:33:44', NULL, NULL),
('usr_6cd7eeb838b03c34', 'snixx@yahoo.com', '$2y$10$NEJ3Cbigtli2O23A56hNjuYQjzCHURSxzDUINXrIgxlZZpbeW9r1a', 'client', NULL, '2026-04-16 20:14:01', NULL, NULL),
('usr_743e2d45fc43c3ae', 'snixx1@yahoo.com', '$2y$10$Cd.3b3bLKJstjkHJNMGPse5hzBWEqzqCE3LBb0AJcsVCsHBQSzkJi', 'client', '{\"display_mode\":\"Real\"}', '2026-02-22 19:53:01', NULL, NULL),
('usr_79f3f2ba15efd945', 'yousef_khaled1@gmail.com', '$2y$10$9u3XvgV4GQkKBvU80bdcH.d9n7TmQK3Xc1po5R2bHLNOKoLxrSei6', 'therapist', NULL, '2026-02-28 00:13:30', NULL, NULL),
('usr_7f5631be5c819306', 'snixx11@yahoo.com', '$2y$10$VISSv8wbawewQSl4PzJYtu1iOyt2U6FZfF796iujrkCblxO8Mwcg.', 'volunteer', NULL, '2026-04-19 19:20:29', NULL, NULL),
('usr_8086415b731d55d8', 'snixx12@yahoo.com', '$2y$10$rlV0J9usC.F2niFBNk3q7eJRFBBuO/tNRboO4QDHIiF7vVfIIPf66', 'client', NULL, '2026-04-16 20:14:50', NULL, NULL),
('usr_8ed8a4f54e296918', 'cozycave7@gmail.com', '$2y$10$VwiPj5gMaaqpWzWnxSTLIOY/TSDTRPRhZIaMpSxJAFKx4FnfXJuc.', 'client', '{\"display_mode\":\"Real\"}', '2026-05-01 14:44:00', NULL, NULL),
('usr_8fde1dd42e25b886', 'yousef_khaled12@gmail.com', '$2y$10$dVm6ZmCp3omU1DgV6c3bWu8DAJJ4mao1A2nGXoQRnMkQh8/lHhTua', 'client', '{\"display_mode\":\"Real\"}', '2026-02-27 16:51:00', NULL, NULL),
('usr_9353afac15190ca4', '123456789@gmail.com', '$2y$10$FW.Q72Ddk8L5H2b4thKQL.MkcnnraUY0I6p8fkigyMXAp5Jkg9xLC', 'therapist', NULL, '2026-05-01 19:11:58', NULL, NULL),
('usr_9a29462131ceb00b', 'hanaabdulkareem@gmail.com', '$2y$10$7.NJq06zJ4jRx/cLimaj4Oid5ADNZ/FW5kPurqS0PStG98nMB6Jpy', 'therapist', NULL, '2026-05-01 15:31:11', NULL, NULL),
('usr_9c5bba13735500e7', 'ghada@gmail.com', '$2y$10$3HQEdcgRAH1yJPs81P1V8.ksNSRctv6HNGqFt93Bw8ZC.NmerofBC', 'client', NULL, '2026-03-16 08:20:21', NULL, NULL),
('usr_9c8a4c789136c8ec', 'hanabdulkareem@gmail.com', '$2y$10$VZrhT5YbIi2jF5Lt87V.BO85fOY/e4lED1jWGS5UPDmNvCD/LJIYm', 'therapist', NULL, '2026-05-02 11:07:01', NULL, NULL),
('usr_a1b8dbb39465ce52', 'cozy2@gmail.com', '$2y$10$p1dVuhEpdLuEaAsAoa8EYOS0qevqUwwWTSSKTGh/nPhk5e20g4g8e', 'therapist', NULL, '2026-05-01 18:15:48', NULL, NULL),
('usr_bab18427cdc77990', 'hanakareem@gmail.com', '$2y$10$/nCd4hV7OGO54azNWqo1u.36LwC6sgoi5h1iLh/7TYzg72o.LClha', 'therapist', NULL, '2026-05-01 19:17:42', NULL, NULL),
('usr_bb380cd1eea80957', 'hanaabdulkareem13@gmail.com', '$2y$10$.E3Wel7eeMb654TWkU8xbeKjbq4oRE.rDmq0Cu3dwYxEds5Wxq/dO', 'therapist', NULL, '2026-05-01 14:50:10', NULL, NULL),
('usr_da0773319daa07c4', 'cozycave1@gmail.com', '$2y$10$eL/4LeMKhpn1e3CYGGGI6uqODuCgg/O/z7lS7lS7WVdQh.rTCpesu', 'client', '{\"display_mode\":\"Anonymous\"}', '2026-05-01 15:02:57', NULL, NULL),
('usr_f58947e3b1322bfd', 'yousef_khaled1111@gmail.com', '$2y$10$P.HoTccKz95SCBqWL.xTfuUSaMFX9PaHAWcRgrG4GMfvckhMOUiz6', 'volunteer', NULL, '2026-04-19 19:55:24', NULL, NULL),
('usr_f6450e3705a12c57', 'yousef_khaled11@gmail.com', '$2y$10$0vm8wzr8AT.vVcgJxsFPUeOpDLs9gzyq68dqhkfkKMg0hhVEcKzy6', 'client', '{\"display_mode\":\"Real\"}', '2026-02-27 15:34:16', '6e0c5de1b082fe611069314cf62238fbec6c394e89445d7b1315006204c60abd', '2026-02-27 18:50:39'),
('usr_f8a63ffd6716c178', 'snixx11@yahoo.com', '$2y$10$sD/GGy3Zh5bcSn795oxpYuiJBs2PaZ60BT5TLbEewxsbwFVJrRDgy', 'client', '{\"display_mode\":\"Real\"}', '2026-04-10 12:37:26', NULL, NULL),
('usr_fedf4ab0bb949856', 'snixx1@yahoo.com', '$2y$10$XBoZjVHhyBw6g9UUq3pjE.5s/XHqg3xIZIs6fZN0N4f9SidDPEm5m', 'volunteer', NULL, '2026-02-27 18:16:28', NULL, NULL),
('U_ADMIN_1', 'admin@safehaven.com', '$2y$10$p9giIpUbqb7IME/4aWAgXe3MvkKAkd3Hvb5W/9.PS.WUF4Jc4S.Ma', 'admin', NULL, '2026-05-01 14:51:33', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_artwork`
--

CREATE TABLE `user_artwork` (
  `artwork_id` int(11) NOT NULL,
  `user_id` varchar(50) NOT NULL,
  `artwork_type` varchar(50) NOT NULL,
  `svg_data` longtext NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_resource`
--

CREATE TABLE `user_resource` (
  `user_id` varchar(50) NOT NULL,
  `resource_id` varchar(50) NOT NULL,
  `access_level` enum('view','edit','owner') DEFAULT 'view',
  `added_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_safe_space`
--

CREATE TABLE `user_safe_space` (
  `user_id` varchar(50) NOT NULL,
  `design_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`design_data`)),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `volunteer`
--

CREATE TABLE `volunteer` (
  `volunteer_id` varchar(50) NOT NULL,
  `user_id` varchar(50) NOT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `languages` text DEFAULT NULL,
  `skills` text DEFAULT NULL,
  `availability` text DEFAULT NULL,
  `total_sessions` int(11) DEFAULT 0,
  `rating` decimal(3,2) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `certificates` varchar(255) DEFAULT NULL,
  `verification_status` varchar(50) DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `client`
--
ALTER TABLE `client`
  ADD PRIMARY KEY (`client_id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `daily_challenge_id` (`daily_challenge_id`);

--
-- Indexes for table `community_comment`
--
ALTER TABLE `community_comment`
  ADD PRIMARY KEY (`comment_id`),
  ADD KEY `post_id` (`post_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `community_qna`
--
ALTER TABLE `community_qna`
  ADD PRIMARY KEY (`post_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `completed_challenges`
--
ALTER TABLE `completed_challenges`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_client_challenge` (`client_id`,`challenge_id`);

--
-- Indexes for table `custom_challenges`
--
ALTER TABLE `custom_challenges`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `daily_challenge`
--
ALTER TABLE `daily_challenge`
  ADD PRIMARY KEY (`challenge_id`);

--
-- Indexes for table `emotional_dashboard`
--
ALTER TABLE `emotional_dashboard`
  ADD PRIMARY KEY (`dashboard_id`),
  ADD KEY `client_id` (`client_id`);

--
-- Indexes for table `game_session`
--
ALTER TABLE `game_session`
  ADD PRIMARY KEY (`session_id`);

--
-- Indexes for table `grounding_session`
--
ALTER TABLE `grounding_session`
  ADD PRIMARY KEY (`grounding_id`);

--
-- Indexes for table `group_session`
--
ALTER TABLE `group_session`
  ADD PRIMARY KEY (`group_session_id`),
  ADD KEY `admin_id` (`admin_id`),
  ADD KEY `therapist_id` (`therapist_id`);

--
-- Indexes for table `group_session_message`
--
ALTER TABLE `group_session_message`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `group_session_id` (`group_session_id`),
  ADD KEY `sender_user_id` (`sender_user_id`);

--
-- Indexes for table `group_session_user`
--
ALTER TABLE `group_session_user`
  ADD PRIMARY KEY (`group_session_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `helpful_votes`
--
ALTER TABLE `helpful_votes`
  ADD PRIMARY KEY (`vote_id`),
  ADD UNIQUE KEY `uq_vote` (`user_id`,`item_id`,`item_type`);

--
-- Indexes for table `journal_entry`
--
ALTER TABLE `journal_entry`
  ADD PRIMARY KEY (`entry_id`);

--
-- Indexes for table `negative_thought`
--
ALTER TABLE `negative_thought`
  ADD PRIMARY KEY (`thought_id`);

--
-- Indexes for table `paid_session`
--
ALTER TABLE `paid_session`
  ADD PRIMARY KEY (`paid_session_id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `therapist_id` (`therapist_id`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`payment_id`),
  ADD UNIQUE KEY `paid_session_id` (`paid_session_id`),
  ADD KEY `client_id` (`client_id`);

--
-- Indexes for table `private_session_message`
--
ALTER TABLE `private_session_message`
  ADD PRIMARY KEY (`message_id`);

--
-- Indexes for table `report`
--
ALTER TABLE `report`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `reporter_user_id` (`reporter_user_id`),
  ADD KEY `reported_user_id` (`reported_user_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `resource`
--
ALTER TABLE `resource`
  ADD PRIMARY KEY (`resource_id`),
  ADD KEY `added_by_admin_id` (`added_by_admin_id`),
  ADD KEY `added_by_user_id` (`added_by_therapist_id`);

--
-- Indexes for table `session_participants`
--
ALTER TABLE `session_participants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_sp` (`session_id`,`user_id`);

--
-- Indexes for table `session_summary`
--
ALTER TABLE `session_summary`
  ADD PRIMARY KEY (`summary_id`),
  ADD UNIQUE KEY `paid_session_id` (`paid_session_id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `therapist_id` (`therapist_id`);

--
-- Indexes for table `therapist`
--
ALTER TABLE `therapist`
  ADD PRIMARY KEY (`therapist_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `therapist_availability`
--
ALTER TABLE `therapist_availability`
  ADD PRIMARY KEY (`availability_id`),
  ADD KEY `therapist_id` (`therapist_id`);

--
-- Indexes for table `therapist_cv_section`
--
ALTER TABLE `therapist_cv_section`
  ADD PRIMARY KEY (`section_id`),
  ADD KEY `therapist_id` (`therapist_id`);

--
-- Indexes for table `therapist_verification`
--
ALTER TABLE `therapist_verification`
  ADD PRIMARY KEY (`verification_id`),
  ADD KEY `therapist_id` (`therapist_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`,`role`),
  ADD UNIQUE KEY `reset_token_index` (`reset_token`);

--
-- Indexes for table `user_artwork`
--
ALTER TABLE `user_artwork`
  ADD PRIMARY KEY (`artwork_id`);

--
-- Indexes for table `user_resource`
--
ALTER TABLE `user_resource`
  ADD PRIMARY KEY (`user_id`,`resource_id`),
  ADD KEY `resource_id` (`resource_id`);

--
-- Indexes for table `user_safe_space`
--
ALTER TABLE `user_safe_space`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `volunteer`
--
ALTER TABLE `volunteer`
  ADD PRIMARY KEY (`volunteer_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `completed_challenges`
--
ALTER TABLE `completed_challenges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `custom_challenges`
--
ALTER TABLE `custom_challenges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `game_session`
--
ALTER TABLE `game_session`
  MODIFY `session_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `grounding_session`
--
ALTER TABLE `grounding_session`
  MODIFY `grounding_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `journal_entry`
--
ALTER TABLE `journal_entry`
  MODIFY `entry_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `negative_thought`
--
ALTER TABLE `negative_thought`
  MODIFY `thought_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `private_session_message`
--
ALTER TABLE `private_session_message`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `session_participants`
--
ALTER TABLE `session_participants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `therapist_availability`
--
ALTER TABLE `therapist_availability`
  MODIFY `availability_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=107;

--
-- AUTO_INCREMENT for table `therapist_cv_section`
--
ALTER TABLE `therapist_cv_section`
  MODIFY `section_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_artwork`
--
ALTER TABLE `user_artwork`
  MODIFY `artwork_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `client`
--
ALTER TABLE `client`
  ADD CONSTRAINT `client_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `client_ibfk_2` FOREIGN KEY (`daily_challenge_id`) REFERENCES `daily_challenge` (`challenge_id`) ON DELETE SET NULL;

--
-- Constraints for table `community_comment`
--
ALTER TABLE `community_comment`
  ADD CONSTRAINT `community_comment_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `community_qna` (`post_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `community_comment_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `community_qna`
--
ALTER TABLE `community_qna`
  ADD CONSTRAINT `community_qna_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `emotional_dashboard`
--
ALTER TABLE `emotional_dashboard`
  ADD CONSTRAINT `emotional_dashboard_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `client` (`client_id`) ON DELETE CASCADE;

--
-- Constraints for table `group_session`
--
ALTER TABLE `group_session`
  ADD CONSTRAINT `group_session_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`admin_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `group_session_ibfk_2` FOREIGN KEY (`therapist_id`) REFERENCES `therapist` (`therapist_id`) ON DELETE SET NULL;

--
-- Constraints for table `group_session_message`
--
ALTER TABLE `group_session_message`
  ADD CONSTRAINT `group_session_message_ibfk_1` FOREIGN KEY (`group_session_id`) REFERENCES `group_session` (`group_session_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `group_session_message_ibfk_2` FOREIGN KEY (`sender_user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `group_session_user`
--
ALTER TABLE `group_session_user`
  ADD CONSTRAINT `group_session_user_ibfk_1` FOREIGN KEY (`group_session_id`) REFERENCES `group_session` (`group_session_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `group_session_user_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `paid_session`
--
ALTER TABLE `paid_session`
  ADD CONSTRAINT `paid_session_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `client` (`client_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `paid_session_ibfk_2` FOREIGN KEY (`therapist_id`) REFERENCES `therapist` (`therapist_id`) ON DELETE CASCADE;

--
-- Constraints for table `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `payment_ibfk_1` FOREIGN KEY (`paid_session_id`) REFERENCES `paid_session` (`paid_session_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payment_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `client` (`client_id`) ON DELETE CASCADE;

--
-- Constraints for table `report`
--
ALTER TABLE `report`
  ADD CONSTRAINT `report_ibfk_1` FOREIGN KEY (`reporter_user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `report_ibfk_2` FOREIGN KEY (`reported_user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `report_ibfk_3` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`admin_id`) ON DELETE SET NULL;

--
-- Constraints for table `resource`
--
ALTER TABLE `resource`
  ADD CONSTRAINT `resource_ibfk_1` FOREIGN KEY (`added_by_admin_id`) REFERENCES `admin` (`admin_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `resource_ibfk_2` FOREIGN KEY (`added_by_therapist_id`) REFERENCES `user` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `session_summary`
--
ALTER TABLE `session_summary`
  ADD CONSTRAINT `session_summary_ibfk_1` FOREIGN KEY (`paid_session_id`) REFERENCES `paid_session` (`paid_session_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `session_summary_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `client` (`client_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `session_summary_ibfk_3` FOREIGN KEY (`therapist_id`) REFERENCES `therapist` (`therapist_id`) ON DELETE CASCADE;

--
-- Constraints for table `therapist`
--
ALTER TABLE `therapist`
  ADD CONSTRAINT `therapist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `therapist_availability`
--
ALTER TABLE `therapist_availability`
  ADD CONSTRAINT `therapist_availability_ibfk_1` FOREIGN KEY (`therapist_id`) REFERENCES `therapist` (`therapist_id`) ON DELETE CASCADE;

--
-- Constraints for table `therapist_cv_section`
--
ALTER TABLE `therapist_cv_section`
  ADD CONSTRAINT `therapist_cv_section_ibfk_1` FOREIGN KEY (`therapist_id`) REFERENCES `therapist` (`therapist_id`) ON DELETE CASCADE;

--
-- Constraints for table `therapist_verification`
--
ALTER TABLE `therapist_verification`
  ADD CONSTRAINT `therapist_verification_ibfk_1` FOREIGN KEY (`therapist_id`) REFERENCES `therapist` (`therapist_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `therapist_verification_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`admin_id`) ON DELETE SET NULL;

--
-- Constraints for table `user_resource`
--
ALTER TABLE `user_resource`
  ADD CONSTRAINT `user_resource_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_resource_ibfk_2` FOREIGN KEY (`resource_id`) REFERENCES `resource` (`resource_id`) ON DELETE CASCADE;

--
-- Constraints for table `volunteer`
--
ALTER TABLE `volunteer`
  ADD CONSTRAINT `volunteer_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
