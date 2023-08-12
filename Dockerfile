FROM php:8.0-cli-alpine

WORKDIR /usr/src/app

RUN apk update && apk add  \
    zip \
    unzip
    
# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copy composer.json
COPY composer.json ./

# Install dependencies
RUN composer install --ignore-platform-reqs 

# Copy source code
COPY . .

CMD [ "tail", "-f", "/dev/null" ]