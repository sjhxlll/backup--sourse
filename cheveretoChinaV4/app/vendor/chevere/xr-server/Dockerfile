FROM php:8-cli-alpine

WORKDIR /app

COPY . .

EXPOSE 27420

ENTRYPOINT [ "php", "xrserver", "-p", "27420" ]
