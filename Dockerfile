# Utiliza a imagem oficial do PHP com Apache
FROM php:8.2-apache

# Copia os arquivos da aplicação para o diretório do Apache
COPY app/ /var/www/html/

# Expõe a porta 80
EXPOSE 80