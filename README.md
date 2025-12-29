# BGC Science Club Website

Official website for the Brahmanbaria Government College Science Club — PHP + MySQL backend, small frontend assets (Vite/React in `src/`). This README documents how to set up, secure, and deploy the project safely to GitHub or a server.

**Contents**
- **Project**: purpose and key components
- **Requirements**: software needed locally and on server
- **Quick setup**: environment variables and commands to run locally
- **Database**: schema and import hints
- **Security**: what was sanitized and how to purge history
- **Deployment**: recommended steps for GitHub / production
- **Uploads & backups**: handling user data
- **Contributing & contact**

**Project**
- **Tech stack**: PHP (mixed procedural + PDO), MySQL/MariaDB, simple frontend with Vite/React in `src/`.
- **What it does**: site pages, admin panel, membership/ticketing, uploads for members/executives/projects.

**Requirements**
- PHP >= 7.4 with PDO and common extensions (mbstring, fileinfo).
- MySQL or MariaDB server.
- Node.js (for frontend dev/build) if you will work on the React/Vite part.
- Composer for any PHP dependencies (if used).

Quick local setup

1. Copy environment variables (create a `.env` or set environment variables directly). The code now reads DB values from environment variables; set these before running:

```bash
export DB_HOST=127.0.0.1
export DB_NAME=bgc_science_club
export DB_USER=mydbuser
export DB_PASSWORD=mysecret
export RECAPTCHA_SECRET=your_recaptcha_secret_here
```

2. Start PHP server for quick local testing (from repo root):

```bash
# serve on port 8000
php -S 127.0.0.1:8000
```

3. If you work on frontend:

```bash
npm install
npm run dev    # Vite dev server
npm run build  # build assets
```

Database
- The project contains SQL schema files under `db/`, `database/`, and `supabase/migrations/` — inspect them to import a schema. Example import:

```bash
mysql -u root -p bgc_science_club < db/schema.sql
```

- Note: any dumped SQL files that previously contained real secrets were removed/ignored; produce a fresh dump from your production DB if migrating.

Security & secrets
- What I sanitized: `db.php`, `pages/config/db.php`, and `admin/config/db.php` were modified to read credentials from environment variables instead of containing plaintext passwords.
- `api.json` and other sensitive files were added to `.gitignore` and untracked to prevent future commits.
- Important: if any secret (DB password, API key, recaptcha secret, etc.) was previously committed to the Git history, you must purge it from history before pushing to a public remote. Two common tools:

1) BFG Repo-Cleaner (simple):

```bash
# clone a mirror (work outside your repo)
git clone --mirror /path/to/your/repo repo-mirror.git
cd repo-mirror.git
# delete files and folders
bfg --delete-files api.json --delete-files "db.php" --delete-folders uploads
# cleanup and push
git reflog expire --expire=now --all
git gc --prune=now --aggressive
git push --force
```

2) git filter-repo (more flexible):

```bash
# recommended: install git-filter-repo and follow its docs
git clone --mirror /path/to/repo repo-mirror.git
cd repo-mirror.git
git filter-repo --invert-paths --paths api.json --paths db/db.sql --paths error_log --paths uploads
# then push the cleaned mirror
git push --force
```

- After purge, rotate any credentials that were exposed (change DB passwords, revoke API keys, reset reCAPTCHA secrets).

Uploads & backups
- The `uploads/` directory contains user-uploaded images and personal data. Do not commit it. It is now listed in `.gitignore`.
- Keep private backups off your source repo — store backups in a secure storage bucket and rotate access keys.

Git/GitHub notes
- Make sure `.gitignore` includes the lines that were added (`api.json`, `*.sql`, `uploads/`, `error_log`, etc.).
- Do not push the repository until you have purged any previously committed secrets.

Maintenance & contribution
- Use environment variables in production or a secure secrets manager (e.g., HashiCorp Vault, cloud provider secrets).
- When adding new config files, update `.gitignore` and document necessary environment variables here.

Contribution & usage policy
- You are welcome to fork this repository to propose fixes or improvements and open a pull request.
- Forking for personal development or to propose changes is allowed. However, editing, redistributing, publishing, or uploading the code from this repository without the explicit permission of the original author is not authorized.
- If you intend to republish or deploy modified copies publicly, contact the project maintainer first (see `pages/about-developer.php` for contact information) and ensure you follow licensing requirements.

Troubleshooting
- If PHP throws a PDO connection error, verify env vars are set and database user has privileges.
- If files appear to be tracked after adding `.gitignore`, run:

```bash
# stop tracking files that are already in the repo
git rm --cached path/to/file
git commit -m "Stop tracking sensitive file"
```

Contact
- For help with this repo or migration, contact the maintainer listed in the project files (developer info is included in `pages/about-developer.php`).


