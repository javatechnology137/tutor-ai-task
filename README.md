Tutor LMS AI Task System Plugin
Overview
This WordPress plugin integrates an AI-powered task system into Tutor LMS lessons. It allows instructors to create lesson-specific AI chatbots that help students learn through interactive conversations powered by OpenAI's GPT models.
Features
✅ AI Chatbot per Lesson: Each lesson can have its own AI assistant
✅ Custom AI Prompts: Define specific AI behavior for each lesson
✅ OpenAI Integration: Uses OpenAI's GPT models (3.5-turbo, GPT-4)
✅ Session Memory: Chat conversations persist during the user session
✅ Responsive Design: Works on desktop and mobile devices
✅ Easy Admin Interface: Simple settings in WordPress admin
Requirements

WordPress 5.0 or higher
Tutor LMS plugin installed and activated
OpenAI API key
PHP 7.4 or higher

Installation
Method 1: Manual Installation

Download all the plugin files
Create the following folder structure in your WordPress installation:

wp-content/plugins/tutor-ai-tasks/
├── tutor-ai-tasks.php (main plugin file)
├── includes/
├── templates/
│   └── ai-task-section.php
├── assets/
│   ├── js/
│   │   ├── frontend.js
│   │   └── admin.js
│   └── css/
│       ├── frontend.css
│       └── admin.css
└── README.md

Upload all files to the wp-content/plugins/tutor-ai-tasks/ directory
Activate the plugin from WordPress Admin → Plugins

Method 2: ZIP Installation

Create a ZIP file containing all plugin files
Upload via WordPress Admin → Plugins → Add New → Upload Plugin
Activate the plugin

Configuration
1. OpenAI API Setup

Get your OpenAI API key from https://platform.openai.com/
Go to WordPress Admin → Settings → Tutor AI Tasks
Enter your OpenAI API Key
Configure other settings:

AI Model: Choose between GPT-3.5 Turbo, GPT-4, etc.
Max Tokens: Set response length (default: 1000)
Temperature: Control creativity (0-2, default: 0.7)



2. Setting up AI Tasks for Lessons

Edit any Tutor LMS lesson
Scroll down to the "AI Task Settings" meta box
Enable "Enable AI Task for this lesson"
Configure the task:

Task Title: e.g., "Cybersecurity with AI"
Task Description: Brief explanation for students
AI System Prompt: Define how the AI should behave



Example AI System Prompt:
You are an expert cybersecurity instructor helping students understand cybersecurity concepts. Focus on explaining cybersecurity principles and their applications in healthcare systems. 

Key guidelines:
- Provide clear, educational explanations
- Use real-world examples from healthcare
- Encourage critical thinking
- Ask follow-up questions to deepen understanding
- Keep responses concise but comprehensive
Usage
For Students

Access a lesson with AI tasks enabled
Scroll down to see the AI chat interface
Type questions or statements in the chat box
Press Enter or click Send to get AI responses
Continue the conversation - the AI remembers the context

For Instructors

Create lesson-specific AI assistants with custom prompts
Monitor student engagement through AI interactions
Update AI prompts to improve learning outcomes
Use different AI personalities for different subjects

File Structure Details
tutor-ai-tasks/
├── tutor-ai-tasks.php              # Main plugin file with core functionality
├── includes/
│   ├── class-admin.php             # Admin settings and interface
│   ├── class-frontend.php          # Frontend display logic
│   ├── class-ajax.php              # AJAX request handlers
│   └── class-openai-api.php        # OpenAI API integration
├── templates/
│   └── ai-task-section.php         # Frontend chat interface template
├── assets/
│   ├── js/
│   │   ├── frontend.js             # Chat functionality and interactions
│   │   └── admin.js                # Admin interface enhancements
│   └── css/
│       ├── frontend.css            # Styling for chat interface
│       └── admin.css               # Admin interface styling
└── README.md                       # This file
Database
The plugin creates one table:

wp_tutor_ai_chat_sessions: Stores chat conversations for session persistence

Customization
Styling
Edit assets/css/frontend.css to customize the chat interface appearance.
AI Behavior
Modify system prompts in lesson settings to change AI behavior per lesson.
Integration
The plugin hooks into Tutor LMS using:

tutor_lesson/single/lesson/content action for displaying chat interface
Custom meta boxes for lesson settings

Troubleshooting
Common Issues
AI not responding:

Check OpenAI API key in settings
Verify API key has sufficient credits
Check browser console for JavaScript errors

Chat interface not showing:

Ensure Tutor LMS is installed and active
Verify "Enable AI Task" is checked for the lesson
Check if lesson post type is correct

Styling issues:

Clear browser cache
Check for CSS conflicts with theme
Ensure CSS files are loading properly

Error Messages

"Security check failed": Browser session expired, refresh page
"Failed to get AI response": OpenAI API issue, check API key and credits
"Invalid parameters": Usually a JavaScript error, check browser console

API Costs
This plugin uses OpenAI's API which has usage-based pricing:

GPT-3.5 Turbo: ~$0.002 per 1K tokens
GPT-4: ~$0.03 per 1K tokens

Monitor usage in your OpenAI dashboard to control costs.
Security

All AJAX requests use WordPress nonces
User input is sanitized before database storage
API keys are stored securely in WordPress options
Chat sessions are tied to user IDs for privacy

Support
For issues and feature requests:

Check this README for solutions
Review WordPress and browser console errors
Verify Tutor LMS compatibility
Test with default WordPress theme to rule out conflicts

Changelog
Version 1.0.0

Initial release
Basic AI chat functionality
OpenAI API integration
Session-based chat memory
Responsive design
Admin configuration interface
