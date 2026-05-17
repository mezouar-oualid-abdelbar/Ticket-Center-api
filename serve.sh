#!/bin/bash

# Start Laravel Reverb in the background
echo "Starting Reverb server on port 81..."
php artisan reverb:start --host=0.0.0.0 --port=81 --debug &

# Start the main Laravel application
echo "Starting Laravel application on port 80..."
php artisan serve --port 80 --host 0.0.0.0
