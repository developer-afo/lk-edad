# LK Edad

Prototype: sign in with TribePeer, upload a PDF, answer a short quiz, then see topic strengths (for example Community 50%, Policy 20%).

TribePeer is used only as an API. This app has its own MySQL database.

## Requirements

- PHP 8.2, Composer, MySQL
- `pdftotext` (Poppler). On a Mac: `brew install poppler`
- TribePeer partner keys, and this app’s URL on the publishable key’s allowed origins

## Run

```bash
cp .env.example .env   # if you do not already have .env
php artisan key:generate
```

Set `TRIBEPEER_CLIENT_ID`, `TRIBEPEER_CLIENT_SECRET`, and `TRIBEPEER_PUBLISHABLE_KEY`.
`TRIBEPEER_ORIGIN` must match `APP_URL` (default `http://127.0.0.1:8088`).

```bash
mysql -u root -e "CREATE DATABASE IF NOT EXISTS lk_edad CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php artisan migrate
php artisan serve --host=127.0.0.1 --port=8088
```

Open http://127.0.0.1:8088

## Flow

1. Email and OTP through TribePeer embed auth.
2. Upload one text PDF (not a scan).
3. The server extracts the text and asks TribePeer to write 10–25 questions tagged with 4–8 topics.
4. Results show an overall score and a bar per topic.
5. **Start new session** uploads another document. Older quizzes stay in the database.
