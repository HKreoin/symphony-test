FROM nginx:alpine

# Копируем конфигурацию nginx для Symfony
COPY docker/nginx/conf.d/app.conf /etc/nginx/conf.d/default.conf