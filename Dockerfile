# ใช้ PHP 8.1 พร้อม Apache
FROM php:8.1-apache

# คัดลอกไฟล์ทั้งหมดไปยัง web root
COPY . /var/www/html/

# เปิดพอร์ต 80
EXPOSE 80
