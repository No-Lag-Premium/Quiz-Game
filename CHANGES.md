# NumerIQ Website Update - Summary of Changes

## Brand Name Change
- Changed brand name from "MATHQUEST" to "NumerIQ" across all files
- Updated logo styling with "Numer" (cyan) and "IQ" (magenta) color scheme

## Retro Arcade Theme Implementation

### Fonts
- Added "Press Start 2P" for headings and buttons (pixel-style font)
- Added "VT323" for body text (retro terminal font)
- Applied `image-rendering: pixelated` for crisp pixel edges

### Colors
- Primary: Cyan (#00ffff) with neon glow
- Secondary: Magenta (#ff00ff) with neon glow
- Accent: Yellow (#ffff00) with neon glow
- Success: Green (#00ff00) with neon glow
- Danger: Red (#ff0040)
- Background: Deep purple (#0a0014) to dark blue gradient

### Visual Effects
1. **Scanline Effect**: CRT-style horizontal lines overlay
2. **Floating Pixel Stars**: Animated glowing pixels drifting upward
3. **Neon Glows**: Box shadows creating neon glow effects on buttons and borders
4. **CRT Flicker**: Subtle screen flicker animation
5. **Blocky Buttons**: Sharp corners (border-radius: 0) with pixel-style shadows

## Features Removed
1. **Social Media**: Removed all social media icons and links from footer
2. **Fake Stats**: Removed fake stats counter, now uses real database stats
3. **Achievements on Game Over**: Removed achievements display from game over screen (only shows on dashboard/achievements page)
4. **Practice Mode Duplicate**: Removed Practice Mode from Select Mode menu (only accessible from landing page)
5. **Daily Login Streaks**: Removed daily login streak tracking and dates from dashboard

## New Features Implemented

### Smart Sidebar (Dashboard & Achievements)
- Toggle button to expand/collapse sidebar
- Collapsed state shows only icons
- Auto-collapse on mobile screens
- State persistence in localStorage
- Smooth transitions

### Rank Progress Bar
- Full rank track showing all 7 ranks (Novice → Legend)
- Current rank highlighted with cyan glow
- Achieved ranks shown with cyan tint
- Progress bar showing XP to next rank
- Percentage-based progress calculation

### Achievements Page
- Shows only unlocked achievements from database
- Summary cards (Unlocked, Locked, Percentage Complete)
- Grouped by category (Streak, Score, Games, Difficulty, Special)
- Locked achievements shown in grayscale with "Locked" status

### Practice Mode
- Banner showing "Practice Mode - Stats are not saved"
- Achievements visible but cannot be unlocked
- No stats saved to database

## Database Changes
- Database name changed from `math_quest` to `numeriq`
- Removed `current_streak`, `longest_streak`, `last_played_date` columns from users table
- Removed daily challenges and streak rewards tables
- Kept achievements, game_stats, user_settings tables

## File Structure
All files are in a single folder for direct deployment into htdocs:
- PHP files: index.php, login.php, register.php, logout.php, dashboard.php, game.php, achievements.php, forgot-password.php, reset-password.php, save-game.php, save-achievement.php, get-stats.php, get-leaderboard.php, userdb.php
- CSS files: styles.css, game-styles.css, dashboard-styles.css, achievements-styles.css
- JS files: landing.js, game.js
- SQL file: database-setup.sql

## Setup Instructions
1. Copy all files to your XAMPP htdocs folder
2. Run the database-setup.sql in phpMyAdmin to create the database
3. Update userdb.php with your database credentials if needed
4. Access the website via http://localhost/
