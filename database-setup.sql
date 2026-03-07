-- NumerIQ Database Setup
-- Run this SQL file to create all necessary tables
-- This will DROP any existing database and create a fresh one

-- Drop existing database if exists
DROP DATABASE IF EXISTS numeriq;

-- Create fresh database
CREATE DATABASE numeriq CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE numeriq;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    profile_pic VARCHAR(255) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_login DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Password resets table (for forgot password feature)
CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Game stats table (to track player progress)
CREATE TABLE game_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    game_mode VARCHAR(20) NOT NULL, -- classic, timeAttack, survival, practice
    difficulty VARCHAR(20) NOT NULL, -- easy, medium, hard, expert
    score INT NOT NULL DEFAULT 0,
    correct_answers INT NOT NULL DEFAULT 0,
    total_questions INT NOT NULL DEFAULT 0,
    streak INT NOT NULL DEFAULT 0,
    played_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_practice TINYINT(1) DEFAULT 0, -- 1 for practice mode (not counted in stats)
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Achievements definition table
CREATE TABLE achievements (
    id VARCHAR(50) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    icon VARCHAR(10) NOT NULL,
    requirement_type VARCHAR(50) NOT NULL, -- streak, score, games, accuracy, etc.
    requirement_value INT NOT NULL,
    points INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User achievements table (tracks unlocked achievements)
CREATE TABLE user_achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    achievement_id VARCHAR(50) NOT NULL,
    unlocked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (achievement_id) REFERENCES achievements(id) ON DELETE CASCADE,
    UNIQUE KEY unique_achievement (user_id, achievement_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User settings table
CREATE TABLE user_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    sound_enabled TINYINT(1) DEFAULT 1,
    music_enabled TINYINT(1) DEFAULT 1,
    haptic_enabled TINYINT(1) DEFAULT 1,
    theme VARCHAR(20) DEFAULT 'dark',
    default_difficulty VARCHAR(20) DEFAULT 'medium',
    hints_enabled TINYINT(1) DEFAULT 1,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default achievements with cascade-friendly values
-- Games played achievements
INSERT INTO achievements (id, name, description, icon, requirement_type, requirement_value, points) VALUES
('first_win', 'First Steps', 'Complete your first game', '👶', 'games', 1, 10),
('games_10', 'Regular Player', 'Play 10 games', '🎮', 'games', 10, 20),
('games_50', 'Dedicated', 'Play 50 games', '🕹️', 'games', 50, 50),
('games_100', 'Centurion', 'Play 100 games', '💪', 'games', 100, 100);

-- Streak achievements (cascade: 5 -> 10 -> 20)
INSERT INTO achievements (id, name, description, icon, requirement_type, requirement_value, points) VALUES
('streak_5', 'On Fire', 'Reach a 5-question streak', '🔥', 'streak', 5, 20),
('streak_10', 'Unstoppable', 'Reach a 10-question streak', '⚡', 'streak', 10, 50),
('streak_20', 'Legendary', 'Reach a 20-question streak', '👑', 'streak', 20, 100);

-- Score achievements (cascade: 100 -> 500 -> 1000)
INSERT INTO achievements (id, name, description, icon, requirement_type, requirement_value, points) VALUES
('score_100', 'Century', 'Score 100 points in one game', '💯', 'score', 100, 15),
('score_500', 'High Roller', 'Score 500 points in one game', '🎯', 'score', 500, 30),
('score_1000', 'Math Master', 'Score 1000 points in one game', '🏆', 'score', 1000, 50);

-- Accuracy achievements (cascade: 80 -> 90 -> 100)
INSERT INTO achievements (id, name, description, icon, requirement_type, requirement_value, points) VALUES
('accuracy_80', 'Sharp Shooter', 'Achieve 80% accuracy in a game', '🎯', 'accuracy', 80, 20),
('accuracy_90', 'Precision Master', 'Achieve 90% accuracy in a game', '🎖️', 'accuracy', 90, 35),
('perfect_game', 'Perfectionist', 'Answer all questions correctly (100% accuracy)', '✨', 'accuracy', 100, 50);

-- Speed achievements
INSERT INTO achievements (id, name, description, icon, requirement_type, requirement_value, points) VALUES
('speed_demon', 'Speed Demon', 'Answer 10 questions in 30 seconds in Time Attack', '⚡', 'speed', 10, 40);

-- Survival mode achievements
INSERT INTO achievements (id, name, description, icon, requirement_type, requirement_value, points) VALUES
('survivor', 'Survivor', 'Reach 20 questions in Survival mode', '❤️', 'survival', 20, 60);

-- Difficulty achievements
INSERT INTO achievements (id, name, description, icon, requirement_type, requirement_value, points) VALUES
('expert_mode', 'Expert', 'Complete a game on Expert difficulty', '💀', 'difficulty', 1, 40);

-- Create indexes for better performance
CREATE INDEX idx_user_email ON users(email);
CREATE INDEX idx_password_reset_token ON password_resets(token);
CREATE INDEX idx_game_stats_user ON game_stats(user_id);
CREATE INDEX idx_game_stats_date ON game_stats(played_at);
CREATE INDEX idx_game_stats_mode ON game_stats(game_mode);
CREATE INDEX idx_achievements_user ON user_achievements(user_id);

-- Create view for leaderboard
CREATE VIEW leaderboard_view AS
SELECT 
    u.id,
    u.name,
    u.profile_pic,
    COUNT(gs.id) as total_games,
    SUM(gs.score) as total_score,
    MAX(gs.score) as high_score,
    AVG(gs.correct_answers / NULLIF(gs.total_questions, 0) * 100) as avg_accuracy,
    MAX(gs.streak) as best_streak
FROM users u
LEFT JOIN game_stats gs ON u.id = gs.user_id AND gs.is_practice = 0
GROUP BY u.id, u.name, u.profile_pic
HAVING total_games > 0
ORDER BY high_score DESC;
