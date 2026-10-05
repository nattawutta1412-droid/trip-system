FROM php:8.2-apache
RUN docker-php-ext-install mysqli
COPY . /var/www/html/

# สร้างโฟลเดอร์ uploads และเปิดสิทธิ์การบันทึกไฟล์ให้สมบูรณ์
RUN mkdir -p /var/www/html/uploads && \
    chown -R www-data:www-data /var/www/html/uploads && \
    chmod -R 777 /var/www/html/uploads
