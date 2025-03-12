FROM wordpress:latest

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Ensure Apache allows .htaccess overrides
RUN sed -i 's/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Copy post-init.sh into the container and ensure it has executable permissions
COPY scripts/post-init.sh /post-init.sh
RUN chmod +x /post-init.sh

# Restart Apache to apply changes
RUN service apache2 restart

# Set the user to root temporarily to avoid permission issues when running scripts
USER root

# Run the post-init script when the container starts
ENTRYPOINT ["/post-init.sh"]

# Switch back to the default user
USER www-data
