mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS garuda_indonesia_website;"

mysql -u root -e "CREATE DATABASE IF NOT EXISTS garuda_indonesia_website;"

mysql -u root garuda_indonesia_website < database/database.sql

cmd /c "mysql -u root garuda_indonesia_website < database/database.sql"

php database/migrations/add_seat_column.php

mysql -u root -e "USE garuda_indonesia_website; SHOW TABLES;"

-------------------------------------------------------------------

mysql -u root -e "CREATE DATABASE garuda_indonesia_website CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

cmd /c "mysql -u root garuda_indonesia_website < database/database.sql"

php database/migrations/fix_database_issues.php

mysql -u root -e "USE garuda_indonesia_website; SHOW TABLES;"

if (!(Test-Path "uploads")) { New-Item -ItemType Directory -Path "uploads" -Force }; if (!(Test-Path "uploads\hotels")) { New-Item -ItemType Directory -Path "uploads\hotels" -Force }; if (!(Test-Path "uploads\payment_receipts")) { New-Item -ItemType Directory -Path "uploads\payment_receipts" -Force }; if (!(Test-Path "uploads\receipts")) { New-Item -ItemType Directory -Path "uploads\receipts" -Force }

if (!(Test-Path "pages\booking\uploads")) { New-Item -ItemType Directory -Path "pages\booking\uploads" -Force }; if (!(Test-Path "pages\booking\uploads\receipts")) { New-Item -ItemType Directory -Path "pages\booking\uploads\receipts" -Force }

php simple_check.php