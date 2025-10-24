FROM nginx:alpine

# Установка certbot
RUN apk add --no-cache certbot

# Копируем конфигурацию nginx для Symfony
COPY docker/nginx/conf.d/app.conf /etc/nginx/conf.d/default.conf