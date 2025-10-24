-- nexus.sql
-- Consolidated database schema + seed for the UIU Nexus project.
--
-- NOTE: This is a placeholder file created by the repository maintenance script.
-- Replace the contents below with the canonical SQL dump that contains the
-- schema and any (sanitized) seed data you want tracked in the repository.
--

/*
Example structure you may want to include:

-- DDL (schema)
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE
);

-- DML (optional sanitized seeds)
INSERT INTO users (name, email) VALUES ('Example User', 'example@example.com');

*/

-- End of placeholder nexus.sql
-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 24, 2025 at 10:46 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `nexus`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `admin_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role_title` varchar(128) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`admin_id`, `user_id`, `role_title`, `created_at`) VALUES
(1, 1, 'Super Admin', '2025-10-24 20:45:49'),
(2, 2, 'System Admin', '2025-10-24 20:45:49');

-- --------------------------------------------------------

--
-- Table structure for table `admin_assignments`
--

CREATE TABLE `admin_assignments` (
  `user_id` int(11) NOT NULL,
  `assignment` varchar(128) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_permissions`
--

CREATE TABLE `admin_permissions` (
  `user_id` int(11) NOT NULL,
  `permission_key` varchar(128) NOT NULL,
  `allowed` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `alumni`
--

CREATE TABLE `alumni` (
  `alumni_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `company` varchar(255) DEFAULT NULL,
  `job_title` varchar(255) DEFAULT NULL,
  `years_of_experience` int(11) DEFAULT NULL,
  `graduation_year` year(4) DEFAULT NULL,
  `cgpa` decimal(3,2) DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `mentorship_availability` tinyint(1) DEFAULT 0,
  `max_mentorship_slots` int(11) DEFAULT 5,
  `industry` varchar(128) DEFAULT NULL,
  `university_id` varchar(64) DEFAULT NULL,
  `student_id_number` varchar(64) DEFAULT NULL,
  `program_level` enum('BSc','MSc') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `alumni`
--

INSERT INTO `alumni` (`alumni_id`, `user_id`, `department`, `company`, `job_title`, `years_of_experience`, `graduation_year`, `cgpa`, `is_verified`, `mentorship_availability`, `max_mentorship_slots`, `industry`, `university_id`, `student_id_number`, `program_level`, `created_at`) VALUES
(1, 6, 'CSE', 'TechFlow Ltd.', 'Senior Backend Engineer', 6, '2018', 3.80, 1, 1, 5, 'Software', 'A-20180001', 'S-20140001', 'BSc', '2025-10-24 20:45:49'),
(2, 7, 'CSE', 'DataMinds', 'Data Engineer', 4, '2019', 3.65, 1, 1, 3, 'Data', 'A-20190002', 'S-20150002', 'BSc', '2025-10-24 20:45:49'),
(3, 14, 'CSE', 'CloudCo', 'SRE', 5, '2019', 3.75, 1, 1, 4, 'Infra', NULL, NULL, 'BSc', '2025-10-24 20:45:49'),
(4, 15, 'CSE', 'MobilityX', 'Senior Android Engineer', 6, '2018', 3.60, 1, 1, 3, 'Mobile', NULL, NULL, 'BSc', '2025-10-24 20:45:49');

-- --------------------------------------------------------

--
-- Table structure for table `alumni_expertise`
--

CREATE TABLE `alumni_expertise` (
  `alumni_expertise_id` int(11) NOT NULL,
  `alumni_id` int(11) NOT NULL,
  `expertise_id` int(11) NOT NULL,
  `years_of_experience` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `alumni_expertise`
--

INSERT INTO `alumni_expertise` (`alumni_expertise_id`, `alumni_id`, `expertise_id`, `years_of_experience`) VALUES
(1, 1, 1, 5),
(2, 2, 2, 4);

-- --------------------------------------------------------

--
-- Table structure for table `alumni_focus_skills`
--

CREATE TABLE `alumni_focus_skills` (
  `user_id` int(11) NOT NULL,
  `skill_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `alumni_preferences`
--

CREATE TABLE `alumni_preferences` (
  `user_id` int(11) NOT NULL,
  `mentees_allowed` int(11) DEFAULT NULL,
  `meeting_type` enum('online','in-person','hybrid') DEFAULT NULL,
  `specific_requirements` text DEFAULT NULL,
  `timezone` varchar(64) DEFAULT NULL,
  `preferred_hours` varchar(128) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `announcement_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `author_id` int(11) NOT NULL,
  `target_roles` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`target_roles`)),
  `is_published` tinyint(1) DEFAULT 0,
  `publish_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`announcement_id`, `title`, `content`, `author_id`, `target_roles`, `is_published`, `publish_at`, `expires_at`, `created_at`, `updated_at`) VALUES
(1, 'Welcome to UIU Nexus', 'Explore People, Forum, Jobs, Events and more!', 1, '[\"student\", \"alumni\", \"recruiter\", \"admin\"]', 1, '2025-10-24 20:45:49', NULL, '2025-10-24 20:45:49', '2025-10-24 20:45:49');

-- --------------------------------------------------------

--
-- Table structure for table `announcement_target_roles`
--

CREATE TABLE `announcement_target_roles` (
  `announcement_id` int(11) NOT NULL,
  `role` enum('student','alumni','recruiter','admin') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcement_target_roles`
--

INSERT INTO `announcement_target_roles` (`announcement_id`, `role`) VALUES
(1, 'student'),
(1, 'alumni'),
(1, 'recruiter'),
(1, 'admin');

-- --------------------------------------------------------

--
-- Table structure for table `application_notes`
--

CREATE TABLE `application_notes` (
  `note_id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `author_id` int(11) NOT NULL,
  `note_text` text NOT NULL,
  `is_internal` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `application_notes`
--

INSERT INTO `application_notes` (`note_id`, `application_id`, `author_id`, `note_text`, `is_internal`, `created_at`) VALUES
(1, 1, 8, 'Strong fundamentals; schedule interview.', 1, '2025-10-24 20:45:49');

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `badges`
--

CREATE TABLE `badges` (
  `badge_id` int(11) NOT NULL,
  `badge_name` varchar(100) NOT NULL,
  `category_id` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `icon_url` varchar(255) DEFAULT NULL,
  `required_points` int(11) DEFAULT 0,
  `level` enum('bronze','silver','gold','platinum') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `badges`
--

INSERT INTO `badges` (`badge_id`, `badge_name`, `category_id`, `description`, `icon_url`, `required_points`, `level`, `created_at`) VALUES
(1, 'Mentor', 1, 'Helps students with mentorship', NULL, 0, 'silver', '2025-10-24 20:45:49'),
(2, 'Helper', 1, 'Provides helpful answers', NULL, 0, 'bronze', '2025-10-24 20:45:49');

-- --------------------------------------------------------

--
-- Table structure for table `badge_categories`
--

CREATE TABLE `badge_categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `badge_categories`
--

INSERT INTO `badge_categories` (`category_id`, `category_name`, `description`) VALUES
(1, 'General', 'General badges');

-- --------------------------------------------------------

--
-- Table structure for table `career_interests`
--

CREATE TABLE `career_interests` (
  `interest_id` int(11) NOT NULL,
  `name` varchar(128) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `conversations`
--

CREATE TABLE `conversations` (
  `conversation_id` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `conversation_type` enum('direct','group') DEFAULT 'direct',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `conversations`
--

INSERT INTO `conversations` (`conversation_id`, `created_by`, `title`, `conversation_type`, `created_at`, `updated_at`) VALUES
(1, 3, 'Alice & Bob', 'direct', '2025-10-24 20:45:49', '2025-10-24 20:45:49'),
(2, 1, 'Admin & Alumni', 'group', '2025-10-24 20:45:49', '2025-10-24 20:45:49');

-- --------------------------------------------------------

--
-- Table structure for table `conversation_participants`
--

CREATE TABLE `conversation_participants` (
  `participant_id` int(11) NOT NULL,
  `conversation_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `conversation_participants`
--

INSERT INTO `conversation_participants` (`participant_id`, `conversation_id`, `user_id`, `joined_at`) VALUES
(1, 1, 3, '2025-10-24 20:45:49'),
(2, 1, 6, '2025-10-24 20:45:49'),
(4, 2, 1, '2025-10-24 20:45:49'),
(5, 2, 2, '2025-10-24 20:45:49'),
(6, 2, 6, '2025-10-24 20:45:49'),
(7, 2, 7, '2025-10-24 20:45:49');

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `course_id` int(11) NOT NULL,
  `code` varchar(32) DEFAULT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `event_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `event_type` enum('career_fair','hackathon','workshop','networking','seminar') NOT NULL,
  `event_date` datetime NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `venue_details` text DEFAULT NULL,
  `organizer_id` int(11) DEFAULT NULL,
  `max_participants` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`event_id`, `title`, `description`, `event_type`, `event_date`, `location`, `venue_details`, `organizer_id`, `max_participants`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'UIU Career Fair 2026', 'Meet top employers and alumni mentors.', 'career_fair', '2025-12-24 00:00:00', 'UIU Campus', NULL, 1, 500, 1, '2025-10-24 20:45:49', '2025-10-24 20:45:49'),
(2, 'Tech Talk: Modern PHP', 'A session on modern PHP practices.', 'workshop', '2025-11-14 00:00:00', 'Auditorium A', NULL, 2, 200, 1, '2025-10-24 20:45:49', '2025-10-24 20:45:49');

-- --------------------------------------------------------

--
-- Table structure for table `event_registrations`
--

CREATE TABLE `event_registrations` (
  `registration_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `registration_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('registered','attended','cancelled') DEFAULT 'registered'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event_registrations`
--

INSERT INTO `event_registrations` (`registration_id`, `event_id`, `user_id`, `registration_date`, `status`) VALUES
(1, 1, 3, '2025-10-24 20:45:49', 'registered');

-- --------------------------------------------------------

--
-- Table structure for table `expertise_areas`
--

CREATE TABLE `expertise_areas` (
  `expertise_id` int(11) NOT NULL,
  `area_name` varchar(100) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `expertise_areas`
--

INSERT INTO `expertise_areas` (`expertise_id`, `area_name`, `category`, `created_at`) VALUES
(1, 'Backend Development', 'Engineering', '2025-10-24 20:45:49'),
(2, 'Career Coaching', 'Mentorship', '2025-10-24 20:45:49');

-- --------------------------------------------------------

--
-- Table structure for table `forum_categories`
--

CREATE TABLE `forum_categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `forum_categories`
--

INSERT INTO `forum_categories` (`category_id`, `category_name`, `description`, `is_active`, `created_at`) VALUES
(1, 'Career & Internship Guidance', 'Advice on careers and internships', 1, '2025-10-24 20:45:49'),
(2, 'CV, Resume & Interview Prep', 'Help with CVs, resumes, and interviews', 1, '2025-10-24 20:45:49'),
(3, 'Higher Studies & Research', 'Discussion on grad school and research', 1, '2025-10-24 20:45:49'),
(4, 'Tech & Projects Helpdesk', 'Technical questions and project support', 1, '2025-10-24 20:45:49'),
(5, 'Networking & Mentorship Opportunities', 'Find mentors and networking', 1, '2025-10-24 20:45:49'),
(6, 'Alumni Industry Insights', 'Insights from alumni in industry', 1, '2025-10-24 20:45:49'),
(7, 'General Discussion / UIU Life', 'Campus life and general topics', 1, '2025-10-24 20:45:49'),
(8, 'Events & Announcements', 'Events and official announcements', 1, '2025-10-24 20:45:49'),
(9, 'System Support & Feedback', 'Support and feedback for the platform', 1, '2025-10-24 20:45:49');

-- --------------------------------------------------------

--
-- Table structure for table `forum_posts`
--

CREATE TABLE `forum_posts` (
  `post_id` int(11) NOT NULL,
  `author_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `parent_post_id` int(11) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `content` text NOT NULL,
  `post_type` enum('question','answer','discussion') DEFAULT 'question',
  `is_pinned` tinyint(1) DEFAULT 0,
  `is_flagged` tinyint(1) DEFAULT 0,
  `is_closed` tinyint(1) DEFAULT 0,
  `is_best_answer` tinyint(1) DEFAULT 0,
  `is_approved` tinyint(1) NOT NULL DEFAULT 0,
  `moderation_status` enum('pending','approved','rejected') DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejected_by` int(11) DEFAULT NULL,
  `rejected_at` datetime DEFAULT NULL,
  `reject_reason` text DEFAULT NULL,
  `upvote_count` int(11) DEFAULT 0,
  `downvote_count` int(11) DEFAULT 0,
  `view_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `forum_posts`
--

INSERT INTO `forum_posts` (`post_id`, `author_id`, `category_id`, `parent_post_id`, `title`, `content`, `post_type`, `is_pinned`, `is_flagged`, `is_closed`, `is_best_answer`, `is_approved`, `moderation_status`, `approved_by`, `approved_at`, `rejected_by`, `rejected_at`, `reject_reason`, `upvote_count`, `downvote_count`, `view_count`, `created_at`, `updated_at`) VALUES
(1, 3, 2, NULL, 'How to prepare for backend interviews?', 'What topics should I focus on for PHP/MySQL backend interviews?', 'question', 0, 0, 0, 0, 1, 'approved', 1, '2025-10-25 02:45:49', NULL, NULL, NULL, 0, 0, 0, '2025-10-24 20:45:49', '2025-10-24 20:45:49'),
(2, 6, 2, 1, NULL, 'Focus on SQL indexing, transactions, PDO prepared statements, and HTTP basics. Build a small project!', 'answer', 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, '2025-10-24 20:45:49', '2025-10-24 20:45:49'),
(3, 4, 4, NULL, 'Best practices for file uploads in PHP?', 'How to safely handle file uploads and MIME validation?', 'question', 0, 0, 0, 0, 1, 'approved', 1, '2025-10-25 02:45:49', NULL, NULL, NULL, 0, 0, 0, '2025-10-24 20:45:49', '2025-10-24 20:45:49'),
(4, 5, 4, NULL, 'How to optimize SQL queries?', 'Tips for optimizing joins and indexes in MySQL?', 'question', 0, 0, 0, 0, 1, 'approved', 1, '2025-10-25 02:45:50', NULL, NULL, NULL, 0, 0, 0, '2025-10-24 20:45:49', '2025-10-24 20:45:49'),
(5, 14, 4, 4, NULL, 'Add covering indexes, analyze EXPLAIN, avoid SELECT * and use proper data types.', 'answer', 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, '2025-10-24 20:45:49', '2025-10-24 20:45:49');

-- --------------------------------------------------------

--
-- Table structure for table `interviews`
--

CREATE TABLE `interviews` (
  `interview_id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `scheduled_date` datetime NOT NULL,
  `duration_minutes` int(11) DEFAULT 30,
  `meeting_link` varchar(255) DEFAULT NULL,
  `interviewer_name` varchar(100) DEFAULT NULL,
  `status` enum('scheduled','completed','cancelled','no_show') DEFAULT 'scheduled',
  `feedback` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `interviews`
--

INSERT INTO `interviews` (`interview_id`, `application_id`, `scheduled_date`, `duration_minutes`, `meeting_link`, `interviewer_name`, `status`, `feedback`, `created_at`, `updated_at`) VALUES
(1, 1, '2025-11-01 02:45:49', 45, 'https://meet.example/interview-1', 'Rachel Recruiter', 'scheduled', NULL, '2025-10-24 20:45:49', '2025-10-24 20:45:49');

-- --------------------------------------------------------

--
-- Table structure for table `job_applications`
--

CREATE TABLE `job_applications` (
  `application_id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `cover_letter` text DEFAULT NULL,
  `status` enum('applied','under_review','shortlisted','interview','accepted','rejected') DEFAULT 'applied',
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `shortlisted_at` timestamp NULL DEFAULT NULL,
  `interviewed_at` timestamp NULL DEFAULT NULL,
  `decided_at` timestamp NULL DEFAULT NULL,
  `application_score` decimal(5,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `job_applications`
--

INSERT INTO `job_applications` (`application_id`, `job_id`, `student_id`, `cover_letter`, `status`, `applied_at`, `shortlisted_at`, `interviewed_at`, `decided_at`, `application_score`) VALUES
(1, 1, 1, 'Excited to contribute and learn!', 'under_review', '2025-10-24 20:45:49', NULL, NULL, NULL, 78.50);

-- --------------------------------------------------------

--
-- Table structure for table `job_application_answers`
--

CREATE TABLE `job_application_answers` (
  `answer_id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `answer_text` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `job_application_answers`
--

INSERT INTO `job_application_answers` (`answer_id`, `application_id`, `question_id`, `answer_text`) VALUES
(1, 1, 1, 'I have built REST APIs with auth, caching, and solid SQL schemas.'),
(2, 1, 2, 'https://github.com/example/alice-nexus-demo');

-- --------------------------------------------------------

--
-- Table structure for table `job_application_questions`
--

CREATE TABLE `job_application_questions` (
  `question_id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `question_text` varchar(500) NOT NULL,
  `question_type` enum('text','textarea','number','url') NOT NULL DEFAULT 'text',
  `is_required` tinyint(1) NOT NULL DEFAULT 1,
  `order_no` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `job_application_questions`
--

INSERT INTO `job_application_questions` (`question_id`, `job_id`, `question_text`, `question_type`, `is_required`, `order_no`) VALUES
(1, 1, 'Why are you a good fit for this role?', 'textarea', 1, 1),
(2, 1, 'Link to a project or repo you are proud of', 'text', 0, 2);

-- --------------------------------------------------------

--
-- Table structure for table `job_application_references`
--

CREATE TABLE `job_application_references` (
  `application_id` int(11) NOT NULL,
  `reference_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `job_application_references`
--

INSERT INTO `job_application_references` (`application_id`, `reference_id`) VALUES
(1, 1),
(1, 2);

-- --------------------------------------------------------

--
-- Table structure for table `job_categories`
--

CREATE TABLE `job_categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `job_categories`
--

INSERT INTO `job_categories` (`category_id`, `category_name`) VALUES
(1, 'Software Engineering');

-- --------------------------------------------------------

--
-- Table structure for table `job_listings`
--

CREATE TABLE `job_listings` (
  `job_id` int(11) NOT NULL,
  `recruiter_id` int(11) NOT NULL,
  `job_title` varchar(255) NOT NULL,
  `job_description` text NOT NULL,
  `category_id` int(11) NOT NULL,
  `type_id` int(11) NOT NULL,
  `location_id` int(11) NOT NULL,
  `duration` varchar(100) DEFAULT NULL,
  `salary_range_min` int(11) DEFAULT NULL,
  `salary_range_max` int(11) DEFAULT NULL,
  `stipend_amount` int(11) DEFAULT NULL,
  `application_deadline` date DEFAULT NULL,
  `required_skills` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`required_skills`)),
  `is_active` tinyint(1) DEFAULT 1,
  `is_approved` tinyint(1) DEFAULT 0,
  `is_premium` tinyint(1) DEFAULT 0,
  `views_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `job_listings`
--

INSERT INTO `job_listings` (`job_id`, `recruiter_id`, `job_title`, `job_description`, `category_id`, `type_id`, `location_id`, `duration`, `salary_range_min`, `salary_range_max`, `stipend_amount`, `application_deadline`, `required_skills`, `is_active`, `is_approved`, `is_premium`, `views_count`, `created_at`, `updated_at`) VALUES
(1, 1, 'Backend Engineer (PHP)', 'Build APIs and services using PHP, PDO, and MySQL. Solid SQL skills required.', 1, 1, 1, 'Full-time', 80000, 120000, NULL, '2025-12-09', '[\"PHP\", \"MySQL\"]', 1, 1, 0, 0, '2025-10-24 20:45:49', '2025-10-24 20:45:49');

-- --------------------------------------------------------

--
-- Table structure for table `job_listing_skills`
--

CREATE TABLE `job_listing_skills` (
  `job_id` int(11) NOT NULL,
  `skill_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `job_listing_skills`
--

INSERT INTO `job_listing_skills` (`job_id`, `skill_id`) VALUES
(1, 1),
(1, 2);

-- --------------------------------------------------------

--
-- Table structure for table `job_types`
--

CREATE TABLE `job_types` (
  `type_id` int(11) NOT NULL,
  `type_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `job_types`
--

INSERT INTO `job_types` (`type_id`, `type_name`) VALUES
(1, 'Full-time');

-- --------------------------------------------------------

--
-- Table structure for table `leaderboards`
--

CREATE TABLE `leaderboards` (
  `leaderboard_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `period_type` enum('daily','weekly','monthly','all') NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `score` int(11) NOT NULL,
  `rank` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `locations`
--

CREATE TABLE `locations` (
  `location_id` int(11) NOT NULL,
  `location_name` varchar(100) NOT NULL,
  `country` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `locations`
--

INSERT INTO `locations` (`location_id`, `location_name`, `country`, `state`, `city`) VALUES
(1, 'Dhaka', 'Bangladesh', 'Dhaka', 'Dhaka');

-- --------------------------------------------------------

--
-- Table structure for table `mentorship_cancellation_requests`
--

CREATE TABLE `mentorship_cancellation_requests` (
  `cancellation_id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `requested_by_user_id` int(11) NOT NULL,
  `requested_by_role` enum('student','alumni','admin') NOT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `decided_by` int(11) DEFAULT NULL,
  `decided_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mentorship_listings`
--

CREATE TABLE `mentorship_listings` (
  `listing_id` int(11) NOT NULL,
  `alumni_id` int(11) NOT NULL,
  `expertise_id` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `min_coin_bid` int(11) NOT NULL,
  `max_slots` int(11) NOT NULL,
  `current_slots` int(11) DEFAULT 0,
  `session_duration` int(11) DEFAULT 60,
  `available_times` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`available_times`)),
  `min_cgpa` decimal(3,2) DEFAULT NULL,
  `min_projects` int(11) DEFAULT NULL,
  `min_wallet_coins` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mentorship_listings`
--

INSERT INTO `mentorship_listings` (`listing_id`, `alumni_id`, `expertise_id`, `description`, `min_coin_bid`, `max_slots`, `current_slots`, `session_duration`, `available_times`, `min_cgpa`, `min_projects`, `min_wallet_coins`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 'Office hours: backend career guidance', 20, 3, 0, 60, '[{\"day\": \"Mon\", \"start\": \"09:00\", \"end\": \"10:00\", \"tz\": \"Asia/Dhaka\"}]', NULL, NULL, NULL, 1, '2025-10-24 20:45:49', '2025-10-24 20:45:49'),
(2, 2, 2, 'Data engineering mentorship hours', 15, 2, 0, 45, '[{\"day\": \"Wed\", \"start\": \"20:00\", \"end\": \"21:00\", \"tz\": \"Asia/Dhaka\"}]', NULL, NULL, NULL, 1, '2025-10-24 20:45:49', '2025-10-24 20:45:49');

-- --------------------------------------------------------

--
-- Table structure for table `mentorship_listing_times`
--

CREATE TABLE `mentorship_listing_times` (
  `time_id` int(11) NOT NULL,
  `listing_id` int(11) NOT NULL,
  `day_of_week` tinyint(4) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `timezone` varchar(64) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mentorship_listing_times`
--

INSERT INTO `mentorship_listing_times` (`time_id`, `listing_id`, `day_of_week`, `start_time`, `end_time`, `timezone`) VALUES
(1, 1, 1, '09:00:00', '10:00:00', 'Asia/Dhaka');

-- --------------------------------------------------------

--
-- Table structure for table `mentorship_requests`
--

CREATE TABLE `mentorship_requests` (
  `request_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `listing_id` int(11) NOT NULL,
  `bid_amount` int(11) NOT NULL,
  `priority_score` int(11) NOT NULL DEFAULT 0,
  `status` enum('pending','accepted','declined','completed','cancelled') DEFAULT 'pending',
  `reserved_until` datetime DEFAULT NULL,
  `reservation_extensions` tinyint(4) NOT NULL DEFAULT 0,
  `message` text DEFAULT NULL,
  `is_free_request` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `responded_at` timestamp NULL DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mentorship_requests`
--

INSERT INTO `mentorship_requests` (`request_id`, `student_id`, `listing_id`, `bid_amount`, `priority_score`, `status`, `reserved_until`, `reservation_extensions`, `message`, `is_free_request`, `created_at`, `responded_at`, `start_date`, `end_date`, `completed_at`) VALUES
(1, 1, 1, 25, 0, 'completed', NULL, 0, 'Would love guidance on interview prep.', 0, '2025-10-24 20:45:49', '2025-10-24 20:45:50', '2025-10-15', '2025-10-22', '2025-10-24 20:45:50'),
(2, 2, 2, 18, 0, 'pending', NULL, 0, 'Interested in data career path.', 0, '2025-10-24 20:45:49', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `mentorship_sessions`
--

CREATE TABLE `mentorship_sessions` (
  `session_id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `session_date` date NOT NULL,
  `session_time` time NOT NULL,
  `duration_minutes` int(11) DEFAULT 60,
  `session_notes` text DEFAULT NULL,
  `meeting_link` varchar(255) DEFAULT NULL,
  `student_rating` int(11) DEFAULT NULL,
  `student_feedback` text DEFAULT NULL,
  `mentor_rating` int(11) DEFAULT NULL,
  `mentor_feedback` text DEFAULT NULL,
  `status` enum('scheduled','completed','cancelled','no_show') DEFAULT 'scheduled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `message_id` int(11) NOT NULL,
  `conversation_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `message_text` text NOT NULL,
  `message_type` enum('text','file','meeting') DEFAULT 'text',
  `meeting_link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`message_id`, `conversation_id`, `sender_id`, `message_text`, `message_type`, `meeting_link`, `is_read`, `created_at`) VALUES
(1, 1, 3, 'Hi Bob! Do you have time to chat about interview prep?', 'text', NULL, 0, '2025-10-24 20:45:49');

-- --------------------------------------------------------

--
-- Table structure for table `message_attachments`
--

CREATE TABLE `message_attachments` (
  `attachment_id` int(11) NOT NULL,
  `message_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_url` varchar(1024) NOT NULL,
  `mime_type` varchar(255) NOT NULL,
  `file_size` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `message_attachments`
--

INSERT INTO `message_attachments` (`attachment_id`, `message_id`, `file_name`, `file_url`, `mime_type`, `file_size`, `created_at`) VALUES
(1, 1, 'interview_prep.pdf', '/uploads/conversations/interview_prep.pdf', 'application/pdf', 204800, '2025-10-24 20:45:49');

-- --------------------------------------------------------

--
-- Table structure for table `message_reads`
--

CREATE TABLE `message_reads` (
  `message_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `read_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `message_reads`
--

INSERT INTO `message_reads` (`message_id`, `user_id`, `read_at`) VALUES
(1, 6, '2025-10-24 20:45:49');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `notification_type` varchar(100) NOT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `action_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_id`, `title`, `message`, `notification_type`, `entity_type`, `entity_id`, `is_read`, `action_url`, `created_at`) VALUES
(1, 3, 'Welcome 🎉', 'Your account is ready. Explore the platform!', 'system', NULL, NULL, 0, '/', '2025-10-24 20:45:49'),
(2, 3, 'New Job Posted', 'Check out the Backend Engineer (PHP) role.', 'job', 'job', 1, 0, '/jobs/', '2025-10-24 20:45:49'),
(3, 4, 'New Job Posted', 'Check out the Backend Engineer (PHP) role.', 'job', 'job', 1, 0, '/jobs/', '2025-10-24 20:45:49');

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `permission_id` int(11) NOT NULL,
  `permission_key` varchar(120) NOT NULL,
  `module` varchar(60) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`permission_id`, `permission_key`, `module`, `description`) VALUES
(1, 'admin.view_audit', 'admin', 'View audit logs'),
(2, 'reports.manage', 'reports', 'Manage user reports'),
(3, 'mentorship.manage', 'mentorship', 'Approve mentorship listings');

-- --------------------------------------------------------

--
-- Table structure for table `post_attachments`
--

CREATE TABLE `post_attachments` (
  `attachment_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_url` varchar(255) NOT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `post_votes`
--

CREATE TABLE `post_votes` (
  `vote_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `vote_type` enum('upvote','downvote') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `post_votes`
--

INSERT INTO `post_votes` (`vote_id`, `user_id`, `post_id`, `vote_type`, `created_at`) VALUES
(1, 1, 1, 'upvote', '2025-10-24 20:45:49'),
(2, 3, 4, 'upvote', '2025-10-24 20:45:50'),
(3, 4, 4, 'upvote', '2025-10-24 20:45:50');

-- --------------------------------------------------------

--
-- Table structure for table `profile_field_visibility`
--

CREATE TABLE `profile_field_visibility` (
  `user_id` int(11) NOT NULL,
  `field_key` varchar(64) NOT NULL,
  `is_visible` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `profile_field_visibility`
--

INSERT INTO `profile_field_visibility` (`user_id`, `field_key`, `is_visible`, `created_at`, `updated_at`) VALUES
(3, 'address', 0, '2025-10-24 20:45:49', '2025-10-24 20:45:49'),
(3, 'certificates', 1, '2025-10-24 20:45:49', '2025-10-24 20:45:49'),
(3, 'cgpa', 0, '2025-10-24 20:45:49', '2025-10-24 20:45:49'),
(3, 'linkedin', 1, '2025-10-24 20:45:49', '2025-10-24 20:45:49'),
(3, 'phone', 0, '2025-10-24 20:45:49', '2025-10-24 20:45:49'),
(3, 'resume', 1, '2025-10-24 20:45:49', '2025-10-24 20:45:49'),
(4, 'address', 0, '2025-10-24 20:45:49', '2025-10-24 20:45:49'),
(4, 'certificates', 1, '2025-10-24 20:45:49', '2025-10-24 20:45:49'),
(4, 'cgpa', 0, '2025-10-24 20:45:49', '2025-10-24 20:45:49'),
(4, 'linkedin', 1, '2025-10-24 20:45:49', '2025-10-24 20:45:49'),
(4, 'phone', 0, '2025-10-24 20:45:49', '2025-10-24 20:45:49'),
(4, 'resume', 1, '2025-10-24 20:45:49', '2025-10-24 20:45:49'),
(5, 'address', 0, '2025-10-24 20:45:49', '2025-10-24 20:45:49'),
(5, 'certificates', 1, '2025-10-24 20:45:49', '2025-10-24 20:45:49'),
(5, 'cgpa', 0, '2025-10-24 20:45:49', '2025-10-24 20:45:49'),
(5, 'linkedin', 1, '2025-10-24 20:45:49', '2025-10-24 20:45:49'),
(5, 'phone', 0, '2025-10-24 20:45:49', '2025-10-24 20:45:49'),
(5, 'resume', 1, '2025-10-24 20:45:49', '2025-10-24 20:45:49'),
(6, 'cgpa', 0, '2025-10-24 20:45:49', '2025-10-24 20:45:49'),
(6, 'company', 1, '2025-10-24 20:45:49', '2025-10-24 20:45:49'),
(6, 'email', 0, '2025-10-24 20:45:49', '2025-10-24 20:45:49'),
(6, 'job_title', 1, '2025-10-24 20:45:49', '2025-10-24 20:45:49'),
(6, 'linkedin', 1, '2025-10-24 20:45:49', '2025-10-24 20:45:49'),
(6, 'mentorship_rating', 0, '2025-10-24 20:45:49', '2025-10-24 20:45:49'),
(6, 'phone', 0, '2025-10-24 20:45:49', '2025-10-24 20:45:49'),
(6, 'portfolio', 1, '2025-10-24 20:45:49', '2025-10-24 20:45:49'),
(7, 'cgpa', 0, '2025-10-24 20:45:49', '2025-10-24 20:45:49'),
(7, 'company', 1, '2025-10-24 20:45:49', '2025-10-24 20:45:49'),
(7, 'email', 0, '2025-10-24 20:45:49', '2025-10-24 20:45:49'),
(7, 'job_title', 1, '2025-10-24 20:45:49', '2025-10-24 20:45:49'),
(7, 'linkedin', 1, '2025-10-24 20:45:49', '2025-10-24 20:45:49'),
(7, 'mentorship_rating', 0, '2025-10-24 20:45:49', '2025-10-24 20:45:49'),
(7, 'phone', 0, '2025-10-24 20:45:49', '2025-10-24 20:45:49'),
(7, 'portfolio', 1, '2025-10-24 20:45:49', '2025-10-24 20:45:49'),
(8, 'hr_contact_email', 0, '2025-10-24 20:45:49', '2025-10-24 20:45:49'),
(8, 'hr_contact_phone', 0, '2025-10-24 20:45:49', '2025-10-24 20:45:49'),
(9, 'hr_contact_email', 0, '2025-10-24 20:45:49', '2025-10-24 20:45:49'),
(9, 'hr_contact_phone', 0, '2025-10-24 20:45:49', '2025-10-24 20:45:49');

-- --------------------------------------------------------

--
-- Table structure for table `recruiters`
--

CREATE TABLE `recruiters` (
  `recruiter_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `company_email` varchar(255) DEFAULT NULL,
  `company_description` text DEFAULT NULL,
  `company_website` varchar(255) DEFAULT NULL,
  `company_logo_url` varchar(255) DEFAULT NULL,
  `company_size` enum('1-10','11-50','51-200','201-500','500+') DEFAULT NULL,
  `industry` varchar(100) DEFAULT NULL,
  `hr_contact_name` varchar(100) DEFAULT NULL,
  `hr_contact_first_name` varchar(100) DEFAULT NULL,
  `hr_contact_last_name` varchar(100) DEFAULT NULL,
  `hr_contact_email` varchar(255) DEFAULT NULL,
  `company_location` varchar(128) DEFAULT NULL,
  `hr_contact_role` varchar(128) DEFAULT NULL,
  `hr_contact_phone` varchar(32) DEFAULT NULL,
  `career_page_url` varchar(255) DEFAULT NULL,
  `company_linkedin` varchar(255) DEFAULT NULL,
  `social_links` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`social_links`)),
  `is_verified` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `recruiters`
--

INSERT INTO `recruiters` (`recruiter_id`, `user_id`, `company_name`, `company_email`, `company_description`, `company_website`, `company_logo_url`, `company_size`, `industry`, `hr_contact_name`, `hr_contact_first_name`, `hr_contact_last_name`, `hr_contact_email`, `company_location`, `hr_contact_role`, `hr_contact_phone`, `career_page_url`, `company_linkedin`, `social_links`, `is_verified`, `created_at`) VALUES
(1, 8, 'Acme Corp', 'hr@acme.example', 'Leading provider of innovative solutions.', 'https://acme.example', NULL, '201-500', 'Software', 'Rachel Recruiter', 'Rachel', 'Recruiter', 'rachel.recruiter@acme.example', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-10-24 20:45:49'),
(2, 9, 'Globex', 'hr@globex.example', 'Global enterprise solutions.', 'https://globex.example', NULL, '500+', 'Technology', 'Victor HR', 'Victor', 'HR', 'victor.hr@globex.example', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-10-24 20:45:49'),
(3, 16, 'Beta Ltd.', 'hr@beta.example', 'Product-led startup.', 'https://beta.example', NULL, '51-200', 'Technology', 'Beta HR', NULL, NULL, 'beta.hr@beta.example', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-10-24 20:45:49');

-- --------------------------------------------------------

--
-- Table structure for table `referrals`
--

CREATE TABLE `referrals` (
  `referral_id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `alumni_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `message` text DEFAULT NULL,
  `status` enum('pending','accepted','declined') DEFAULT 'pending',
  `reward_coins` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `referrals`
--

INSERT INTO `referrals` (`referral_id`, `job_id`, `alumni_id`, `student_id`, `message`, `status`, `reward_coins`, `created_at`) VALUES
(1, 1, 1, 1, 'Alice is a great candidate for backend roles.', 'pending', 0, '2025-10-24 20:45:49');

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `report_id` int(11) NOT NULL,
  `reported_by` int(11) NOT NULL,
  `target_type` enum('user','post','job','message','event') NOT NULL,
  `target_id` int(11) NOT NULL,
  `reason` text NOT NULL,
  `status` enum('pending','investigating','resolved','dismissed') DEFAULT 'pending',
  `assigned_to` int(11) DEFAULT NULL,
  `resolution_notes` text DEFAULT NULL,
  `evidence` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`evidence`)),
  `target_author_id` int(11) DEFAULT NULL,
  `target_author_email` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `resolved_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reports`
--

INSERT INTO `reports` (`report_id`, `reported_by`, `target_type`, `target_id`, `reason`, `status`, `assigned_to`, `resolution_notes`, `evidence`, `target_author_id`, `target_author_email`, `created_at`, `resolved_at`) VALUES
(1, 3, 'post', 1, 'Test report: inappropriate content.', 'pending', NULL, NULL, '[{\"type\": \"screenshot\", \"url\": \"/uploads/reports/evidence1.png\"}]', 3, 'alice.student@uiu.ac.bd', '2025-10-24 20:45:49', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `report_exports`
--

CREATE TABLE `report_exports` (
  `export_id` int(11) NOT NULL,
  `exported_by` int(11) NOT NULL,
  `export_type` varchar(50) NOT NULL,
  `filters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`filters`)),
  `file_format` enum('pdf','excel','csv') NOT NULL,
  `file_url` varchar(255) DEFAULT NULL,
  `record_count` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reputation_events`
--

CREATE TABLE `reputation_events` (
  `event_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `delta` int(11) NOT NULL,
  `source` varchar(50) DEFAULT NULL,
  `reference_entity_type` varchar(50) DEFAULT NULL,
  `reference_entity_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reputation_events`
--

INSERT INTO `reputation_events` (`event_id`, `user_id`, `delta`, `source`, `reference_entity_type`, `reference_entity_id`, `created_at`) VALUES
(1, 3, 8, 'seed:rep:alice:weekly1', 'forum_post', 1, '2025-10-22 20:45:50'),
(2, 6, 15, 'seed:rep:bob:weekly1', 'forum_post', 2, '2025-10-21 20:45:50'),
(3, 12, -1, 'seed:rep:omar:weekly1', 'forum_vote', NULL, '2025-10-23 20:45:50'),
(4, 14, 12, 'seed:rep:karim:monthly1', 'forum_post', 4, '2025-10-12 20:45:50'),
(5, 11, 6, 'seed:rep:leena:monthly1', 'forum_post', NULL, '2025-10-15 20:45:50'),
(6, 7, 3, 'seed:rep:diana:weekly1', 'forum_vote', NULL, '2025-10-20 20:45:50'),
(7, 3, 5, 'seed:rep:alice:thisweek', 'forum_post', NULL, '2025-10-24 20:45:50'),
(8, 6, 7, 'seed:rep:bob:thisweek', 'forum_post', NULL, '2025-10-24 20:45:50'),
(9, 12, -1, 'seed:rep:omar:thisweek', 'forum_vote', NULL, '2025-10-24 20:45:50');

--
-- Triggers `reputation_events`
--
DELIMITER $$
CREATE TRIGGER `trg_bi_rep_events_no_recruiter` BEFORE INSERT ON `reputation_events` FOR EACH ROW BEGIN
  IF EXISTS (SELECT 1 FROM users WHERE user_id = NEW.user_id AND role IN ('recruiter','admin')) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Recruiters/Admins cannot have reputation';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `role` enum('student','alumni','recruiter','admin') NOT NULL,
  `permission_id` int(11) NOT NULL,
  `granted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `granted_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`role`, `permission_id`, `granted_at`, `granted_by`) VALUES
('admin', 1, '2025-10-24 20:45:49', NULL),
('admin', 2, '2025-10-24 20:45:49', NULL),
('admin', 3, '2025-10-24 20:45:49', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `skills`
--

CREATE TABLE `skills` (
  `skill_id` int(11) NOT NULL,
  `skill_name` varchar(100) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `skills`
--

INSERT INTO `skills` (`skill_id`, `skill_name`, `category`, `created_at`) VALUES
(1, 'PHP', 'Programming', '2025-10-24 20:45:49'),
(2, 'MySQL', 'Database', '2025-10-24 20:45:49'),
(3, 'JavaScript', 'Programming', '2025-10-24 20:45:49'),
(4, 'Communication', 'Soft Skill', '2025-10-24 20:45:49'),
(5, 'Project Management', 'Management', '2025-10-24 20:45:49');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `program_level` enum('BSc','MSc') DEFAULT NULL,
  `cgpa` decimal(3,2) DEFAULT NULL,
  `university_id` varchar(100) DEFAULT NULL,
  `admission_year` smallint(6) DEFAULT NULL,
  `admission_trimester` enum('Spring','Summer','Fall') DEFAULT NULL,
  `current_semester` varchar(32) DEFAULT NULL,
  `free_mentorship_requests` int(11) DEFAULT 3,
  `free_mentorship_reset_at` timestamp NULL DEFAULT current_timestamp(),
  `mentorship_cooldown_until` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `user_id`, `department`, `program_level`, `cgpa`, `university_id`, `admission_year`, `admission_trimester`, `current_semester`, `free_mentorship_requests`, `free_mentorship_reset_at`, `mentorship_cooldown_until`, `created_at`) VALUES
(1, 3, 'CSE', 'BSc', 3.85, 'S-20210001', 2021, 'Fall', 'Fall-2025', 3, '2025-10-24 20:45:49', NULL, '2025-10-24 20:45:49'),
(2, 4, 'EEE', 'BSc', 3.45, 'S-20210002', 2021, 'Fall', 'Fall-2025', 3, '2025-10-24 20:45:49', NULL, '2025-10-24 20:45:49'),
(3, 5, 'BBA', 'BSc', 3.71, 'S-20210003', 2021, 'Fall', 'Fall-2025', 3, '2025-10-24 20:45:49', NULL, '2025-10-24 20:45:49'),
(4, 10, 'CSE', 'BSc', 3.20, 'S-20210004', 2021, 'Fall', 'Fall-2025', 3, '2025-10-24 20:45:49', NULL, '2025-10-24 20:45:49'),
(5, 11, 'CSE', 'BSc', 3.90, 'S-20210005', 2021, 'Fall', 'Fall-2025', 3, '2025-10-24 20:45:49', NULL, '2025-10-24 20:45:49'),
(6, 12, 'EEE', 'BSc', 3.10, 'S-20210006', 2021, 'Fall', 'Fall-2025', 3, '2025-10-24 20:45:49', NULL, '2025-10-24 20:45:49'),
(7, 13, 'CSE', 'BSc', 3.70, 'S-20210007', 2021, 'Fall', 'Fall-2025', 3, '2025-10-24 20:45:49', NULL, '2025-10-24 20:45:49');

-- --------------------------------------------------------

--
-- Table structure for table `student_projects`
--

CREATE TABLE `student_projects` (
  `project_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `short_description` text DEFAULT NULL,
  `github_url` varchar(255) DEFAULT NULL,
  `portfolio_url` varchar(255) DEFAULT NULL,
  `certificate_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_references`
--

CREATE TABLE `student_references` (
  `reference_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `alumni_id` int(11) NOT NULL,
  `reference_text` text DEFAULT NULL,
  `status` enum('active','revoked') NOT NULL DEFAULT 'active',
  `created_by` int(11) NOT NULL,
  `created_by_role` enum('student','alumni','admin') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `revoked_by` int(11) DEFAULT NULL,
  `revoked_at` datetime DEFAULT NULL,
  `revoke_reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_references`
--

INSERT INTO `student_references` (`reference_id`, `student_id`, `alumni_id`, `reference_text`, `status`, `created_by`, `created_by_role`, `created_at`, `revoked_by`, `revoked_at`, `revoke_reason`) VALUES
(1, 1, 1, 'I mentored Alice on backend best practices and she progressed fast.', 'active', 6, 'alumni', '2025-10-24 20:45:49', NULL, NULL, NULL),
(2, 1, 2, 'Alice is consistent and communicates well.', 'active', 7, 'alumni', '2025-10-24 20:45:49', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `setting_id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `data_type` enum('string','integer','boolean','json') DEFAULT 'string',
  `description` text DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`setting_id`, `setting_key`, `setting_value`, `data_type`, `description`, `updated_by`, `updated_at`) VALUES
(1, 'schema_version', '5', 'integer', 'Database schema version used by the application', 1, '2025-10-24 20:45:49');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `transaction_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type_id` int(11) NOT NULL,
  `amount` int(11) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `reference_entity_type` varchar(50) DEFAULT NULL,
  `reference_entity_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`transaction_id`, `user_id`, `type_id`, `amount`, `description`, `reference_entity_type`, `reference_entity_id`, `created_at`) VALUES
(1, 3, 1, 25, 'seed:mentorship:alice-bob:hold', 'mentorship_request', 1, '2025-10-12 18:00:00'),
(2, 6, 2, 25, 'seed:mentorship:alice-bob:release', 'mentorship_request', 1, '2025-10-21 18:00:00'),
(3, 3, 8, 5, 'seed:coins:today:quest', NULL, NULL, '2025-10-24 20:45:50'),
(4, 4, 8, 5, 'seed:coins:today:quest', NULL, NULL, '2025-10-24 20:45:50'),
(5, 5, 8, 5, 'seed:coins:today:quest', NULL, NULL, '2025-10-24 20:45:50'),
(6, 10, 7, 25, 'seed:coins:weekly:bonus', NULL, NULL, '2025-10-19 20:45:50'),
(7, 11, 7, 25, 'seed:coins:weekly:bonus', NULL, NULL, '2025-10-19 20:45:50'),
(8, 12, 7, 25, 'seed:coins:weekly:bonus', NULL, NULL, '2025-10-19 20:45:50'),
(9, 6, 4, 2, 'seed:coins:weekly:forum-up', NULL, NULL, '2025-10-22 20:45:50'),
(10, 14, 4, 2, 'seed:coins:weekly:forum-up', NULL, NULL, '2025-10-22 20:45:50'),
(11, 15, 4, 2, 'seed:coins:weekly:forum-up', NULL, NULL, '2025-10-22 20:45:50'),
(12, 12, 5, 1, 'seed:coins:weekly:down-penalty', NULL, NULL, '2025-10-23 20:45:50'),
(13, 6, 6, 10, 'seed:coins:monthly:best-answer', NULL, NULL, '2025-10-09 20:45:50'),
(14, 14, 6, 10, 'seed:coins:monthly:best-answer', NULL, NULL, '2025-10-09 20:45:50');

--
-- Triggers `transactions`
--
DELIMITER $$
CREATE TRIGGER `trg_bi_transactions_no_recruiter` BEFORE INSERT ON `transactions` FOR EACH ROW BEGIN
  IF EXISTS (SELECT 1 FROM users WHERE user_id = NEW.user_id AND role IN ('recruiter','admin')) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Recruiters/Admins cannot have coin transactions';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `transaction_types`
--

CREATE TABLE `transaction_types` (
  `type_id` int(11) NOT NULL,
  `type_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `default_amount` int(11) NOT NULL,
  `is_earning` tinyint(1) DEFAULT 1,
  `module` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transaction_types`
--

INSERT INTO `transaction_types` (`type_id`, `type_name`, `description`, `default_amount`, `is_earning`, `module`) VALUES
(1, 'Mentorship Escrow Hold', 'Coins held from student on accept', 0, 0, 'mentorship'),
(2, 'Mentorship Escrow Release', 'Coins released to alumni on complete', 0, 1, 'mentorship'),
(3, 'Mentorship Priority Boost', 'Coins spent to boost mentorship request', 0, 0, 'mentorship'),
(4, 'Forum Upvote Reward', 'Coins mirrored for upvotes', 1, 1, 'forum'),
(5, 'Forum Downvote Penalty', 'Penalty for abusive voting', 1, 0, 'forum'),
(6, 'Forum Best Answer Reward', 'Coins for accepted answer', 10, 1, 'forum'),
(7, 'Signup Bonus', 'One-time bonus for new users', 25, 1, 'system'),
(8, 'Daily Quest', 'Daily participation reward', 5, 1, 'system');

-- --------------------------------------------------------

--
-- Table structure for table `typing_activity`
--

CREATE TABLE `typing_activity` (
  `conversation_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `last_typed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `typing_activity`
--

INSERT INTO `typing_activity` (`conversation_id`, `user_id`, `last_typed_at`) VALUES
(1, 3, '2025-10-24 20:45:49');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('student','alumni','recruiter','admin') NOT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `email`, `password_hash`, `role`, `is_verified`, `is_active`, `last_login_at`, `created_at`) VALUES
(1, 'admin1@uiu.ac.bd', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1, 1, NULL, '2025-10-24 20:45:48'),
(2, 'admin2@uiu.ac.bd', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1, 1, NULL, '2025-10-24 20:45:48'),
(3, 'alice.student@uiu.ac.bd', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1, 1, NULL, '2025-10-24 20:45:49'),
(4, 'chris.student@uiu.ac.bd', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1, 1, NULL, '2025-10-24 20:45:49'),
(5, 'nina.student@uiu.ac.bd', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1, 1, NULL, '2025-10-24 20:45:49'),
(6, 'bob.alumni@uiu.ac.bd', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'alumni', 1, 1, NULL, '2025-10-24 20:45:49'),
(7, 'diana.alumni@uiu.ac.bd', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'alumni', 1, 1, NULL, '2025-10-24 20:45:49'),
(8, 'rachel.recruiter@acme.example', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'recruiter', 1, 1, NULL, '2025-10-24 20:45:49'),
(9, 'victor.hr@globex.example', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'recruiter', 1, 1, NULL, '2025-10-24 20:45:49'),
(10, 'jamal.student@uiu.ac.bd', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1, 1, NULL, '2025-10-24 20:45:49'),
(11, 'leena.student@uiu.ac.bd', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1, 1, NULL, '2025-10-24 20:45:49'),
(12, 'omar.student@uiu.ac.bd', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1, 1, NULL, '2025-10-24 20:45:49'),
(13, 'fatima.student@uiu.ac.bd', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1, 1, NULL, '2025-10-24 20:45:49'),
(14, 'karim.alumni@uiu.ac.bd', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'alumni', 1, 1, NULL, '2025-10-24 20:45:49'),
(15, 'sara.alumni@uiu.ac.bd', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'alumni', 1, 1, NULL, '2025-10-24 20:45:49'),
(16, 'beta.hr@beta.example', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'recruiter', 1, 1, NULL, '2025-10-24 20:45:49');

-- --------------------------------------------------------

--
-- Table structure for table `user_badges`
--

CREATE TABLE `user_badges` (
  `user_badge_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `badge_id` int(11) NOT NULL,
  `awarded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `awarded_for_entity_type` varchar(50) DEFAULT NULL,
  `awarded_for_entity_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_badges`
--

INSERT INTO `user_badges` (`user_badge_id`, `user_id`, `badge_id`, `awarded_at`, `awarded_for_entity_type`, `awarded_for_entity_id`) VALUES
(1, 6, 1, '2025-10-24 20:45:49', NULL, NULL),
(2, 3, 2, '2025-10-24 20:45:49', NULL, NULL);

--
-- Triggers `user_badges`
--
DELIMITER $$
CREATE TRIGGER `trg_bi_user_badges_no_recruiter` BEFORE INSERT ON `user_badges` FOR EACH ROW BEGIN
  IF EXISTS (SELECT 1 FROM users WHERE user_id = NEW.user_id AND role IN ('recruiter','admin')) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Recruiters/Admins cannot receive badges';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `user_career_interests`
--

CREATE TABLE `user_career_interests` (
  `user_id` int(11) NOT NULL,
  `interest_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_certificates`
--

CREATE TABLE `user_certificates` (
  `certificate_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `issued_by` varchar(255) DEFAULT NULL,
  `issued_on` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_course_interests`
--

CREATE TABLE `user_course_interests` (
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_documents`
--

CREATE TABLE `user_documents` (
  `document_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `doc_type` enum('cv','resume','portfolio','other') NOT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_url` varchar(1024) NOT NULL,
  `mime_type` varchar(255) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_feature_restrictions`
--

CREATE TABLE `user_feature_restrictions` (
  `restriction_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `feature_key` varchar(100) NOT NULL,
  `restricted_until` timestamp NULL DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `acted_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_feature_restrictions`
--

INSERT INTO `user_feature_restrictions` (`restriction_id`, `user_id`, `feature_key`, `restricted_until`, `reason`, `acted_by`, `created_at`) VALUES
(1, 4, 'chat', '2025-10-25 20:45:49', 'Spam prevention test', 1, '2025-10-24 20:45:49');

-- --------------------------------------------------------

--
-- Table structure for table `user_feature_restriction_events`
--

CREATE TABLE `user_feature_restriction_events` (
  `event_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `feature_key` varchar(100) NOT NULL,
  `event_type` enum('suspend','ban','lift') NOT NULL,
  `restricted_until` timestamp NULL DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `acted_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_feature_restriction_events`
--

INSERT INTO `user_feature_restriction_events` (`event_id`, `user_id`, `feature_key`, `event_type`, `restricted_until`, `reason`, `acted_by`, `created_at`) VALUES
(1, 4, 'chat', 'suspend', '2025-10-25 20:45:49', 'Spam prevention test', 1, '2025-10-24 20:45:49');

-- --------------------------------------------------------

--
-- Table structure for table `user_field_locks`
--

CREATE TABLE `user_field_locks` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `field_key` varchar(64) NOT NULL,
  `locked_by` int(11) NOT NULL,
  `locked_at` datetime NOT NULL,
  `locked_until` datetime DEFAULT NULL,
  `reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_permissions`
--

CREATE TABLE `user_permissions` (
  `user_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  `valid_from` datetime NOT NULL DEFAULT current_timestamp(),
  `valid_to` datetime DEFAULT NULL,
  `granted_by` int(11) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_permissions`
--

INSERT INTO `user_permissions` (`user_id`, `permission_id`, `valid_from`, `valid_to`, `granted_by`, `reason`, `created_at`) VALUES
(6, 3, '2025-10-25 02:45:49', NULL, NULL, NULL, '2025-10-24 20:45:49');

-- --------------------------------------------------------

--
-- Table structure for table `user_profiles`
--

CREATE TABLE `user_profiles` (
  `profile_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `bio` text DEFAULT NULL,
  `portfolio_url` varchar(255) DEFAULT NULL,
  `linkedin_url` varchar(255) DEFAULT NULL,
  `profile_picture_url` varchar(255) DEFAULT NULL,
  `privacy_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '{"contact_visible": false, "cgpa_visible": false}' CHECK (json_valid(`privacy_settings`)),
  `phone` varchar(32) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `region` varchar(128) DEFAULT NULL,
  `resume_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_profiles`
--

INSERT INTO `user_profiles` (`profile_id`, `user_id`, `first_name`, `last_name`, `bio`, `portfolio_url`, `linkedin_url`, `profile_picture_url`, `privacy_settings`, `phone`, `address`, `region`, `resume_url`, `created_at`, `updated_at`) VALUES
(1, 1, 'Admin', 'One', 'Platform administrator', NULL, NULL, NULL, '{\"contact_visible\": false, \"cgpa_visible\": false}', NULL, NULL, NULL, NULL, '2025-10-24 20:45:49', '2025-10-24 20:45:49'),
(2, 2, 'Admin', 'Two', 'Infra & Ops', NULL, NULL, NULL, '{\"contact_visible\": false, \"cgpa_visible\": false}', NULL, NULL, NULL, NULL, '2025-10-24 20:45:49', '2025-10-24 20:45:49'),
(3, 3, 'Alice', 'Student', 'CS student into backend dev.', 'https://portfolio.example/alice', 'https://linkedin.com/in/alice-student', NULL, '{\"contact_visible\": false, \"cgpa_visible\": false}', NULL, NULL, NULL, NULL, '2025-10-24 20:45:49', '2025-10-24 20:45:49'),
(4, 4, 'Chris', 'Student', 'Frontend explorer learning backend.', NULL, 'https://linkedin.com/in/chris-student', NULL, '{\"contact_visible\": false, \"cgpa_visible\": false}', NULL, NULL, NULL, NULL, '2025-10-24 20:45:49', '2025-10-24 20:45:49'),
(5, 5, 'Nina', 'Student', 'ML enthusiast and data viz.', NULL, 'https://linkedin.com/in/nina-student', NULL, '{\"contact_visible\": false, \"cgpa_visible\": false}', NULL, NULL, NULL, NULL, '2025-10-24 20:45:49', '2025-10-24 20:45:49'),
(6, 6, 'Bob', 'Alumni', 'Backend engineer and mentor.', NULL, 'https://linkedin.com/in/bob-alumni', NULL, '{\"contact_visible\": false, \"cgpa_visible\": false}', NULL, NULL, NULL, NULL, '2025-10-24 20:45:49', '2025-10-24 20:45:49'),
(7, 7, 'Diana', 'Alumni', 'Data engineer & mentor.', NULL, 'https://linkedin.com/in/diana-alumni', NULL, '{\"contact_visible\": false, \"cgpa_visible\": false}', NULL, NULL, NULL, NULL, '2025-10-24 20:45:49', '2025-10-24 20:45:49'),
(8, 8, 'Rachel', 'Recruiter', 'Recruiter at Acme', NULL, 'https://linkedin.com/in/rachel-recruiter', NULL, '{\"contact_visible\": false, \"cgpa_visible\": false}', NULL, NULL, NULL, NULL, '2025-10-24 20:45:49', '2025-10-24 20:45:49'),
(9, 9, 'Victor', 'HR', 'HR Manager at Globex', NULL, 'https://linkedin.com/in/victor-hr', NULL, '{\"contact_visible\": false, \"cgpa_visible\": false}', NULL, NULL, NULL, NULL, '2025-10-24 20:45:49', '2025-10-24 20:45:49'),
(10, 10, 'Jamal', 'Student', 'Interested in systems programming.', NULL, NULL, NULL, '{\"contact_visible\": false, \"cgpa_visible\": false}', NULL, NULL, NULL, NULL, '2025-10-24 20:45:49', '2025-10-24 20:45:49'),
(11, 11, 'Leena', 'Student', 'Learning ML and data viz.', NULL, NULL, NULL, '{\"contact_visible\": false, \"cgpa_visible\": false}', NULL, NULL, NULL, NULL, '2025-10-24 20:45:49', '2025-10-24 20:45:49'),
(12, 12, 'Omar', 'Student', 'Frontend focused, React + PHP.', NULL, NULL, NULL, '{\"contact_visible\": false, \"cgpa_visible\": false}', NULL, NULL, NULL, NULL, '2025-10-24 20:45:49', '2025-10-24 20:45:49'),
(13, 13, 'Fatima', 'Student', 'Security and CTFs.', NULL, NULL, NULL, '{\"contact_visible\": false, \"cgpa_visible\": false}', NULL, NULL, NULL, NULL, '2025-10-24 20:45:49', '2025-10-24 20:45:49'),
(14, 14, 'Karim', 'Alumni', 'SRE at CloudCo, mentoring ops.', NULL, NULL, NULL, '{\"contact_visible\": false, \"cgpa_visible\": false}', NULL, NULL, NULL, NULL, '2025-10-24 20:45:49', '2025-10-24 20:45:49'),
(15, 15, 'Sara', 'Alumni', 'Mobile engineer, accessibility advocate.', NULL, NULL, NULL, '{\"contact_visible\": false, \"cgpa_visible\": false}', NULL, NULL, NULL, NULL, '2025-10-24 20:45:49', '2025-10-24 20:45:49'),
(16, 16, 'Beta', 'HR', 'Recruiter at Beta Ltd.', NULL, NULL, NULL, '{\"contact_visible\": false, \"cgpa_visible\": false}', NULL, NULL, NULL, NULL, '2025-10-24 20:45:49', '2025-10-24 20:45:49');

-- --------------------------------------------------------

--
-- Table structure for table `user_skills`
--

CREATE TABLE `user_skills` (
  `user_skill_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `skill_id` int(11) NOT NULL,
  `proficiency_level` enum('beginner','intermediate','advanced','expert') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_skills`
--

INSERT INTO `user_skills` (`user_skill_id`, `user_id`, `skill_id`, `proficiency_level`, `created_at`) VALUES
(1, 3, 1, 'intermediate', '2025-10-24 20:45:49'),
(2, 3, 3, 'beginner', '2025-10-24 20:45:49'),
(3, 6, 1, 'expert', '2025-10-24 20:45:49'),
(4, 6, 2, 'advanced', '2025-10-24 20:45:49'),
(5, 8, 5, 'advanced', '2025-10-24 20:45:49'),
(6, 8, 4, 'advanced', '2025-10-24 20:45:49');

-- --------------------------------------------------------

--
-- Table structure for table `user_status_history`
--

CREATE TABLE `user_status_history` (
  `status_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `old_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) NOT NULL,
  `reason` text DEFAULT NULL,
  `changed_by` int(11) NOT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_wallets`
--

CREATE TABLE `user_wallets` (
  `wallet_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `balance` int(11) DEFAULT 100,
  `total_earned` int(11) DEFAULT 0,
  `total_spent` int(11) DEFAULT 0,
  `reputation_score` int(11) DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_wallets`
--

INSERT INTO `user_wallets` (`wallet_id`, `user_id`, `balance`, `total_earned`, `total_spent`, `reputation_score`, `updated_at`) VALUES
(1, 3, 250, 250, 0, 10, '2025-10-24 20:45:49'),
(2, 4, 200, 200, 0, 5, '2025-10-24 20:45:49'),
(3, 5, 180, 180, 0, 6, '2025-10-24 20:45:49'),
(4, 6, 500, 700, 200, 120, '2025-10-24 20:45:49'),
(5, 7, 350, 350, 0, 80, '2025-10-24 20:45:49'),
(6, 10, 220, 300, 80, 12, '2025-10-24 20:45:49'),
(7, 11, 260, 260, 0, 15, '2025-10-24 20:45:49'),
(8, 12, 140, 200, 60, 4, '2025-10-24 20:45:49'),
(9, 13, 310, 330, 20, 18, '2025-10-24 20:45:49'),
(10, 14, 600, 800, 200, 140, '2025-10-24 20:45:49'),
(11, 15, 420, 470, 50, 95, '2025-10-24 20:45:49');

--
-- Triggers `user_wallets`
--
DELIMITER $$
CREATE TRIGGER `trg_bi_user_wallets_no_recruiter` BEFORE INSERT ON `user_wallets` FOR EACH ROW BEGIN
  IF EXISTS (SELECT 1 FROM users WHERE user_id = NEW.user_id AND role IN ('recruiter','admin')) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Recruiters/Admins cannot have wallets';
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_bu_user_wallets_no_recruiter` BEFORE UPDATE ON `user_wallets` FOR EACH ROW BEGIN
  IF NEW.user_id <> OLD.user_id AND EXISTS (SELECT 1 FROM users WHERE user_id = NEW.user_id AND role IN ('recruiter','admin')) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Recruiters/Admins cannot have wallets';
  END IF;
END
$$
DELIMITER ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `admin_assignments`
--
ALTER TABLE `admin_assignments`
  ADD PRIMARY KEY (`user_id`,`assignment`);

--
-- Indexes for table `admin_permissions`
--
ALTER TABLE `admin_permissions`
  ADD PRIMARY KEY (`user_id`,`permission_key`);

--
-- Indexes for table `alumni`
--
ALTER TABLE `alumni`
  ADD PRIMARY KEY (`alumni_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `alumni_expertise`
--
ALTER TABLE `alumni_expertise`
  ADD PRIMARY KEY (`alumni_expertise_id`),
  ADD UNIQUE KEY `uq_alumni_expertise` (`alumni_id`,`expertise_id`),
  ADD KEY `fk_alumni_expertise_exp` (`expertise_id`);

--
-- Indexes for table `alumni_focus_skills`
--
ALTER TABLE `alumni_focus_skills`
  ADD PRIMARY KEY (`user_id`,`skill_id`),
  ADD KEY `fk_afs_skill` (`skill_id`);

--
-- Indexes for table `alumni_preferences`
--
ALTER TABLE `alumni_preferences`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`announcement_id`),
  ADD KEY `fk_announcements_author` (`author_id`);

--
-- Indexes for table `announcement_target_roles`
--
ALTER TABLE `announcement_target_roles`
  ADD PRIMARY KEY (`announcement_id`,`role`),
  ADD KEY `idx_announcement_roles_role` (`role`);

--
-- Indexes for table `application_notes`
--
ALTER TABLE `application_notes`
  ADD PRIMARY KEY (`note_id`),
  ADD KEY `fk_an_application` (`application_id`),
  ADD KEY `fk_an_author` (`author_id`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_audit_logs_user_created` (`user_id`,`created_at`);

--
-- Indexes for table `badges`
--
ALTER TABLE `badges`
  ADD PRIMARY KEY (`badge_id`),
  ADD UNIQUE KEY `badge_name` (`badge_name`),
  ADD KEY `fk_badges_category` (`category_id`);

--
-- Indexes for table `badge_categories`
--
ALTER TABLE `badge_categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `category_name` (`category_name`);

--
-- Indexes for table `career_interests`
--
ALTER TABLE `career_interests`
  ADD PRIMARY KEY (`interest_id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `conversations`
--
ALTER TABLE `conversations`
  ADD PRIMARY KEY (`conversation_id`),
  ADD KEY `fk_conversations_creator` (`created_by`);

--
-- Indexes for table `conversation_participants`
--
ALTER TABLE `conversation_participants`
  ADD PRIMARY KEY (`participant_id`),
  ADD UNIQUE KEY `uq_conversation_user` (`conversation_id`,`user_id`),
  ADD KEY `fk_cp_user` (`user_id`),
  ADD KEY `idx_conv_participants_conversation` (`conversation_id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`course_id`),
  ADD UNIQUE KEY `uq_course_name` (`name`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`event_id`),
  ADD KEY `fk_events_organizer` (`organizer_id`);

--
-- Indexes for table `event_registrations`
--
ALTER TABLE `event_registrations`
  ADD PRIMARY KEY (`registration_id`),
  ADD UNIQUE KEY `uq_event_user` (`event_id`,`user_id`),
  ADD KEY `idx_event_registrations_user` (`user_id`);

--
-- Indexes for table `expertise_areas`
--
ALTER TABLE `expertise_areas`
  ADD PRIMARY KEY (`expertise_id`),
  ADD UNIQUE KEY `area_name` (`area_name`);

--
-- Indexes for table `forum_categories`
--
ALTER TABLE `forum_categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `category_name` (`category_name`);

--
-- Indexes for table `forum_posts`
--
ALTER TABLE `forum_posts`
  ADD PRIMARY KEY (`post_id`),
  ADD KEY `fk_fp_approved_by` (`approved_by`),
  ADD KEY `fk_fp_rejected_by` (`rejected_by`),
  ADD KEY `idx_forum_posts_author` (`author_id`),
  ADD KEY `idx_forum_posts_category` (`category_id`),
  ADD KEY `idx_forum_posts_parent` (`parent_post_id`),
  ADD KEY `idx_forum_posts_approved` (`is_approved`),
  ADD KEY `idx_forum_posts_moderation_status` (`moderation_status`);

--
-- Indexes for table `interviews`
--
ALTER TABLE `interviews`
  ADD PRIMARY KEY (`interview_id`),
  ADD KEY `fk_interviews_application` (`application_id`);

--
-- Indexes for table `job_applications`
--
ALTER TABLE `job_applications`
  ADD PRIMARY KEY (`application_id`),
  ADD UNIQUE KEY `uq_job_student` (`job_id`,`student_id`),
  ADD KEY `fk_ja_student` (`student_id`);

--
-- Indexes for table `job_application_answers`
--
ALTER TABLE `job_application_answers`
  ADD PRIMARY KEY (`answer_id`),
  ADD UNIQUE KEY `uq_jaa_app_q` (`application_id`,`question_id`),
  ADD KEY `fk_jaa_question` (`question_id`);

--
-- Indexes for table `job_application_questions`
--
ALTER TABLE `job_application_questions`
  ADD PRIMARY KEY (`question_id`),
  ADD KEY `idx_jaq_job_order` (`job_id`,`order_no`);

--
-- Indexes for table `job_application_references`
--
ALTER TABLE `job_application_references`
  ADD PRIMARY KEY (`application_id`,`reference_id`),
  ADD KEY `fk_jar_ref` (`reference_id`);

--
-- Indexes for table `job_categories`
--
ALTER TABLE `job_categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `category_name` (`category_name`);

--
-- Indexes for table `job_listings`
--
ALTER TABLE `job_listings`
  ADD PRIMARY KEY (`job_id`),
  ADD KEY `fk_jl_recruiter` (`recruiter_id`),
  ADD KEY `idx_job_listings_active_approved` (`is_active`,`is_approved`),
  ADD KEY `idx_job_listings_category` (`category_id`),
  ADD KEY `idx_job_listings_type` (`type_id`),
  ADD KEY `idx_job_listings_location` (`location_id`);

--
-- Indexes for table `job_listing_skills`
--
ALTER TABLE `job_listing_skills`
  ADD PRIMARY KEY (`job_id`,`skill_id`),
  ADD KEY `idx_job_listing_skills_skill` (`skill_id`);

--
-- Indexes for table `job_types`
--
ALTER TABLE `job_types`
  ADD PRIMARY KEY (`type_id`),
  ADD UNIQUE KEY `type_name` (`type_name`);

--
-- Indexes for table `leaderboards`
--
ALTER TABLE `leaderboards`
  ADD PRIMARY KEY (`leaderboard_id`),
  ADD KEY `fk_leaderboards_user` (`user_id`),
  ADD KEY `idx_leaderboard_period` (`period_type`,`period_start`,`rank`);

--
-- Indexes for table `locations`
--
ALTER TABLE `locations`
  ADD PRIMARY KEY (`location_id`),
  ADD UNIQUE KEY `location_name` (`location_name`);

--
-- Indexes for table `mentorship_cancellation_requests`
--
ALTER TABLE `mentorship_cancellation_requests`
  ADD PRIMARY KEY (`cancellation_id`),
  ADD KEY `fk_mcr_requester` (`requested_by_user_id`),
  ADD KEY `fk_mcr_decider` (`decided_by`),
  ADD KEY `idx_mcr_request` (`request_id`),
  ADD KEY `idx_mcr_status` (`status`);

--
-- Indexes for table `mentorship_listings`
--
ALTER TABLE `mentorship_listings`
  ADD PRIMARY KEY (`listing_id`),
  ADD KEY `fk_ml_alumni` (`alumni_id`),
  ADD KEY `fk_ml_expertise` (`expertise_id`);

--
-- Indexes for table `mentorship_listing_times`
--
ALTER TABLE `mentorship_listing_times`
  ADD PRIMARY KEY (`time_id`),
  ADD UNIQUE KEY `uq_mentorship_time` (`listing_id`,`day_of_week`,`start_time`,`end_time`),
  ADD KEY `idx_mentorship_times_listing_dow` (`listing_id`,`day_of_week`);

--
-- Indexes for table `mentorship_requests`
--
ALTER TABLE `mentorship_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `fk_mr_student` (`student_id`),
  ADD KEY `fk_mr_listing` (`listing_id`);

--
-- Indexes for table `mentorship_sessions`
--
ALTER TABLE `mentorship_sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD KEY `fk_ms_request` (`request_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `fk_messages_sender` (`sender_id`),
  ADD KEY `idx_messages_conversation_created` (`conversation_id`,`created_at`);

--
-- Indexes for table `message_attachments`
--
ALTER TABLE `message_attachments`
  ADD PRIMARY KEY (`attachment_id`),
  ADD KEY `idx_msg_att` (`message_id`);

--
-- Indexes for table `message_reads`
--
ALTER TABLE `message_reads`
  ADD PRIMARY KEY (`message_id`,`user_id`),
  ADD KEY `idx_reads_user` (`user_id`),
  ADD KEY `idx_reads_msg` (`message_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `idx_notifications_user_read` (`user_id`,`is_read`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`permission_id`),
  ADD UNIQUE KEY `permission_key` (`permission_key`);

--
-- Indexes for table `post_attachments`
--
ALTER TABLE `post_attachments`
  ADD PRIMARY KEY (`attachment_id`),
  ADD KEY `fk_post_attachments_post` (`post_id`);

--
-- Indexes for table `post_votes`
--
ALTER TABLE `post_votes`
  ADD PRIMARY KEY (`vote_id`),
  ADD UNIQUE KEY `uq_post_vote` (`user_id`,`post_id`),
  ADD KEY `fk_post_votes_post` (`post_id`);

--
-- Indexes for table `profile_field_visibility`
--
ALTER TABLE `profile_field_visibility`
  ADD UNIQUE KEY `uq_user_field` (`user_id`,`field_key`);

--
-- Indexes for table `recruiters`
--
ALTER TABLE `recruiters`
  ADD PRIMARY KEY (`recruiter_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `referrals`
--
ALTER TABLE `referrals`
  ADD PRIMARY KEY (`referral_id`),
  ADD KEY `fk_referrals_job` (`job_id`),
  ADD KEY `fk_referrals_alumni` (`alumni_id`),
  ADD KEY `fk_referrals_student` (`student_id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `fk_reports_reported_by` (`reported_by`),
  ADD KEY `fk_reports_assigned_to` (`assigned_to`),
  ADD KEY `idx_reports_target_author` (`target_author_id`);

--
-- Indexes for table `report_exports`
--
ALTER TABLE `report_exports`
  ADD PRIMARY KEY (`export_id`),
  ADD KEY `fk_report_exports_exported_by` (`exported_by`);

--
-- Indexes for table `reputation_events`
--
ALTER TABLE `reputation_events`
  ADD PRIMARY KEY (`event_id`),
  ADD KEY `idx_rep_events_user_created` (`user_id`,`created_at`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`role`,`permission_id`),
  ADD KEY `fk_role_permissions_perm` (`permission_id`),
  ADD KEY `fk_role_permissions_granted_by` (`granted_by`);

--
-- Indexes for table `skills`
--
ALTER TABLE `skills`
  ADD PRIMARY KEY (`skill_id`),
  ADD UNIQUE KEY `skill_name` (`skill_name`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `student_projects`
--
ALTER TABLE `student_projects`
  ADD PRIMARY KEY (`project_id`),
  ADD KEY `fk_student_projects_user` (`user_id`);

--
-- Indexes for table `student_references`
--
ALTER TABLE `student_references`
  ADD PRIMARY KEY (`reference_id`),
  ADD UNIQUE KEY `uq_sr_pair` (`student_id`,`alumni_id`),
  ADD KEY `fk_sr_alumni` (`alumni_id`),
  ADD KEY `fk_sr_created_by` (`created_by`),
  ADD KEY `fk_sr_revoked_by` (`revoked_by`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`setting_id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `fk_system_settings_updated_by` (`updated_by`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `fk_transactions_type` (`type_id`),
  ADD KEY `idx_transactions_user_created` (`user_id`,`created_at`);

--
-- Indexes for table `transaction_types`
--
ALTER TABLE `transaction_types`
  ADD PRIMARY KEY (`type_id`),
  ADD UNIQUE KEY `type_name` (`type_name`);

--
-- Indexes for table `typing_activity`
--
ALTER TABLE `typing_activity`
  ADD PRIMARY KEY (`conversation_id`,`user_id`),
  ADD KEY `idx_typing_user` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_badges`
--
ALTER TABLE `user_badges`
  ADD PRIMARY KEY (`user_badge_id`),
  ADD UNIQUE KEY `uq_user_badge` (`user_id`,`badge_id`),
  ADD KEY `fk_user_badges_badge` (`badge_id`);

--
-- Indexes for table `user_career_interests`
--
ALTER TABLE `user_career_interests`
  ADD PRIMARY KEY (`user_id`,`interest_id`),
  ADD KEY `fk_uci_interest` (`interest_id`);

--
-- Indexes for table `user_certificates`
--
ALTER TABLE `user_certificates`
  ADD PRIMARY KEY (`certificate_id`),
  ADD KEY `fk_user_certificates_user` (`user_id`);

--
-- Indexes for table `user_course_interests`
--
ALTER TABLE `user_course_interests`
  ADD PRIMARY KEY (`user_id`,`course_id`),
  ADD KEY `fk_uci2_course` (`course_id`);

--
-- Indexes for table `user_documents`
--
ALTER TABLE `user_documents`
  ADD PRIMARY KEY (`document_id`),
  ADD UNIQUE KEY `uq_user_doctype` (`user_id`,`doc_type`);

--
-- Indexes for table `user_feature_restrictions`
--
ALTER TABLE `user_feature_restrictions`
  ADD PRIMARY KEY (`restriction_id`),
  ADD KEY `fk_ufr_actor` (`acted_by`),
  ADD KEY `idx_ufr_user` (`user_id`),
  ADD KEY `idx_user_restrictions_user` (`user_id`);

--
-- Indexes for table `user_feature_restriction_events`
--
ALTER TABLE `user_feature_restriction_events`
  ADD PRIMARY KEY (`event_id`),
  ADD KEY `fk_ufre_actor` (`acted_by`),
  ADD KEY `idx_ufre_user_created` (`user_id`,`created_at`),
  ADD KEY `idx_ufre_user_event` (`user_id`,`event_type`),
  ADD KEY `idx_user_restriction_events_user` (`user_id`);

--
-- Indexes for table `user_field_locks`
--
ALTER TABLE `user_field_locks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_field` (`field_key`),
  ADD KEY `fk_ufl_lockedby` (`locked_by`);

--
-- Indexes for table `user_permissions`
--
ALTER TABLE `user_permissions`
  ADD PRIMARY KEY (`user_id`,`permission_id`,`valid_from`),
  ADD KEY `fk_user_permissions_perm` (`permission_id`),
  ADD KEY `fk_user_permissions_granted_by` (`granted_by`),
  ADD KEY `idx_user_permissions_window` (`user_id`,`valid_from`,`valid_to`);

--
-- Indexes for table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD PRIMARY KEY (`profile_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `user_skills`
--
ALTER TABLE `user_skills`
  ADD PRIMARY KEY (`user_skill_id`),
  ADD UNIQUE KEY `uq_user_skill` (`user_id`,`skill_id`),
  ADD KEY `fk_user_skills_skill` (`skill_id`);

--
-- Indexes for table `user_status_history`
--
ALTER TABLE `user_status_history`
  ADD PRIMARY KEY (`status_id`),
  ADD KEY `fk_ush_changed_by` (`changed_by`),
  ADD KEY `idx_user_status_history_user` (`user_id`);

--
-- Indexes for table `user_wallets`
--
ALTER TABLE `user_wallets`
  ADD PRIMARY KEY (`wallet_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `alumni`
--
ALTER TABLE `alumni`
  MODIFY `alumni_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `alumni_expertise`
--
ALTER TABLE `alumni_expertise`
  MODIFY `alumni_expertise_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `announcement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `application_notes`
--
ALTER TABLE `application_notes`
  MODIFY `note_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `badges`
--
ALTER TABLE `badges`
  MODIFY `badge_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `badge_categories`
--
ALTER TABLE `badge_categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `career_interests`
--
ALTER TABLE `career_interests`
  MODIFY `interest_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `conversations`
--
ALTER TABLE `conversations`
  MODIFY `conversation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `conversation_participants`
--
ALTER TABLE `conversation_participants`
  MODIFY `participant_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `course_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `event_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `event_registrations`
--
ALTER TABLE `event_registrations`
  MODIFY `registration_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `expertise_areas`
--
ALTER TABLE `expertise_areas`
  MODIFY `expertise_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `forum_categories`
--
ALTER TABLE `forum_categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `forum_posts`
--
ALTER TABLE `forum_posts`
  MODIFY `post_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `interviews`
--
ALTER TABLE `interviews`
  MODIFY `interview_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `job_applications`
--
ALTER TABLE `job_applications`
  MODIFY `application_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `job_application_answers`
--
ALTER TABLE `job_application_answers`
  MODIFY `answer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `job_application_questions`
--
ALTER TABLE `job_application_questions`
  MODIFY `question_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `job_categories`
--
ALTER TABLE `job_categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `job_listings`
--
ALTER TABLE `job_listings`
  MODIFY `job_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `job_types`
--
ALTER TABLE `job_types`
  MODIFY `type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `leaderboards`
--
ALTER TABLE `leaderboards`
  MODIFY `leaderboard_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `locations`
--
ALTER TABLE `locations`
  MODIFY `location_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `mentorship_cancellation_requests`
--
ALTER TABLE `mentorship_cancellation_requests`
  MODIFY `cancellation_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mentorship_listings`
--
ALTER TABLE `mentorship_listings`
  MODIFY `listing_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `mentorship_listing_times`
--
ALTER TABLE `mentorship_listing_times`
  MODIFY `time_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `mentorship_requests`
--
ALTER TABLE `mentorship_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `mentorship_sessions`
--
ALTER TABLE `mentorship_sessions`
  MODIFY `session_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `message_attachments`
--
ALTER TABLE `message_attachments`
  MODIFY `attachment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `permission_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `post_attachments`
--
ALTER TABLE `post_attachments`
  MODIFY `attachment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `post_votes`
--
ALTER TABLE `post_votes`
  MODIFY `vote_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `recruiters`
--
ALTER TABLE `recruiters`
  MODIFY `recruiter_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `referrals`
--
ALTER TABLE `referrals`
  MODIFY `referral_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `report_exports`
--
ALTER TABLE `report_exports`
  MODIFY `export_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reputation_events`
--
ALTER TABLE `reputation_events`
  MODIFY `event_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `skills`
--
ALTER TABLE `skills`
  MODIFY `skill_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `student_projects`
--
ALTER TABLE `student_projects`
  MODIFY `project_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_references`
--
ALTER TABLE `student_references`
  MODIFY `reference_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `transaction_types`
--
ALTER TABLE `transaction_types`
  MODIFY `type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `user_badges`
--
ALTER TABLE `user_badges`
  MODIFY `user_badge_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `user_certificates`
--
ALTER TABLE `user_certificates`
  MODIFY `certificate_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_documents`
--
ALTER TABLE `user_documents`
  MODIFY `document_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_feature_restrictions`
--
ALTER TABLE `user_feature_restrictions`
  MODIFY `restriction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `user_feature_restriction_events`
--
ALTER TABLE `user_feature_restriction_events`
  MODIFY `event_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `user_field_locks`
--
ALTER TABLE `user_field_locks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_profiles`
--
ALTER TABLE `user_profiles`
  MODIFY `profile_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `user_skills`
--
ALTER TABLE `user_skills`
  MODIFY `user_skill_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `user_status_history`
--
ALTER TABLE `user_status_history`
  MODIFY `status_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_wallets`
--
ALTER TABLE `user_wallets`
  MODIFY `wallet_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admins`
--
ALTER TABLE `admins`
  ADD CONSTRAINT `fk_admins_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `admin_assignments`
--
ALTER TABLE `admin_assignments`
  ADD CONSTRAINT `fk_admin_assignments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `admin_permissions`
--
ALTER TABLE `admin_permissions`
  ADD CONSTRAINT `fk_admin_permissions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `alumni`
--
ALTER TABLE `alumni`
  ADD CONSTRAINT `fk_alumni_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `alumni_expertise`
--
ALTER TABLE `alumni_expertise`
  ADD CONSTRAINT `fk_alumni_expertise_alumni` FOREIGN KEY (`alumni_id`) REFERENCES `alumni` (`alumni_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_alumni_expertise_exp` FOREIGN KEY (`expertise_id`) REFERENCES `expertise_areas` (`expertise_id`) ON DELETE CASCADE;

--
-- Constraints for table `alumni_focus_skills`
--
ALTER TABLE `alumni_focus_skills`
  ADD CONSTRAINT `fk_afs_skill` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`skill_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_afs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `alumni_preferences`
--
ALTER TABLE `alumni_preferences`
  ADD CONSTRAINT `fk_alumni_prefs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `fk_announcements_author` FOREIGN KEY (`author_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `announcement_target_roles`
--
ALTER TABLE `announcement_target_roles`
  ADD CONSTRAINT `fk_announcement_roles_announcement` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`announcement_id`) ON DELETE CASCADE;

--
-- Constraints for table `application_notes`
--
ALTER TABLE `application_notes`
  ADD CONSTRAINT `fk_an_application` FOREIGN KEY (`application_id`) REFERENCES `job_applications` (`application_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_an_author` FOREIGN KEY (`author_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `fk_audit_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `badges`
--
ALTER TABLE `badges`
  ADD CONSTRAINT `fk_badges_category` FOREIGN KEY (`category_id`) REFERENCES `badge_categories` (`category_id`);

--
-- Constraints for table `conversations`
--
ALTER TABLE `conversations`
  ADD CONSTRAINT `fk_conversations_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `conversation_participants`
--
ALTER TABLE `conversation_participants`
  ADD CONSTRAINT `fk_cp_conversation` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`conversation_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cp_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `fk_events_organizer` FOREIGN KEY (`organizer_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `event_registrations`
--
ALTER TABLE `event_registrations`
  ADD CONSTRAINT `fk_er_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_er_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `forum_posts`
--
ALTER TABLE `forum_posts`
  ADD CONSTRAINT `fk_fp_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_fp_author` FOREIGN KEY (`author_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_fp_category` FOREIGN KEY (`category_id`) REFERENCES `forum_categories` (`category_id`),
  ADD CONSTRAINT `fk_fp_parent` FOREIGN KEY (`parent_post_id`) REFERENCES `forum_posts` (`post_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_fp_rejected_by` FOREIGN KEY (`rejected_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `interviews`
--
ALTER TABLE `interviews`
  ADD CONSTRAINT `fk_interviews_application` FOREIGN KEY (`application_id`) REFERENCES `job_applications` (`application_id`) ON DELETE CASCADE;

--
-- Constraints for table `job_applications`
--
ALTER TABLE `job_applications`
  ADD CONSTRAINT `fk_ja_job` FOREIGN KEY (`job_id`) REFERENCES `job_listings` (`job_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ja_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `job_application_answers`
--
ALTER TABLE `job_application_answers`
  ADD CONSTRAINT `fk_jaa_app` FOREIGN KEY (`application_id`) REFERENCES `job_applications` (`application_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_jaa_question` FOREIGN KEY (`question_id`) REFERENCES `job_application_questions` (`question_id`) ON DELETE CASCADE;

--
-- Constraints for table `job_application_questions`
--
ALTER TABLE `job_application_questions`
  ADD CONSTRAINT `fk_jaq_job` FOREIGN KEY (`job_id`) REFERENCES `job_listings` (`job_id`) ON DELETE CASCADE;

--
-- Constraints for table `job_application_references`
--
ALTER TABLE `job_application_references`
  ADD CONSTRAINT `fk_jar_app` FOREIGN KEY (`application_id`) REFERENCES `job_applications` (`application_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_jar_ref` FOREIGN KEY (`reference_id`) REFERENCES `student_references` (`reference_id`) ON DELETE CASCADE;

--
-- Constraints for table `job_listings`
--
ALTER TABLE `job_listings`
  ADD CONSTRAINT `fk_jl_category` FOREIGN KEY (`category_id`) REFERENCES `job_categories` (`category_id`),
  ADD CONSTRAINT `fk_jl_location` FOREIGN KEY (`location_id`) REFERENCES `locations` (`location_id`),
  ADD CONSTRAINT `fk_jl_recruiter` FOREIGN KEY (`recruiter_id`) REFERENCES `recruiters` (`recruiter_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_jl_type` FOREIGN KEY (`type_id`) REFERENCES `job_types` (`type_id`);

--
-- Constraints for table `job_listing_skills`
--
ALTER TABLE `job_listing_skills`
  ADD CONSTRAINT `fk_jls_job` FOREIGN KEY (`job_id`) REFERENCES `job_listings` (`job_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_jls_skill` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`skill_id`) ON DELETE CASCADE;

--
-- Constraints for table `leaderboards`
--
ALTER TABLE `leaderboards`
  ADD CONSTRAINT `fk_leaderboards_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `mentorship_cancellation_requests`
--
ALTER TABLE `mentorship_cancellation_requests`
  ADD CONSTRAINT `fk_mcr_decider` FOREIGN KEY (`decided_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_mcr_request` FOREIGN KEY (`request_id`) REFERENCES `mentorship_requests` (`request_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_mcr_requester` FOREIGN KEY (`requested_by_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `mentorship_listings`
--
ALTER TABLE `mentorship_listings`
  ADD CONSTRAINT `fk_ml_alumni` FOREIGN KEY (`alumni_id`) REFERENCES `alumni` (`alumni_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ml_expertise` FOREIGN KEY (`expertise_id`) REFERENCES `expertise_areas` (`expertise_id`);

--
-- Constraints for table `mentorship_listing_times`
--
ALTER TABLE `mentorship_listing_times`
  ADD CONSTRAINT `fk_mlt_listing` FOREIGN KEY (`listing_id`) REFERENCES `mentorship_listings` (`listing_id`) ON DELETE CASCADE;

--
-- Constraints for table `mentorship_requests`
--
ALTER TABLE `mentorship_requests`
  ADD CONSTRAINT `fk_mr_listing` FOREIGN KEY (`listing_id`) REFERENCES `mentorship_listings` (`listing_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_mr_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `mentorship_sessions`
--
ALTER TABLE `mentorship_sessions`
  ADD CONSTRAINT `fk_ms_request` FOREIGN KEY (`request_id`) REFERENCES `mentorship_requests` (`request_id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `fk_messages_conversation` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`conversation_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_messages_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `message_attachments`
--
ALTER TABLE `message_attachments`
  ADD CONSTRAINT `fk_att_msg` FOREIGN KEY (`message_id`) REFERENCES `messages` (`message_id`) ON DELETE CASCADE;

--
-- Constraints for table `message_reads`
--
ALTER TABLE `message_reads`
  ADD CONSTRAINT `fk_reads_msg` FOREIGN KEY (`message_id`) REFERENCES `messages` (`message_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_reads_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `post_attachments`
--
ALTER TABLE `post_attachments`
  ADD CONSTRAINT `fk_post_attachments_post` FOREIGN KEY (`post_id`) REFERENCES `forum_posts` (`post_id`) ON DELETE CASCADE;

--
-- Constraints for table `post_votes`
--
ALTER TABLE `post_votes`
  ADD CONSTRAINT `fk_post_votes_post` FOREIGN KEY (`post_id`) REFERENCES `forum_posts` (`post_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_post_votes_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `profile_field_visibility`
--
ALTER TABLE `profile_field_visibility`
  ADD CONSTRAINT `fk_pfv_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `recruiters`
--
ALTER TABLE `recruiters`
  ADD CONSTRAINT `fk_recruiters_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `referrals`
--
ALTER TABLE `referrals`
  ADD CONSTRAINT `fk_referrals_alumni` FOREIGN KEY (`alumni_id`) REFERENCES `alumni` (`alumni_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_referrals_job` FOREIGN KEY (`job_id`) REFERENCES `job_listings` (`job_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_referrals_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `fk_reports_assigned_to` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_reports_reported_by` FOREIGN KEY (`reported_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `report_exports`
--
ALTER TABLE `report_exports`
  ADD CONSTRAINT `fk_report_exports_exported_by` FOREIGN KEY (`exported_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `reputation_events`
--
ALTER TABLE `reputation_events`
  ADD CONSTRAINT `fk_rep_events_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `fk_role_permissions_granted_by` FOREIGN KEY (`granted_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_role_permissions_perm` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`permission_id`) ON DELETE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `fk_students_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `student_projects`
--
ALTER TABLE `student_projects`
  ADD CONSTRAINT `fk_student_projects_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `student_references`
--
ALTER TABLE `student_references`
  ADD CONSTRAINT `fk_sr_alumni` FOREIGN KEY (`alumni_id`) REFERENCES `alumni` (`alumni_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sr_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sr_revoked_by` FOREIGN KEY (`revoked_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sr_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD CONSTRAINT `fk_system_settings_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `fk_transactions_type` FOREIGN KEY (`type_id`) REFERENCES `transaction_types` (`type_id`),
  ADD CONSTRAINT `fk_transactions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `typing_activity`
--
ALTER TABLE `typing_activity`
  ADD CONSTRAINT `fk_typing_conversation` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`conversation_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_typing_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_badges`
--
ALTER TABLE `user_badges`
  ADD CONSTRAINT `fk_user_badges_badge` FOREIGN KEY (`badge_id`) REFERENCES `badges` (`badge_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_user_badges_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_career_interests`
--
ALTER TABLE `user_career_interests`
  ADD CONSTRAINT `fk_uci_interest` FOREIGN KEY (`interest_id`) REFERENCES `career_interests` (`interest_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_uci_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_certificates`
--
ALTER TABLE `user_certificates`
  ADD CONSTRAINT `fk_user_certificates_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_course_interests`
--
ALTER TABLE `user_course_interests`
  ADD CONSTRAINT `fk_uci2_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_uci2_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_documents`
--
ALTER TABLE `user_documents`
  ADD CONSTRAINT `fk_user_documents_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_feature_restrictions`
--
ALTER TABLE `user_feature_restrictions`
  ADD CONSTRAINT `fk_ufr_actor` FOREIGN KEY (`acted_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ufr_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_feature_restriction_events`
--
ALTER TABLE `user_feature_restriction_events`
  ADD CONSTRAINT `fk_ufre_actor` FOREIGN KEY (`acted_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ufre_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_field_locks`
--
ALTER TABLE `user_field_locks`
  ADD CONSTRAINT `fk_ufl_lockedby` FOREIGN KEY (`locked_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ufl_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_permissions`
--
ALTER TABLE `user_permissions`
  ADD CONSTRAINT `fk_user_permissions_granted_by` FOREIGN KEY (`granted_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_user_permissions_perm` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`permission_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_user_permissions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD CONSTRAINT `fk_user_profiles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_skills`
--
ALTER TABLE `user_skills`
  ADD CONSTRAINT `fk_user_skills_skill` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`skill_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_user_skills_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_status_history`
--
ALTER TABLE `user_status_history`
  ADD CONSTRAINT `fk_ush_changed_by` FOREIGN KEY (`changed_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ush_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_wallets`
--
ALTER TABLE `user_wallets`
  ADD CONSTRAINT `fk_user_wallets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
