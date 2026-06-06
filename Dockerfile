# Use an official PHP runtime with Apache as a parent image
FROM php:8.2-apache

# Install system dependencies for Python, pip, and PostgreSQL (for Supabase)
RUN apt-get update && apt-get install -y \
    python3 \
    python3-pip \
    python3-venv \
    python3-dev \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql

# Enable Apache mod_rewrite for nice URLs
RUN a2enmod rewrite

# Create a Python virtual environment to avoid PIP externally-managed-environment errors
ENV VIRTUAL_ENV=/opt/venv
RUN python3 -m venv $VIRTUAL_ENV
ENV PATH="$VIRTUAL_ENV/bin:$PATH"

# Copy the requirements file and install Python dependencies
COPY requirements.txt /var/www/html/requirements.txt
RUN pip install --no-cache-dir -r /var/www/html/requirements.txt

# Copy the rest of your application code into the Apache root
COPY . /var/www/html/

# Expose port 80 for Azure App Service
EXPOSE 80
