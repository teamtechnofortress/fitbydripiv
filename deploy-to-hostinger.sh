#!/bin/bash

echo "--------------------------------------------"
echo "Laravel Production Restructure Script"
echo "--------------------------------------------"

# Stop on any error

set -e

ROOT_DIR=$(pwd)
BACKEND_DIR="$ROOT_DIR/backend"

echo ""
echo "Step 1: Ensuring backend directory exists..."

# Create backend if it does not exist

if [ ! -d "$BACKEND_DIR" ]; then
mkdir backend
echo "✔ Backend directory created."
else
echo "✔ Backend directory already exists."
fi

echo ""
echo "Step 2: Moving Laravel directories to backend..."

for dir in app bootstrap config database resources routes storage vendor; do
if [ -d "$dir" ]; then
mv -f "$dir" backend/
echo "✔ Moved $dir"
else
echo "⚠ $dir not found, skipping"
fi
done

echo ""
echo "Step 3: Moving Laravel root files..."

FILES=(
artisan
composer.json
composer.lock
phpunit.xml
.env
.env.example
lang
)

for file in "${FILES[@]}"; do
if [ -e "$file" ]; then
mv -f "$file" backend/
echo "✔ Moved $file"
else
echo "⚠ $file not found, skipping"
fi
done

echo ""
echo "Step 4: Preparing public folder..."

if [ -d "public" ]; then

```
if [ -f "public/index.php" ]; then
    rm -f public/index.php
    echo "✔ Removed public/index.php to avoid conflict"
fi

if [ -f "public/.htaccess" ]; then
    rm -f public/.htaccess
    echo "✔ Removed public/.htaccess to avoid conflict"
fi
```

else
echo "⚠ public directory not found"
fi

echo ""
echo "Step 5: Moving all public folder contents to root..."

if [ -d "public" ]; then

```
shopt -s dotglob nullglob
mv -f public/* .

echo "✔ Public assets moved to root"
```

else
echo "⚠ public directory not found"
fi

echo ""
echo "Step 6: Removing old public directory..."

if [ -d "public" ]; then
rm -rf public
echo "✔ public directory removed"
fi

echo ""
echo "Step 7: Updating index.php paths..."

if [ -f "index.php" ]; then

```
sed -i.bak "s|require __DIR__.'/vendor/autoload.php'|require __DIR__.'/backend/vendor/autoload.php'|g" index.php

sed -i.bak "s|require_once __DIR__.'/bootstrap/app.php'|require_once __DIR__.'/backend/bootstrap/app.php'|g" index.php

echo "✔ index.php updated"
echo "✔ Backup created: index.php.bak"
```

else
echo "⚠ index.php not found"
fi

echo ""
echo "--------------------------------------------"
echo "✔ Restructure Completed Successfully"
echo "--------------------------------------------"

echo ""
echo "Final directory structure:"
ls -la
