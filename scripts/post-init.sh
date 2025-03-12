#!/bin/bash
set -e

LOCK_FILE="/var/www/html/.setup_done"

# ✅ If setup is already done, exit immediately
if [ -f "$LOCK_FILE" ]; then
    echo "Setup already completed. Skipping initialization."
    exit 0
fi

echo "Waiting for database to be ready..."
sleep 10  # Ensures MySQL starts before continuing

echo "Installing WP-CLI..."
if [ ! -f /usr/local/bin/wp ]; then
    curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
    chmod +x wp-cli.phar
    mv wp-cli.phar /usr/local/bin/wp
fi

# ✅ Ensure WordPress is only downloaded if missing
if [ ! -f /var/www/html/wp-load.php ]; then
    echo "Downloading WordPress..."
    php -d memory_limit=512M /usr/local/bin/wp core download --allow-root --path=/var/www/html
    chown -R www-data:www-data /var/www/html
else
    echo "WordPress is already installed. Skipping download."
fi

# ✅ Prevent reinstallation if WordPress is already installed
if php -d memory_limit=512M /usr/local/bin/wp core is-installed --allow-root --path=/var/www/html; then
    echo "WordPress is already installed. Skipping installation."
else
    echo "Installing WordPress..."
    php -d memory_limit=512M /usr/local/bin/wp core install --url="http://localhost:8080" --title="WordPress Assessment" --admin_user="admin" --admin_password="admin" --admin_email="admin@example.com" --allow-root --path=/var/www/html
fi

# ✅ Ensure admin user exists
if php -d memory_limit=512M /usr/local/bin/wp user get admin --allow-root --path=/var/www/html; then
    echo "Admin user already exists."
else
    php -d memory_limit=512M /usr/local/bin/wp user create admin admin@example.com --role=administrator --user_pass=admin --allow-root --path=/var/www/html
fi

# ✅ Ensure `.htaccess` exists and is correct
if [ ! -f /var/www/html/.htaccess ]; then
    echo "Creating .htaccess file..."
    cat <<EOL > /var/www/html/.htaccess
# BEGIN WordPress
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase /
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]
</IfModule>
# END WordPress
EOL
    chown www-data:www-data /var/www/html/.htaccess
    chmod 644 /var/www/html/.htaccess
fi

# ✅ Set permalinks to "/%postname%/"
php -d memory_limit=512M /usr/local/bin/wp rewrite structure '/%postname%/' --allow-root
php -d memory_limit=512M /usr/local/bin/wp rewrite flush --allow-root

# ✅ Insert sample product reviews using WP-CLI's `wp post create`
echo "Inserting sample product reviews into the database..."
php -d memory_limit=512M /usr/local/bin/wp eval --allow-root '
$reviews = [
    ["Amazing Product", "This product exceeded my expectations!", "positive", 0.92],
    ["Terrible Experience", "I had a really bad experience with this product.", "negative", 0.20],
    ["Decent but Expensive", "The product works fine but it is overpriced.", "neutral", 0.50],
    ["Loved it!", "Absolutely love this product, would recommend it!", "positive", 0.85],
    ["Not worth the hype", "This product is just okay, nothing special.", "neutral", 0.50],
];

foreach ($reviews as $review) {
    $post_id = wp_insert_post([
        "post_title"   => $review[0],
        "post_content" => $review[1],
        "post_status"  => "publish",
        "post_type"    => "product_review",
    ]);

    if ($post_id) {
        update_post_meta($post_id, "sentiment", $review[2]);
        update_post_meta($post_id, "sentiment_score", $review[3]);
        echo "Inserted: {$review[0]}\n";
    } else {
        echo "Failed to insert: {$review[0]}\n";
    }
}
'

echo "Sample product reviews inserted successfully!"

# ✅ Ensure the plugin is only activated if not already active
if php -d memory_limit=512M /usr/local/bin/wp plugin is-active simple-reviews --allow-root --path=/var/www/html; then
    echo "Simple Reviews plugin is already active."
else
    echo "Activating Simple Reviews plugin..."
    php -d memory_limit=512M /usr/local/bin/wp plugin activate simple-reviews --allow-root --path=/var/www/html
fi

# ✅ Mark setup as complete to prevent rerunning
touch "$LOCK_FILE"
echo "Setup completed successfully!"
