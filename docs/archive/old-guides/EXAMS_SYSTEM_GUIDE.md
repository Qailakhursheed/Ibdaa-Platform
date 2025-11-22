# 🎓 Smart Exams System Upgrade Guide

This document details the upgrades made to the Exams System, introducing AI capabilities, advanced anti-cheat measures, and a modern management interface.

## 🚀 New Features

### 1. AI-Powered Question Generation
- **Feature:** Automatically generate exam questions based on a topic.
- **How to use:** In the "Create Exam" modal, enter a topic (e.g., "PHP Basics") and the number of questions, then click "Generate".
- **Technology:** Currently uses a simulation mode. To enable real AI, update the `generate_ai_questions` action in `Manager/api/exams_system.php` with your OpenAI/Gemini API key.

### 2. Advanced Anti-Cheat System
- **Fullscreen Enforcement:** Students must stay in fullscreen mode.
- **Tab Switch Detection:** Logs when a student switches tabs or minimizes the browser.
- **Copy/Paste Prevention:** Disables clipboard operations and keyboard shortcuts.
- **DevTools Detection:** Prevents opening browser developer tools.
- **Automatic Submission:** Automatically submits the exam if cheating attempts exceed the limit (default: 3 warnings).

### 3. Modern Management Interface
- **New Dashboard:** `Manager/exams_management.php` provides a sleek, responsive interface for managing exams.
- **Real-time Stats:** View total exams, published status, and student attempts.
- **Dynamic Question Editor:** Add/Edit/Remove questions with a user-friendly form.

## 📂 File Structure Changes

- **New File:** `Manager/exams_management.php` - The main interface for exam management.
- **Updated File:** `Manager/api/exams_system.php` - Added AI generation, list, and delete actions.
- **Updated File:** `Manager/dashboard.php` - Added link to the new Exams system.
- **Updated File:** `Manager/exam_interface.html` - Fixed API paths and verified anti-cheat logic.
- **New File:** `EXAMS_SYSTEM_UPDATE.sql` - SQL script to create necessary database tables.

## 🛠️ Installation Steps

1. **Database Update:**
   Run the `EXAMS_SYSTEM_UPDATE.sql` script in your MySQL database (e.g., via phpMyAdmin) to ensure all tables exist.

2. **Verify Permissions:**
   Ensure the `Manager/api` folder has write permissions if you plan to add file uploads later (currently not used).

3. **Access:**
   Log in as a Manager or Trainer and click on "Smart Exams" (الاختبارات الذكية) in the dashboard sidebar.

## 🤖 AI Configuration (Optional)

To use a real AI provider:
1. Open `Manager/api/exams_system.php`.
2. Locate the `generate_ai_questions` action.
3. Replace the mock generation logic with a cURL call to OpenAI or Gemini API.

```php
// Example for OpenAI
$response = $client->chat()->create([
    'model' => 'gpt-3.5-turbo',
    'messages' => [
        ['role' => 'user', 'content' => "Generate $count multiple choice questions about $topic in JSON format..."]
    ],
]);
```
