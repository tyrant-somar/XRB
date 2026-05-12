# My Silverstripe Vue Project

This is a web app using Silverstripe CMS and Vue.js for processing accountancy rules with AI.

## Setup

1. Install PHP and Composer.
2. Run `composer install` in the root directory.
3. For the frontend, go to frontend/ and run `npm install`, then `npm run serve`.
4. Place the "XRB AI Prompt_Rule and Examples.docx" file in assets/docs/.
5. Set your Gemini API key in .env: GEMINI_API_KEY=your_key
6. Add Silverstripe DB config to .env:
   - `SS_ENVIRONMENT_TYPE=dev`
   - `SS_DATABASE_CLASS=SQLite3Database`
   - `SS_DATABASE_NAME=silverstripe.db`
   - `SS_DEFAULT_ADMIN_USERNAME=admin`
   - `SS_DEFAULT_ADMIN_PASSWORD=admin`

## Running

- Start Silverstripe: php -S localhost:8001 public/index.php
- Vue: npm run serve in frontend/

The app will be at http://localhost:8080